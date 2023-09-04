<?php

use CRM_Elavon_ExtensionUtil as E;
use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;
use Civi\Test\CiviEnvBuilder;

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
class CRM_Core_Payment_ElavonTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {

  use \Civi\Test\GuzzleTestTrait;
  use \Civi\Test\Api3TestTrait;

  /**
   * Instance of CRM_Core_Payment_Elavon|null
   * @var CRM_Core_Payment_Elavon
   */
  protected $processor;

  /**
   * Created Object Ids
   * @var array
   */
  public $ids;

  /**
   * Setup used when HeadlessInterface is implemented.
   *
   * Civi\Test has many helpers, like install(), uninstall(), sql(), and sqlFile().
   *
   * @link https://github.com/civicrm/org.civicrm.testapalooza/blob/master/civi-test.md
   *
   * @return \Civi\Test\CiviEnvBuilder
   *
   * @throws \CRM_Extension_Exception_ParseException
   */
  public function setUpHeadless(): CiviEnvBuilder {
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  public function setUp(): void {
    $this->setUpElavonProcessor();
    $this->processor = \Civi\Payment\System::singleton()->getById($this->ids['PaymentProcessor']['Elavon']);
    parent::setUp();
  }

  public function tearDown(): void {
    $this->callAPISuccess('PaymentProcessor', 'delete', ['id' => $this->ids['PaymentProcessor']['Elavon']]);
    parent::tearDown();
  }

  /**
   * Test making a once off payment
   */
  public function testSinglePayment(): void {
    $this->setupMockHandler();
    $params = $this->getBillingParams();
    $params['amount'] = 2.00;
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
    $this->assertEquals($this->getExpectedSinglePaymentRequests(), $this->getRequestUrls());
  }

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

  public function setUpElavonProcessor(): void {
    $params = [
      'name' => 'demo',
      'title' => 'demo',
      'domain_id' => CRM_Core_Config::domainID(),
      'payment_processor_type_id' => 'Elavon',
      'is_active' => 1,
      'is_default' => 0,
      'is_test' => 0,
      'user_name' => 'adfg',
      'password' => 'abc1234',
      'signature' => '12345',
      'url_site' => 'https://api.demo.convergepay.com/VirtualMerchantDemo/processxml.do',
      'class_name' => 'Payment_Elavon',
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
    $this->ids['PaymentProcessor']['Elavon'] = $processorID;
  }

  /**
   * Add a mock handler to the paypal Pro processor for testing.
   *
   * @param int|null $id
   *
   * @throws \CRM_Core_Exception
   */
  protected function setupMockHandler($id = NULL): void {
    if ($id) {
      $this->processor = Civi\Payment\System::singleton()->getById($id);
    }
    $responses = $this->getExpectedSinglePaymentResponses();
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
      '<ssl_result>0</ssl_result><ssl_result_message>APPROVAL</ssl_result_message><ssl_trxn_id>00000-0000-000-00-00-00000</ssl_trxn_id><ssl_cvv2_response>M</ssl_cvv2_response><ssl_avs_response>G</ssl_avs_response><ssl_approal_code1115</ssl_approal_code><errorCode></errorCode><errorName></errorName><errorMessage></errorMessage>',
    ];
  }

  /**
   *  Get the expected request to Elavon.
   *
   * @return array
   */
  public function getExpectedSinglePaymentRequests(): array {
    return [
      'https://api.demo.convergepay.com/VirtualMerchantDemo/processxml.do?xmldata=%3Ctxn%3E%3Cssl_first_name%3EJohn%3C/ssl_first_name%3E%3Cssl_last_name%3EO%27Connor%3C/ssl_last_name%3E%3Cssl_ship_to_first_name%3EJohn%3C/ssl_ship_to_first_name%3E%3Cssl_ship_to_last_name%3EO%27Connor%3C/ssl_ship_to_last_name%3E%3Cssl_card_number%3E4444333322221111%3C/ssl_card_number%3E%3Cssl_amount%3E2%3C/ssl_amount%3E%3Cssl_exp_date%3E1022%3C/ssl_exp_date%3E%3Cssl_cvv2cvc2%3E123%3C/ssl_cvv2cvc2%3E%3Cssl_cvv2cvc2_indicator%3E1%3C/ssl_cvv2cvc2_indicator%3E%3Cssl_avs_address%3E8%20Hobbitton%20Road%3C/ssl_avs_address%3E%3Cssl_city%3EThe%20Shire%3C/ssl_city%3E%3Cssl_state%3ENSW%3C/ssl_state%3E%3Cssl_avs_zip%3E5010%3C/ssl_avs_zip%3E%3Cssl_country%3EAU%3C/ssl_country%3E%3Cssl_email%3Eunittesteway@civicrm.org%3C/ssl_email%3E%3Cssl_invoice_number%3Exyz%3C/ssl_invoice_number%3E%3Cssl_transaction_type%3ECCSALE%3C/ssl_transaction_type%3E%3Cssl_description%3ETest%20Contribution%3C/ssl_description%3E%3Cssl_customer_number%3E1111%3C/ssl_customer_number%3E%3Cssl_customer_code%3E1111%3C/ssl_customer_code%3E%3Cssl_salestax%3E0%3C/ssl_salestax%3E%3Cssl_merchant_id%3Eadfg%3C/ssl_merchant_id%3E%3Cssl_user_id%3Eabc1234%3C/ssl_user_id%3E%3Cssl_pin%3E12345%3C/ssl_pin%3E%3C/txn%3E',
    ];
  }

}
