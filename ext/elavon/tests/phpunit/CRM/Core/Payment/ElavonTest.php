<?php

use Civi\Api4\PaymentProcessor;
use Civi\Payment\System;
use Civi\Test;
use Civi\Test\CiviEnvBuilder;
use Civi\Test\EntityTrait;
use Civi\Test\GuzzleTestTrait;
use Civi\Test\HeadlessInterface;
use Civi\Test\LookupHelperTrait;
use Civi\Test\TransactionalInterface;
use Civi\Core\HookInterface;
use PHPUnit\Framework\TestCase;

/**
 * Elavon processor test.
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
class CRM_Core_Payment_ElavonTest extends TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {

  use GuzzleTestTrait;
  use LookupHelperTrait;
  use EntityTrait;

  /**
   * @var CRM_Core_Payment_Elavon
   */
  protected CRM_Core_Payment $processor;

  /**
   * Setup used when HeadlessInterface is implemented.
   *
   * @return \Civi\Test\CiviEnvBuilder
   *
   * @throws \CRM_Extension_Exception_ParseException
   */
  public function setUpHeadless(): CiviEnvBuilder {
    return Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public function setUp(): void {
    $this->setUpElavonProcessor();
    $this->processor = System::singleton()->getById($this->ids['PaymentProcessor']['Elavon']);
    parent::setUp();
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public function tearDown(): void {
    if (!empty($this->ids['PaymentProcessor']['Elavon'])) {
      PaymentProcessor::delete(FALSE)
        ->addWhere('id', '=', $this->ids['PaymentProcessor']['Elavon'])
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
    $params['amount'] = 2.00;
    $this->processor->doPayment($params);
    $this->assertEquals($this->getExpectedSinglePaymentRequests(), $this->getRequestUrls());
  }

  /**
   * Get the payment parameters, keyed as the processor reads them.
   *
   * @return array
   */
  protected function getPaymentParams(): array {
    return [
      'billing_first_name' => 'John',
      'billing_last_name' => "O'Connor",
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
  private function setUpElavonProcessor(): void {
    $this->createTestEntity('PaymentProcessor', [
      'name' => 'demo',
      'title' => 'demo',
      'domain_id' => CRM_Core_Config::domainID(),
      'payment_processor_type_id:name' => 'Elavon',
      'is_active' => TRUE,
      'is_default' => FALSE,
      'is_test' => FALSE,
      'user_name' => 'adfg',
      'password' => 'abc1234',
      'signature' => '12345',
      'url_site' => 'https://api.demo.convergepay.com/VirtualMerchantDemo/processxml.do',
      'class_name' => 'Payment_Elavon',
      'billing_mode' => 1,
      'financial_account_id' => $this->getFinancialAccountID('Payment Processor Account'),
      // Credit card = 1 so can pass 'by accident'.
      'payment_instrument_id:name' => 'Debit Card',
    ], 'Elavon');
    $this->setupMockHandler($this->ids['PaymentProcessor']['Elavon']);
  }

  /**
   * Add a mock handler to the paypal Pro processor for testing.
   *
   * @param int|null $id
   *
   * @throws \CRM_Core_Exception
   */
  protected function setupMockHandler(?int $id = NULL): void {
    if ($id) {
      $this->processor = System::singleton()->getById($id);
    }
    $responses = $this->getExpectedSinglePaymentResponses();
    // Comment the next line out when trying to capture the response.
    // see https://github.com/civicrm/civicrm-core/pull/18350
    $this->createMockHandler($responses);
    $this->setUpClientWithHistoryContainer();
    $this->processor->setGuzzleClient($this->getGuzzleClient());
  }

  /**
   * Get the expected response from Elavon for a single payment.
   *
   * @return array
   */
  public function getExpectedSinglePaymentResponses(): array {
    return [
      '<ssl_result>0</ssl_result><ssl_result_message>APPROVAL</ssl_result_message><ssl_trxn_id>00000-0000-000-00-00-00000</ssl_trxn_id><ssl_cvv2_response>M</ssl_cvv2_response><ssl_avs_response>G</ssl_avs_response><ssl_approal_code1115</ssl_approal_code><errorCode></errorCode><errorName></errorName><errorMessage></errorMessage>',
    ];
  }

  /**
   * Get the expected request to Elavon.
   *
   * @return array
   */
  public function getExpectedSinglePaymentRequests(): array {
    return [
      'https://api.demo.convergepay.com/VirtualMerchantDemo/processxml.do?xmldata=%3Ctxn%3E%3Cssl_first_name%3EJohn%3C/ssl_first_name%3E%3Cssl_last_name%3EO%27Connor%3C/ssl_last_name%3E%3Cssl_ship_to_first_name%3EJohn%3C/ssl_ship_to_first_name%3E%3Cssl_ship_to_last_name%3EO%27Connor%3C/ssl_ship_to_last_name%3E%3Cssl_card_number%3E4444333322221111%3C/ssl_card_number%3E%3Cssl_amount%3E2%3C/ssl_amount%3E%3Cssl_exp_date%3E1022%3C/ssl_exp_date%3E%3Cssl_cvv2cvc2%3E123%3C/ssl_cvv2cvc2%3E%3Cssl_cvv2cvc2_indicator%3E1%3C/ssl_cvv2cvc2_indicator%3E%3Cssl_avs_address%3E8%20Hobbitton%20Road%3C/ssl_avs_address%3E%3Cssl_city%3EThe%20Shire%3C/ssl_city%3E%3Cssl_state%3ENSW%3C/ssl_state%3E%3Cssl_avs_zip%3E5010%3C/ssl_avs_zip%3E%3Cssl_country%3EAU%3C/ssl_country%3E%3Cssl_email%3Eunittesteway@civicrm.org%3C/ssl_email%3E%3Cssl_invoice_number%3Exyz%3C/ssl_invoice_number%3E%3Cssl_transaction_type%3ECCSALE%3C/ssl_transaction_type%3E%3Cssl_description%3ETest%20Contribution%3C/ssl_description%3E%3Cssl_customer_number%3E1111%3C/ssl_customer_number%3E%3Cssl_customer_code%3E1111%3C/ssl_customer_code%3E%3Cssl_salestax%3E0%3C/ssl_salestax%3E%3Cssl_cardholder_ip%3E127.0.0.1%3C/ssl_cardholder_ip%3E%3Cssl_merchant_id%3Eadfg%3C/ssl_merchant_id%3E%3Cssl_user_id%3Eabc1234%3C/ssl_user_id%3E%3Cssl_pin%3E12345%3C/ssl_pin%3E%3C/txn%3E',
    ];
  }

}
