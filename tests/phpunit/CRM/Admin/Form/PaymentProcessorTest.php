<?php

use Civi\APi4\PaymentProcessor;
use Civi\Test\FormWrapper;

/**
 * @group headless
 */
class CRM_Admin_Form_PaymentProcessorTest extends CiviUnitTestCase {

  use CRM_Core_Payment_AuthorizeNetTrait;

  /**
   * Test that saving accept credit card field doesn't double json encode.
   *
   * @throws \CRM_Core_Exception
   */
  public function testUpdateAcceptCreditCard(): void {
    $this->createAuthorizeNetProcessor();
    $this->paymentProcessorAuthorizeNetCreate();
    $processorID = $this->ids['PaymentProcessor']['anet'];
    $form = $this->getTestForm('CRM_Admin_Form_PaymentProcessor', [], [
      'id' => $processorID,
      'action' => 'update',
    ]);
    $form->processForm(FormWrapper::PREPROCESSED);
    $paymentProcessor = $this->callAPISuccess('PaymentProcessor', 'getSingle', ['id' => $processorID]);
    $form->updatePaymentProcessor(array_merge($paymentProcessor, [
      'accept_credit_cards' => [
        'Visa' => 1,
        'MasterCard' => 1,
      ],
      'financial_account_id' => CRM_Financial_BAO_PaymentProcessor::getDefaultFinancialAccountID(),
    ]), 1, 0);
    $this->assertEquals([
      'Visa' => 'Visa',
      'MasterCard' => 'MasterCard',
    ], PaymentProcessor::get()->addWhere('id', '=', $processorID)->execute()->first()['accepted_credit_cards']);
  }

}
