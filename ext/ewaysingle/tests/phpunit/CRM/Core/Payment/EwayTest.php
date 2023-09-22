<?php

use CRM_Ewaysingle_ExtensionUtil as E;
use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;

/**
 * FIXME - Add test description.
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
class CRM_Core_Payment_EwayTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface, HookInterface {

  use \Civi\Test\GuzzleTestTrait;
  use \Civi\Test\Api3TestTrait;

  /**
   * Instance of CRM_Core_Payment_eWay|null
   * @var CRM_Core_Payment_eWay
   */
  protected $processor;

  /**
   * Created Object Ids
   * @var array
   */
  public $ids;

  public function setUpHeadless() {
    // Civi\Test has many helpers, like install(), uninstall(), sql(), and sqlFile().
    // See: https://docs.civicrm.org/dev/en/latest/testing/phpunit/#civitest
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  public function setUp(): void {
    $this->setUpEwayProcessor();
    $this->processor = \Civi\Payment\System::singleton()->getById($this->ids['PaymentProcessor']['eWAY']);
    parent::setUp();
  }

  public function tearDown(): void {
    $this->callAPISuccess('PaymentProcessor', 'delete', ['id' => $this->ids['PaymentProcessor']['eWAY']]);
    parent::tearDown();
  }

  /**
   * Test making a once off payment
   */
  public function testSinglePayment(): void {
    $this->setupMockHandler();
    $params = $this->getBillingParams();
    $params['amount'] = 10.00;
    $params['currency'] = 'AUD';
    $params['description'] = 'Test Contribution';
    $params['invoiceID'] = 'xyz';
    $params['email'] = 'unittesteway@civicrm.org';
    $params['ip_address'] = '127.0.0.1';
    foreach ($params as $key => $value) {
      // Paypal is super special and requires this. Leaving out of the more generic
      // get billing params for now to make it more obvious.
      // When/if PropertyBag supports all the params paypal needs we can convert & simplify this.
      $params[str_replace('-5', '', str_replace('billing_', '', $key))] = $value;
    }
    $params['state_province'] = 'NSW';
    $params['country'] = 'AU';
    $this->processor->doPayment($params);
    $this->assertEquals($this->getExpectedSinglePaymentRequests(), $this->getRequestBodies());
  }

  /**
   * Test making a failed once off payment
   */
  public function testErrorSinglePayment(): void {
    $this->setupMockHandler(NULL, TRUE);
    $params = $this->getBillingParams();
    $params['amount'] = 5.24;
    $params['currency'] = 'AUD';
    $params['description'] = 'Test Contribution';
    $params['invoiceID'] = 'xyz';
    $params['email'] = 'unittesteway@civicrm.org';
    $params['ip_address'] = '127.0.0.1';
    foreach ($params as $key => $value) {
      // Paypal is super special and requires this. Leaving out of the more generic
      // get billing params for now to make it more obvious.
      // When/if PropertyBag supports all the params paypal needs we can convert & simplify this.
      $params[str_replace('-5', '', str_replace('billing_', '', $key))] = $value;
    }
    $params['state_province'] = 'NSW';
    $params['country'] = 'AU';
    try {
      $this->processor->doPayment($params);
      $this->fail('Test was meant to throw an exception');
    }
    catch (\Civi\Payment\Exception\PaymentProcessorException $e) {
      $this->assertEquals('Error: [24] - Do Not Honour(Test Gateway).', $e->getMessage());
      $this->assertEquals(9008, $e->getErrorCode());
    }
  }

  /**
   * Get some basic billing parameters.
   *
   * These are what are entered by the form-filler.
   *
   * @return array
   */
  protected function getBillingParams(): array {
    return [
      'billing_first_name' => 'John',
      'billing_middle_name' => '',
      'billing_last_name' => "O'Connor",
      'billing_street_address-5' => '8 Hobbitton Road',
      'billing_city-5' => 'The Shire',
      'billing_state_province_id-5' => 1012,
      'billing_postal_code-5' => 5010,
      'billing_country_id-5' => 1228,
      'credit_card_number' => '4444333322221111',
      'cvv2' => 123,
      'credit_card_exp_date' => [
        'M' => 9,
        'Y' => 2025,
      ],
      'credit_card_type' => 'Visa',
      'year' => 2022,
      'month' => 10,
    ];
  }

  public function setUpEwayProcessor(): void {
    $params = [
      'name' => 'demo',
      'title' => 'demo',
      'domain_id' => CRM_Core_Config::domainID(),
      'payment_processor_type_id' => 'eWAY',
      'is_active' => 1,
      'is_default' => 0,
      'is_test' => 0,
      'user_name' => '87654321',
      'url_site' => 'https://www.eway.com.au/gateway/xmltest/testpage.asp',
      'class_name' => 'Payment_eWAY',
      'billing_mode' => 1,
      'financial_type_id' => 1,
      'financial_account_id' => 12,
      // Credit card = 1 so can pass 'by accident'.
      'payment_instrument_id' => 'Debit Card',
    ];
    if (!is_numeric($params['payment_processor_type_id'])) {
      // really the api should handle this through getoptions but it's not exactly api call so lets just sort it
      //here
      $params['payment_processor_type_id'] = $this->callAPISuccess('payment_processor_type', 'getvalue', [
        'name' => $params['payment_processor_type_id'],
        'return' => 'id',
      ], 'integer');
    }
    $result = $this->callAPISuccess('payment_processor', 'create', $params);
    $processorID = $result['id'];
    $this->setupMockHandler($processorID);
    $this->ids['PaymentProcessor']['eWAY'] = $processorID;
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
      $this->processor = Civi\Payment\System::singleton()->getById($id);
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
   *  Get the expected request from eWAY.
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
