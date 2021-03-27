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

use Civi\Payment\PropertyBag;

/**
 * Class CRM_Core_Payment_AuthorizeNetTest
 * @group headless
 */
class CRM_Core_Payment_AuthorizeNetTest extends CiviUnitTestCase {

  use CRM_Core_Payment_AuthorizeNetTrait;

  public function setUp(): void {
    parent::setUp();
    $this->_paymentProcessorID = $this->paymentProcessorAuthorizeNetCreate();

    $this->processor = Civi\Payment\System::singleton()->getById($this->_paymentProcessorID);
    $this->_financialTypeId = 1;

    // for some strange unknown reason, in batch mode this value gets set to null
    // so crude hack here to avoid an exception and hence an error
    $GLOBALS['_PEAR_ERRORSTACK_OVERRIDE_CALLBACK'] = [];
  }

  public function tearDown(): void {
    $this->quickCleanUpFinancialEntities();
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
    $this->processor->doPayment($params);
    $this->assertEquals($this->getExpectedSinglePaymentRequest(), $this->getRequestBodies()[0]);
  }

  /**
   * Create a single post dated payment as a recurring transaction.
   *
   * Test works but not both due to some form of caching going on in the SmartySingleton
   */
  public function testCreateSingleNowDated() {
    $this->isRecur = TRUE;
    $this->setupMockHandler();
    $firstName = 'John';
    $lastName = "O\'Connor";
    $nameParams = ['first_name' => 'John', 'last_name' => $lastName];
    $contactId = $this->individualCreate($nameParams);

    $invoiceID = 123456;
    $amount = 7;

    $recur = $this->callAPISuccess('ContributionRecur', 'create', [
      'contact_id' => $contactId,
      'amount' => $amount,
      'currency' => 'USD',
      'frequency_unit' => 'week',
      'frequency_interval' => 1,
      'installments' => 2,
      'start_date' => date('Ymd'),
      'create_date' => date('Ymd'),
      'invoice_id' => $invoiceID,
      'contribution_status_id' => 2,
      'is_test' => 1,
      'payment_processor_id' => $this->_paymentProcessorID,
    ]);

    $contribution = $this->callAPISuccess('Contribution', 'create', [
      'contact_id' => $contactId,
      'financial_type_id' => 'Donation',
      'receive_date' => date('Ymd'),
      'total_amount' => $amount,
      'invoice_id' => $invoiceID,
      'currency' => 'USD',
      'contribution_recur_id' => $recur['id'],
      'is_test' => 1,
      'contribution_status_id' => 2,
    ]);

    $billingParams = $this->getBillingParams();

    $params = array_merge($billingParams, [
      'qfKey' => '08ed21c7ca00a1f7d32fff2488596ef7_4454',
      'hidden_CreditCard' => 1,
      'is_recur' => 1,
      'frequency_interval' => 1,
      'frequency_unit' => 'month',
      'installments' => 12,
      'financial_type_id' => $this->_financialTypeId,
      'is_email_receipt' => 1,
      'from_email_address' => 'john.smith@example.com',
      'receive_date' => date('Ymd'),
      'receipt_date_time' => '',
      'payment_processor_id' => $this->_paymentProcessorID,
      'price_set_id' => '',
      'total_amount' => $amount,
      'currency' => 'USD',
      'source' => 'Mordor',
      'soft_credit_to' => '',
      'soft_contact_id' => '',
      'billing_state_province-5' => 'IL',
      'state_province-5' => 'IL',
      'billing_country-5' => 'US',
      'country-5' => 'US',
      'year' => 2025,
      'month' => 9,
      'ip_address' => '127.0.0.1',
      'amount' => 7,
      'amount_level' => 0,
      'currencyID' => 'USD',
      'pcp_display_in_roll' => '',
      'pcp_roll_nickname' => '',
      'pcp_personal_note' => '',
      'non_deductible_amount' => '',
      'fee_amount' => '',
      'net_amount' => '',
      'invoiceID' => $invoiceID,
      'contribution_page_id' => '',
      'thankyou_date' => NULL,
      'honor_contact_id' => NULL,
      'first_name' => $firstName,
      'middle_name' => '',
      'last_name' => $lastName,
      'street_address' => '8 Hobbiton Road',
      'city' => 'The Shire',
      'state_province' => 'IL',
      'postal_code' => 5010,
      'country' => 'US',
      'contributionType_name' => 'My precious',
      'contributionType_accounting_code' => '',
      'contributionPageID' => '',
      'email' => 'john.smith@example.com',
      'contactID' => $contactId,
      'contributionID' => $contribution['id'],
      'contributionRecurID' => $recur['id'],
    ]);

    // turn verifySSL off
    Civi::settings()->set('verifySSL', '0');
    $this->processor->doPayment($params);
    // turn verifySSL on
    Civi::settings()->set('verifySSL', '0');

    // if subscription was successful, processor_id / subscription-id must not be null
    $this->assertDBNotNull('CRM_Contribute_DAO_ContributionRecur', $recur['id'], 'processor_id',
      'id', 'Failed to create subscription with Authorize.'
    );

    $requests = $this->getRequestBodies();
    $this->assertEquals($this->getExpectedRequest($contactId, date('Y-m-d')), $requests[0]);
    $header = $this->getRequestHeaders()[0];
    $this->assertEquals(['apitest.authorize.net'], $header['Host']);
    $this->assertEquals(['text/xml; charset=UTF8'], $header['Content-Type']);

    $this->assertEquals([
      CURLOPT_RETURNTRANSFER => TRUE,
      CURLOPT_SSL_VERIFYPEER => Civi::settings()->get('verifySSL'),
    ], $this->container[0]['options']['curl']);
  }

  /**
   * Create a single post dated payment as a recurring transaction.
   */
  public function testCreateSinglePostDated() {
    $this->isRecur = TRUE;
    $this->setupMockHandler();
    $start_date = date('Ymd', strtotime('+ 1 week'));

    $firstName = 'John';
    $lastName = "O'Connor";
    $nameParams = ['first_name' => $firstName, 'last_name' => $lastName];
    $contactId = $this->individualCreate($nameParams);

    $invoiceID = 123456;
    $amount = 70.23;

    $contributionRecurParams = [
      'contact_id' => $contactId,
      'amount' => $amount,
      'currency' => 'USD',
      'frequency_unit' => 'month',
      'frequency_interval' => 1,
      'installments' => 3,
      'start_date' => $start_date,
      'create_date' => date('Ymd'),
      'invoice_id' => $invoiceID,
      'contribution_status_id' => '',
      'is_test' => 1,
      'payment_processor_id' => $this->_paymentProcessorID,
    ];
    $recur = $this->callAPISuccess('ContributionRecur', 'create', $contributionRecurParams);

    $contributionParams = [
      'contact_id' => $contactId,
      'financial_type_id' => $this->_financialTypeId,
      'receive_date' => $start_date,
      'total_amount' => $amount,
      'invoice_id' => $invoiceID,
      'currency' => 'USD',
      'contribution_recur_id' => $recur['id'],
      'is_test' => 1,
      'contribution_status_id' => 2,
    ];

    $contribution = $this->callAPISuccess('Contribution', 'create', $contributionParams);

    $params = [
      'qfKey' => '00ed21c7ca00a1f7d555555596ef7_4454',
      'hidden_CreditCard' => 1,
      'billing_first_name' => $firstName,
      'billing_middle_name' => '',
      'billing_last_name' => $lastName,
      'billing_street_address-5' => '8 Hobbitton Road',
      'billing_city-5' => 'The Shire',
      'billing_state_province_id-5' => 1012,
      'billing_postal_code-5' => 5010,
      'billing_country_id-5' => 1228,
      'credit_card_number' => '4007000000027',
      'cvv2' => 123,
      'credit_card_exp_date' => [
        'M' => 11,
        'Y' => 2022,
      ],
      'credit_card_type' => 'Visa',
      'is_recur' => 1,
      'frequency_interval' => 1,
      'frequency_unit' => 'month',
      'installments' => 3,
      'financial_type_id' => $this->_financialTypeId,
      'is_email_receipt' => 1,
      'from_email_address' => "{$firstName}.{$lastName}@example.com",
      'receive_date' => $start_date,
      'receipt_date_time' => '',
      'payment_processor_id' => $this->_paymentProcessorID,
      'price_set_id' => '',
      'total_amount' => $amount,
      'currency' => 'USD',
      'source' => 'Mordor',
      'soft_credit_to' => '',
      'soft_contact_id' => '',
      'billing_state_province-5' => 'IL',
      'state_province-5' => 'IL',
      'billing_country-5' => 'US',
      'country-5' => 'US',
      'year' => 2022,
      'month' => 10,
      'ip_address' => '127.0.0.1',
      'amount' => 70.23,
      'amount_level' => 0,
      'currencyID' => 'USD',
      'pcp_display_in_roll' => '',
      'pcp_roll_nickname' => '',
      'pcp_personal_note' => '',
      'non_deductible_amount' => '',
      'fee_amount' => '',
      'net_amount' => '',
      'invoice_id' => '',
      'contribution_page_id' => '',
      'thankyou_date' => NULL,
      'honor_contact_id' => NULL,
      'invoiceID' => $invoiceID,
      'first_name' => $firstName,
      'middle_name' => 'bob',
      'last_name' => $lastName,
      'street_address' => '8 Hobbiton Road',
      'city' => 'The Shire',
      'state_province' => 'IL',
      'postal_code' => 5010,
      'country' => 'US',
      'contributionPageID' => '',
      'email' => 'john.smith@example.com',
      'contactID' => $contactId,
      'contributionID' => $contribution['id'],
      'contributionRecurID' => $recur['id'],
    ];

    // if cancel-subscription has been called earlier 'subscriptionType' would be set to cancel.
    // to make a successful call for another trxn, we need to set it to something else.
    $smarty = CRM_Core_Smarty::singleton();
    $smarty->assign('subscriptionType', 'create');

    $this->processor->doPayment($params);

    // if subscription was successful, processor_id / subscription-id must not be null
    $this->assertDBNotNull('CRM_Contribute_DAO_ContributionRecur', $recur['id'], 'processor_id',
      'id', 'Failed to create subscription with Authorize.'
    );

    $response = $this->getResponseBodies();
    $this->assertEquals($this->getExpectedRecurResponse(), $response[0], 3);
    $requests = $this->getRequestBodies();
    $this->assertEquals($this->getExpectedRequest($contactId, date('Y-m-d', strtotime($start_date)), 70.23, 3, 4007000000027, '2022-10'), $requests[0]);
  }

  /**
   * Get the content that we expect to see sent out.
   *
   * @param int $contactID
   * @param string $startDate
   *
   * @param int $amount
   * @param int $occurrences
   * @param int $cardNumber
   * @param string $cardExpiry
   *
   * @return string
   */
  public function getExpectedRequest($contactID, $startDate, $amount = 7, $occurrences = 12, $cardNumber = 4444333322221111, $cardExpiry = '2025-09') {
    return '<?xml version="1.0" encoding="utf-8"?>
<ARBCreateSubscriptionRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
  <merchantAuthentication>
    <name>4y5BfuW7jm</name>
    <transactionKey>4cAmW927n8uLf5J8</transactionKey>
  </merchantAuthentication>
  <refId>123456</refId>
  <subscription>
        <paymentSchedule>
      <interval>
        <length>1</length>
        <unit>months</unit>
      </interval>
      <startDate>' . $startDate . '</startDate>
      <totalOccurrences>' . $occurrences . '</totalOccurrences>
    </paymentSchedule>
    <amount>' . $amount . '</amount>
    <payment>
      <creditCard>
        <cardNumber>' . $cardNumber . '</cardNumber>
        <expirationDate>' . $cardExpiry . '</expirationDate>
      </creditCard>
    </payment>
      <order>
     <invoiceNumber>1</invoiceNumber>
        </order>
       <customer>
      <id>' . $contactID . '</id>
      <email>john.smith@example.com</email>
    </customer>
    <billTo>
      <firstName>John</firstName>
      <lastName>O\'Connor</lastName>
      <address>8 Hobbiton Road</address>
      <city>The Shire</city>
      <state>IL</state>
      <zip>5010</zip>
      <country>US</country>
    </billTo>
  </subscription>
</ARBCreateSubscriptionRequest>
';
  }

  /**
   * Get some basic billing parameters.
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

  /**
   * Test the update billing function.
   */
  public function testUpdateBilling() {
    $this->setUpClient($this->getExpectedUpdateResponse());
    $params = [
      'qfKey' => '52e3078a34158a80b18d0e3c690c5b9f_2369',
      'entryURL' => 'http://dmaster.local/civicrm/contribute/updatebilling?reset=1&amp;crid=2&amp;cid=202&amp;context=contribution',
      'credit_card_number' => '4444333322221111',
      'cvv2' => '123',
      'credit_card_exp_date' => ['M' => '3', 'Y' => '2022'],
      'credit_card_type' => 'Visa',
      'first_name' => 'q',
      'middle_name' => '',
      'last_name' => 't',
      'street_address' => 'y',
      'city' => 'xyz',
      'state_province_id' => '1587',
      'postal_code' => '777',
      'country_id' => '1006',
      'state_province' => 'Bengo',
      'country' => 'Angola',
      'month' => '3',
      'year' => '2022',
      'subscriptionId' => 6656444,
      'amount' => '6.00',
    ];
    $message = '';
    $result = $this->processor->updateSubscriptionBillingInfo($message, $params);
    $requests = $this->getRequestBodies();
    $this->assertEquals('I00001: Successful.', $message);
    $this->assertTrue($result);
    $this->assertEquals($this->getExpectedUpdateRequest(), $requests[0]);
  }

  /**
   * Test change subscription function.
   *
   * @throws \Civi\Payment\Exception\PaymentProcessorException
   */
  public function testChangeSubscription() {
    $this->setUpClient($this->getExpectedUpdateResponse());
    $params = [
      'hidden_custom' => '1',
      'hidden_custom_group_count' => ['' => 1],
      'qfKey' => '38588554ecd5c01d5ecdedf3870d9100_7980',
      'entryURL' => 'http://dmaster.local/civicrm/contribute/updaterecur?reset=1&amp;action=update&amp;crid=2&amp;cid=202&amp;context=contribution',
      'amount' => '9.67',
      'currency' => 'USD',
      'installments' => '8',
      'is_notify' => '1',
      'financial_type_id' => '3',
      '_qf_default' => 'UpdateSubscription:next',
      '_qf_UpdateSubscription_next' => 'Save',
      'id' => '2',
      'subscriptionId' => 1234,
    ];
    $message = '';
    $result = $this->processor->changeSubscriptionAmount($message, $params);
    $requests = $this->getRequestBodies();
    $this->assertEquals('I00001: Successful.', $message);
    $this->assertTrue($result);
    $this->assertEquals($this->getExpectedChangeSubscriptionRequest(), $requests[0]);
  }

  /**
   * Get the expected request string for updateBilling.
   *
   * @return string
   */
  public function getExpectedUpdateRequest() {
    return '<?xml version="1.0" encoding="utf-8"?>
<ARBUpdateSubscriptionRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
  <merchantAuthentication>
    <name>4y5BfuW7jm</name>
    <transactionKey>4cAmW927n8uLf5J8</transactionKey>
  </merchantAuthentication>
  <subscriptionId>6656444</subscriptionId>
  <subscription>
    <payment>
      <creditCard>
        <cardNumber>4444333322221111</cardNumber>
        <expirationDate>2022-03</expirationDate>
      </creditCard>
    </payment>
    <billTo>
      <firstName>q</firstName>
      <lastName>t</lastName>
      <address>y</address>
      <city>xyz</city>
      <state>Bengo</state>
      <zip>777</zip>
      <country>Angola</country>
    </billTo>
  </subscription>
</ARBUpdateSubscriptionRequest>
';
  }

  /**
   * Get the expected response string for update billing.
   *
   * @return string
   */
  public function getExpectedUpdateResponse() {
    return 'HTTP/1.1 200 OK
Cache-Control: no-store
Pragma: no-cache
Content-Type: application/xml; charset=utf-8
X-OPNET-Transaction-Trace: a2_4345e2c4-e273-46be-8517-8e6c8c408f5c-11416-3580701
Access-Control-Allow-Credentials: true
Access-Control-Allow-Headers: x-requested-with,cache-control,content-type,origin,method,SOAPAction
Access-Control-Allow-Methods: PUT,OPTIONS,POST,GET
Access-Control-Allow-Origin: *
Strict-Transport-Security: max-age=31536000
X-Cnection: close
Date: Thu, 11 Jun 2020 23:19:48 GMT
Content-Length: 557

﻿<?xml version="1.0" encoding="utf-8"?><resultCode>Ok</resultCode><message><code>I00001</code><text>Successful.</text>';
  }

  /**
   * Get the expected outgoing request for changeSubscription.
   *
   * @return string
   */
  protected function getExpectedChangeSubscriptionRequest() {
    return '<?xml version="1.0" encoding="utf-8"?>
<ARBUpdateSubscriptionRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
  <merchantAuthentication>
    <name>4y5BfuW7jm</name>
    <transactionKey>4cAmW927n8uLf5J8</transactionKey>
  </merchantAuthentication>
<subscriptionId>1234</subscriptionId>
  <subscription>
    <paymentSchedule>
    <totalOccurrences>8</totalOccurrences>
    </paymentSchedule>
    <amount>9.67</amount>
   </subscription>
</ARBUpdateSubscriptionRequest>
';
  }

  /**
   * Get the expected incoming response for changeSubscription.
   *
   * @return string
   */
  protected function getExpectedChangeSubscriptionResponse() {
    return 'HTTP/1.1 200 OK
Cache-Control: no-store
Pragma: no-cache
Content-Type: application/xml; charset=utf-8
X-OPNET-Transaction-Trace: a2_e77aa7be-8f98-4f54-ba8a-e8a7f3d9e5ab-8400-7232961
Access-Control-Allow-Credentials: true
Access-Control-Allow-Headers: x-requested-with,cache-control,content-type,origin,method,SOAPAction
Access-Control-Allow-Methods: PUT,OPTIONS,POST,GET
Access-Control-Allow-Origin: *
Strict-Transport-Security: max-age=31536000
X-Cnection: close
Date: Fri, 12 Jun 2020 00:18:11 GMT
Content-Length: 492

﻿<?xml version="1.0" encoding="utf-8"?><ARBUpdateSubscriptionResponse xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd"><messages><resultCode>Ok</resultCode><message><code>I00001</code><text>Successful.</text></message></messages><profile><customerProfileId>1512214263</customerProfileId><customerPaymentProfileId>1512250079</customerPaymentProfileId></profile></ARBUpdateSubscriptionResponse>';
  }

  /**
   * Setup the guzzle client, helper.
   *
   * @param string $response
   */
  protected function setUpClient($response) {
    $this->createMockHandler([$response]);
    $this->setUpClientWithHistoryContainer();
    $this->processor->setGuzzleClient($this->getGuzzleClient());
  }

  /**
   * @throws \Civi\Payment\Exception\PaymentProcessorException
   */
  public function testCancelRecurring() {
    $this->setUpClient($this->getExpectedCancelResponse());
    $propertyBag = new PropertyBag();
    $propertyBag->setContributionRecurID(9);
    $propertyBag->setIsNotifyProcessorOnCancelRecur(TRUE);
    $propertyBag->setRecurProcessorID(6656333);
    $this->processor->doCancelRecurring($propertyBag);
    $requests = $this->getRequestBodies();
    $this->assertEquals($this->getExpectedCancelRequest(), $requests[0]);
  }

  /**
   * Get expected incoming cancel response.
   *
   * @return string
   */
  protected function getExpectedCancelResponse() {
    return 'HTTP/1.1 200 OK
Cache-Control: no-store
Pragma: no-cache
Content-Type: application/xml; charset=utf-8
X-OPNET-Transaction-Trace: a2_e77aa7be-8f98-4f54-ba8a-e8a7f3d9e5ab-8400-7311552
Access-Control-Allow-Credentials: true
Access-Control-Allow-Headers: x-requested-with,cache-control,content-type,origin,method,SOAPAction
Access-Control-Allow-Methods: PUT,OPTIONS,POST,GET
Access-Control-Allow-Origin: *
Strict-Transport-Security: max-age=31536000
X-Cnection: close
Date: Fri, 12 Jun 2020 00:52:00 GMT
Content-Length: 361

﻿<?xml version="1.0" encoding="utf-8"?><ARBCancelSubscriptionResponse xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd"><messages><resultCode>Ok</resultCode><message><code>I00001</code><text>Successful.</text></message></messages></ARBCancelSubscriptionResponse>';
  }

  /**
   * Get the expected outgoing cancel request.
   *
   * @return string
   */
  protected function getExpectedCancelRequest() {
    return '<?xml version="1.0" encoding="utf-8"?>
<ARBCancelSubscriptionRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
  <merchantAuthentication>
    <name>4y5BfuW7jm</name>
    <transactionKey>4cAmW927n8uLf5J8</transactionKey>
  </merchantAuthentication>
  <subscriptionId>6656333</subscriptionId>
</ARBCancelSubscriptionRequest>
';

  }

}
