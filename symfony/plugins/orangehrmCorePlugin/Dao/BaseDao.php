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

namespace OrangeHRM\Core\Dao;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ObjectRepository;
use OrangeHRM\ORM\Doctrine;

abstract class BaseDao
{
    /**
     * @param string $entityClass
     * @return ObjectRepository|EntityRepository
     *
     * @template T
     * @psalm-param class-string<T> $entityClass
     * @psalm-return EntityRepository<T>
     */
    protected function getRepository(string $entityClass)
    {
        return Doctrine::getEntityManager()->getRepository($entityClass);
    }

    /**
     * @param $entity
     * @throws ORMException
     * @throws OptimisticLockException
     */
    protected function persist($entity): void
    {
        Doctrine::getEntityManager()->persist($entity);
        Doctrine::getEntityManager()->flush();
    }

    protected function createQueryBuilder(string $entityClass, string $alias, ?string $indexBy = null)
    {
        return Doctrine::getEntityManager()->createQueryBuilder()
            ->select($alias)
            ->from($entityClass, $alias, $indexBy);
    }
}
