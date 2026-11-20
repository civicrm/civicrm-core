<?php

use Civi\Api4\PaymentProcessor;
use Civi\Payment\Exception\PaymentProcessorException;
use Civi\Payment\System;
use Civi\Test;
use Civi\Test\GuzzleTestTrait;
use Civi\Test\LookupHelperTrait;
use Civi\Test\HeadlessInterface;
use Civi\Core\HookInterface;
use PHPUnit\Framework\TestCase;
use Civi\Test\EntityTrait;

/**
 * eWAY processor test.
 *
 * Tips:
 *  - With HookInterface, you may implement CiviCRM hooks directly in the test class.
 *    Simply create corresponding functions (e.g. "hook_civicrm_post(...)" or similar).
 *  - With TransactionalInterface, any data changes made by setUp() or test****() functions will
 *    rollback automatically -- as long as you don't manipulate schema or truncate tables.
 *    If this test needs to manipulate schema or truncate tables, then either:
 *       a. Do all that using setupHeadless() and Civi\Test.
 *       b. Disable TransactionalInterface, and handle all setup/teardown yourself.
 *
 * @group headless
 */
class CRM_Core_Payment_EwayTest extends TestCase implements HeadlessInterface, HookInterface {

  use GuzzleTestTrait;
  use LookupHelperTrait;
  use EntityTrait;

  /**
   * @var CRM_Core_Payment_eWAY
   */
  protected CRM_Core_Payment $processor;

  /**
   * @throws \CRM_Extension_Exception_ParseException
   */
  public function setUpHeadless(): Test\CiviEnvBuilder {
    // Civi\Test has many helpers, like install(), uninstall(), sql(), and sqlFile().
    // See: https://docs.civicrm.org/dev/en/latest/testing/phpunit/#civitest
    return Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public function setUp(): void {
    $this->setUpEwayProcessor();
    $this->processor = System::singleton()->getById($this->ids['PaymentProcessor']['Eway']);
    parent::setUp();
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public function tearDown(): void {
    if (!empty($this->ids['PaymentProcessor']['Eway'])) {
      PaymentProcessor::delete(FALSE)
        ->addWhere('id', '=', $this->ids['PaymentProcessor']['Eway'])
        ->execute();
    }
    parent::tearDown();
  }

  /**
   * Test making a once off payment
   *
   * @throws \CRM_Core_Exception
   */
  public function testSinglePayment(): void {
    $this->setupMockHandler();
    $params = $this->getPaymentParams();
    $params['amount'] = 10.00;
    $this->processor->doPayment($params);
    $this->assertEquals($this->getExpectedSinglePaymentRequests(), $this->getRequestBodies());
  }

  /**
   * Test making a failed once off payment
   *
   * @throws \CRM_Core_Exception
   */
  public function testErrorSinglePayment(): void {
    $this->setupMockHandler(NULL, TRUE);
    $params = $this->getPaymentParams();
    $params['amount'] = 5.24;
    try {
      $this->processor->doPayment($params);
      $this->fail('Test was meant to throw an exception');
    }
    catch (PaymentProcessorException $e) {
      $this->assertSame('Error: [24] - Do Not Honour(Test Gateway).', $e->getMessage());
      $this->assertSame(9008, $e->getErrorCode());
    }
  }

  /**
   * Get the payment parameters, keyed as the processor reads them.
   *
   * @return array
   */
  protected function getPaymentParams(): array {
    return [
      'first_name' => 'John',
      'middle_name' => '',
      'last_name' => "O'Connor",
      'street_address' => '8 Hobbitton Road',
      'city' => 'The Shire',
      'state_province_id' => 1012,
      'state_province' => 'NSW',
      'postal_code' => 5010,
      'country_id' => 1228,
      'country' => 'AU',
      'email' => 'unittesteway@civicrm.org',
      'ip_address' => '127.0.0.1',
      'currency' => 'AUD',
      'description' => 'Test Contribution',
      'invoiceID' => 'xyz',
      'credit_card_number' => '4444333322221111',
      'cvv2' => '123',
      'credit_card_exp_date' => [
        'M' => 9,
        'Y' => 2025,
      ],
      'credit_card_type' => 'Visa',
      'year' => 2022,
      'month' => 10,
    ];
  }

  /**
   * @throws \CRM_Core_Exception
   */
  private function setUpEwayProcessor(): void {
    $this->createTestEntity('PaymentProcessor', [
      'name' => 'demo',
      'title' => 'demo',
      'domain_id' => CRM_Core_Config::domainID(),
      'payment_processor_type_id:name' => 'eWAY',
      'is_active' => 1,
      'is_default' => 0,
      'is_test' => 0,
      'user_name' => '87654321',
      'url_site' => 'https://www.eway.com.au/gateway/xmltest/testpage.asp',
      'class_name' => 'Payment_eWAY',
      'billing_mode' => 1,
      'financial_account_id' => $this->getFinancialAccountID('Payment Processor Account'),
      // Credit card = 1 so can pass 'by accident'.
      'payment_instrument_id:name' => 'Debit Card',
    ], 'Eway');
    $this->setupMockHandler($this->ids['PaymentProcessor']['Eway']);
  }

  /**
   * Add a mock handler to the paypal Pro processor for testing.
   *
   * @param int|null $id
   * @param bool $error
   *
   * @throws \CRM_Core_Exception
   */
  protected function setupMockHandler($id = NULL, $error = FALSE): void {
    if ($id) {
      $this->processor = System::singleton()->getById($id);
    }
    $responses = $error ? $this->getExpectedSinglePaymentErrorResponses() : $this->getExpectedSinglePaymentResponses();
    // Comment the next line out when trying to capture the response.
    // see https://github.com/civicrm/civicrm-core/pull/18350
    $this->createMockHandler($responses);
    $this->setUpClientWithHistoryContainer();
    $this->processor->setGuzzleClient($this->getGuzzleClient());
  }

  /**
   * Get the expected response from eWAY for a single payment.
   *
   * @return array
   */
  public function getExpectedSinglePaymentResponses(): array {
    return [
      '<ewayResponse><ewayTrxnStatus>True</ewayTrxnStatus><ewayTrxnNumber>10002</ewayTrxnNumber><ewayTrxnReference>xyz</ewayTrxnReference><ewayTrxnOption1/><ewayTrxnOption2/><ewayTrxnOption3/><ewayAuthCode>123456</ewayAuthCode><ewayReturnAmount>1000</ewayReturnAmount><ewayTrxnError>00,Transaction Approved(Test Gateway)</ewayTrxnError></ewayResponse>',
    ];
  }

  /**
   * Get the expected request to eWAY.
   *
   * @return array
   */
  public function getExpectedSinglePaymentRequests(): array {
    return [
      '<ewaygateway><ewayCustomerID>87654321</ewayCustomerID><ewayTotalAmount>1000</ewayTotalAmount><ewayCardHoldersName>John O&apos;Connor</ewayCardHoldersName><ewayCardNumber>4444333322221111</ewayCardNumber><ewayCardExpiryMonth>10</ewayCardExpiryMonth><ewayCardExpiryYear>22</ewayCardExpiryYear><ewayTrxnNumber>xyz</ewayTrxnNumber><ewayCustomerInvoiceDescription>Test Contribution</ewayCustomerInvoiceDescription><ewayCustomerFirstName>John</ewayCustomerFirstName><ewayCustomerLastName>O&apos;Connor</ewayCustomerLastName><ewayCustomerEmail>unittesteway@civicrm.org</ewayCustomerEmail><ewayCustomerAddress>8 Hobbitton Road, The Shire, NSW.</ewayCustomerAddress><ewayCustomerPostcode>5010</ewayCustomerPostcode><ewayCustomerInvoiceRef>xyz</ewayCustomerInvoiceRef><ewayCVN>123</ewayCVN><ewayOption1></ewayOption1><ewayOption2></ewayOption2><ewayOption3></ewayOption3><ewayCustomerIPAddress>127.0.0.1</ewayCustomerIPAddress><ewayCustomerBillingCountry>AU</ewayCustomerBillingCountry></ewaygateway>',
    ];
  }

  /**
   * Get the expected response from eWAY for a single payment.
   *
   * @return array
   */
  public function getExpectedSinglePaymentErrorResponses(): array {
    return [
      '<ewayResponse><ewayTrxnStatus>False</ewayTrxnStatus><ewayTrxnNumber>10003</ewayTrxnNumber><ewayTrxnReference>xyz</ewayTrxnReference><ewayTrxnOption1/><ewayTrxnOption2/><ewayTrxnOption3/><ewayAuthCode>123456</ewayAuthCode><ewayReturnAmount>524</ewayReturnAmount><ewayTrxnError>24,Do Not Honour(Test Gateway)</ewayTrxnError></ewayResponse>',
    ];
  }

}
