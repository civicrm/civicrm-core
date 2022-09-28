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
 * Class CRM_Core_Payment_PaypalPro
 * @group headless
 */
class CRM_Core_Payment_PaypalProTest extends CiviUnitTestCase {

  use CRM_Core_Payment_PaypalProTrait;

  /**
   * @throws \CiviCRM_API3_Exception
   */
  public function setUp(): void {
    parent::setUp();
    $this->createPaypalProProcessor();

    $this->processor = Civi\Payment\System::singleton()->getById($this->ids['PaymentProcessor']['paypal_pro']);
  }

  public function tearDown(): void {
    $this->quickCleanUpFinancialEntities();
    parent::tearDown();
  }

  /**
   * Test doing a one-off payment.
   *
   * @throws \Civi\Payment\Exception\PaymentProcessorException
   * @throws \CiviCRM_API3_Exception
   */
  public function testSinglePayment() {
    $this->setupMockHandler();
    $params = $this->getBillingParams();
    $params['amount'] = 5.24;
    $params['currency'] = 'USD';
    $params['invoiceID'] = 'xyz';
    $params['ip_address'] = '127.0.0.1';
    foreach ($params as $key => $value) {
      // Paypal is super special and requires this. Leaving out of the more generic
      // get billing params for now to make it more obvious.
      // When/if PropertyBag supports all the params paypal needs we can convert & simplify this.
      $params[str_replace('-5', '', str_replace('billing_', '', $key))] = $value;
    }
    $params['state_province'] = 'IL';
    $params['country'] = 'US';
    $this->processor->doPayment($params);
    $this->assertEquals($this->getExpectedSinglePaymentRequests(), $this->getRequestBodies());
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

}
