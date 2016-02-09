<?php

/**
 * Class CRM_Core_Payment_PayPalProIPNTest
 * @group headless
 */
class CRM_Core_Payment_AuthorizeNetIPNTest extends CiviUnitTestCase {
  protected $_contributionID;
  protected $_invoiceID = 'c2r9c15f7be20b4f3fef1f77e4c37424';
  protected $_financialTypeID = 1;
  protected $_contactID;
  protected $_contributionRecurID;
  protected $_contributionPageID;
  protected $_paymentProcessorID;

  public function setUp() {
    parent::setUp();
    $this->_paymentProcessorID = $this->paymentProcessorAuthorizeNetCreate(array('is_test' => 0));
    $this->_contactID = $this->individualCreate();
    $contributionPage = $this->callAPISuccess('contribution_page', 'create', array(
      'title' => "Test Contribution Page",
      'financial_type_id' => $this->_financialTypeID,
      'currency' => 'USD',
      'payment_processor' => $this->_paymentProcessorID,
      'max_amount' => 1000,
      'receipt_from_email' => 'gaia@the.cosmos',
      'receipt_from_name' => 'Pachamama',
      'is_email_receipt' => TRUE,
    ));
    $this->_contributionPageID = $contributionPage['id'];
  }

  public function tearDown() {
    $this->quickCleanUpFinancialEntities();
  }

  /**
   * Test IPN response updates contribution_recur & contribution for first & second contribution
   */
  public function testIPNPaymentRecurSuccess() {
    $this->setupRecurringPaymentProcessorTransaction();
    $IPN = new CRM_Core_Payment_AuthorizeNetIPN($this->getRecurTransaction());
    $IPN->main();
    $contribution = $this->callAPISuccess('contribution', 'getsingle', array('id' => $this->_contributionID));
    $this->assertEquals(1, $contribution['contribution_status_id']);
    $this->assertEquals('6511143069', $contribution['trxn_id']);
    // source gets set by processor
    $this->assertTrue(substr($contribution['contribution_source'], 0, 20) == "Online Contribution:");
    $contributionRecur = $this->callAPISuccess('contribution_recur', 'getsingle', array('id' => $this->_contributionRecurID));
    $this->assertEquals(5, $contributionRecur['contribution_status_id']);
    $IPN = new CRM_Core_Payment_AuthorizeNetIPN($this->getRecurSubsequentTransaction());
    $IPN->main();
    $contribution = $this->callAPISuccess('contribution', 'get', array(
        'contribution_recur_id' => $this->_contributionRecurID,
        'sequential' => 1,
      ));
    $this->assertEquals(2, $contribution['count']);
    $this->assertEquals('second_one', $contribution['values'][1]['trxn_id']);
  }

  /**
   * Test IPN response updates contribution_recur & contribution for first & second contribution
   */
  public function testIPNPaymentMembershipRecurSuccess() {
    $this->setupMembershipRecurringPaymentProcessorTransaction();
    $IPN = new CRM_Core_Payment_AuthorizeNetIPN($this->getRecurTransaction());
    $IPN->main();
    $contribution = $this->callAPISuccess('contribution', 'getsingle', array('id' => $this->_contributionID));
    $this->assertEquals(1, $contribution['contribution_status_id']);
    $this->assertEquals('6511143069', $contribution['trxn_id']);
    // source gets set by processor
    $this->assertTrue(substr($contribution['contribution_source'], 0, 20) == "Online Contribution:");
    $contributionRecur = $this->callAPISuccess('contribution_recur', 'getsingle', array('id' => $this->_contributionRecurID));
    $this->assertEquals(5, $contributionRecur['contribution_status_id']);
    $IPN = new CRM_Core_Payment_AuthorizeNetIPN($this->getRecurSubsequentTransaction());
    $IPN->main();
    $contribution = $this->callAPISuccess('contribution', 'get', array(
        'contribution_recur_id' => $this->_contributionRecurID,
        'sequential' => 1,
      ));
    $this->assertEquals(2, $contribution['count']);
    $this->assertEquals('second_one', $contribution['values'][1]['trxn_id']);
    $this->callAPISuccessGetSingle('membership_payment', array('contribution_id' => $contribution['values'][1]['id']));
    $this->callAPISuccessGetSingle('line_item', array(
        'contribution_id' => $contribution['values'][1]['id'],
        'entity_table' => 'civicrm_membership',
      ));
  }

  /**
   * Test IPN response mails don't leak.
   */
  public function testIPNPaymentMembershipRecurSuccessNoLeakage() {
    $mut = new CiviMailUtils($this, TRUE);
    $this->setupMembershipRecurringPaymentProcessorTransaction(array('is_email_receipt' => TRUE));
    $this->addProfile('supporter_profile', $this->_contributionPageID);
    $IPN = new CRM_Core_Payment_AuthorizeNetIPN($this->getRecurTransaction());
    $IPN->main();
    $mut->checkAllMailLog(array(
      'Membership Type: General',
      'Mr. Anthony Anderson II" <anthony_anderson@civicrm.org>',
      'Amount: $ 200.00',
      'Membership Start Date:',
      'Supporter Profile',
      'First Name: Anthony',
      'Last Name: Anderson',
      'Email Address: anthony_anderson@civicrm.org',
      'This membership will be automatically renewed every',
      'Dear Mr. Anthony Anderson II',
      'Thanks for your auto renew membership sign-up',
    ));
    $mut->clearMessages();
    $this->_contactID = $this->individualCreate(array('first_name' => 'Antonia', 'prefix_id' => 'Mrs.', 'email' => 'antonia_anderson@civicrm.org'));
    $this->_invoiceID = uniqid();

    $this->setupMembershipRecurringPaymentProcessorTransaction(array('is_email_receipt' => TRUE));
    $IPN = new CRM_Core_Payment_AuthorizeNetIPN($this->getRecurTransaction(array('x_trans_id' => 'hers')));
    $IPN->main();

    $mut->checkAllMailLog(array(
      'Membership Type: General',
      'Mrs. Antonia Anderson II',
      'antonia_anderson@civicrm.org',
      'Amount: $ 200.00',
      'Membership Start Date:',
      'Transaction #: hers',
      'Supporter Profile',
      'First Name: Antonia',
      'Last Name: Anderson',
      'Email Address: antonia_anderson@civicrm.org',
      'This membership will be automatically renewed every',
      'Dear Mrs. Antonia Anderson II',
      'Thanks for your auto renew membership sign-up',
    ));

    $mut->stop();
    $mut->clearMessages();
  }

  /**
   * Test IPN response mails don't leak.
   */
  public function testIPNPaymentMembershipRecurSuccessNoLeakageOnlineThenOffline() {
    $mut = new CiviMailUtils($this, TRUE);
    $this->setupMembershipRecurringPaymentProcessorTransaction(array('is_email_receipt' => TRUE));
    $this->addProfile('supporter_profile', $this->_contributionPageID);
    $IPN = new CRM_Core_Payment_AuthorizeNetIPN($this->getRecurTransaction());
    $IPN->main();
    $mut->checkAllMailLog(array(
      'Membership Type: General',
      'Mr. Anthony Anderson II" <anthony_anderson@civicrm.org>',
      'Amount: $ 200.00',
      'Membership Start Date:',
      'Supporter Profile',
      'First Name: Anthony',
      'Last Name: Anderson',
      'Email Address: anthony_anderson@civicrm.org',
      'This membership will be automatically renewed every',
      'Dear Mr. Anthony Anderson II',
      'Thanks for your auto renew membership sign-up',
    ));

    $this->_contactID = $this->individualCreate(array('first_name' => 'Antonia', 'prefix_id' => 'Mrs.', 'email' => 'antonia_anderson@civicrm.org'));
    $this->_invoiceID = uniqid();
    $this->_contributionPageID = NULL;

    $this->setupMembershipRecurringPaymentProcessorTransaction(array('is_email_receipt' => TRUE));
    $mut->clearMessages(99999);
    $IPN = new CRM_Core_Payment_AuthorizeNetIPN($this->getRecurTransaction(array('x_trans_id' => 'hers')));
    $IPN->main();

    $mut->checkAllMailLog(array(
      'Membership Type: General',
      'Mrs. Antonia Anderson II',
      'antonia_anderson@civicrm.org',
      'Amount: $ 200.00',
      'Membership Start Date:',
      'Transaction #: hers',
      'This membership will be automatically renewed every',
      'Dear Mrs. Antonia Anderson II',
      'Thanks for your auto renew membership sign-up',
    ),
    array(
      'First Name: Anthony',
      'First Name: Antonia',
      'Last Name: Anderson',
      'Supporter Profile',
      'Email Address: antonia_anderson@civicrm.org',
    ));

    $mut->stop();
    $mut->clearMessages();
  }

  /**
   * Get detail for recurring transaction.
   *
   * @param array $params
   *   Additional parameters.
   *
   * @return array
   *   Parameters like AuthorizeNet silent post paramters.
   */
  public function getRecurTransaction($params = array()) {
    return array_merge(array(
      "x_amount" => "200.00",
      "x_country" => 'US',
      "x_phone" => "",
      "x_fax" => "",
      "x_email" => "me@gmail.com",
      "x_description" => "lots of money",
      "x_type" => "auth_capture",
      "x_ship_to_first_name" => "",
      "x_ship_to_last_name" => "",
      "x_ship_to_company" => "",
      "x_ship_to_address" => "",
      "x_ship_to_city" => "",
      "x_ship_to_state" => "",
      "x_ship_to_zip" => "",
      "x_ship_to_country" => "",
      "x_tax" => "0.00",
      "x_duty" => "0.00",
      "x_freight" => "0.00",
      "x_tax_exempt" => "FALSE",
      "x_po_num" => "",
      "x_MD5_Hash" => "1B7C0C5B4DEDD9CAD0636E35E22FC594",
      "x_cvv2_resp_code" => "",
      "x_cavv_response" => "",
      "x_test_request" => "false",
      "x_subscription_id" => $this->_contactID,
      "x_subscription_paynum" => "1",
      'x_first_name' => 'Robert',
      'x_zip' => '90210',
      'x_state' => 'WA',
      'x_city' => 'Dallas',
      'x_address' => '41 My ST',
      'x_invoice_num' => $this->_contributionID,
      'x_cust_id' => $this->_contactID,
      'x_company' => 'nowhere@civicrm.org',
      'x_last_name' => 'Roberts',
      'x_account_number' => 'XXXX5077',
      'x_card_type' => 'Visa',
      'x_method' => 'CC',
      'x_trans_id' => '6511143069',
      'x_auth_code' => '123456',
      'x_avs_code' => 'Z',
      'x_response_reason_text' => 'This transaction has been approved.',
      'x_response_reason_code' => '1',
      'x_response_code' => '1',
    ), $params);
  }

  /**
   * @return array
   */
  public function getRecurSubsequentTransaction() {
    return array_merge($this->getRecurTransaction(), array(
      'x_trans_id' => 'second_one',
      'x_MD5_Hash' => 'EA7A3CD65A85757827F51212CA1486A8',
    ));
  }

}
