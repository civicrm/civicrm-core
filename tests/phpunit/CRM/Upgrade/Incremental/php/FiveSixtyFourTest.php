<?php

use Civi\Api4\PaymentProcessor;

/**
 * Class CRM_Upgrade_Incremental_php_FiveSixtyFour
 * @group headless
 */
class CRM_Upgrade_Incremental_php_FiveSixtyFourTest extends CiviUnitTestCase {

  use CRM_Core_Payment_AuthorizeNetTrait;

  /**
   * Test that fixing the double json encode works as expected
   */
  public function testFixDobuleJsonEncode() {
    $this->createAuthorizeNetProcessor();
    $this->paymentProcessorAuthorizeNetCreate();
    $creditCards = [
      'visa' => 'visa',
      'mastercard' => 'mastercard',
    ];
    PaymentProcessor::update()->addValue('accepted_credit_cards', $creditCards)->addWhere('id', '=', $this->ids['PaymentProcessor']['anet'])->execute();
    PaymentProcessor::update()->addValue('accepted_credit_cards', json_encode($creditCards))->addWhere('id', '=', $this->ids['PaymentProcessor']['authorize_net'])->execute();
    CRM_Upgrade_Incremental_php_FiveSixtyFour::fixDoubleEscapingPaymentProcessorCreditCards();
    $paymentProcessors = PaymentProcessor::get()->execute();
    foreach ($paymentProcessors as $paymentProcessor) {
      $this->assertEquals($creditCards, $paymentProcessor['accepted_credit_cards']);
    }
  }

}
