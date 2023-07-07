<?php

use Civi\APi4\PaymentProcessor;

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
    $_REQUEST['id'] = $this->ids['PaymentProcessor']['anet'];
    $form = new CRM_Admin_Form_PaymentProcessor(NULL, CRM_Core_Action::UPDATE);
    $form->controller = new CRM_Core_Controller();
    $pageName = $form->getName();
    $form->controller->setStateMachine(new CRM_Core_StateMachine($form->controller));
    $form->preProcess();
    $paymentProcessor = $this->callAPISuccess('PaymentProcessor', 'getSingle', ['id' => $form->_id]);
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
    ], PaymentProcessor::get()->addWhere('id', '=', $form->_id)->execute()->first()['accepted_credit_cards']);
  }

}
