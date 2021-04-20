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

namespace OrangeHRM\Admin\Api;

use DaoException;
use Exception;
use JobCategoryService;
use OrangeHRM\Admin\Api\Model\JobCategoryModel;
use OrangeHRM\Core\Api\V2\CollectionEndpointInterface;
use OrangeHRM\Core\Api\V2\Endpoint;
use OrangeHRM\Core\Api\V2\Model\ArrayModel;
use OrangeHRM\Core\Api\V2\ParameterBag;
use OrangeHRM\Core\Api\V2\RequestParams;
use OrangeHRM\Core\Api\V2\ResourceEndpointInterface;
use OrangeHRM\Core\Api\V2\Serializer\EndpointCreateResult;
use OrangeHRM\Core\Api\V2\Serializer\EndpointDeleteResult;
use OrangeHRM\Core\Api\V2\Serializer\EndpointGetAllResult;
use OrangeHRM\Core\Api\V2\Serializer\EndpointGetOneResult;
use OrangeHRM\Core\Api\V2\Serializer\EndpointUpdateResult;
use OrangeHRM\Entity\JobCategory;
use Orangehrm\Rest\Api\Exception\RecordNotFoundException;

class JobCategoryAPI extends Endpoint implements CollectionEndpointInterface, ResourceEndpointInterface
{
    /**
     * @var null|JobCategoryService
     */
    protected ?JobCategoryService $jobCategoryService = null;

    const PARAMETER_ID = 'id';
    const PARAMETER_IDS = 'ids';
    const PARAMETER_NAME = 'name';

    const PARAMETER_SORT_FIELD = 'sortField';
    const PARAMETER_SORT_ORDER = 'sortOrder';
    const PARAMETER_OFFSET = 'offset';
    const PARAMETER_LIMIT = 'limit';

    /**
     * @return JobCategoryService
     */
    public function getJobCategoryService(): JobCategoryService
    {
        if (is_null($this->jobCategoryService)) {
            $this->jobCategoryService = new JobCategoryService();
        }
        return $this->jobCategoryService;
    }

    /**
     * @param JobCategoryService $jobCategoryService
     */
    public function setJobCategoryService(JobCategoryService $jobCategoryService)
    {
        $this->jobCategoryService = $jobCategoryService;
    }

    /**
     * @return EndpointGetOneResult
     * @throws RecordNotFoundException
     * @throws DaoException
     * @throws Exception
     */
    public function getOne(): EndpointGetOneResult
    {
        // TODO:: Check data group permission
        $id = $this->getRequestParams()->getInt(RequestParams::PARAM_TYPE_ATTRIBUTE, self::PARAMETER_ID);
        $jobCategory = $this->getJobCategoryService()->getJobCategoryById($id);
        if (!$jobCategory instanceof JobCategory) {
            throw new RecordNotFoundException('No Record Found');
        }

        return new EndpointGetOneResult(JobCategoryModel::class, $jobCategory);
    }

    /**
     * @return EndpointGetAllResult
     * @throws DaoException
     * @throws RecordNotFoundException
     * @throws Exception
     */
    public function getAll(): EndpointGetAllResult
    {
        // TODO:: Check data group permission
        $sortField = $this->getRequestParams()->getString(
            RequestParams::PARAM_TYPE_QUERY,
            self::PARAMETER_SORT_FIELD,
            'jc.name'
        );
        $sortOrder = $this->getRequestParams()->getString(
            RequestParams::PARAM_TYPE_QUERY,
            self::PARAMETER_SORT_ORDER,
            'ASC'
        );
        $limit = $this->getRequestParams()->getInt(RequestParams::PARAM_TYPE_QUERY, self::PARAMETER_LIMIT, 50);
        $offset = $this->getRequestParams()->getInt(RequestParams::PARAM_TYPE_QUERY, self::PARAMETER_OFFSET, 0);

        $count = $this->getJobCategoryService()->getJobCategoryList(
            $sortField,
            $sortOrder,
            $limit,
            $offset,
            true
        );

        $jobCategories = $this->getJobCategoryService()->getJobCategoryList($sortField, $sortOrder, $limit, $offset);

        return new EndpointGetAllResult(
            JobCategoryModel::class, $jobCategories,
            new ParameterBag(['total' => $count])
        );
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function create(): EndpointCreateResult
    {
        // TODO:: Check data group permission
        $jobCategory = $this->saveJobCategory();

        return new EndpointCreateResult(JobCategoryModel::class, $jobCategory);
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function update(): EndpointUpdateResult
    {
        // TODO:: Check data group permission
        $jobCategory = $this->saveJobCategory();

        return new EndpointUpdateResult(JobCategoryModel::class, $jobCategory);
    }

    /**
     * @return JobCategory
     * @throws DaoException
     */
    private function saveJobCategory(): JobCategory
    {
        $id = $this->getRequestParams()->getInt(RequestParams::PARAM_TYPE_ATTRIBUTE, self::PARAMETER_ID);
        $name = $this->getRequestParams()->getString(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_NAME);
        if (!empty($id)) {
            $jobCategory = $this->getJobCategoryService()->getJobCategoryById($id);
        } else {
            $jobCategory = new JobCategory();
        }

        $jobCategory->setName($name);
        return $this->getJobCategoryService()->saveJobCategory($jobCategory);
    }

    /**
     * @inheritDoc
     * @throws DaoException
     * @throws Exception
     */
    public function delete(): EndpointDeleteResult
    {
        // TODO:: Check data group permission
        $ids = $this->getRequestParams()->getArray(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_IDS);
        $this->getJobCategoryService()->deleteJobCategory($ids);
        return new EndpointDeleteResult(ArrayModel::class, $ids);
    }
}
