<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 * Class CRM_Core_Payment_AuthorizeNetTest
 * @group headless
 */
class CRM_Core_Payment_AuthorizeNetTest extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();
    $this->_paymentProcessorID = $this->paymentProcessorAuthorizeNetCreate();

    $this->processor = Civi\Payment\System::singleton()->getById($this->_paymentProcessorID);
    $this->_financialTypeId = 1;

    // for some strange unknown reason, in batch mode this value gets set to null
    // so crude hack here to avoid an exception and hence an error
    $GLOBALS['_PEAR_ERRORSTACK_OVERRIDE_CALLBACK'] = array();
  }

  public function tearDown() {
    $this->quickCleanUpFinancialEntities();
  }

  /**
   * Create a single post dated payment as a recurring transaction.
   *
   * Test works but not both due to some form of caching going on in the SmartySingleton
   */
  public function testCreateSingleNowDated() {
    $firstName = 'John_' . substr(sha1(rand()), 0, 7) . uniqid();
    $lastName = 'Smith_' . substr(sha1(rand()), 0, 7) . uniqid();
    $nameParams = array('first_name' => $firstName, 'last_name' => $lastName);
    $contactId = $this->individualCreate($nameParams);

    $invoiceID = sha1(rand());
    $amount = rand(100, 1000) . '.00';

    $recur = $this->callAPISuccess('ContributionRecur', 'create', array(
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
    ));

    $contribution = $this->callAPISuccess('Contribution', 'create', array(
      'contact_id' => $contactId,
      'financial_type_id' => $this->_financialTypeId,
      'receive_date' => date('Ymd'),
      'total_amount' => $amount,
      'invoice_id' => $invoiceID,
      'currency' => 'USD',
      'contribution_recur_id' => $recur['id'],
      'is_test' => 1,
      'contribution_status_id' => 2,
    ));

    $params = array(
      'qfKey' => '08ed21c7ca00a1f7d32fff2488596ef7_4454',
      'hidden_CreditCard' => 1,
      'billing_first_name' => $firstName,
      'billing_middle_name' => "",
      'billing_last_name' => $lastName,
      'billing_street_address-5' => '8 Hobbitton Road',
      'billing_city-5' => 'The Shire',
      'billing_state_province_id-5' => 1012,
      'billing_postal_code-5' => 5010,
      'billing_country_id-5' => 1228,
      'credit_card_number' => '4007000000027',
      'cvv2' => 123,
      'credit_card_exp_date' => array(
        'M' => 10,
        'Y' => 2019,
      ),
      'credit_card_type' => 'Visa',
      'is_recur' => 1,
      'frequency_interval' => 1,
      'frequency_unit' => 'month',
      'installments' => 12,
      'financial_type_id' => $this->_financialTypeId,
      'is_email_receipt' => 1,
      'from_email_address' => "{$firstName}.{$lastName}@example.com",
      'receive_date' => date('Ymd'),
      'receipt_date_time' => '',
      'payment_processor_id' => $this->_paymentProcessorID,
      'price_set_id' => '',
      'total_amount' => $amount,
      'currency' => 'USD',
      'source' => "Mordor",
      'soft_credit_to' => '',
      'soft_contact_id' => '',
      'billing_state_province-5' => 'IL',
      'state_province-5' => 'IL',
      'billing_country-5' => 'US',
      'country-5' => 'US',
      'year' => 2019,
      'month' => 10,
      'ip_address' => '127.0.0.1',
      'amount' => 7,
      'amount_level' => 0,
      'currencyID' => 'USD',
      'pcp_display_in_roll' => "",
      'pcp_roll_nickname' => "",
      'pcp_personal_note' => "",
      'non_deductible_amount' => "",
      'fee_amount' => "",
      'net_amount' => "",
      'invoiceID' => $invoiceID,
      'contribution_page_id' => "",
      'thankyou_date' => NULL,
      'honor_contact_id' => NULL,
      'first_name' => $firstName,
      'middle_name' => '',
      'last_name' => $lastName,
      'street_address' => '8 Hobbiton Road' . uniqid(),
      'city' => 'The Shire',
      'state_province' => 'IL',
      'postal_code' => 5010,
      'country' => 'US',
      'contributionType_name' => 'My precious',
      'contributionType_accounting_code' => '',
      'contributionPageID' => '',
      'email' => "{$firstName}.{$lastName}@example.com",
      'contactID' => $contactId,
      'contributionID' => $contribution['id'],
      'contributionTypeID' => $this->_financialTypeId,
      'contributionRecurID' => $recur['id'],
    );

    // turn verifySSL off
    Civi::settings()->set('verifySSL', '0');
    $this->doPayment($params);
    // turn verifySSL on
    Civi::settings()->set('verifySSL', '0');

    // if subscription was successful, processor_id / subscription-id must not be null
    $this->assertDBNotNull('CRM_Contribute_DAO_ContributionRecur', $recur['id'], 'processor_id',
      'id', 'Failed to create subscription with Authorize.'
    );

    // cancel it or the transaction will be rejected by A.net if the test is re-run
    $subscriptionID = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_ContributionRecur', $recur['id'], 'processor_id');
    $message = '';
    $result = $this->processor->cancelSubscription($message, array('subscriptionId' => $subscriptionID));
    $this->assertTrue($result, 'Failed to cancel subscription with Authorize.');
  }

  /**
   * Create a single post dated payment as a recurring transaction.
   */
  public function testCreateSinglePostDated() {
    $start_date = date('Ymd', strtotime("+ 1 week"));

    $firstName = 'John_' . substr(sha1(rand()), 0, 7);
    $lastName = 'Smith_' . substr(sha1(rand()), 0, 7);
    $nameParams = array('first_name' => $firstName, 'last_name' => $lastName);
    $contactId = $this->individualCreate($nameParams);

    $ids = array('contribution' => NULL);
    $invoiceID = sha1(rand());
    $amount = rand(100, 1000) . '.00';

    $contributionRecurParams = array(
      'contact_id' => $contactId,
      'amount' => $amount,
      'currency' => 'USD',
      'frequency_unit' => 'month',
      'frequency_interval' => 1,
      'installments' => 3,
      'start_date' => $start_date,
      'create_date' => date('Ymd'),
      'invoice_id' => $invoiceID,
      'contribution_status_id' => 2,
      'is_test' => 1,
      'payment_processor_id' => $this->_paymentProcessorID,
    );
    $recur = CRM_Contribute_BAO_ContributionRecur::add($contributionRecurParams, $ids);

    $contributionParams = array(
      'contact_id' => $contactId,
      'financial_type_id' => $this->_financialTypeId,
      'receive_date' => $start_date,
      'total_amount' => $amount,
      'invoice_id' => $invoiceID,
      'currency' => 'USD',
      'contribution_recur_id' => $recur->id,
      'is_test' => 1,
      'contribution_status_id' => 2,
    );

    $contribution = $this->callAPISuccess('contribution', 'create', $contributionParams);

    $params = array(
      'qfKey' => '00ed21c7ca00a1f7d555555596ef7_4454',
      'hidden_CreditCard' => 1,
      'billing_first_name' => $firstName,
      'billing_middle_name' => "",
      'billing_last_name' => $lastName,
      'billing_street_address-5' => '8 Hobbitton Road',
      'billing_city-5' => 'The Shire',
      'billing_state_province_id-5' => 1012,
      'billing_postal_code-5' => 5010,
      'billing_country_id-5' => 1228,
      'credit_card_number' => '4007000000027',
      'cvv2' => 123,
      'credit_card_exp_date' => array(
        'M' => 11,
        'Y' => 2022,
      ),
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
      'source' => "Mordor",
      'soft_credit_to' => '',
      'soft_contact_id' => '',
      'billing_state_province-5' => 'IL',
      'state_province-5' => 'IL',
      'billing_country-5' => 'US',
      'country-5' => 'US',
      'year' => 2022,
      'month' => 10,
      'ip_address' => '127.0.0.1',
      'amount' => 70,
      'amount_level' => 0,
      'currencyID' => 'USD',
      'pcp_display_in_roll' => "",
      'pcp_roll_nickname' => "",
      'pcp_personal_note' => "",
      'non_deductible_amount' => "",
      'fee_amount' => "",
      'net_amount' => "",
      'invoice_id' => "",
      'contribution_page_id' => "",
      'thankyou_date' => NULL,
      'honor_contact_id' => NULL,
      'invoiceID' => $invoiceID,
      'first_name' => $firstName,
      'middle_name' => 'bob',
      'last_name' => $lastName,
      'street_address' => '8 Hobbiton Road' . uniqid(),
      'city' => 'The Shire',
      'state_province' => 'IL',
      'postal_code' => 5010,
      'country' => 'US',
      'contributionPageID' => '',
      'email' => "{$firstName}.{$lastName}@example.com",
      'contactID' => $contactId,
      'contributionID' => $contribution['id'],
      'contributionRecurID' => $recur->id,
    );

    // if cancel-subscription has been called earlier 'subscriptionType' would be set to cancel.
    // to make a successful call for another trxn, we need to set it to something else.
    $smarty = CRM_Core_Smarty::singleton();
    $smarty->assign('subscriptionType', 'create');

    // turn verifySSL off
    Civi::settings()->set('verifySSL', '0');
    $this->doPayment($params);
    // turn verifySSL on
    Civi::settings()->set('verifySSL', '0');

    // if subscription was successful, processor_id / subscription-id must not be null
    $this->assertDBNotNull('CRM_Contribute_DAO_ContributionRecur', $recur->id, 'processor_id',
      'id', 'Failed to create subscription with Authorize.'
    );

    // cancel it or the transaction will be rejected by A.net if the test is re-run
    $subscriptionID = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_ContributionRecur', $recur->id, 'processor_id');
    $message = '';
    $result = $this->processor->cancelSubscription($message, array('subscriptionId' => $subscriptionID));
    $this->assertTrue($result, 'Failed to cancel subscription with Authorize.');
  }

  /**
   * Process payment against the Authorize.net test server.
   *
   * Skip the test if the server is unresponsive.
   *
   * @param array $params
   *
   * @throws PHPUnit_Framework_SkippedTestError
   */
  public function doPayment($params) {
    try {
      $this->processor->doPayment($params);
    }
    catch (Exception $e) {
      $this->assertTrue((strpos($e->getMessage(), 'E00001: Internal Error Occurred.') !== FALSE),
        'AuthorizeNet failed for unknown reason.' . $e->getMessage());
      $this->markTestSkipped('AuthorizeNet test server is not in a good mood so we can\'t test this right now');
    }
  }

}
