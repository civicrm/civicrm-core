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

  public function testGetReturnSuccessUrlWithId(): void {
    /** @var CRM_Core_Payment_Dummy $processor */
    $processor = \Civi\Payment\System::singleton()->getById($this->processorCreate(['name' => 'DummySuccessUrl']));
    Invasive::set([$processor, '_component'], 'event');
    $url = Invasive::call([$processor, 'getReturnSuccessUrl'], ['test_qf_key', NULL, 123]);
    $this->assertStringContainsString('_qf_ThankYou_display=1', $url);
    $this->assertStringContainsString('qfKey=test_qf_key', $url);
    $this->assertStringContainsString('id=123', $url);

    Invasive::set([$processor, '_component'], 'contribute');
    $urlContrib = Invasive::call([$processor, 'getReturnSuccessUrl'], ['test_qf_key_2', NULL, 456]);
    $this->assertStringContainsString('_qf_ThankYou_display=1', $urlContrib);
    $this->assertStringContainsString('qfKey=test_qf_key_2', $urlContrib);
    $this->assertStringContainsString('id=456', $urlContrib);
  }

  /**
   * For an event, the id can be resolved from the participant.
   */
  public function testGetReturnSuccessUrlResolvesEventFromParticipant(): void {
    $eventID = $this->createTestEntity('Event', [
      'title' => 'Event with a payment url',
      'event_type_id:name' => 'Conference',
      'start_date' => '2026-01-01',
    ])['id'];
    $contactID = $this->createTestEntity('Contact', [
      'contact_type' => 'Individual',
      'last_name' => 'Payment',
    ])['id'];
    $participantID = $this->createTestEntity('Participant', [
      'contact_id' => $contactID,
      'event_id' => $eventID,
    ])['id'];
    /** @var CRM_Core_Payment_Dummy $processor */
    $processor = \Civi\Payment\System::singleton()->getById($this->processorCreate(['name' => 'DummyParticipantUrl']));
    Invasive::set([$processor, '_component'], 'event');

    $url = Invasive::call([$processor, 'getReturnSuccessUrl'], ['test_qf_key', $participantID]);

    $this->assertStringContainsString('id=' . $eventID, $url);
  }

  /**
   * A participant that no longer exists leaves the id off the url, rather than
   * throwing on the way back from the payment processor.
   */
  public function testGetReturnSuccessUrlWithMissingParticipant(): void {
    /** @var CRM_Core_Payment_Dummy $processor */
    $processor = \Civi\Payment\System::singleton()->getById($this->processorCreate(['name' => 'DummyMissingParticipant']));
    Invasive::set([$processor, '_component'], 'event');

    $url = Invasive::call([$processor, 'getReturnSuccessUrl'], ['test_qf_key', 999999999]);

    $this->assertStringContainsString('qfKey=test_qf_key', $url);
    $this->assertStringNotContainsString('&id=', $url);
  }

  /**
   * The cancel url resolves the event the same way, and has the same problem
   * when the participant is gone.
   */
  public function testGetCancelUrlWithMissingParticipant(): void {
    /** @var CRM_Core_Payment_Dummy $processor */
    $processor = \Civi\Payment\System::singleton()->getById($this->processorCreate(['name' => 'DummyMissingCancel']));
    Invasive::set([$processor, '_component'], 'event');

    $url = $processor->getCancelUrl('test_qf_key', 999999999);

    $this->assertStringContainsString('cc=fail', $url);
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

}
