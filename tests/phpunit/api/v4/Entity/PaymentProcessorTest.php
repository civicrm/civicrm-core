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
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */


namespace api\v4\Entity;

use Civi\Api4\PaymentProcessor;

/**
 * @group headless
 */
class PaymentProcessorTest extends \CiviUnitTestCase {

  public function testGetFields(): void {
    $fields = PaymentProcessor::getFields(FALSE)
      ->execute()->indexBy('name');

    $this->assertFalse($fields['title']['required']);
    $this->assertSame('empty($values.frontend_title) && empty($values.name)', $fields['title']['required_if']);

    $this->assertFalse($fields['frontend_title']['required']);
    $this->assertSame('empty($values.title) && empty($values.name)', $fields['frontend_title']['required_if']);
  }

  /**
   * Test Refund action.
   */
  public function testRefund(): void {
    $dummyProcessor = $this->dummyProcessorCreate();
    $paymentProcessorID = $dummyProcessor->getID();

    // Test successful refund
    $result = PaymentProcessor::refund(FALSE)
      ->setPaymentProcessorID($paymentProcessorID)
      ->setAmountToRefund(10.00)
      ->setCurrency('USD')
      ->setTransactionID('xyz123')
      ->execute();
    $this->assertInstanceOf(\Civi\Api4\Generic\Result::class, $result);

    // Test when refund is not supported by payment processor
    $dummyProcessor->setSupports(['Refund' => FALSE]);
    try {
      PaymentProcessor::refund(FALSE)
        ->setPaymentProcessorID($paymentProcessorID)
        ->setAmountToRefund(10.00)
        ->setTransactionID('xyz123')
        ->execute();
      $this->fail('Expected CRM_Core_Exception because processor does not support refund');
    }
    catch (\CRM_Core_Exception $e) {
      $this->assertEquals('Payment Processor does not support refund', $e->getMessage());
    }
  }

}
