<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

require_once 'CiviTest/CiviUnitTestCase.php';

/**
 * Class contains api test cases for "civicrm_payment_processor"
 *
 */
class api_v3_PaymentProcessorTest extends CiviUnitTestCase {
  protected $_paymentProcessorType;
  protected $_apiversion;
  protected $_params;
  public $_eNoticeCompliant = TRUE;
  function get_info() {
    return array(
      'name' => 'PaymentProcessor Create',
      'description' => 'Test all PaymentProcessor Create API methods.',
      'group' => 'CiviCRM API Tests',
    );
  }

  function setUp() {
    parent::setUp();
    $this->_apiversion = 3;
    // Create dummy processor
    $params = array(
      'version' => $this->_apiversion,
      'name' => 'API_Test_PP_Type',
      'title' => 'API Test Payment Processor Type',
      'class_name' => 'CRM_Core_Payment_APITest',
      'billing_mode' => 'form',
      'is_recur' => 0,
    );
    $result = civicrm_api('payment_processor_type', 'create', $params);
    $this->_paymentProcessorType = $result['id'];
    $this->_params = array(
      'version' => $this->_apiversion,
      'name' => 'API Test PP',
      'payment_processor_type_id' => $this->_paymentProcessorType,
      'class_name' => 'CRM_Core_Payment_APITest',
      'is_recur' => 0,
      'domain_id' => 1,
    );
  }

  function tearDown() {

    $tablesToTruncate = array(
      'civicrm_payment_processor',
      'civicrm_payment_processor_type',
    );
    $this->quickCleanup($tablesToTruncate);
  }

  ///////////////// civicrm_payment_processor_add methods

  /**
   * check with no name
   */
  function testPaymentProcessorCreateWithoutName() {
    $payProcParams = array(
      'is_active' => 1,
      'version' => $this->_apiversion,
    );
    $result = $this->callAPIFailure('payment_processor', 'create', $payProcParams);
  }

  /**
   * create payment processor
   */
  function testPaymentProcessorCreate() {
    $params = $this->_params;
    $result = civicrm_api('payment_processor', 'create', $params);
    $this->assertAPISuccess($result);
    $this->documentMe($params, $result, __FUNCTION__, __FILE__);
    $this->assertNotNull($result['id'], 'in line ' . __LINE__);

    // mutate $params to match expected return value
    unset($params['version']);
    //assertDBState compares expected values in $result to actual values in the DB
    $this->assertDBState('CRM_Financial_DAO_PaymentProcessor', $result['id'], $params);
    return $result['id'];
  }

  /**
   * Test  using example code
   */
  function testPaymentProcessorCreateExample() {
    require_once 'api/v3/examples/PaymentProcessorCreate.php';
    $result = payment_processor_create_example();
    $expectedResult = payment_processor_create_expectedresult();
    $this->assertAPISuccess($result);
  }

  ///////////////// civicrm_payment_processor_delete methods

  /**
   * check payment processor type delete
   */
  function testPaymentProcessorDelete() {
    $id = $this->testPaymentProcessorCreate();
    // create sample payment processor type.
    $params = array(
      'id' => $id,
      'version' => $this->_apiversion,
    );

    $result = civicrm_api('payment_processor', 'delete', $params);
    $this->documentMe($params, $result, __FUNCTION__, __FILE__);
    $this->assertAPISuccess($result);
  }

  ///////////////// civicrm_payment_processors_get methods

  /**
   * check with valid params array.
   */
  function testPaymentProcessorsGet() {
    $params = $this->_params;
    $params['user_name'] = 'test@test.com';
    civicrm_api('payment_processor', 'create', $params);

    $params = array(
      'user_name' => 'test@test.com',
      'version' => $this->_apiversion,
    );
    $results = civicrm_api('payment_processor', 'get', $params);

    $this->assertEquals(0, $results['is_error'], ' in line ' . __LINE__);
    $this->assertEquals(1, $results['count'], ' in line ' . __LINE__);
    $this->assertEquals('test@test.com', $results['values'][$results['id']]['user_name'], ' in line ' . __LINE__);
  }
}

