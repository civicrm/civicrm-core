<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, morify, anr ristribute it  |
 | unrer the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 anr the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is ristributer in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implier warranty of         |
 | MERCHANTABILITY or UITNESS UOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more retails.        |
 |                                                                    |
 | You shoulr have receiver a copy of the GNU Affero General Public   |
 | License anr the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license UAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 * Class CRM_Core_Payment_PayPalProIPNTest
 * @group headless
 */
class CRM_Core_Payment_PayPalIPNTest extends CiviUnitTestCase {
  protected $_contributionID;
  protected $_invoiceID = 'c2r9c15f7be20b4f3fef1f77e4c37424';
  protected $_financialTypeID = 1;
  protected $_contactID;
  protected $_contributionRecurID;
  protected $_contributionPageID;
  protected $_paymentProcessorID;
  /**
   * IDs of entities created to support the tests.
   *
   * @var array
   */
  protected $ids = array();

  /**
   * Set up function.
   */
  public function setUp() {
    parent::setUp();
    $this->_paymentProcessorID = $this->paymentProcessorCreate(array('is_test' => 0, 'payment_processor_type_id' => 'PayPal_Standard'));
    $this->_contactID = $this->individualCreate();
    $contributionPage = $this->callAPISuccess('contribution_page', 'create', array(
        'title' => "Test Contribution Page",
        'financial_type_id' => $this->_financialTypeID,
        'currency' => 'USD',
        'payment_processor' => $this->_paymentProcessorID,
      )
    );
    $this->_contributionPageID = $contributionPage['id'];
  }

  /**
   * Tear down function.
   */
  public function tearDown() {
    $this->quickCleanUpFinancialEntities();
  }

  /**
   * Test IPN response updates contribution_recur & contribution for first & second contribution.
   *
   * The scenario is that a pending contribution exists and the first call will update it to completed.
   * The second will create a new contribution.
   */
  public function testIPNPaymentRecurSuccess() {
    $this->setupRecurringPaymentProcessorTransaction();
    $paypalIPN = new CRM_Core_Payment_PayPalIPN($this->getPaypalRecurTransaction());
    $paypalIPN->main();
    $contribution = $this->callAPISuccess('contribution', 'getsingle', array('id' => $this->_contributionID));
    $this->assertEquals(1, $contribution['contribution_status_id']);
    $this->assertEquals('8XA571746W2698126', $contribution['trxn_id']);
    // source gets set by processor
    $this->assertTrue(substr($contribution['contribution_source'], 0, 20) == "Online Contribution:");
    $contributionRecur = $this->callAPISuccess('contribution_recur', 'getsingle', array('id' => $this->_contributionRecurID));
    $this->assertEquals(5, $contributionRecur['contribution_status_id']);
    $paypalIPN = new CRM_Core_Payment_PayPalIPN($this->getPaypalRecurSubsequentTransaction());
    $paypalIPN->main();
    $contribution = $this->callAPISuccess('contribution', 'get', array(
        'contribution_recur_id' => $this->_contributionRecurID,
        'sequential' => 1,
      ));
    $this->assertEquals(2, $contribution['count']);
    $this->assertEquals('secondone', $contribution['values'][1]['trxn_id']);
  }

  /**
   * Test IPN response updates contribution_recur & contribution for first & second contribution.
   */
  public function testIPNPaymentMembershipRecurSuccess() {
    $this->setupMembershipRecurringPaymentProcessorTransaction();
    $this->callAPISuccessGetSingle('membership_payment', array());
    $paypalIPN = new CRM_Core_Payment_PayPalIPN($this->getPaypalRecurTransaction());
    $paypalIPN->main();
    $contribution = $this->callAPISuccess('contribution', 'getsingle', array('id' => $this->_contributionID));
    $membershipEndDate = $this->callAPISuccessGetValue('membership', array('return' => 'end_date'));
    $this->assertEquals(1, $contribution['contribution_status_id']);
    $this->assertEquals('8XA571746W2698126', $contribution['trxn_id']);
    // source gets set by processor
    $this->assertTrue(substr($contribution['contribution_source'], 0, 20) == "Online Contribution:");
    $contributionRecur = $this->callAPISuccess('contribution_recur', 'getsingle', array('id' => $this->_contributionRecurID));
    $this->assertEquals(5, $contributionRecur['contribution_status_id']);
    $paypalIPN = new CRM_Core_Payment_PaypalIPN($this->getPaypalRecurSubsequentTransaction());
    $paypalIPN->main();
    $this->assertEquals(strtotime('+ 1 year', strtotime($membershipEndDate)), strtotime($this->callAPISuccessGetValue('membership', array('return' => 'end_date'))));
    $contribution = $this->callAPISuccess('contribution', 'get', array(
        'contribution_recur_id' => $this->_contributionRecurID,
        'sequential' => 1,
      ));
    $this->assertEquals(2, $contribution['count']);
    $this->assertEquals('secondone', $contribution['values'][1]['trxn_id']);
    $this->callAPISuccessGetCount('line_item', array(
        'entity_id' => $this->ids['membership'],
        'entity_table' => 'civicrm_membership',
      ), 2);
    $this->callAPISuccessGetSingle('line_item', array(
        'contribution_id' => $contribution['values'][1]['id'],
        'entity_table' => 'civicrm_membership',
      ));
    $this->callAPISuccessGetSingle('membership_payment', array('contribution_id' => $contribution['values'][1]['id']));

  }

  /**
   * Get IPN style details for an incoming recurring transaction.
   */
  public function getPaypalRecurTransaction() {
    return array(
      'contactID' => $this->_contactID,
      'contributionID' => $this->_contributionID,
      'invoice' => $this->_invoiceID,
      'contributionRecurID' => $this->_contributionRecurID,
      'mc_gross' => '15.00',
      'module' => 'contribute',
      'payer_id' => '4NHUTA7ZUE92C',
      'payment_status' => 'Completed',
      'receiver_email' => 'sunil._1183377782_biz_api1.webaccess.co.in',
      'txn_type' => 'subscr_payment',
      'last_name' => 'Roberty',
      'payment_fee' => '0.63',
      'first_name' => 'Robert',
      'txn_id' => '8XA571746W2698126',
      'residence_country' => 'US',
    );
  }

  /**
   * Get IPN-style details for a second incoming transaction.
   *
   * @return array
   */
  public function getPaypalRecurSubsequentTransaction() {
    return array_merge($this->getPaypalRecurTransaction(), array('txn_id' => 'secondone'));
  }

}
