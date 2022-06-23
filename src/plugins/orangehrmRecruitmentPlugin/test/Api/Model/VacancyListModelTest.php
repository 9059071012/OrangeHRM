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

namespace OrangeHRM\Tests\CRecruitment\Api\Model;

use OrangeHRM\Core\Api\V2\Serializer\NormalizeException;
use OrangeHRM\Entity\Vacancy;
use OrangeHRM\Recruitment\Api\Model\VacancyListModel;
use OrangeHRM\Tests\Util\TestCase;

class VacancyListModelTest extends TestCase
{
    /**
     * @return void
     * @throws NormalizeException
     */
    public function testToArray()
    {
        $resultArray = [
            'id' => 1,
            'name' => 'Title',
            'description' => 'Description',
        ];

        $vacancy = new Vacancy();
        $vacancy->setId(1);
        $vacancy->setName('Title');
        $vacancy->setDescription('Description');
        $vacancyModel = new VacancyListModel($vacancy);
        $this->assertEquals($resultArray, $vacancyModel->toArray());
    }
}
