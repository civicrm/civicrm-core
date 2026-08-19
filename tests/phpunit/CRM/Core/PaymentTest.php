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

use Civi\Test\Invasive;

/**
 * Class CRM_Core_PaymentTest
 * @group headless
 */
class CRM_Core_PaymentTest extends CiviUnitTestCase {

  /**
   * Test the payment method is adequately logged - we don't expect the processing to succeed
   */
  public function testHandlePaymentMethodLogging(): void {
    $params = ['processor_name' => 'Paypal', 'data' => 'blah'];
    try {
      CRM_Core_Payment::handlePaymentMethod('method', $params);
    }
    catch (Exception $e) {

    }
    $log = $this->callAPISuccess('SystemLog', 'get', []);
    $this->assertEquals('payment_notification processor_name=Paypal', $log['values'][$log['id']]['message']);
  }

  /**
   * Test that CVV is always required for front facing pages.
   */
  public function testCVVSettingForContributionPages(): void {
    Civi::settings()->set('cvv_backoffice_required', 0);
    $processor = NULL;
    $dummyPayment = new CRM_Core_Payment_Dummy("test", $processor);
    $dummyPayment->setBackOffice(TRUE);
    $paymentMetaData = $dummyPayment->getPaymentFormFieldsMetadata();
    $this->assertEquals(0, $paymentMetaData["cvv2"]["is_required"], "CVV should be non required for back office.");

    $dummyPayment->setBackOffice(FALSE);
    $paymentMetaData = $dummyPayment->getPaymentFormFieldsMetadata();
    $this->assertEquals(1, $paymentMetaData["cvv2"]["is_required"], "CVV should always be required for front office.");

    Civi::settings()->set('cvv_backoffice_required', 1);

    $dummyPayment->setBackOffice(TRUE);
    $paymentMetaData = $dummyPayment->getPaymentFormFieldsMetadata();
    $this->assertEquals(1, $paymentMetaData["cvv2"]["is_required"], "CVV should be required for back office.");

    $dummyPayment->setBackOffice(FALSE);
    $paymentMetaData = $dummyPayment->getPaymentFormFieldsMetadata();
    $this->assertEquals(1, $paymentMetaData["cvv2"]["is_required"], "CVV should always be required for front office.");
  }

  /**
   * Test that back-office payment form fields are driven by the payment_instrument's
   * 'grouping' column, so a custom or renamed instrument can opt into the same fields
   * as the reserved Check or Credit Card instruments.
   */
  public function testGetPaymentFormFieldsByGrouping(): void {
    $checkInstrument = $this->createTestEntity('OptionValue', [
      'option_group_id:name' => 'payment_instrument',
      'name' => 'International Check',
      'label' => 'International Check',
      'value' => 90,
      'grouping' => json_encode(['check_number']),
    ], 'internationalCheck');

    $cardInstrument = $this->createTestEntity('OptionValue', [
      'option_group_id:name' => 'payment_instrument',
      'name' => 'Store Card',
      'label' => 'Store Card',
      'value' => 91,
      'grouping' => json_encode(['card_type_id', 'pan_truncation']),
    ], 'storeCard');

    $ungroupedInstrument = $this->createTestEntity('OptionValue', [
      'option_group_id:name' => 'payment_instrument',
      'name' => 'Money Order',
      'label' => 'Money Order',
      'value' => 92,
    ], 'moneyOrder');

    $manualPayment = new CRM_Core_Payment_Manual();
    $manualPayment->setBackOffice(TRUE);

    $manualPayment->setPaymentInstrumentID($checkInstrument['value']);
    $this->assertEquals(['check_number'], $manualPayment->getPaymentFormFields());

    // 'card_type_id' (the civicrm_financial_trxn column name stored in grouping) is translated
    // to 'credit_card_type' (the generic payment-processor field name) for this consumer.
    $manualPayment->setPaymentInstrumentID($cardInstrument['value']);
    $this->assertEquals(['credit_card_type', 'pan_truncation'], $manualPayment->getPaymentFormFields());

    $manualPayment->setPaymentInstrumentID($ungroupedInstrument['value']);
    $this->assertEquals([], $manualPayment->getPaymentFormFields());

    // The reserved instruments' grouping is set by core's seed data / upgrade step.
    $reservedCheckID = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'payment_instrument_id', 'Check');
    $manualPayment->setPaymentInstrumentID($reservedCheckID);
    $this->assertEquals(['check_number'], $manualPayment->getPaymentFormFields());

    $reservedCreditCardID = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'payment_instrument_id', 'Credit Card');
    $manualPayment->setPaymentInstrumentID($reservedCreditCardID);
    $this->assertEquals(['credit_card_type', 'pan_truncation'], $manualPayment->getPaymentFormFields());
  }

  public function testSettingUrl(): void {
    /** @var CRM_Core_Payment_Dummy $processor */
    $processor = \Civi\Payment\System::singleton()->getById($this->processorCreate());
    $success = 'http://success.com';
    $cancel = 'http://cancel.com';
    $processor->setCancelUrl($cancel);
    $processor->setSuccessUrl($success);

    $this->assertEquals($success, Invasive::call([$processor, 'getReturnSuccessUrl'], [NULL]));
    $this->assertEquals($cancel, Invasive::call([$processor, 'getReturnFailUrl'], [NULL]));
  }

  /**
   * A processor implementing Civi\Payment\PaymentProcessorWebhookInterface must be
   * dispatched to on the strength of that interface alone, not just method_exists().
   */
  public function testHandlePaymentMethodDispatchesToWebhookInterface(): void {
    CRM_Core_Payment_WebhookInterfaceStubForTest::$notified = FALSE;
    $this->paymentProcessorTypeCreate([
      'name' => 'WebhookInterfaceStubForTest',
      'title' => 'Webhook Interface Stub',
      'class_name' => 'Payment_WebhookInterfaceStubForTest',
      'is_recur' => 0,
    ]);
    $processorID = $this->processorCreate([
      'payment_processor_type_id:name' => 'WebhookInterfaceStubForTest',
      'name' => 'WebhookInterfaceStubForTest',
    ]);

    CRM_Core_Payment::handlePaymentMethod('PaymentNotification', ['processor_id' => $processorID]);

    $this->assertTrue(CRM_Core_Payment_WebhookInterfaceStubForTest::$notified, 'Processor implementing PaymentProcessorWebhookInterface should have had handlePaymentNotification() dispatched to it.');
  }

}

/**
 * Test double confirming that handlePaymentMethod() dispatches to a processor
 * purely because it implements PaymentProcessorWebhookInterface, without also
 * needing method_exists()/is_callable() to independently confirm it.
 */
class CRM_Core_Payment_WebhookInterfaceStubForTest extends CRM_Core_Payment_Dummy implements \Civi\Payment\PaymentProcessorWebhookInterface {

  public static $notified = FALSE;

  public function handlePaymentNotification(): void {
    self::$notified = TRUE;
  }

  public function processWebhookEvent(array $webhookEvent): bool {
    return TRUE;
  }

}
