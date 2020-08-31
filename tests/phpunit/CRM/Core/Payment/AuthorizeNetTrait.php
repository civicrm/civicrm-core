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
trait CRM_Core_Payment_AuthorizeNetTrait {
  use \Civi\Test\GuzzleTestTrait;

  /**
   * @var \CRM_Core_Payment_AuthorizeNet
   */
  protected $processor;

  /**
   * Is this a recurring transaction.
   *
   * @var bool
   */
  protected $isRecur = FALSE;

  /**
   * Get the expected response from Authorize.net.
   *
   * @return string
   */
  public function getExpectedSinglePaymentResponse() {
    return '"1","1","1","(TESTMODE) This transaction has been approved.","000000","P","0","","","5.24","CC","auth_capture","","John","O&#39;Connor","","","","","","","","","","","","","","","","","","","","","","","",""';
  }

  /**
   *  Get the expected request from Authorize.net.
   *
   * @return string
   */
  public function getExpectedSinglePaymentRequest() {
    return 'x_login=4y5BfuW7jm&x_tran_key=4cAmW927n8uLf5J8&x_email_customer=&x_first_name=John&x_last_name=O%27Connor&x_address=&x_city=&x_state=&x_zip=&x_country=&x_customer_ip=&x_email=&x_invoice_num=&x_amount=5.24&x_currency_code=&x_description=&x_cust_id=&x_relay_response=FALSE&x_delim_data=TRUE&x_delim_char=%2C&x_encap_char=%22&x_card_num=4444333322221111&x_card_code=123&x_exp_date=10%2F2022&x_test_request=TRUE';
  }

  /**
   * Add a mock handler to the authorize.net processor for testing.
   *
   * @param int|null $id
   *
   * @throws \CiviCRM_API3_Exception
   */
  protected function setupMockHandler($id = NULL) {
    if ($id) {
      $this->processor = Civi\Payment\System::singleton()->getById($id);
    }
    $response = $this->isRecur ? $this->getExpectedRecurResponse() : $this->getExpectedSinglePaymentResponse();
    $this->createMockHandler([$response]);
    $this->setUpClientWithHistoryContainer();
    $this->processor->setGuzzleClient($this->getGuzzleClient());
  }

  /**
   * Get a successful response to setting up a recurring.
   *
   * @return string
   */
  public function getExpectedRecurResponse() {
    return '﻿<?xml version="1.0" encoding="utf-8"?><ARBCreateSubscriptionResponse xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd"><refId>8d468ca1b1dd5c2b56c7</refId><messages><resultCode>Ok</resultCode><message><code>I00001</code><text>Successful.</text></message></messages><subscriptionId>6632052</subscriptionId><profile><customerProfileId>1512023280</customerProfileId><customerPaymentProfileId>1512027350</customerPaymentProfileId></profile></ARBCreateSubscriptionResponse>';
  }

  /**
   * Create an AuthorizeNet processors with a configured mock handler.
   *
   * @throws \CiviCRM_API3_Exception
   */
  protected function createAuthorizeNetProcessor() {
    $processorID = $this->paymentProcessorAuthorizeNetCreate(['is_test' => FALSE]);
    $this->setupMockHandler($processorID);
    $this->ids['PaymentProcessor']['anet'] = $processorID;
  }

  /**
   * Assert the request sent to Authorize.net contains the expected values.
   *
   * @param array $expected
   */
  protected function assertRequestValid($expected = []) {
    $expected = array_merge([
      'x_card_num' => '4111111111111111',
      'x_card_code' => 123,
    ], $expected);
    $request = explode('&', $this->getRequestBodies()[0]);
    // This is stand in for now just to check a request happened. We can improve later.
    foreach ($expected as $key => $value) {
      $this->assertContains($key . '=' . $value, $request);
    }
  }

}
