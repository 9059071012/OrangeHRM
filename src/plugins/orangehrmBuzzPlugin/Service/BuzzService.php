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

namespace OrangeHRM\Buzz\Service;

use OrangeHRM\Buzz\Dao\BuzzDao;
use OrangeHRM\Core\Traits\UserRoleManagerTrait;

class BuzzService
{
    use UserRoleManagerTrait;

    private BuzzDao $buzzDao;
    private array $buzzFeedPostPermissionCache = [];

    /**
     * @return BuzzDao
     */
    public function getBuzzDao(): BuzzDao
    {
        return $this->buzzDao ??= new BuzzDao();
    }

    /**
     * @param int $empNumber
     * @return bool
     */
    public function canUpdateBuzzFeedPost(int $empNumber): bool
    {
        $self = $this->getUserRoleManagerHelper()->isSelfByEmpNumber($empNumber);
        if (!isset($this->buzzFeedPostPermissionCache[$self])) {
            $this->buzzFeedPostPermissionCache[$self] = $this->getUserRoleManager()
                ->getDataGroupPermissions('buzz_post', [], [], $self);
        }
        return $this->buzzFeedPostPermissionCache[$self]->canUpdate();
    }

    /**
     * @param int $empNumber
     * @return bool
     */
    public function canDeleteBuzzFeedPost(int $empNumber): bool
    {
        $self = $this->getUserRoleManagerHelper()->isSelfByEmpNumber($empNumber);
        if (!isset($this->buzzFeedPostPermissionCache[$self])) {
            $this->buzzFeedPostPermissionCache[$self] = $this->getUserRoleManager()
                ->getDataGroupPermissions('buzz_post', [], [], $self);
        }
        return $this->buzzFeedPostPermissionCache[$self]->canDelete();
    }
}
