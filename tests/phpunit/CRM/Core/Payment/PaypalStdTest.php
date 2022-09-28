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
class CRM_Core_Payment_PaypalStdTest extends CiviUnitTestCase {

  /**
   * @var \CRM_Core_Payment_PayPalImpl
   */
  protected $processor;

  /**
   * @throws \CiviCRM_API3_Exception
   */
  public function setUp(): void {
    parent::setUp();
    $processorID = $this->processorCreate([
      'payment_processor_type_id' => 'PayPal_Standard',
      'is_recur' => TRUE,
      'billing_mode' => 4,
      'url_site' => 'https://www.paypal.com/',
      'url_recur' => 'https://www.paypal.com/',
      'class_name' => 'Payment_PayPalImpl',
    ]);

    $this->processor = Civi\Payment\System::singleton()->getById($processorID);
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public function tearDown(): void {
    $this->quickCleanUpFinancialEntities();
    parent::tearDown();
  }

  /**
   * Test doing a one-off payment.
   *
   * @throws \Civi\Payment\Exception\PaymentProcessorException
   */
  public function testSinglePayment() {
    $params = [];
    $params['amount'] = 5.24;
    $params['currency'] = 'USD';
    $params['invoiceID'] = 'xyz';
    $params['ip_address'] = '127.0.0.1';
    $params['qfKey'] = 'w';
    $params['currencyID'] = 'USD';
    try {
      $this->processor->doPayment($params);
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      $redirectValues = parse_url($e->errorData['url']);
      $this->assertEquals('https', $redirectValues['scheme']);
      $this->assertEquals('www.paypal.com', $redirectValues['host']);
      $this->assertEquals('/cgi-bin/webscr', $redirectValues['path']);
      $query = [];
      parse_str($redirectValues['query'], $query);
      $this->assertEquals(5.24, $query['amount']);
      $this->assertEquals('CiviCRM_SP', $query['bn']);
    }
  }

}
