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
