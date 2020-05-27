<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 * Class contains api test cases for "civicrm_payment_processor_type"
 *
 * @group headless
 */
class api_v3_PaymentProcessorTypeTest extends CiviUnitTestCase {
  protected $_ppTypeID;

  public function setUp() {

    parent::setUp();
    $this->useTransaction(TRUE);
  }

  //  function tearDown() {
  //
  //    $tablesToTruncate = array(
  //      'civicrm_payment_processor_type',
  //    );
  //    $this->quickCleanup($tablesToTruncate);
  //  }

  ///////////////// civicrm_payment_processor_type_add methods

  /**
   * Check with no name.
   * @dataProvider versionThreeAndFour
   */
  public function testPaymentProcessorTypeCreateWithoutName($version) {
    $this->_apiversion = $version;
    $payProcParams = [
      'is_active' => 1,
    ];
    $result = $this->callAPIFailure('payment_processor_type', 'create', $payProcParams);
    $this->assertContains('name, title, class_name, billing_mode', $result['error_message']);
  }

  /**
   * Create payment processor type.
   * @dataProvider versionThreeAndFour
   */
  public function testPaymentProcessorTypeCreate($version) {
    $this->_apiversion = $version;
    $params = [
      'sequential' => 1,
      'name' => 'API_Test_PP',
      'title' => 'API Test Payment Processor',
      'class_name' => 'CRM_Core_Payment_APITest',
      'billing_mode' => 'form',
      'is_recur' => 0,
    ];
    $result = $this->callAPIAndDocument('payment_processor_type', 'create', $params, __FUNCTION__, __FILE__);
    $this->assertNotNull($result['values'][0]['id']);

    // mutate $params to match expected return value
    unset($params['sequential']);
    $params['billing_mode'] = CRM_Core_Payment::BILLING_MODE_FORM;
    //assertDBState compares expected values in $result to actual values in the DB
    $this->assertDBState('CRM_Financial_DAO_PaymentProcessorType', $result['id'], $params);
  }

  /**
   * Test  using example code.
   */
  public function testPaymentProcessorTypeCreateExample() {
    require_once 'api/v3/examples/PaymentProcessorType/Create.ex.php';
    $result = payment_processor_type_create_example();
    $expectedResult = payment_processor_type_create_expectedresult();
    $this->assertAPISuccess($result);
  }

  ///////////////// civicrm_payment_processor_type_delete methods

  /**
   * Check with empty array.
   * @dataProvider versionThreeAndFour
   */
  public function testPaymentProcessorTypeDeleteEmpty($version) {
    $this->_apiversion = $version;
    $params = [];
    $result = $this->callAPIFailure('payment_processor_type', 'delete', $params);
  }

  /**
   * Check if required fields are not passed.
   * @dataProvider versionThreeAndFour
   */
  public function testPaymentProcessorTypeDeleteWithoutRequired($version) {
    $this->_apiversion = $version;
    $params = [
      'name' => 'API_Test_PP',
      'title' => 'API Test Payment Processor',
      'class_name' => 'CRM_Core_Payment_APITest',
    ];

    $result = $this->callAPIFailure('payment_processor_type', 'delete', $params);
    $this->assertEquals(($version === 4 ? 'Parameter "where" is required.' : 'Mandatory key(s) missing from params array: id'), $result['error_message']);
  }

  /**
   * Check with incorrect required fields.
   * @dataProvider versionThreeAndFour
   */
  public function testPaymentProcessorTypeDeleteWithIncorrectData($version) {
    $this->_apiversion = $version;
    $result = $this->callAPIFailure('payment_processor_type', 'delete', ['id' => 'abcd']);
  }

  /**
   * Check payment processor type delete.
   * @dataProvider versionThreeAndFour
   */
  public function testPaymentProcessorTypeDelete($version) {
    $this->_apiversion = $version;
    $payProcType = $this->paymentProcessorTypeCreate();
    $params = [
      'id' => $payProcType,
    ];

    $result = $this->callAPIAndDocument('payment_processor_type', 'delete', $params, __FUNCTION__, __FILE__);
  }

  ///////////////// civicrm_payment_processor_type_update

  /**
   * Check with empty array.
   * @dataProvider versionThreeAndFour
   */
  public function testPaymentProcessorTypeUpdateEmpty($version) {
    $this->_apiversion = $version;
    $params = [];
    $result = $this->callAPIFailure('payment_processor_type', 'create', $params);
    $this->assertContains('name, title, class_name, billing_mode', $result['error_message']);
  }

  /**
   * Check with all parameters.
   * @dataProvider versionThreeAndFour
   */
  public function testPaymentProcessorTypeUpdate($version) {
    $this->_apiversion = $version;
    // create sample payment processor type.
    $this->_ppTypeID = $this->paymentProcessorTypeCreate(NULL);

    $params = [
      'id' => $this->_ppTypeID,
      // keep the same
      'name' => 'API_Test_PP',
      'title' => 'API Test Payment Processor 2',
      'class_name' => 'CRM_Core_Payment_APITest 2',
      'billing_mode' => 2,
      'is_recur' => 0,
    ];

    $result = $this->callAPISuccess('payment_processor_type', 'create', $params);
    $this->assertNotNull($result['id']);
    // assertDBState compares expected values in $result to actual values in the DB
    $this->assertDBState('CRM_Financial_DAO_PaymentProcessorType', $this->_ppTypeID, $params);
  }

  ///////////////// civicrm_payment_processor_types_get methods

  /**
   * Check with empty array.
   * @dataProvider versionThreeAndFour
   */
  public function testPaymentProcessorTypesGetEmptyParams($version) {
    $this->_apiversion = $version;
    $results = $this->callAPISuccess('payment_processor_type', 'get', []);
    $baselineCount = $results['count'];

    $firstRelTypeParams = [
      'name' => 'API_Test_PP',
      'title' => 'API Test Payment Processor',
      'class_name' => 'CRM_Core_Payment_APITest',
      'billing_mode' => 1,
      'is_recur' => 0,
    ];

    $first = $this->callAPISuccess('PaymentProcessorType', 'Create', $firstRelTypeParams);

    $secondRelTypeParams = [
      'name' => 'API_Test_PP2',
      'title' => 'API Test Payment Processor 2',
      'class_name' => 'CRM_Core_Payment_APITest 2',
      'billing_mode' => 2,
      'is_recur' => 0,
    ];
    $second = $this->callAPISuccess('PaymentProcessorType', 'Create', $secondRelTypeParams);
    $result = $this->callAPISuccess('payment_processor_type', 'get', []);

    $this->assertEquals($baselineCount + 2, $result['count']);
    $this->assertAPISuccess($result);
  }

  /**
   * Check with valid params array.
   * @dataProvider versionThreeAndFour
   */
  public function testPaymentProcessorTypesGet($version) {
    $this->_apiversion = $version;
    $firstRelTypeParams = [
      'name' => 'API_Test_PP_11',
      'title' => 'API Test Payment Processor 11',
      'class_name' => 'CRM_Core_Payment_APITest_11',
      'billing_mode' => 1,
      'is_recur' => 0,
    ];

    $first = $this->callAPISuccess('PaymentProcessorType', 'Create', $firstRelTypeParams);

    $secondRelTypeParams = [
      'name' => 'API_Test_PP_12',
      'title' => 'API Test Payment Processor 12',
      'class_name' => 'CRM_Core_Payment_APITest_12',
      'billing_mode' => 2,
      'is_recur' => 0,
    ];
    $second = $this->callAPISuccess('PaymentProcessorType', 'Create', $secondRelTypeParams);

    $params = [
      'name' => 'API_Test_PP_12',
    ];
    $result = $this->callAPISuccess('payment_processor_type', 'get', $params);

    $this->assertAPISuccess($result);
    $this->assertEquals(1, $result['count'], ' in line ' . __LINE__);
    $this->assertEquals('CRM_Core_Payment_APITest_12', $result['values'][$result['id']]['class_name'], ' in line ' . __LINE__);
  }

}
