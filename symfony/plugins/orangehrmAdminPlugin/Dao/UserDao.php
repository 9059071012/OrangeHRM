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

namespace OrangeHRM\Admin\Dao;

use Doctrine\ORM\QueryBuilder;
use Exception;
use OrangeHRM\Admin\Dto\UserSearchParamHolder;
use OrangeHRM\Core\Exception\DaoException;
use OrangeHRM\Entity\Employee;
use OrangeHRM\Entity\User;
use OrangeHRM\Entity\UserRole;
use OrangeHRM\ORM\Doctrine;
use OrangeHRM\ORM\ListSorter;
use OrangeHRM\ORM\Paginator;
use OrangeHRM\Authentication\Dto\UserCredential;

class UserDao
{

    /**
     * Save System User
     *
     * @param User $systemUser
     * @return User
     * @throws DaoException
     */
    public function saveSystemUser(User $systemUser): User
    {
        try {
            Doctrine::getEntityManager()->persist($systemUser);
            Doctrine::getEntityManager()->flush();
            return $systemUser;
        } catch (Exception $e) {
            throw new DaoException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Check is existing user according to user name
     *
     * @param UserCredential $credentials
     * @param int|null $userId
     * @return User|null
     * @throws DaoException
     */
    public function isExistingSystemUser(UserCredential $credentials, ?int $userId = null): ?User
    {
        try {
            $query = Doctrine::getEntityManager()->getRepository(
                User::class
            )->createQueryBuilder('u');
            $query->andWhere('u.userName = :username');
            $query->setParameter('username', $credentials->getUsername());
            if (!empty($userId)) {
                $query->andWhere('u.id = :userId');
                $query->setParameter('userId', $userId);
            }

            return $query->getQuery()->getOneOrNullResult();
        } catch (Exception $e) {
            throw new DaoException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Get System User for given User Id
     * @param int $userId
     * @return User|null
     * @throws DaoException
     */
    public function getSystemUser(int $userId): ?User
    {
        try {
            $user = Doctrine::getEntityManager()->getRepository(User::class)->find($userId);
            if ($user instanceof User) {
                return $user;
            }
            return null;
        } catch (Exception $e) {
            throw new DaoException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Get System Users
     *
     * @return User[]
     * @throws DaoException
     */
    public function getSystemUsers(): array
    {
        try {
            $query = Doctrine::getEntityManager()->getRepository(
                User::class
            )->createQueryBuilder('u');
            $query->andWhere('u.deleted = :deleted');
            $query->setParameter('deleted', false);

            return $query->getQuery()->execute();
        } catch (Exception $e) {
            throw new DaoException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Return an array of System User Ids
     * @return array
     * @throws DaoException
     */
    public function getSystemUserIdList(): array
    {
        try {
            $query = Doctrine::getEntityManager()->getRepository(
                User::class
            )->createQueryBuilder('u');
            $query->select('u.id');
            $query->andWhere('u.deleted = :deleted');
            $query->setParameter('deleted', false);


            $result = $query->getQuery()->getScalarResult();
            return array_column($result, 'id');
        } catch (Exception $e) {
            throw new DaoException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Soft Delete System Users
     * @param array $deletedIds
     * @return int
     * @throws DaoException
     */
    public function deleteSystemUsers(array $deletedIds): int
    {
        try {
            $q = Doctrine::getEntityManager()->createQueryBuilder();
            $q->update(User::class, 'u')
                ->set('u.deleted', ':deleted')
                ->setParameter('deleted', true)
                ->where($q->expr()->in('u.id', $deletedIds));
            return $q->getQuery()->execute();
        } catch (Exception $e) {
            throw new DaoException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Get System Users
     *
     * @return UserRole[]
     * @throws DaoException
     */
    public function getAssignableUserRoles(): array
    {
        try {
            $query = Doctrine::getEntityManager()->getRepository(
                UserRole::class
            )->createQueryBuilder('ur');
            $query->andWhere($query->expr()->in('ur.isAssignable', 1));
            $query->addOrderBy('ur.name', ListSorter::ASCENDING);

            return $query->getQuery()->execute();
        } catch (Exception $e) {
            throw new DaoException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @param string $roleName
     * @return UserRole|null
     * @throws DaoException
     */
    public function getUserRole(string $roleName): ?UserRole
    {
        try {
            $query = Doctrine::getEntityManager()->getRepository(
                UserRole::class
            )->createQueryBuilder('ur');
            $query->andWhere('ur.name = :name');
            $query->setParameter('name', $roleName);
            return $query->getQuery()->getOneOrNullResult();
        } catch (Exception $e) {
            throw new DaoException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @param int $id
     * @return UserRole|null
     * @throws DaoException
     */
    public function getUserRoleById(int $id): ?UserRole
    {
        try {
            $userRole = Doctrine::getEntityManager()->getRepository(UserRole::class)->find($id);
            if ($userRole instanceof UserRole) {
                return $userRole;
            }
            return null;
        } catch (Exception $e) {
            throw new DaoException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @return UserRole[]
     * @throws DaoException
     */
    public function getNonPredefinedUserRoles(): array
    {
        try {
            $query = Doctrine::getEntityManager()->getRepository(
                UserRole::class
            )->createQueryBuilder('ur');
            $query->andWhere('ur.isPredefined = :isPredefined');
            $query->setParameter('isPredefined', false);
            $query->addOrderBy('ur.name', ListSorter::ASCENDING);

            return $query->getQuery()->execute();
        } catch (Exception $e) {
            throw new DaoException($e);
        }
    }

    /**
     * Get Count of Search Query
     *
     * @param UserSearchParamHolder $userSearchParamHolder
     * @return int
     * @throws DaoException
     */
    public function getSearchSystemUsersCount(UserSearchParamHolder $userSearchParamHolder): int
    {
        try {
            $q = $this->_buildSearchQuery($userSearchParamHolder);
            $paginator = new Paginator($q);
            return $paginator->count();
        } catch (Exception $e) {
            throw new DaoException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Search System Users
     *
     * @param UserSearchParamHolder $userSearchParamHolder
     * @return array
     * @throws DaoException
     */
    public function searchSystemUsers(UserSearchParamHolder $userSearchParamHolder): array
    {
        try {
            $q = $this->_buildSearchQuery($userSearchParamHolder);
            return $q->getQuery()->execute();
        } catch (Exception $e) {
            throw new DaoException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @param UserSearchParamHolder $userSearchParamHolder
     * @return QueryBuilder
     */
    private function _buildSearchQuery(UserSearchParamHolder $userSearchParamHolder): QueryBuilder
    {
        $q = Doctrine::getEntityManager()->getRepository(
            User::class
        )->createQueryBuilder('u');
        $q->leftJoin('u.userRole', 'r');
        $q->leftJoin('u.employee', 'e');

        if (!is_null($userSearchParamHolder->getSortField())) {
            $q->addOrderBy($userSearchParamHolder->getSortField(), $userSearchParamHolder->getSortOrder());
        }
        if (!empty($userSearchParamHolder->getLimit())) {
            $q->setFirstResult($userSearchParamHolder->getOffset())
                ->setMaxResults($userSearchParamHolder->getLimit());
        }

        if (!empty($userSearchParamHolder->getUsername())) {
            $q->andWhere('u.userName = :userName');
            $q->setParameter('userName', $userSearchParamHolder->getUsername());
        }
        if (!is_null($userSearchParamHolder->getUserRoleId())) {
            $q->andWhere('r.id = :userRoleId');
            $q->setParameter('userRoleId', $userSearchParamHolder->getUserRoleId());
        }
        if (!is_null($userSearchParamHolder->getEmpNumber())) {
            $q->andWhere('e.empNumber = :empNumber');
            $q->setParameter('empNumber', $userSearchParamHolder->getEmpNumber());
        }
        if (!is_null($userSearchParamHolder->getStatus())) {
            $q->andWhere('u.status = :status');
            $q->setParameter('status', $userSearchParamHolder->getStatus());
        }

        $q->andWhere('u.deleted = :deleted');
        $q->setParameter('deleted', false);

        return $q;
    }

    /**
     * @param bool $enabledOnly
     * @param bool $undeletedOnly
     * @return int
     */
    public function getAdminUserCount(bool $enabledOnly = true, bool $undeletedOnly = true): int
    {
        $q = Doctrine::getEntityManager()->getRepository(
            User::class
        )->createQueryBuilder('u');
        $q->leftJoin('u.userRole', 'ur');
        $q->andWhere('ur.id = :userRoleId');
        $q->setParameter('userRoleId', User::ADMIN_USER_ROLE_ID);
        if ($enabledOnly) {
            $q->andWhere('u.status = :status');
            $q->setParameter('status', User::ENABLED);
        }
        if ($undeletedOnly) {
            $q->andWhere('u.deleted = :deleted');
            $q->setParameter('deleted', User::UNDELETED);
        }

        $paginator = new Paginator($q);
        return $paginator->count();
    }

    /**
     * @param int $userId
     * @param string $password
     * @return bool
     * @throws DaoException
     */
    public function updatePassword(int $userId, string $password): bool
    {
        try {
            $q = Doctrine::getEntityManager()->createQueryBuilder();
            $q->update(User::class, 'u')
                ->set('u.userPassword', ':userPassword')
                ->setParameter('userPassword', $password)
                ->where('u.id = :id')
                ->setParameter('id', $userId);
            $result = $q->getQuery()->execute();
            return !($result < 1);
        } catch (Exception $e) {
            throw new DaoException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @param string $roleName
     * @param bool $includeInactive
     * @param bool $includeTerminated
     * @return Employee[]
     * @throws DaoException
     */
    public function getEmployeesByUserRole(
        string $roleName,
        bool $includeInactive = false,
        bool $includeTerminated = false
    ): array {
        try {
            $q = Doctrine::getEntityManager()->getRepository(
                Employee::class
            )->createQueryBuilder('e');
            $q->innerJoin('e.users', 'u');
            $q->leftJoin('u.userRole', 'r');
            $q->andWhere('r.name = :roleName');
            $q->setParameter('roleName', $roleName);

            if (!$includeInactive) {
                $q->andWhere('u.deleted = :deleted');
                $q->setParameter('deleted', false);
            }

            if (!$includeTerminated) {
                $q->andWhere($q->expr()->isNull('e.employeeTerminationRecord'));
            }

            return $q->getQuery()->execute();
        } catch (Exception $e) {
            throw new DaoException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
