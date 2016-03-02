<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * Class CRM_Contribute_BAO_ContributionRecurTest
 * @group headless
 */
class CRM_Contribute_BAO_ContributionRecurTest extends CiviUnitTestCase {
  protected $_params = array();

  public function setUp() {
    parent::setUp();
    $this->_ids['payment_processor'] = $this->paymentProcessorCreate();
    $this->_params = array(
      'contact_id' => $this->individualCreate(),
      'amount' => 3.00,
      'frequency_unit' => 'week',
      'frequency_interval' => 1,
      'installments' => 2,
      'start_date' => 'yesterday',
      'create_date' => 'yesterday',
      'modified_date' => 'yesterday',
      'cancel_date' => NULL,
      'end_date' => '+ 2 weeks',
      'processor_id' => '643411460836',
      'trxn_id' => 'e0d0808e26f3e661c6c18eb7c039d363',
      'invoice_id' => 'e0d0808e26f3e661c6c18eb7c039d363',
      'contribution_status_id' => 1,
      'is_test' => 0,
      'cycle_day' => 1,
      'next_sched_contribution_date' => '+ 1 week',
      'failure_count' => 0,
      'failure_retry_date' => NULL,
      'auto_renew' => 0,
      'currency' => 'USD',
      'payment_processor_id' => $this->_ids['payment_processor'],
      'is_email_receipt' => 1,
      'financial_type_id' => 1,
      'payment_instrument_id' => 1,
      'campaign_id' => NULL,
    );
  }

  public function teardown() {
    $this->quickCleanup(array('civicrm_contribution_recur', 'civicrm_payment_processor'));
  }

  /**
   * Test that an object can be retrieved & saved (per CRM-14986).
   *
   * This has been causing a DB error so we are checking for absence of error
   */
  public function testFindSave() {
    $contributionRecur = $this->callAPISuccess('contribution_recur', 'create', $this->_params);
    $dao = new CRM_Contribute_BAO_ContributionRecur();
    $dao->id = $contributionRecur['id'];
    $dao->find(TRUE);
    $dao->is_email_receipt = 0;
    $dao->save();
  }

  /**
   * Test cancellation works per CRM-14986.
   *
   * We are checking for absence of error.
   */
  public function testCancelRecur() {
    $contributionRecur = $this->callAPISuccess('contribution_recur', 'create', $this->_params);
    CRM_Contribute_BAO_ContributionRecur::cancelRecurContribution($contributionRecur['id'], CRM_Core_DAO::$_nullObject);
  }

}
