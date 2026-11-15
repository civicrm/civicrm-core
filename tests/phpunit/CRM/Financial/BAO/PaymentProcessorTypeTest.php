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
  protected $_apiversion = 4;

  /**
   * Check method create()
   */
  public function testCreate(): void {
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
    $this->assertSame('Test Payment Processor', $result, 'Verify Payment Processor Type');
  }

  /**
   * Check method retrieve()
   */
  public function testRetrieve(): void {
    $params = [
      'name' => 'Test_Retrieve_Payment_Processor',
      'title' => 'Test Retrieve Payment Processor',
      'billing_mode' => 1,
      'class_name' => 'Payment_Dummy',
    ];
    $defaults = [];
    CRM_Financial_BAO_PaymentProcessorType::create($params);
    $result = CRM_Financial_BAO_PaymentProcessorType::retrieve($params, $defaults);
    $this->assertSame('Test_Retrieve_Payment_Processor', $result->name, 'Verify Payment Processor Type');
  }

  /**
   * Check method setIsActive()
   */
  public function testSetIsActive(): void {
    $params = [
      'name' => 'Test_Set_Payment_Processor',
      'title' => 'Test Set Payment Processor',
      'billing_mode' => 1,
      'is_active' => 1,
      'class_name' => 'Payment_Dummy',
    ];

    $paymentProcessor = CRM_Financial_BAO_PaymentProcessorType::create($params);
    $result = CRM_Financial_BAO_PaymentProcessorType::setIsActive($paymentProcessor->id, 0);
    $this->assertTrue($result, 'Verify financial type record updation for is_active.');

    $isActive = $this->assertDBNotNull(
      'CRM_Financial_DAO_PaymentProcessorType',
      $paymentProcessor->id,
      'is_active',
      'id',
      'Database check on updated for payment processor type is_active.'
    );
    $this->assertSame('0', $isActive, 'Verify payment processor  types is_active.');
  }

  /**
   * Check method getDefault()
   */
  public function testGetDefault(): void {
    $params = ['is_default' => 1];
    $defaults = [];
    $result = CRM_Financial_BAO_PaymentProcessorType::retrieve($params, $defaults);

    $default = CRM_Financial_BAO_PaymentProcessorType::getDefault();
    $this->assertEquals($result->name, $default->name, 'Verify default payment processor.');
  }

  /**
   * Check method del()
   */
  public function testDel(): void {
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
    $this->assertTrue(empty($result), 'Verify financial types record deletion.');
  }

}
