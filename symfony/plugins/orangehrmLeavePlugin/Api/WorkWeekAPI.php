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

use OrangeHRM\Core\Api\CommonParams;
use OrangeHRM\Core\Api\V2\Endpoint;
use OrangeHRM\Core\Api\V2\EndpointResourceResult;
use OrangeHRM\Core\Api\V2\EndpointResult;
use OrangeHRM\Core\Api\V2\RequestParams;
use OrangeHRM\Core\Api\V2\ResourceEndpoint;
use OrangeHRM\Core\Api\V2\Validator\ParamRule;
use OrangeHRM\Core\Api\V2\Validator\ParamRuleCollection;
use OrangeHRM\Core\Api\V2\Validator\Rule;
use OrangeHRM\Core\Api\V2\Validator\Rules;
use OrangeHRM\Entity\WorkWeek;
use OrangeHRM\Leave\Api\Model\WorkWeekModel;
use OrangeHRM\Leave\Service\WorkWeekService;

class WorkWeekAPI extends Endpoint implements ResourceEndpoint
{
    public const PARAMETER_MONDAY = 'monday';
    public const PARAMETER_TUESDAY = 'tuesday';
    public const PARAMETER_WEDNESDAY = 'wednesday';
    public const PARAMETER_THURSDAY = 'thursday';
    public const PARAMETER_FRIDAY = 'friday';
    public const PARAMETER_SATURDAY = 'saturday';
    public const PARAMETER_SUNDAY = 'sunday';

    public const PARAMETER_WORKWEEK = [
        self::PARAMETER_MONDAY,
        self::PARAMETER_TUESDAY,
        self::PARAMETER_WEDNESDAY,
        self::PARAMETER_THURSDAY,
        self::PARAMETER_FRIDAY,
        self::PARAMETER_SATURDAY,
        self::PARAMETER_SUNDAY,
    ];

    /**
     * @var WorkWeekService|null
     */
    private ?WorkWeekService $workWeekService = null;

    /**
     * @return WorkWeekService
     */
    protected function getWorkWeekService(): WorkWeekService
    {
        if (!$this->workWeekService instanceof WorkWeekService) {
            $this->workWeekService = new WorkWeekService();
        }
        return $this->workWeekService;
    }

    /**
     * @inheritDoc
     */
    public function getOne(): EndpointResult
    {
        $workWeek = $this->getWorkWeekService()
            ->getWorkWeekDao()
            ->getWorkWeekById($this->getIdFromUrlAttributes());
        $this->throwRecordNotFoundExceptionIfNotExist($workWeek, WorkWeek::class);

        return new EndpointResourceResult(WorkWeekModel::class, $workWeek);
    }

    /**
     * @return int
     */
    private function getIdFromUrlAttributes(): int
    {
        return $this->getRequestParams()->getInt(RequestParams::PARAM_TYPE_ATTRIBUTE, CommonParams::PARAMETER_ID);
    }

    /**
     * @return ParamRule
     */
    private function getIdParamRule(): ParamRule
    {
        return new ParamRule(CommonParams::PARAMETER_ID, new Rule(Rules::POSITIVE));
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForGetOne(): ParamRuleCollection
    {
        return new ParamRuleCollection($this->getIdParamRule());
    }

    /**
     * @inheritDoc
     */
    public function update(): EndpointResult
    {
        $workWeek = $this->getWorkWeekService()
            ->getWorkWeekDao()
            ->getWorkWeekById($this->getIdFromUrlAttributes());
        $this->throwRecordNotFoundExceptionIfNotExist($workWeek, WorkWeek::class);
        foreach (self::PARAMETER_WORKWEEK as $workWeekKey) {
            $setter = 'set' . ucfirst($workWeekKey);
            $workWeek->$setter($this->getRequestParams()->getInt(RequestParams::PARAM_TYPE_BODY, $workWeekKey));
        }

        $workWeek = $this->getWorkWeekService()
            ->getWorkWeekDao()
            ->saveWorkWeek($workWeek);

        return new EndpointResourceResult(WorkWeekModel::class, $workWeek);
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForUpdate(): ParamRuleCollection
    {
        $paramRules = new ParamRuleCollection($this->getIdParamRule());
        foreach (self::PARAMETER_WORKWEEK as $workWeekKey) {
            $paramRules->addParamValidation(
                new ParamRule($workWeekKey, new Rule(Rules::IN, [WorkWeek::WORKWEEK_LENGTHS, true]))
            );
        }
        return $paramRules;
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
