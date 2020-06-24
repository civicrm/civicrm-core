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
 * Class CRM_Core_PaymentTest
 * @group headless
 */
class CRM_Core_PaymentTest extends CiviUnitTestCase {

  /**
   * Test the payment method is adequately logged - we don't expect the processing to succeed
   */
  public function testHandlePaymentMethodLogging() {
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
  public function testCVVSettingForContributionPages() {
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

  public function testSettingUrl() {
    /** @var CRM_Core_Payment_Dummy $processor */
    $processor = \Civi\Payment\System::singleton()->getById($this->processorCreate());
    $success = 'http://success.com';
    $cancel = 'http://cancel.com';
    $processor->setCancelUrl($cancel);
    $processor->setSuccessUrl($success);

    // Using ReflectionUtils to access protected methods
    $successGetter = new ReflectionMethod($processor, 'getReturnSuccessUrl');
    $successGetter->setAccessible(TRUE);
    $this->assertEquals($success, $successGetter->invoke($processor, NULL));

    $cancelGetter = new ReflectionMethod($processor, 'getReturnFailUrl');
    $cancelGetter->setAccessible(TRUE);
    $this->assertEquals($cancel, $cancelGetter->invoke($processor, NULL));
  }

}
