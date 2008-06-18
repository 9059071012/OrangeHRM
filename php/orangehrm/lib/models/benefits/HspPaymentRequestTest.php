<?php
// Call HspPaymentRequestTest::main() if this source file is executed directly.
if (!defined("PHPUnit_MAIN_METHOD")) {
    define("PHPUnit_MAIN_METHOD", "HspPaymentRequestTest::main");
}

require_once "PHPUnit/Framework/TestCase.php";
require_once "PHPUnit/Framework/TestSuite.php";

require_once "testConf.php";

require_once 'HspPaymentRequest.php';


require_once ROOT_PATH."/lib/confs/Conf.php";
require_once ROOT_PATH."/lib/common/UniqueIDGenerator.php";

/**
 * Test class for HspPaymentRequest.
 * Generated by PHPUnit_Util_Skeleton on 2007-10-23 at 23:31:17.
 */
class HspPaymentRequestTest extends PHPUnit_Framework_TestCase {
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main() {
        require_once "PHPUnit/TextUI/TestRunner.php";

        $suite  = new PHPUnit_Framework_TestSuite("HspPaymentRequestTest");
        $result = PHPUnit_TextUI_TestRunner::run($suite);
    }

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp() {
    	$conf = new Conf();
    	$this->connection = mysql_connect($conf->dbhost.":".$conf->dbport, $conf->dbuser, $conf->dbpass);
        mysql_select_db($conf->dbname);
    	$this->_deleteTables();
    	
		$this->_runQuery("INSERT INTO `hs_hr_employee`(emp_number, emp_lastname, emp_firstname, emp_nick_name) " .
				"VALUES (11, 'Arnold', 'Subasinghe', 'Arnold')");

        $this->_runQuery("INSERT INTO `hs_hr_hsp` (`id`,`employee_id`,`hsp_value`,`total_acrued`," .
        							  "`termination_date`,`halted`,`halted_date`,`terminated`) " .
        							  "VALUES(10, 11, 1000, 500, NULL, false, NULL, false)");

	    $this->_runQuery("INSERT INTO `hs_hr_hsp_payment_request` (`id`, `hsp_id`, `employee_id`, `date_incurred`," .
	    							  "`provider_name`, `person_incurring_expense`, `expense_description`, `expense_amount`, `payment_made_to`," .
	    							  "`third_party_account_number`, `mail_address`, `comments`, `date_paid`, `check_number`,	`status`) " .
	    							  "VALUES (10, 10, 11, '".date('Y-m-d', time()-3600*24)."', 'Test provider', 'Tester', 'Just testing', '100', 'TestX', '12345GD', " .
	    							  "'1231, Test Grove, Test City', 'Test', '".date('Y-m-d')."',  '123552-55821-ff25', 1)");

		$this->_runQuery("INSERT INTO `hs_hr_hsp_payment_request` (`id`, `hsp_id`, `employee_id`, `date_incurred`," .
	    							  "`provider_name`, `person_incurring_expense`, `expense_description`, `expense_amount`, `payment_made_to`," .
	    							  "`third_party_account_number`, `mail_address`, `comments`, `date_paid`, `check_number`,	`status`) " .
	    							  "VALUES (11, 10, 11, '".date('Y-m-d', time()-3600*24)."', 'Test provider 1', 'Tester 1', 'Just testing 1', '100', 'TestX 1', '12345GD', " .
	    							  "'1231, Test Grove, Test City 1', 'Test 1', NULL,  NULL, 0)");

		$this->_runQuery("INSERT INTO `hs_hr_hsp_payment_request` (`id`, `hsp_id`, `employee_id`, `date_incurred`," .
	    							  "`provider_name`, `person_incurring_expense`, `expense_description`, `expense_amount`, `payment_made_to`," .
	    							  "`third_party_account_number`, `mail_address`, `comments`, `date_paid`, `check_number`,	`status`) " .
	    							  "VALUES (12, 10, 11, '".date('Y-m-d', time()-3600*24)."', 'Test provider 2', 'Tester 2', 'Just testing 2', '100', 'TestX 2', '12345GD', " .
	    							  "'1231, Test Grove, Test City 2', 'Test 2', NULL,  NULL, 2)");

		$this->_runQuery("INSERT INTO `hs_hr_hsp_payment_request` (`id`, `hsp_id`, `employee_id`, `date_incurred`," .
	    							  "`provider_name`, `person_incurring_expense`, `expense_description`, `expense_amount`, `payment_made_to`," .
	    							  "`third_party_account_number`, `mail_address`, `comments`, `date_paid`, `check_number`,	`status`) " .
	    							  "VALUES (13, 10, 11, '".date('Y-m-d', time()-3600*24)."', 'Test provider 3', 'Tester 3', 'Just testing 3', '100', 'TestX 3', '12345GD', " .
	    							  "'1231, Test Grove, Test City 3', 'Test 3', NULL,  NULL, 3)");

		$this->_runQuery("INSERT INTO `hs_hr_emp_children` (`emp_number`, `ec_name`, `ec_seqno`) VALUES(11, 'saman', 1)");
    	$this->_runQuery("INSERT INTO `hs_hr_emp_children` (`emp_number`, `ec_name`, `ec_seqno`) VALUES(11, 'saman2', 2)");
    	$this->_runQuery("INSERT INTO `hs_hr_emp_dependents` (`emp_number`, `ed_name`, `ed_relationship`, `ed_seqno`) VALUES (11, 'kamal', 'Father', 1)");
    	$this->_runQuery("INSERT INTO `hs_hr_emp_dependents` (`emp_number`, `ed_name`, `ed_relationship`, `ed_seqno`) VALUES (11, 'kasun', 'Father in low', 2)");
		$this->_runQuery("INSERT INTO `hs_hr_emp_dependents` (`emp_number`, `ed_name`, `ed_relationship`, `ed_seqno`) VALUES (11, 'kasun2', 'Father in low', 3)");

    	UniqueIDGenerator::getInstance()->resetIDs();
    }

    /**
     * Tears down the fixture, for example, close a network connection.
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown() {
    	$this->_deleteTables();
    	UniqueIDGenerator::getInstance()->resetIDs();
    }
    
    private function _deleteTables() {
    	$this->_runQuery("TRUNCATE hs_hr_hsp_payment_request;");
    	$this->_runQuery("DELETE FROM hs_hr_hsp WHERE `id` = '10'");
    	$this->_runQuery("DELETE FROM hs_hr_employee WHERE `emp_number` = 11");
		$this->_runQuery("DELETE FROM hs_hr_emp_children WHERE `emp_number` = 11");
		$this->_runQuery("DELETE FROM hs_hr_emp_dependents WHERE `emp_number` = '11'");    	
    }

	private function _runQuery($sql) {
	
		$this->assertTrue(mysql_query($sql), mysql_error());	
	}
	
    public function testGetHspRequest() {
    	$paymentRequest = HspPaymentRequest::getHspRequest(50);

		$this->assertNull($paymentRequest);

    	$expected = array(10, 10, 11,date('Y-m-d', time()-3600*24), 'Test provider', 'Tester', 'Just testing', '100', 'TestX', '12345GD',
	    							  '1231, Test Grove, Test City', 'Test', date('Y-m-d'),  '123552-55821-ff25', 1);
		$paymentRequest = HspPaymentRequest::getHspRequest(10);

		$this->assertNotNull($paymentRequest);

		$this->assertEquals($expected[0], $paymentRequest->getId());
		$this->assertEquals($expected[1], $paymentRequest->getHspId());
		$this->assertEquals($expected[2], $paymentRequest->getEmployeeId());
		$this->assertEquals($expected[3], $paymentRequest->getDateIncurred());
		$this->assertEquals($expected[4], $paymentRequest->getProviderName());
		$this->assertEquals($expected[5], $paymentRequest->getPersonIncurringExpense());
		$this->assertEquals($expected[6], $paymentRequest->getExpenseDescription());
		$this->assertEquals($expected[7], $paymentRequest->getExpenseAmount());
		$this->assertEquals($expected[8], $paymentRequest->getPaymentMadeTo());
		$this->assertEquals($expected[9], $paymentRequest->getThirdPartyAccountNumber());
		$this->assertEquals($expected[10], $paymentRequest->getMailAddress());
		$this->assertEquals($expected[11], $paymentRequest->getComments());
		$this->assertEquals($expected[12], $paymentRequest->getDatePaid());
		$this->assertEquals($expected[13], $paymentRequest->getCheckNumber());
		$this->assertEquals($expected[14], $paymentRequest->getStatus());

    }

    public function testListUnPaidHspRequests() {

		$expected[] = array(11, 10, 11, date('Y-m-d', time()-3600*24), 'Test provider 1', 'Tester 1', 'Just testing 1', '100', 'TestX 1', '12345GD',
	    							  '1231, Test Grove, Test City 1', 'Test 1', null, null, 0);

		$paymentRequests = HspPaymentRequest::listUnPaidHspRequests();
		$this->assertNotNull($paymentRequests);

		for ($i=0; $i<count($paymentRequests); $i++) {
			$this->assertNotNull($paymentRequests[$i]);

			$this->assertEquals($expected[$i][0], $paymentRequests[$i]->getId());
			$this->assertEquals($expected[$i][1], $paymentRequests[$i]->getHspId());
			$this->assertEquals($expected[$i][2], $paymentRequests[$i]->getEmployeeId());
			$this->assertEquals($expected[$i][3], $paymentRequests[$i]->getDateIncurred());
			$this->assertEquals($expected[$i][4], $paymentRequests[$i]->getProviderName());
			$this->assertEquals($expected[$i][5], $paymentRequests[$i]->getPersonIncurringExpense());
			$this->assertEquals($expected[$i][6], $paymentRequests[$i]->getExpenseDescription());
			$this->assertEquals($expected[$i][7], $paymentRequests[$i]->getExpenseAmount());
			$this->assertEquals($expected[$i][8], $paymentRequests[$i]->getPaymentMadeTo());
			$this->assertEquals($expected[$i][9], $paymentRequests[$i]->getThirdPartyAccountNumber());
			$this->assertEquals($expected[$i][10], $paymentRequests[$i]->getMailAddress());
			$this->assertEquals($expected[$i][11], $paymentRequests[$i]->getComments());
			$this->assertEquals($expected[$i][12], $paymentRequests[$i]->getDatePaid());
			$this->assertEquals($expected[$i][13], $paymentRequests[$i]->getCheckNumber());
			$this->assertEquals($expected[$i][14], $paymentRequests[$i]->getStatus());
		}

		$this->assertTrue(mysql_query("DELETE FROM `hs_hr_hsp_payment_request` WHERE `id` IN (11);", $this->connection), mysql_error());

		UniqueIDGenerator::getInstance()->resetIDs();

		$paymentRequests = HspPaymentRequest::listUnPaidHspRequests();
		$this->assertNull($paymentRequests);
    }

    public function testListEmployeeHspRequests() {

		$expected[] = array(10, 10, 11, date('Y-m-d', time()-3600*24), 'Test provider', 'Tester', 'Just testing', '100', 'TestX', '12345GD',
	    							  '1231, Test Grove, Test City', 'Test', date('Y-m-d'),  '123552-55821-ff25', 1);
		$expected[] = array(11, 10, 11, date('Y-m-d', time()-3600*24), 'Test provider 1', 'Tester 1', 'Just testing 1', '100', 'TestX 1', '12345GD',
	    							  '1231, Test Grove, Test City 1', 'Test 1', null, null, 0);
	    $expected[] = array(12, 10, 11, date('Y-m-d', time()-3600*24), 'Test provider 2', 'Tester 2', 'Just testing 2', '100', 'TestX 2', '12345GD',
	    							  '1231, Test Grove, Test City 2', 'Test 2', null, null, 2);

		$paymentRequests = HspPaymentRequest::listEmployeeHspRequests(date('Y'), 11);
		$this->assertNotNull($paymentRequests);

		for ($i=0; $i<count($paymentRequests); $i++) {
			$this->assertNotNull($paymentRequests[$i]);

			$this->assertEquals($expected[$i][0], $paymentRequests[$i]->getId());
			$this->assertEquals($expected[$i][1], $paymentRequests[$i]->getHspId());
			$this->assertEquals($expected[$i][2], $paymentRequests[$i]->getEmployeeId());
			$this->assertEquals($expected[$i][3], $paymentRequests[$i]->getDateIncurred());
			$this->assertEquals($expected[$i][4], $paymentRequests[$i]->getProviderName());
			$this->assertEquals($expected[$i][5], $paymentRequests[$i]->getPersonIncurringExpense());
			$this->assertEquals($expected[$i][6], $paymentRequests[$i]->getExpenseDescription());
			$this->assertEquals($expected[$i][7], $paymentRequests[$i]->getExpenseAmount());
			$this->assertEquals($expected[$i][8], $paymentRequests[$i]->getPaymentMadeTo());
			$this->assertEquals($expected[$i][9], $paymentRequests[$i]->getThirdPartyAccountNumber());
			$this->assertEquals($expected[$i][10], $paymentRequests[$i]->getMailAddress());
			$this->assertEquals($expected[$i][11], $paymentRequests[$i]->getComments());
			$this->assertEquals($expected[$i][12], $paymentRequests[$i]->getDatePaid());
			$this->assertEquals($expected[$i][13], $paymentRequests[$i]->getCheckNumber());
			$this->assertEquals($expected[$i][14], $paymentRequests[$i]->getStatus());
		}

		$paymentRequests = HspPaymentRequest::listEmployeeHspRequests(date('Y')+1, 11);
		$this->assertNull($paymentRequests);

		$this->assertTrue(mysql_query("DELETE FROM `hs_hr_hsp_payment_request` WHERE `id` IN (12);", $this->connection), mysql_error());

		try {
			$paymentRequests = HspPaymentRequest::listEmployeeHspRequests(date('Y'), 'Xd85');
			$this->fail('Exception not thrown');
		} catch (HspPaymentRequestException $e) {
			$this->assertEquals(HspPaymentRequestException::INVALID_EMPLOYEE_ID, $e->getCode(), 'Unexpected exception thrown');
		}
    }

    public function testPayHspRequest() {
        $paymentRequest = new HspPaymentRequest();
		$paymentRequest->setId(10);

		try {
			$paymentRequest->payHspRequest();
			$this->fail('Exception not thrown');
		} catch (HspPaymentRequestException $e) {
			$this->assertEquals(HspPaymentRequestException::ALREADY_PAID, $e->getCode(), 'Unexpected exception thrown');
		}

		$paymentRequest = new HspPaymentRequest();
		$paymentRequest->setId(11);
		$paymentRequest->setDatePaid(date('Y-m-d'));
		$paymentRequest->setCheckNumber('bsdfds-gfgbvbv-bfdtr');

		try {
			$paymentRequest->payHspRequest();
			$paymentRequest = HspPaymentRequest::getHspRequest(11);
			$this->assertNotNull($paymentRequest);
			$this->assertEquals(HspPaymentRequest::HSP_PAYMENT_REQUEST_STATUS_PAID, $paymentRequest->getStatus());

		} catch (HspPaymentRequestException $e) {
			$this->fail('Unexpected exception thrown');
		}
    }

    public function testDeleteHspRequest() {
        $paymentRequest = new HspPaymentRequest();
		$paymentRequest->setId(10);

		try {
			$paymentRequest->deleteHspRequest();
			$this->fail('Exception not thrown');
		} catch (HspPaymentRequestException $e) {
			$this->assertEquals(HspPaymentRequestException::ALREADY_PAID, $e->getCode(), 'Unexpected exception thrown');
		}

		$paymentRequest = new HspPaymentRequest();
		$paymentRequest->setId(11);

		try {
			$paymentRequest->deleteHspRequest();
			$paymentRequest = HspPaymentRequest::getHspRequest(11);
			$this->assertNotNull($paymentRequest);
			$this->assertEquals(HspPaymentRequest::HSP_PAYMENT_REQUEST_STATUS_DELETED, $paymentRequest->getStatus());

		} catch (HspPaymentRequestException $e) {
			$this->fail('Unexpected exception thrown');
		}
    }

    public function testDenyHspRequest() {
		$paymentRequest = new HspPaymentRequest();
		$paymentRequest->setId(10);

		try {
			$paymentRequest->denyHspRequest();
			$this->fail('Exception not thrown');
		} catch (HspPaymentRequestException $e) {
			$this->assertEquals(HspPaymentRequestException::ALREADY_PAID, $e->getCode(), 'Unexpected exception thrown');
		}

		$paymentRequest = new HspPaymentRequest();
		$paymentRequest->setId(11);

		try {
			$paymentRequest->denyHspRequest();
			$paymentRequest = HspPaymentRequest::getHspRequest(11);
			$this->assertNotNull($paymentRequest);
			$this->assertEquals(HspPaymentRequest::HSP_PAYMENT_REQUEST_STATUS_DENIED, $paymentRequest->getStatus());
		} catch (HspPaymentRequestException $e) {
			$this->fail('Unexpected exception thrown');
		}
    }


    public function testAddHspRequest() {

	    $paymentRequest = new HspPaymentRequest();

	    // Adding correct Data

	    $paymentRequest->setHspId(0);
	    $paymentRequest->setEmployeeId(1);
	    $paymentRequest->setDateIncurred(date('Y-m-d', time()-3600*24));
	    $paymentRequest->setProviderName('Jack');
	    $paymentRequest->setPersonIncurringExpense('Bauer');
	    $paymentRequest->setExpenseDescription('Health');
	    $paymentRequest->setExpenseAmount(100);
	    $paymentRequest->setPaymentMadeTo('Neena');
	    $paymentRequest->setThirdPartyAccountNumber('123456');
	    $paymentRequest->setMailAddress('');
	    $paymentRequest->setComments('');
	    //$paymentRequest->setDatePaid($expected[13]);
	    //$paymentRequest->setCheckNumber($expected[14]);
	    $paymentRequest->setStatus(1);

	    $this->assertTrue($paymentRequest->addHspRequest());

    }

    /**
     *
     */
     public function testFetchDependants() {
		$hspPaymentRequest = new HspPaymentRequest();
		$empId = 11;

		$dependents = $hspPaymentRequest->fetchDependants($empId);
		$this->assertTrue(is_array($dependents));
		$this->assertEquals(3, count($dependents));
		$this->assertEquals("kamal", $dependents[0]);
		$this->assertEquals("kasun", $dependents[1]);
		$this->assertEquals("kasun2", $dependents[2]);

		$empId = 12;
		$dependents = $hspPaymentRequest->fetchDependants($empId);
		$this->assertFalse(isset($dependents));
     }

     /**
     *
     */
     public function testFetchChildren() {
		$hspPaymentRequest = new HspPaymentRequest();
		$empId = 11;

		$children = $hspPaymentRequest->fetchChildren($empId);
		$this->assertTrue(is_array($children));
		$this->assertEquals(2, count($children));
		$this->assertEquals("saman", $children[0]);
		$this->assertEquals("saman2", $children[1]);

		$empId = 12;
		$children = $hspPaymentRequest->fetchChildren($empId);
		$this->assertFalse(isset($children));
     }

     public function testIsDataChangedByAdmin() {
     	$hspPaymentRequest = new HspPaymentRequest();
     	$hspPaymentRequest->setDateIncurred(date('Y-m-d', time()-3600*24));
     	$hspPaymentRequest->setProviderName('Test provider');
     	$hspPaymentRequest->setPersonIncurringExpense('Tester');
     	$hspPaymentRequest->setExpenseDescription('Just testing');
     	$hspPaymentRequest->setExpenseAmount('100');
     	$hspPaymentRequest->setPaymentMadeTo('TestX');
     	$hspPaymentRequest->setThirdPartyAccountNumber('12345GD');
     	$hspPaymentRequest->setMailAddress('1231, Test Grove, Test City');
     	$hspPaymentRequest->setComments('Test');

     	$exsistingRequest = $hspPaymentRequest->getHspRequest(10);

     	$this->assertFalse($hspPaymentRequest->isDataChangedByAdmin($exsistingRequest));

     	$hspPaymentRequest->setDateIncurred(date('Y-m-d', time()-3600*24*2));
     	$msg = $hspPaymentRequest->isDataChangedByAdmin($exsistingRequest);
     	$this->assertFalse($msg == false, $msg);

		$hspPaymentRequest->setProviderName('wrong name');
     	$msg = $hspPaymentRequest->isDataChangedByAdmin($exsistingRequest);
     	$this->assertFalse($msg == false, $msg);

     }

     public function testCalculateNewHspUsed() {

     	$this->assertNotNull(mysql_query("TRUNCATE `hs_hr_employee`;", $this->connection), mysql_error());
     	$this->assertNotNull(mysql_query("TRUNCATE `hs_hr_hsp_payment_request`;", $this->connection), mysql_error());

		$this->assertNotNull(mysql_query("INSERT INTO `hs_hr_employee` VALUES (1, '001', 'Arnold', 'Subasinghe', '', 'Arnold', 0, NULL, '0000-00-00 00:00:00', NULL, NULL, NULL, '', '', '', '', '0000-00-00', '', NULL, NULL, NULL, NULL, '', '', '', 'AF', '', '', '', '', '', '', NULL, '0000-00-00', '')"));
		$this->assertNotNull(mysql_query("INSERT INTO `hs_hr_hsp_payment_request` (`id`, `hsp_id`, `employee_id`, `date_incurred`," .
	    							  "`provider_name`, `person_incurring_expense`, `expense_description`, `expense_amount`, `payment_made_to`," .
	    							  "`third_party_account_number`, `mail_address`, `comments`, `date_paid`, `check_number`,	`status`) " .
	    							  "VALUES (1, 1, 1, '".date('Y')."-02-01', 'Test provider', 'Tester', 'Just testing', '150', 'TestX', '12345GD', " .
	    							  "'1231, Test Grove, Test City', 'Test', '".date('Y')."-02-02',  '123552-55821-ff25', 1)"), mysql_error());

	    $this->assertNotNull(mysql_query("INSERT INTO `hs_hr_hsp_payment_request` (`id`, `hsp_id`, `employee_id`, `date_incurred`," .
	    							  "`provider_name`, `person_incurring_expense`, `expense_description`, `expense_amount`, `payment_made_to`," .
	    							  "`third_party_account_number`, `mail_address`, `comments`, `date_paid`, `check_number`,	`status`) " .
	    							  "VALUES (2, 1, 1, '".date('Y')."-02-10', 'Test provider', 'Tester', 'Just testing', '100', 'TestX', '12345GD', " .
	    							  "'1231, Test Grove, Test City', 'Test', '".date('Y')."-02-11',  '123552-55821-ff25', 1)"), mysql_error());

		$this->assertNotNull(mysql_query("INSERT INTO `hs_hr_hsp_payment_request` (`id`, `hsp_id`, `employee_id`, `date_incurred`," .
	    							  "`provider_name`, `person_incurring_expense`, `expense_description`, `expense_amount`, `payment_made_to`," .
	    							  "`third_party_account_number`, `mail_address`, `comments`, `date_paid`, `check_number`,	`status`) " .
	    							  "VALUES (3, 1, 1, '".date('Y')."-02-20', 'Test provider', 'Tester', 'Just testing', '127', 'TestX', '12345GD', " .
	    							  "'1231, Test Grove, Test City', 'Test', '".date('Y')."-02-21',  '123552-55821-ff25', 1)"), mysql_error());

	    $lastUpdated = date('Y')."-02-05";
	    $this->assertEquals(HspPaymentRequest::calculateNewHspUsed(1, 1, $lastUpdated), 227);

	    $lastUpdated = (date('Y')-1)."-02-05";
	    $this->assertEquals(HspPaymentRequest::calculateNewHspUsed(1, 1, $lastUpdated), 377);

     	$this->assertNotNull(mysql_query("TRUNCATE `hs_hr_employee`;", $this->connection), mysql_error());
     	$this->assertNotNull(mysql_query("TRUNCATE `hs_hr_hsp_payment_request`;", $this->connection), mysql_error());

     }


}

// Call HspPaymentRequestTest::main() if this source file is executed directly.
if (PHPUnit_MAIN_METHOD == "HspPaymentRequestTest::main") {
    HspPaymentRequestTest::main();
}
?>
