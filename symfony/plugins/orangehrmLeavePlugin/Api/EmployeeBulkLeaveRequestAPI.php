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
 */

namespace OrangeHRM\Leave\Api;

use Exception;
use OrangeHRM\Core\Api\V2\Endpoint;
use OrangeHRM\Core\Api\V2\EndpointCollectionResult;
use OrangeHRM\Core\Api\V2\EndpointResult;
use OrangeHRM\Core\Api\V2\RequestParams;
use OrangeHRM\Core\Api\V2\ResourceEndpoint;
use OrangeHRM\Core\Api\V2\Validator\ParamRule;
use OrangeHRM\Core\Api\V2\Validator\ParamRuleCollection;
use OrangeHRM\Core\Api\V2\Validator\Rule;
use OrangeHRM\Core\Api\V2\Validator\Rules;
use OrangeHRM\Core\Traits\Auth\AuthUserTrait;
use OrangeHRM\Core\Traits\ORM\EntityManagerHelperTrait;
use OrangeHRM\Core\Traits\UserRoleManagerTrait;
use OrangeHRM\Entity\WorkflowStateMachine;
use OrangeHRM\Leave\Api\Model\LeaveRequestModel;
use OrangeHRM\Leave\Api\Traits\LeaveRequestParamHelperTrait;
use OrangeHRM\Leave\Api\Traits\LeaveRequestPermissionTrait;
use OrangeHRM\Leave\Traits\Service\LeaveRequestServiceTrait;
use OrangeHRM\ORM\Exception\TransactionException;

class EmployeeBulkLeaveRequestAPI extends Endpoint implements ResourceEndpoint
{
    use LeaveRequestParamHelperTrait;
    use LeaveRequestServiceTrait;
    use UserRoleManagerTrait;
    use AuthUserTrait;
    use LeaveRequestPermissionTrait;
    use EntityManagerHelperTrait;

    public const PARAMETER_ACTION = 'action';
    public const PARAMETER_LEAVE_REQUEST_ID = 'leaveRequestId';
    public const PARAMETER_DATA = 'data';

    /**
     * @inheritDoc
     */
    public function getOne(): EndpointResult
    {
        throw $this->getNotImplementedException();
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForGetOne(): ParamRuleCollection
    {
        throw $this->getNotImplementedException();
    }

    /**
     * @inheritDoc
     */
    public function update(): EndpointResult
    {
        $leaveRequestsData = $this->getRequestParams()->getArray(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_DATA
        );

        $leaveRequestsIdActionMap = [];
        foreach ($leaveRequestsData as $leaveRequestData) {
            if (isset($leaveRequestsIdActionMap[$leaveRequestData[self::PARAMETER_LEAVE_REQUEST_ID]])) {
                $this->getBadRequestException('Multiple actions defined for a leave request');
            }
            $leaveRequestsIdActionMap[$leaveRequestData[self::PARAMETER_LEAVE_REQUEST_ID]] = $leaveRequestData[self::PARAMETER_ACTION];
        }

        $this->beginTransaction();
        try {
            $leaveRequests = $this->getLeaveRequestService()
                ->getLeaveRequestDao()
                ->getLeaveRequestsByLeaveRequestIds(array_keys($leaveRequestsIdActionMap));

            if (count($leaveRequestsData) !== count($leaveRequests)) {
                throw $this->getRecordNotFoundException();
            }
            foreach ($leaveRequests as $leaveRequest) {
                $this->checkLeaveRequestAccessible($leaveRequest);
            }

            $detailedLeaveRequests = $this->getLeaveRequestService()
                ->getDetailedLeaveRequests($leaveRequests);

            foreach ($detailedLeaveRequests as $detailedLeaveRequest) {
                if ($detailedLeaveRequest->hasMultipleStatus()) {
                    throw $this->getBadRequestException('Leave request have multiple status');
                }

                $action = $leaveRequestsIdActionMap[$detailedLeaveRequest->getLeaveRequest()->getId()];
                if (!$detailedLeaveRequest->isActionAllowed($action)) {
                    throw $this->getBadRequestException('Performed action not allowed');
                }

                $workflow = $detailedLeaveRequest->getWorkflowForAction($action);
                if (!$workflow instanceof WorkflowStateMachine) {
                    throw $this->getBadRequestException('Invalid action performed');
                }

                $this->getLeaveRequestService()->changeLeaveRequestStatus(
                    $detailedLeaveRequest,
                    $workflow->getResultingState()
                );
            }

            $this->commitTransaction();

            return new EndpointCollectionResult(LeaveRequestModel::class, $leaveRequests);
        } catch (Exception $e) {
            $this->rollBackTransaction();
            throw new TransactionException($e);
        }
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForUpdate(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(
                self::PARAMETER_DATA,
                new Rule(Rules::ARRAY_TYPE),
            ),
        );
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
}
