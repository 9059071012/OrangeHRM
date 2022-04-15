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

namespace OrangeHRM\Installer\Controller\Upgrader\Traits;

use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Exception;
use OrangeHRM\Installer\Util\Connection;

trait UpgraderUtilityTrait
{
    /**
     * @return bool
     */
    public function checkDatabaseConnection(): bool
    {
        try {
            $connection = $this->getConnection();
            $connection->connect();
            return true;
        } catch (Exception $exception) {
            return false;
        }
    }

    /**
     * @return bool
     * @throws \Doctrine\DBAL\Exception
     */
    public function checkDatabaseStatus(): bool
    {
        $connection = $this->getConnection();
        return $connection->createSchemaManager()->tablesExist(['ohrm_upgrade_status']);
    }

    /**
     * @return \Doctrine\DBAL\Connection
     */
    public function getConnection(): \Doctrine\DBAL\Connection
    {
        return Connection::getConnection();
    }

    /**
     * @return AbstractSchemaManager
     * @throws \Doctrine\DBAL\Exception
     */
    public function getSchemaManager(): AbstractSchemaManager
    {
        return $this->getConnection()->createSchemaManager();
    }
}
