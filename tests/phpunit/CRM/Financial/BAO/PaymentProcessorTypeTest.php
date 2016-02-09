<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
    $params = array(
      'name' => 'Test_Payment_Processor',
      'title' => 'Test Payment Processor',
      'billing_mode' => 1,
    );
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
    $params = array(
      'name' => 'Test_Retrieve_Payment_Processor',
      'title' => 'Test Retrieve Payment Processor',
      'billing_mode' => 1,
    );
    $defaults = array();
    CRM_Financial_BAO_PaymentProcessorType::create($params);
    $result = CRM_Financial_BAO_PaymentProcessorType::retrieve($params, $defaults);
    $this->assertEquals($result->name, 'Test_Retrieve_Payment_Processor', 'Verify Payment Processor Type');
  }

  /**
   * Check method setIsActive()
   */
  public function testSetIsActive() {
    $params = array(
      'name' => 'Test_Set_Payment_Processor',
      'title' => 'Test Set Payment Processor',
      'billing_mode' => 1,
      'is_active' => 1,
    );

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
    $params = array('is_default' => 1);
    $defaults = array();
    $result = CRM_Financial_BAO_PaymentProcessorType::retrieve($params, $defaults);

    $default = CRM_Financial_BAO_PaymentProcessorType::getDefault();
    $this->assertEquals($result->name, $default->name, 'Verify default payment processor.');
  }

  /**
   * Check method del()
   */
  public function testDel() {
    $params = array(
      'name' => 'Test_Del_Payment_Processor',
      'title' => 'Test Del Payment Processor',
      'billing_mode' => 1,
      'is_active' => 1,
    );

    $defaults = array();
    $paymentProcessor = CRM_Financial_BAO_PaymentProcessorType::create($params);
    CRM_Financial_BAO_PaymentProcessorType::del($paymentProcessor->id);

    $params = array('id' => $paymentProcessor->id);
    $result = CRM_Financial_BAO_PaymentProcessorType::retrieve($params, $defaults);
    $this->assertEquals(empty($result), TRUE, 'Verify financial types record deletion.');
  }

}
