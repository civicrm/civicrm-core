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
 * Class CRM_Financial_BAO_PaymentProcessorTypeTest
 * @group headless
 */
class CRM_Financial_BAO_PaymentProcessorTypeTest extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();
  }

  /**
   * Check method create()
   */
  public function testCreate() {
    $params = [
      'name' => 'Test_Payment_Processor',
      'title' => 'Test Payment Processor',
      'billing_mode' => 1,
      'class_name' => 'Payment_Dummy',
    ];
    $paymentProcessor = CRM_Financial_BAO_PaymentProcessorType::create($params);
    $result = $this->assertDBNotNull(
      'CRM_Financial_DAO_PaymentProcessorType',
      $paymentProcessor->name,
      'title',
      'name',
      'Database check on added payment processor type record.'
    );
    $this->assertEquals($result, 'Test Payment Processor', 'Verify Payment Processor Type');
  }

  /**
   * Check method retrieve()
   */
  public function testRetrieve() {
    $params = [
      'name' => 'Test_Retrieve_Payment_Processor',
      'title' => 'Test Retrieve Payment Processor',
      'billing_mode' => 1,
      'class_name' => 'Payment_Dummy',
    ];
    $defaults = [];
    CRM_Financial_BAO_PaymentProcessorType::create($params);
    $result = CRM_Financial_BAO_PaymentProcessorType::retrieve($params, $defaults);
    $this->assertEquals($result->name, 'Test_Retrieve_Payment_Processor', 'Verify Payment Processor Type');
  }

  /**
   * Check method setIsActive()
   */
  public function testSetIsActive() {
    $params = [
      'name' => 'Test_Set_Payment_Processor',
      'title' => 'Test Set Payment Processor',
      'billing_mode' => 1,
      'is_active' => 1,
      'class_name' => 'Payment_Dummy',
    ];

    $paymentProcessor = CRM_Financial_BAO_PaymentProcessorType::create($params);
    $result = CRM_Financial_BAO_PaymentProcessorType::setIsActive($paymentProcessor->id, 0);
    $this->assertEquals($result, TRUE, 'Verify financial type record updation for is_active.');

    $isActive = $this->assertDBNotNull(
      'CRM_Financial_DAO_PaymentProcessorType',
      $paymentProcessor->id,
      'is_active',
      'id',
      'Database check on updated for payment processor type is_active.'
    );
    $this->assertEquals($isActive, 0, 'Verify payment processor  types is_active.');
  }

  /**
   * Check method getDefault()
   */
  public function testGetDefault() {
    $params = ['is_default' => 1];
    $defaults = [];
    $result = CRM_Financial_BAO_PaymentProcessorType::retrieve($params, $defaults);

    $default = CRM_Financial_BAO_PaymentProcessorType::getDefault();
    $this->assertEquals($result->name, $default->name, 'Verify default payment processor.');
  }

  /**
   * Check method del()
   */
  public function testDel() {
    $params = [
      'name' => 'Test_Del_Payment_Processor',
      'title' => 'Test Del Payment Processor',
      'billing_mode' => 1,
      'is_active' => 1,
      'class_name' => 'Payment_Dummy',
    ];

    $defaults = [];
    $paymentProcessor = CRM_Financial_BAO_PaymentProcessorType::create($params);
    CRM_Financial_BAO_PaymentProcessorType::del($paymentProcessor->id);

    $params = ['id' => $paymentProcessor->id];
    $result = CRM_Financial_BAO_PaymentProcessorType::retrieve($params, $defaults);
    $this->assertEquals(empty($result), TRUE, 'Verify financial types record deletion.');
  }

}
