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
 * Class CRM_Core_Payment_AuthorizeNetTest
 * @group headless
 */
trait CRM_Core_Payment_PaypalProTrait {
  use \Civi\Test\GuzzleTestTrait;

  /**
   * @var \CRM_Core_Payment_PayPalImpl
   */
  protected $processor;

  /**
   * Is this a recurring transaction.
   *
   * @var bool
   */
  protected $isRecur = FALSE;

  /**
   * Get the expected response from Paypal Pro for a single payment.
   *
   * @return array
   */
  public function getExpectedSinglePaymentResponses() {
    return [
      'placeholder',
    ];
  }

  /**
   *  Get the expected request from Authorize.net.
   *
   * @return array
   */
  public function getExpectedSinglePaymentRequests() {
    return [
      'placeholder',
    ];
  }

  /**
   *  Get the expected request from Authorize.net.
   *
   * @return array
   */
  public function getExpectedRecurResponses() {
    return [
      'placeholder',
    ];
  }

  /**
   * Add a mock handler to the paypal Pro processor for testing.
   *
   * @param int|null $id
   *
   * @throws \CiviCRM_API3_Exception
   */
  protected function setupMockHandler($id = NULL) {
    if ($id) {
      $this->processor = Civi\Payment\System::singleton()->getById($id);
    }
    $responses = $this->isRecur ? $this->getExpectedRecurResponses() : $this->getExpectedSinglePaymentResponses();
    // Comment the next line out when trying to capture the response.
    // see https://github.com/civicrm/civicrm-core/pull/18350
    //$this->createMockHandler($responses);
    $this->setUpClientWithHistoryContainer();
    $this->processor->setGuzzleClient($this->getGuzzleClient());
  }

  /**
   * Create an AuthorizeNet processors with a configured mock handler.
   *
   * @throws \CiviCRM_API3_Exception
   */
  protected function createPaypalProProcessor() {
    $processorID = $this->paymentProcessorCreate(['is_test' => 0]);
    $this->setupMockHandler($processorID);
    $this->ids['PaymentProcessor']['paypal_pro'] = $processorID;
  }

}
