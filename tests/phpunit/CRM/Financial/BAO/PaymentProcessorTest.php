<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2017                                |
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
class CRM_Financial_BAO_PaymentProcessorTest extends CiviUnitTestCase {
  public function setUp() {
    parent::setUp();
  }

  /**
   * Check method create()
   */
  public function testGetCreditCards() {
    $params = array(
      'name' => 'API_Test_PP_Type',
      'title' => 'API Test Payment Processor Type',
      'class_name' => 'CRM_Core_Payment_APITest',
      'billing_mode' => 'form',
      'payment_processor_type_id' => 1,
      'is_recur' => 0,
      'domain_id' => 1,
      'accepted_credit_cards' => json_encode(array(
        'Visa' => 'Visa',
        'Mastercard' => 'Mastercard',
        'Amex' => 'American Express',
      )),
    );
    $paymentProcessor = CRM_Financial_BAO_PaymentProcessor::create($params);
    $expectedCards = array(
      'Visa' => 'Visa',
      'Mastercard' => 'Mastercard',
      'Amex' => 'American Express',
    );
    $cards = CRM_Financial_BAO_PaymentProcessor::getCreditCards($paymentProcessor->id);
    $this->assertEquals($cards, $expectedCards, 'Verify correct credit card types are returned');
  }

  public function testCreditCardType() {
    $params = array(
      'name' => 'API_Test_PP_Type',
      'title' => 'API Test Payment Processor Type',
      'class_name' => 'CRM_Core_Payment_APITest',
      'billing_mode' => 'form',
      'payment_processor_type_id' => 1,
      'is_recur' => 0,
      'domain_id' => 1,
      'accepted_credit_cards' => json_encode(array(
        'Visa' => 'Visa',
        'Mastercard' => 'Mastercard',
        'Amex' => 'American Express',
      )),
    );
    $paymentProcessor = CRM_Financial_BAO_PaymentProcessor::create($params);

    // Check what credit card types are available for the Test Payment Processor
    $cards = CRM_Financial_BAO_PaymentProcessor::getCreditCards($paymentProcessor->id);
    $Cards = CRM_Contribute_PseudoConstant::creditCard($cards);
    $expectedCards = array(
      'Visa' => 'Visa',
      'Mastercard' => 'Mastercard',
      'Amex' => 'American Express',
    );
    $this->assertEquals($Cards, $expectedCards, 'Verify correct credit card types are returned');

    // Check what credit card types are available with no payment processor specified - the default ones
    $Cards2 = CRM_Contribute_PseudoConstant::creditCard(array());
    $allCards = array(
      'Visa' => 'Visa',
      'Mastercard' => 'Mastercard',
      'Amex' => 'American Express',
      'Discover' => 'Discover', // Note: Discover is a default credit card type available, not assigned to a processor
    );
    $this->assertEquals($Cards2, $allCards, 'Verify correct credit card types are returned');
  }

}
