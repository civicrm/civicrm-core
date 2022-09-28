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

use Civi\Api4\PaymentProcessor;

/**
 * Class CRM_Financial_BAO_PaymentProcessorTypeTest
 * @group headless
 */
class CRM_Financial_BAO_PaymentProcessorTest extends CiviUnitTestCase {

  /**
   * Check method create()
   */
  public function testGetCreditCards() {
    $params = [
      'name' => 'API_Test_PP_Type',
      'title' => 'API Test Payment Processor Type',
      'class_name' => 'CRM_Core_Payment_APITest',
      'billing_mode' => 'form',
      'payment_processor_type_id' => 1,
      'is_recur' => 0,
      'domain_id' => 1,
      'accepted_credit_cards' => json_encode([
        'Visa' => 'Visa',
        'Mastercard' => 'Mastercard',
        'Amex' => 'Amex',
      ]),
    ];
    $paymentProcessor = CRM_Financial_BAO_PaymentProcessor::create($params);
    $expectedCards = [
      'Visa' => 'Visa',
      'Mastercard' => 'Mastercard',
      'Amex' => 'Amex',
    ];
    $cards = CRM_Financial_BAO_PaymentProcessor::getCreditCards($paymentProcessor->id);
    $this->assertEquals($cards, $expectedCards, 'Verify correct credit card types are returned');
  }

  /**
   * Test the processor retrieval function.
   *
   * @throws \API_Exception
   * @throws \CiviCRM_API3_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function testGetProcessors() {
    $testProcessor = $this->dummyProcessorCreate();
    $testProcessorID = $testProcessor->getID();
    $liveProcessorID = $testProcessorID + 1;

    $processors = CRM_Financial_BAO_PaymentProcessor::getPaymentProcessors(['BackOffice', 'TestMode']);
    $this->assertEquals([$testProcessorID, 0], array_keys($processors), 'Only the test processor and the manual processor should be returned');

    $processors = CRM_Financial_BAO_PaymentProcessor::getPaymentProcessors(['BackOffice', 'TestMode'], [$liveProcessorID]);
    $this->assertEquals([$testProcessorID], array_keys($processors), 'Only the test processor should be returned');

    $processors = CRM_Financial_BAO_PaymentProcessor::getPaymentProcessors(['BackOffice', 'TestMode'], [$testProcessorID]);
    $this->assertEquals([$testProcessorID], array_keys($processors), 'Only the test processor should be returned');

    $processors = CRM_Financial_BAO_PaymentProcessor::getPaymentProcessors(['BackOffice', 'LiveMode']);
    $this->assertEquals([$liveProcessorID, 0], array_keys($processors), 'Only the Live processor and the manual processor should be returned');

    $processors = CRM_Financial_BAO_PaymentProcessor::getPaymentProcessors(['BackOffice', 'LiveMode'], [$liveProcessorID]);
    $this->assertEquals([$liveProcessorID], array_keys($processors), 'Only the Live processor should be returned');

    PaymentProcessor::update()->addWhere('id', 'IS NOT NULL')->setValues(['domain_id' => 2])->execute();
    $processors = CRM_Financial_BAO_PaymentProcessor::getPaymentProcessors(['BackOffice', 'LiveMode'], [$liveProcessorID]);
    $this->assertEquals([$liveProcessorID], array_keys($processors), 'Live processor should still be returned even though it is on a different domain');

    // The api won't permit disabling only live mode due to lack of integrity so use direct SQL
    CRM_Core_DAO::executeQuery('UPDATE civicrm_payment_processor SET is_active = 0 WHERE is_test = 0');
    Civi\Payment\System::singleton()->flushProcessors();
    $processors = CRM_Financial_BAO_PaymentProcessor::getPaymentProcessors(['BackOffice', 'LiveMode'], [$liveProcessorID]);
    $this->assertEquals([], array_keys($processors), 'Live processor should not be returned as it is inactive');

    CRM_Core_DAO::executeQuery('UPDATE civicrm_payment_processor SET is_active = 0 WHERE is_test = 0');
    $processors = CRM_Financial_BAO_PaymentProcessor::getPaymentProcessors(['BackOffice', 'TestMode'], [$testProcessorID]);
    $this->assertEquals([$testProcessorID], array_keys($processors), 'The test processor should still be returned');

  }

  /**
   * Test the Manual processor supports 'NoEmailProvided'
   */
  public function testManualProcessorSupportsNoEmailProvided() {
    $processors = CRM_Financial_BAO_PaymentProcessor::getPaymentProcessors(['NoEmailProvided']);
    $found = FALSE;
    foreach ($processors as $processor) {
      if ($processor['class_name'] === 'Payment_Manual') {
        $found = TRUE;
        continue;
      }
    }
    $this->assertTrue($found, 'The Manual payment processor should support "NoEmailProvided"');
  }

}
