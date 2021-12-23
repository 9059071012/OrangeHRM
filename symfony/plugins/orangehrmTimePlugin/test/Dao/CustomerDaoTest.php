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

namespace OrangeHRM\Tests\Time\Dao;

use Exception;
use OrangeHRM\Config\Config;
use OrangeHRM\Entity\Customer;
use OrangeHRM\Tests\Util\KernelTestCase;
use OrangeHRM\Tests\Util\TestDataService;
use OrangeHRM\Time\Dao\CustomerDao;
use OrangeHRM\Time\Dto\CustomerSearchFilterParams;

/**
 * @group Time
 * @group Dao
 */
class CustomerDaoTest extends KernelTestCase
{
    private CustomerDao $customerDao;
    protected string $fixture;

    /**
     * Set up method
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->customerDao = new CustomerDao();
        $this->fixture = Config::get(Config::PLUGINS_DIR) . '/orangehrmTimePlugin/test/fixtures/CustomerService.yml';
        TestDataService::populate($this->fixture);
    }

    public function testAddCustomer(): void
    {
        $customer = new Customer();
        $customer->setName('Customer 2');
        $customer->setDescription('Description 2');
        $customer->setDeleted(false);
        $result = $this->customerDao->saveCustomer($customer);
        $this->assertTrue($result instanceof Customer);
        $this->assertEquals('Customer 2', $result->getName());
    }

    public function testGetCustomerList(): void
    {
        $customerFilterParams = new CustomerSearchFilterParams();
        $result = $this->customerDao->searchCustomers($customerFilterParams);
        $this->assertCount(3, $result);
        $this->assertTrue($result[0] instanceof Customer);
    }

    public function testFilterByCustomerName(): void
    {
        $customerFilterParams = new CustomerSearchFilterParams();
        $customerFilterParams->setName("Orange");
        $result = $this->customerDao->searchCustomers($customerFilterParams);
        $this->assertCount(1, $result);
        $this->assertTrue($result[0] instanceof Customer);
        $this->assertEquals('Orange', $result[0]->getName());
    }

    public function testGetCustomerById(): void
    {
        $result = $this->customerDao->getCustomerById(1);
        $this->assertEquals('Orange', $result->getName());
        $this->assertEquals('HRM', $result->getDescription());
    }

    public function testGetCustomerByIdOnNull(): void
    {
        $result = $this->customerDao->getCustomerById(10);
        $this->assertFalse($result instanceof Customer);
        $this->assertEquals(null, $result);
    }

    public function testGetCustomer(): void
    {
        $result = $this->customerDao->getCustomer(100);
        $this->assertFalse($result instanceof Customer);
        $this->assertEquals(null, $result);
    }

    public function testUpdateCustomer(): void
    {
        $customer = $this->customerDao->getCustomerById(1);
        $customer->setName("TTTT");
        $customer->setDescription("DDD");
        $result = $this->customerDao->saveCustomer($customer);
        $this->assertTrue($result instanceof Customer);
        $this->assertEquals("TTTT", $result->getName());
        $this->assertEquals("DDD", $result->getDescription());
        $this->assertEquals(1, $result->getId());
    }

    public function testDeleteCustomer(): void
    {
        $customerId = [1, 2];
        $result = $this->customerDao->deleteCustomer($customerId);
        $this->assertEquals(2, $result);
    }
}
