<?php
/**
 * OrangeHRM is a comprehensive Human Resource Management (HRM) System that captures
 * all the essential functionalities required for any enterprise.
 * Copyright (C) 2006 OrangeHRM Inc., http://www.orangehrm.com
 *
 * OrangeHRM is free software; you can redistribute it and/or modify it under the terms of
 * the GNU General Public License as published by the Free Software Foundation; either
 * version 2 of the License, or (at your option) any later version.
 *
 * OrangeHRM is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with this program;
 * if not, write to the Free Software Foundation, Inc., 51 Franklin Street, Fifth Floor,
 * Boston, MA  02110-1301, USA
 *
 */

namespace OrangeHRM\Claim\Api;

use Exception;
use OpenApi\Annotations as OA;
use OrangeHRM\Claim\Api\Model\ClaimRequestModel;
use OrangeHRM\Claim\Traits\Service\ClaimServiceTrait;
use OrangeHRM\Core\Api\CommonParams;
use OrangeHRM\Core\Api\V2\CrudEndpoint;
use OrangeHRM\Core\Api\V2\Endpoint;
use OrangeHRM\Core\Api\V2\EndpointResourceResult;
use OrangeHRM\Core\Api\V2\EndpointResult;
use OrangeHRM\Core\Api\V2\Exception\BadRequestException;
use OrangeHRM\Core\Api\V2\Exception\InvalidParamException;
use OrangeHRM\Core\Api\V2\Model\WorkflowStateModel;
use OrangeHRM\Core\Api\V2\RequestParams;
use OrangeHRM\Core\Api\V2\Validator\ParamRule;
use OrangeHRM\Core\Api\V2\Validator\ParamRuleCollection;
use OrangeHRM\Core\Api\V2\Validator\Rule;
use OrangeHRM\Core\Api\V2\Validator\Rules;
use OrangeHRM\Core\Traits\Auth\AuthUserTrait;
use OrangeHRM\Core\Traits\ORM\EntityManagerHelperTrait;
use OrangeHRM\Core\Traits\Service\DateTimeHelperTrait;
use OrangeHRM\Core\Traits\Service\NormalizerServiceTrait;
use OrangeHRM\Core\Traits\UserRoleManagerTrait;
use OrangeHRM\Entity\ClaimRequest;
use OrangeHRM\Entity\Employee;
use OrangeHRM\Entity\WorkflowStateMachine;
use OrangeHRM\ORM\Exception\TransactionException;

class EmployeeClaimRequestAPI extends Endpoint implements CrudEndpoint
{
    use EntityManagerHelperTrait;
    use ClaimServiceTrait;
    use DateTimeHelperTrait;
    use AuthUserTrait;
    use NormalizerServiceTrait;
    use UserRoleManagerTrait;

    public const PARAMETER_CLAIM_EVENT_ID = 'claimEventId';
    public const PARAMETER_CURRENCY_ID = 'currencyId';
    public const PARAMETER_REMARKS = 'remarks';
    public const REMARKS_MAX_LENGTH = 1000;
    public const PARAMETER_ALLOWED_ACTIONS = 'allowedActions';
    public const ACTIONABLE_STATES_MAP = [
        WorkflowStateMachine::CLAIM_ACTION_SUBMIT => 'SUBMIT',
        WorkflowStateMachine::CLAIM_ACTION_APPROVE => 'APPROVE',
        WorkflowStateMachine::CLAIM_ACTION_PAY => 'PAY',
        WorkflowStateMachine::CLAIM_ACTION_CANCEL => 'CANCEL',
        WorkflowStateMachine::CLAIM_ACTION_REJECT => 'REJECT'
    ];

    /**
     * @OA\Post(
     *     path="/api/v2/claim/employee/{empNumber}/requests",
     *     tags={"Claim/Requests"},
     *     @OA\PathParameter(
     *         name="empNumber",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="claimEventId", type="integer"),
     *             @OA\Property(property="currencyId", type="string"),
     *             @OA\Property(property="remarks", type="string"),
     *             required={"claimEventId", "currency"}
     *         )
     *     ),
     *     @OA\Response(response="200",
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 ref="#/components/schemas/Claim-RequestModel"
     *             ),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     )
     * )
     * @inheritDoc
     */
    public function create(): EndpointResult
    {
        $claimRequest = new ClaimRequest();
        $this->setClaimRequest($claimRequest);
        return new EndpointResourceResult(ClaimRequestModel::class, $claimRequest);
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForCreate(): ParamRuleCollection //TODO: Add employee number
    {
        return $this->getCommonParamRuleCollection();
    }

    /**
     * @return ParamRuleCollection
     */
    protected function getCommonParamRuleCollection(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(
                self::PARAMETER_CLAIM_EVENT_ID,
                new Rule(Rules::POSITIVE)
            ),
            new ParamRule(
                self::PARAMETER_CURRENCY_ID,
                new Rule(Rules::REQUIRED)
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::PARAMETER_REMARKS,
                    new Rule(Rules::STRING_TYPE),
                    new Rule(Rules::LENGTH, [null, self::REMARKS_MAX_LENGTH]),
                ),
                true
            ),
        );
    }

    /**
     * @param ClaimRequest $claimRequest
     * @param int|null $empNumber
     * @return ClaimRequest
     */
    protected function setClaimRequest(ClaimRequest $claimRequest, ?int $empNumber = null): ClaimRequest
    {
        $this->beginTransaction();
        try {
            $claimEventId = $this->getRequestParams()->getInt(
                RequestParams::PARAM_TYPE_BODY,
                self::PARAMETER_CLAIM_EVENT_ID
            );

            $currencyId = $this->getRequestParams()->getString(
                RequestParams::PARAM_TYPE_BODY,
                self::PARAMETER_CURRENCY_ID
            );

            $claimEvent = $this->getClaimService()->getClaimDao()->getClaimEventById($claimEventId);
            if ($claimEvent === null) {
                throw $this->getInvalidParamException(self::PARAMETER_CLAIM_EVENT_ID);
            }

            if ($claimRequest->getDecorator()->getCurrencyByCurrencyId($currencyId) === null) {
                throw $this->getInvalidParamException(self::PARAMETER_CURRENCY_ID);
            }

            $claimRequest->setClaimEvent($claimEvent);
            $claimRequest->getDecorator()->setCurrencyByCurrencyId($currencyId);
            $claimRequest->setDescription(
                $this->getRequestParams()->getStringOrNull(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_REMARKS)
            );

            $claimRequest->setReferenceId($this->getClaimService()->getReferenceId());
            $claimRequest->setStatus(ClaimRequest::REQUEST_STATUS_INITIATED);
            $claimRequest->setCreatedDate($this->getDateTimeHelper()->getNow());
            $userId = $this->getAuthUser()->getUserId();
            $claimRequest->getDecorator()->setUserByUserId($userId);

            if (is_null($empNumber)) { //TODO Implement for assign claims
                $claimRequest->getDecorator()->setEmployeeByUserId($userId);
            }

            $this->commitTransaction();
            return $this->getClaimService()->getClaimDao()->saveClaimRequest($claimRequest);
        } catch (InvalidParamException|BadRequestException $e) {
            $this->rollBackTransaction();
            throw $e;
        } catch (Exception $e) {
            $this->rollBackTransaction();
            throw new TransactionException($e);
        }
    }

    /**
     * @inheritDoc
     */
    public function getAll(): EndpointResult
    {
        throw $this->getNotImplementedException();
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForGetAll(): ParamRuleCollection
    {
        throw $this->getNotImplementedException();
    }

    /**
     * @OA\Get(
     *     path="/api/v2/claim/employee/{empNumber}/requests/{id}",
     *     tags={"Claim/Requests"},
     *     @OA\PathParameter(
     *         name="empNumber",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Claim Request Id",
     *         @OA\Schema(type="integer"),
     *         required=true
     *     ),
     *     @OA\Response(response="200",
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 ref="#/components/schemas/Claim-RequestModel"
     *             )
     *         )
     *     )
     * )
     * @inheritDoc
     */
    public function getOne(): EndpointResult
    {
        $id = $this->getRequestParams()->getInt(RequestParams::PARAM_TYPE_ATTRIBUTE, CommonParams::PARAMETER_ID);
        $claimRequest = $this->getClaimService()->getClaimDao()->getClaimRequestById($id);
        $this->throwRecordNotFoundExceptionIfNotExist($claimRequest, ClaimRequest::class);
        return new EndpointResourceResult(ClaimRequestModel::class, $claimRequest);
    }

    /**
     * @return ParamRuleCollection
     */
    public function getValidationRuleForGetOne(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(
                CommonParams::PARAMETER_ID,
                new Rule(Rules::POSITIVE)
            ),
        );
    }

    /**
     * @param ClaimRequest $claimRequest
     * @return array
     */
    protected function getAllowedActions(ClaimRequest $claimRequest): array
    {
        $allowedWorkflowItems = $this->getUserRoleManager()->getAllowedActions(
            WorkflowStateMachine::FLOW_CLAIM,
            $claimRequest->getStatus(),
            [],
            [],
            [Employee::class => $claimRequest->getEmployee()->getEmpNumber()]
        );
        foreach ($allowedWorkflowItems as $allowedWorkflowItem) {
            $allowedWorkflowItem->setAction(self::ACTIONABLE_STATES_MAP[$allowedWorkflowItem->getAction()]);
        }
        return $this->getNormalizerService()
            ->normalizeArray(WorkflowStateModel::class, $allowedWorkflowItems);
    }

    /**
     * @inheritDoc
     */
    public function delete(): EndpointResult
    {
        throw $this->getNotImplementedException();
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForDelete(): ParamRuleCollection
    {
        throw $this->getNotImplementedException();
    }

    /**
     * @inheritDoc
     */
    public function update(): EndpointResult
    {
        throw $this->getNotImplementedException();
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForUpdate(): ParamRuleCollection
    {
        throw $this->getNotImplementedException();
    }
}
