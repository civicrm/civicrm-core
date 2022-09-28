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

use Civi\Test\GuzzleTestTrait;

/**
 * Class CRM_Core_Payment_AuthorizeNetTest
 * @group headless
 */
trait CRM_Core_Payment_PaypalProTrait {
  use GuzzleTestTrait;

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
      'TIMESTAMP=2020%2d09%2d04T04%3a05%3a11Z&CORRELATIONID=246132f75a6f3&ACK=Success&VERSION=3&BUILD=54790461&AMT=5%2e24&CURRENCYCODE=USD&AVSCODE=A&CVV2MATCH=M&TRANSACTIONID=9TU23130NB247535M',
      'RECEIVERBUSINESS=sunil%2e_1183377782_biz%40webaccess%2eco%2ein&RECEIVEREMAIL=sunil%2e_1183377782_biz%40webaccess%2eco%2ein&RECEIVERID=BNCETES6EECQQ&EMAIL=86Y37281N5671683A%40dcc2%2epaypal%2ecom&PAYERID=RYUKTRK2TRA4J&PAYERSTATUS=verified&COUNTRYCODE=US&ADDRESSOWNER=PayPal&ADDRESSSTATUS=None&INVNUM=68023&SALESTAX=0%2e00&TIMESTAMP=2020%2d09%2d04T05%3a18%3a33Z&CORRELATIONID=b74cfc6e440ea&ACK=Success&VERSION=3&BUILD=54686869&FIRSTNAME=John&LASTNAME=O%27Connor&TRANSACTIONID=58E89379MT3066727&RECEIPTID=3065%2d9946%2d9883%2d0395&TRANSACTIONTYPE=webaccept&PAYMENTTYPE=instant&ORDERTIME=2020%2d09%2d04T05%3a18%3a28Z&AMT=5%2e24&FEEAMT=0%2e45&TAXAMT=0%2e00&CURRENCYCODE=USD&PAYMENTSTATUS=Completed&PENDINGREASON=None&REASONCODE=None&SHIPPINGMETHOD=Default&L_QTY0=1&L_TAXAMT0=0%2e00&L_CURRENCYCODE0=USD&L_AMT0=5%2e24',
    ];
  }

  /**
   *  Get the expected request from Authorize.net.
   *
   * @return array
   */
  public function getExpectedSinglePaymentRequests() {
    return [
      'user=sunil._1183377782_biz_api1.webaccess.co.in&pwd=1183377788&version=3&signature=APixCoQ-Zsaj-u3IH7mD5Do-7HUqA9loGnLSzsZga9Zr-aNmaJa3WGPH&subject=&method=DoDirectPayment&paymentAction=Sale&amt=5.24&currencyCode=USD&invnum=xyz&ipaddress=127.0.0.1&creditCardType=Visa&acct=4444333322221111&expDate=102022&cvv2=123&firstName=John&lastName=O%27Connor&email=&street=8+Hobbitton+Road&city=The+Shire&state=IL&countryCode=US&zip=5010&desc=&custom=&BUTTONSOURCE=CiviCRM_SP',
      'TRANSACTIONID=9TU23130NB247535M&user=sunil._1183377782_biz_api1.webaccess.co.in&pwd=1183377788&version=3&signature=APixCoQ-Zsaj-u3IH7mD5Do-7HUqA9loGnLSzsZga9Zr-aNmaJa3WGPH&subject=&method=GetTransactionDetails',
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
    $this->createMockHandler($responses);
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
