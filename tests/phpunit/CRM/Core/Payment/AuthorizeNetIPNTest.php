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
   * Ensure recurring contributions from Contribution Pages
   * with receipt turned off don't send a receipt.
   */
  public function testIPNPaymentRecurNoReceipt() {
    $mut = new CiviMailUtils($this, TRUE);
    // Turn off receipts in contribution page.
    $api_params = array(
      'id' => $this->_contributionPageID,
      'is_email_receipt' => FALSE,
    );
    $this->callAPISuccess('contributionPage', 'update', $api_params);

    // Create initial recurring payment and initial contribution.
    // Note - we can't use setupRecurringPaymentProcessorTransaction(), which
    // would be convenient because it does not fully mimic the real user
    // experience. Using setupRecurringPaymentProcessorTransaction() doesn't
    // specify is_email_receipt so it is always set to 1. We need to more
    // closely mimic what happens with a live transaction to test that
    // is_email_receipt is not set to 1 if the originating contribution page
    // has is_email_receipt set to 0.
    $form = new CRM_Contribute_Form_Contribution();
    $form->_mode = 'Live';
    $contribution = $form->testSubmit(array(
      'total_amount' => 200,
      'financial_type_id' => 1,
      'receive_date' => date('m/d/Y'),
      'receive_date_time' => date('H:i:s'),
      'contact_id' => $this->_contactID,
      'contribution_status_id' => 1,
      'credit_card_number' => 4444333322221111,
      'cvv2' => 123,
      'credit_card_exp_date' => array(
        'M' => 9,
        'Y' => 2025,
      ),
      'credit_card_type' => 'Visa',
      'billing_first_name' => 'Junko',
      'billing_middle_name' => '',
      'billing_last_name' => 'Adams',
      'billing_street_address-5' => time() . ' Lincoln St S',
      'billing_city-5' => 'Maryknoll',
      'billing_state_province_id-5' => 1031,
      'billing_postal_code-5' => 10545,
      'billing_country_id-5' => 1228,
      'frequency_interval' => 1,
      'frequency_unit' => 'month',
      'installments' => '',
      'hidden_AdditionalDetail' => 1,
      'hidden_Premium' => 1,
      'payment_processor_id' => $this->_paymentProcessorID,
      'currency' => 'USD',
      'source' => 'bob sled race',
      'contribution_page_id' => $this->_contributionPageID,
      'is_recur' => TRUE,
    ), CRM_Core_Action::ADD);

    $this->_contributionID = $contribution->id;
    $this->_contributionRecurID = $contribution->contribution_recur_id;
    $recur_params = array(
      'id' => $this->_contributionRecurID,
      'return' => 'processor_id',
    );
    $processor_id = civicrm_api3('ContributionRecur', 'getvalue', $recur_params);
    // Process the initial one.
    $IPN = new CRM_Core_Payment_AuthorizeNetIPN(
      $this->getRecurTransaction(array('x_subscription_id' => $processor_id))
    );
    $IPN->main();

    // Now send a second one (authorize seems to treat first and second contributions
    // differently.
    $IPN = new CRM_Core_Payment_AuthorizeNetIPN($this->getRecurSubsequentTransaction(
      array('x_subscription_id' => $processor_id)
    ));
    $IPN->main();

    // There should not be any email.
    $mut->assertMailLogEmpty();
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
    $this->assertEquals(date('Y-m-d'), date('Y-m-d', strtotime($contribution['values'][1]['receive_date'])));
  }

  /**
   * Test payment processor is correctly assigned for the IPN payment.
   */
  public function testIPNPaymentRecurSuccessMultiAuthNetProcessor() {
    //Create and set up recur payment using second instance of AuthNet Processor.
    $this->_paymentProcessorID2 = $this->paymentProcessorAuthorizeNetCreate(array('name' => 'Authorize2', 'is_test' => 0));
    $this->setupRecurringPaymentProcessorTransaction(array('payment_processor_id' => $this->_paymentProcessorID2));

    //Call IPN with processor id.
    $IPN = new CRM_Core_Payment_AuthorizeNetIPN($this->getRecurTransaction(array('processor_id' => $this->_paymentProcessorID2)));
    $IPN->main();
    $contribution = $this->callAPISuccess('contribution', 'getsingle', array('id' => $this->_contributionID));
    $this->assertEquals(1, $contribution['contribution_status_id']);
    $this->assertEquals('6511143069', $contribution['trxn_id']);
    // source gets set by processor
    $this->assertTrue(substr($contribution['contribution_source'], 0, 20) == "Online Contribution:");
    $contributionRecur = $this->callAPISuccess('contribution_recur', 'getsingle', array('id' => $this->_contributionRecurID));
    $this->assertEquals(5, $contributionRecur['contribution_status_id']);
  }

  /**
   * Test IPN response updates contribution_recur & contribution for first & second contribution
   */
  public function testIPNPaymentRecurSuccessSuppliedReceiveDate() {
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
    $IPN = new CRM_Core_Payment_AuthorizeNetIPN(array_merge(array('receive_date' => '1 July 2010'), $this->getRecurSubsequentTransaction()));
    $IPN->main();
    $contribution = $this->callAPISuccess('contribution', 'get', array(
      'contribution_recur_id' => $this->_contributionRecurID,
      'sequential' => 1,
    ));
    $this->assertEquals(2, $contribution['count']);
    $this->assertEquals('second_one', $contribution['values'][1]['trxn_id']);
    $this->assertEquals('2010-07-01', date('Y-m-d', strtotime($contribution['values'][1]['receive_date'])));
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
    // Ensure both contributions are coded as credit card contributions.
    $this->assertEquals(1, $contribution['values'][0]['payment_instrument_id']);
    $this->assertEquals(1, $contribution['values'][1]['payment_instrument_id']);
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
    $this->addProfile('honoree_individual', $this->_contributionPageID, 'soft_credit');

    $this->callAPISuccess('ContributionSoft', 'create', [
      'contact_id' => $this->individualCreate(),
      'contribution_id' => $this->_contributionID,
      'soft_credit_type_id' => 'in_memory_of',
      'amount' => 200,
    ]);

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
      'Honor',
      'This membership will be automatically renewed every',
      'Dear Mr. Anthony Anderson II',
      'Thanks for your auto renew membership sign-up',
      'In Memory of',
    ));
    $mut->clearMessages();
    $this->_contactID = $this->individualCreate(array('first_name' => 'Antonia', 'prefix_id' => 'Mrs.', 'email' => 'antonia_anderson@civicrm.org'));
    $this->_invoiceID = uniqid();

    // Note, the second contribution is not in honor of anyone and the
    // receipt should not mention honor at all.
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

    $shouldNotBeInMailing = array(
      'Honor',
      'In Memory of',
    );
    $mails = $mut->getAllMessages('raw');
    foreach ($mails as $mail) {
      $mut->checkMailForStrings(array(), $shouldNotBeInMailing, '', $mail);
    }
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
    $mut->clearMessages();
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
  public function getRecurSubsequentTransaction($params = array()) {
    return array_merge($this->getRecurTransaction(), array(
      'x_trans_id' => 'second_one',
      'x_MD5_Hash' => 'EA7A3CD65A85757827F51212CA1486A8',
    ), $params);
  }

}
