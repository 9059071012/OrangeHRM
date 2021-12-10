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

namespace OrangeHRM\Time\Api\Model;

use OrangeHRM\Core\Api\V2\Serializer\ModelTrait;
use OrangeHRM\Core\Api\V2\Serializer\Normalizable;
use OrangeHRM\Entity\Project;

class ProjectDetailedModel implements Normalizable
{
    use ModelTrait;

    public function __construct(Project $project)
    {
        $this->setEntity($project);
        $this->setFilters([
            'id',
            'name',
            'description',
            ['getCustomer', 'getId'],
            ['getCustomer', 'getName'],
            ['getCustomer', 'isDeleted'],
            ['getProjectAdmins', ['getEmpNumber', 'getLastName', 'getFirstName', 'getMiddleName','getEmployeeTerminationRecord']],
            ['isDeleted'],
        ]);

        $this->setAttributeNames([
            'id',
            'name',
            'description',
            ['customer', 'id'],
            ['customer', 'name'],
            ['customer', 'deleted'],
            ['projectAdmins', ['empNumber', 'lastName', 'firstName', 'middleName','terminationId']],
            'deleted',
        ]);
    }
}
