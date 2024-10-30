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
  public function testFixDobuleJsonEncode(): void {
    $this->createAuthorizeNetProcessor();
    $this->paymentProcessorAuthorizeNetCreate();
    $creditCards = [
      'visa' => 'visa',
      'mastercard' => 'mastercard',
    ];
    PaymentProcessor::update()->addValue('accepted_credit_cards', $creditCards)->addWhere('id', '=', $this->ids['PaymentProcessor']['anet'])->execute();
    PaymentProcessor::update()->addValue('accepted_credit_cards', json_encode($creditCards))->addWhere('id', '=', $this->ids['PaymentProcessor']['authorize_net'])->execute();
    $dummyPaymentProcessor = $this->processorCreate();
    PaymentProcessor::update()->addValue('accepted_credit_cards', NULL)->addWhere('id', '=', $dummyPaymentProcessor)->execute();
    CRM_Upgrade_Incremental_php_FiveSixtyFour::fixDoubleEscapingPaymentProcessorCreditCards();
    $paymentProcessors = PaymentProcessor::get()->addWhere('id', 'IN', [$this->ids['PaymentProcessor']['anet'], $this->ids['PaymentProcessor']['authorize_net'], $dummyPaymentProcessor])->execute();
    foreach ($paymentProcessors as $paymentProcessor) {
      if (!empty($paymentProcessor['accepted_credit_cards'])) {
        $this->assertEquals($creditCards, $paymentProcessor['accepted_credit_cards']);
      }
    }
  }

}
