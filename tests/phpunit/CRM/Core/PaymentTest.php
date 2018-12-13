<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * Class CRM_Core_PaymentTest
 * @group headless
 */
class CRM_Core_PaymentTest extends CiviUnitTestCase {

  /**
   * Test the payment method is adequately logged - we don't expect the processing to succeed
   */
  public function testHandlePaymentMethodLogging() {
    $params = array('processor_name' => 'Paypal', 'data' => 'blah');
    try {
      CRM_Core_Payment::handlePaymentMethod('method', $params);
    }
    catch (Exception $e) {

    }
    $log = $this->callAPISuccess('SystemLog', 'get', array());
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
