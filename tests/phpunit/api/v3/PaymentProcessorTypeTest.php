<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * Class contains api test cases for "civicrm_payment_processor_type"
 *
 */
class api_v3_PaymentProcessorTypeTest extends CiviUnitTestCase {
  protected $_ppTypeID;
  protected $_apiversion;

  function get_info() {
    return array(
      'name' => 'PaymentProcessorType Create',
      'description' => 'Test all PaymentProcessorType Create API methods.',
      'group' => 'CiviCRM API Tests',
    );
  }

  function setUp() {

    parent::setUp();
    $this->_apiversion = 3;
  }

  function tearDown() {

    $tablesToTruncate = array(
      'civicrm_payment_processor_type',
    );
    $this->quickCleanup($tablesToTruncate);
  }

  ///////////////// civicrm_payment_processor_type_add methods

  /**
   * check with no name
   */
  function testPaymentProcessorTypeCreateWithoutName() {
    $payProcParams = array(
      'is_active' => 1,    );
    $result = $this->callAPIFailure('payment_processor_type', 'create', $payProcParams);
    $this->assertEquals($result['error_message'],
      'Mandatory key(s) missing from params array: name, title, class_name, billing_mode'
    );
  }

  /**
   * create payment processor type
   */
  function testPaymentProcessorTypeCreate() {
    $params = array(      'sequential' => 1,
      'name' => 'API_Test_PP',
      'title' => 'API Test Payment Processor',
      'class_name' => 'CRM_Core_Payment_APITest',
      'billing_mode' => 'form',
      'is_recur' => 0,
    );
    $result = $this->callAPIAndDocument('payment_processor_type', 'create', $params, __FUNCTION__, __FILE__);
    $this->assertNotNull($result['values'][0]['id'], 'in line ' . __LINE__);

    // mutate $params to match expected return value
    unset($params['sequential']);
    $params['billing_mode'] = CRM_Core_Payment::BILLING_MODE_FORM;
    //assertDBState compares expected values in $result to actual values in the DB
    $this->assertDBState('CRM_Financial_DAO_PaymentProcessorType', $result['id'], $params);
  }

  /**
   *  Test  using example code
   */
  function testPaymentProcessorTypeCreateExample() {
    require_once 'api/v3/examples/PaymentProcessorType/Create.php';
    $result = payment_processor_type_create_example();
    $expectedResult = payment_processor_type_create_expectedresult();
    $this->assertAPISuccess($result);
  }

  ///////////////// civicrm_payment_processor_type_delete methods

  /**
   * check with empty array
   */
  function testPaymentProcessorTypeDeleteEmpty() {
    $params = array();
    $result = $this->callAPIFailure('payment_processor_type', 'delete', $params);
  }

  /**
   * check with No array
   */
  function testPaymentProcessorTypeDeleteParamsNotArray() {
    $result = $this->callAPIFailure('payment_processor_type', 'delete', 'string');
  }

  /**
   * check if required fields are not passed
   */
  function testPaymentProcessorTypeDeleteWithoutRequired() {
    $params = array(
      'name' => 'API_Test_PP',
      'title' => 'API Test Payment Processor',
      'class_name' => 'CRM_Core_Payment_APITest',
    );

    $result = $this->callAPIFailure('payment_processor_type', 'delete', $params);
    $this->assertEquals($result['error_message'], 'Mandatory key(s) missing from params array: id');
  }

  /**
   * check with incorrect required fields
   */
  function testPaymentProcessorTypeDeleteWithIncorrectData() {
    $result = $this->callAPIFailure('payment_processor_type', 'delete', array('id' => 'abcd'));
  }

  /**
   * check payment processor type delete
   */
  function testPaymentProcessorTypeDelete() {
    $payProcType = $this->paymentProcessorTypeCreate();
    $params = array(
      'id' => $payProcType,
    );

    $result = $this->callAPIAndDocument('payment_processor_type', 'delete', $params, __FUNCTION__, __FILE__);
  }

  ///////////////// civicrm_payment_processor_type_update

  /**
   * check with empty array
   */
  function testPaymentProcessorTypeUpdateEmpty() {
    $params = array();
    $result = $this->callAPIFailure('payment_processor_type', 'create', $params);
    $this->assertEquals($result['error_message'], 'Mandatory key(s) missing from params array: name, title, class_name, billing_mode');
  }

  /**
   * check with No array
   */
  function testPaymentProcessorTypeUpdateParamsNotArray() {
    $result = $this->callAPIFailure('payment_processor_type', 'create', 'string');
    $this->assertEquals($result['error_message'], 'Input variable `params` is not an array');
  }

  /**
   * check with all parameters
   */
  function testPaymentProcessorTypeUpdate() {
    // create sample payment processor type.
    $this->_ppTypeID = $this->paymentProcessorTypeCreate(NULL);

    $params = array(
      'id' => $this->_ppTypeID,
      'name' => 'API_Test_PP', // keep the same
      'title' => 'API Test Payment Processor 2',
      'class_name' => 'CRM_Core_Payment_APITest 2',
      'billing_mode' => 2,
      'is_recur' => 0,    );

    $result = $this->callAPISuccess('payment_processor_type', 'create', $params);
    $this->assertNotNull($result['id']);
    // assertDBState compares expected values in $result to actual values in the DB
    $this->assertDBState('CRM_Financial_DAO_PaymentProcessorType', $this->_ppTypeID, $params);
  }

  ///////////////// civicrm_payment_processor_types_get methods

  /**
   * check with empty array
   */
  function testPaymentProcessorTypesGetEmptyParams() {
    $results = $this->callAPISuccess('payment_processor_type', 'get', array(    ));
    $baselineCount = $results['count'];

    $firstRelTypeParams = array(
      'name' => 'API_Test_PP',
      'title' => 'API Test Payment Processor',
      'class_name' => 'CRM_Core_Payment_APITest',
      'billing_mode' => 1,
      'is_recur' => 0,    );

    $first = $this->callAPISuccess('PaymentProcessorType', 'Create', $firstRelTypeParams);

    $secondRelTypeParams = array(
      'name' => 'API_Test_PP2',
      'title' => 'API Test Payment Processor 2',
      'class_name' => 'CRM_Core_Payment_APITest 2',
      'billing_mode' => 2,
      'is_recur' => 0,    );
    $second = $this->callAPISuccess('PaymentProcessorType', 'Create', $secondRelTypeParams);
    $result = $this->callAPISuccess('payment_processor_type', 'get', array(    ));

    $this->assertEquals($baselineCount + 2, $result['count']);
    $this->assertAPISuccess($result);
  }

  /**
   * check with valid params array.
   */
  function testPaymentProcessorTypesGet() {
    $firstRelTypeParams = array(
      'name' => 'API_Test_PP_11',
      'title' => 'API Test Payment Processor 11',
      'class_name' => 'CRM_Core_Payment_APITest_11',
      'billing_mode' => 1,
      'is_recur' => 0,    );

    $first = $this->callAPISuccess('PaymentProcessorType', 'Create', $firstRelTypeParams);

    $secondRelTypeParams = array(
      'name' => 'API_Test_PP_12',
      'title' => 'API Test Payment Processor 12',
      'class_name' => 'CRM_Core_Payment_APITest_12',
      'billing_mode' => 2,
      'is_recur' => 0,    );
    $second = $this->callAPISuccess('PaymentProcessorType', 'Create', $secondRelTypeParams);

    $params = array(
      'name' => 'API_Test_PP_12',    );
    $result = $this->callAPISuccess('payment_processor_type', 'get', $params);

    $this->assertAPISuccess($result);
    $this->assertEquals(1, $result['count'], ' in line ' . __LINE__);
    $this->assertEquals('CRM_Core_Payment_APITest_12', $result['values'][$result['id']]['class_name'], ' in line ' . __LINE__);
  }
}

