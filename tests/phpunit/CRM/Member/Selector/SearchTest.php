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
 * Class CRM_Member_BAO_MembershipTest
 *
 * @group headless
 */
class CRM_Member_Selector_SearchTest extends CiviUnitTestCase {

  /**
   * Test results from getRows.
   *
   * @throws \CRM_Core_Exception
   */
  public function testSelectorGetRows() {
    $this->_contactID = $this->individualCreate();
    $this->_invoiceID = 1234;
    $this->_contributionPageID = NULL;
    $this->_paymentProcessorID = $this->paymentProcessorCreate();
    $this->setupMembershipRecurringPaymentProcessorTransaction();
    $membership = $this->callAPISuccessGetSingle('Membership', ['contact_id' => $this->_contactID]);
    $membershipID = $membership['id'];
    $params = [];
    $selector = new CRM_Member_Selector_Search($params);
    $rows = $selector->getRows(CRM_Core_Permission::VIEW, 0, 25, NULL);
    $this->assertEquals([
      'contact_id' => $this->_contactID,
      'membership_id' => $membershipID,
      'contact_type' => '<a href="/index.php?q=civicrm/profile/view&amp;reset=1&amp;gid=7&amp;id=' . $this->_contactID . '&amp;snippet=4" class="crm-summary-link"><div class="icon crm-icon Individual-icon"></div></a>',
      'sort_name' => 'Anderson, Anthony',
      'membership_type' => 'General',
      'membership_join_date' => date('Y-m-d'),
      'membership_start_date' => date('Y-m-d'),
      'membership_end_date' => $membership['end_date'],
      'membership_source' => 'Payment',
      'member_is_test' => '0',
      'owner_membership_id' => NULL,
      'membership_status' => 'New',
      'member_campaign_id' => NULL,
      'campaign' => NULL,
      'campaign_id' => NULL,
      'checkbox' => 'mark_x_1',
      'action' => '<span><a href="/index.php?q=civicrm/contact/view/membership&amp;reset=1&amp;id=1&amp;cid=' . $this->_contactID . '&amp;action=view&amp;context=search&amp;selectedChild=member&amp;compContext=membership" class="action-item crm-hover-button" title=\'View Membership\' >View</a><a href="/index.php?q=civicrm/contact/view/membership&amp;reset=1&amp;action=update&amp;id=' . $membershipID . '&amp;cid=' . $this->_contactID . '&amp;context=search&amp;compContext=membership" class="action-item crm-hover-button" title=\'Edit Membership\' >Edit</a></span><span class=\'btn-slide crm-hover-button\'>Renew...<ul class=\'panel\'><li><a href="/index.php?q=civicrm/contact/view/membership&amp;reset=1&amp;action=delete&amp;id=' . $membershipID . '&amp;cid=' . $this->_contactID . '&amp;context=search&amp;compContext=membership" class="action-item crm-hover-button small-popup" title=\'Delete Membership\' >Delete</a></li><li><a href="/index.php?q=civicrm/contact/view/membership&amp;reset=1&amp;action=renew&amp;id=' . $membershipID . '&amp;cid=' . $this->_contactID . '&amp;context=search&amp;compContext=membership" class="action-item crm-hover-button" title=\'Renew Membership\' >Renew</a></li><li><a href="/index.php?q=civicrm/contribute/unsubscribe&amp;reset=1&amp;mid=' . $membershipID . '&amp;context=search&amp;compContext=membership" class="action-item crm-hover-button" title=\'Cancel Auto Renew Subscription\' >Cancel Auto-renewal</a></li></ul></span>',
      'auto_renew' => 1,
    ], $rows[0]);
    $this->assertCount(1, $rows);

    //Verify if NULL search on source returns the row correctly.
    $params = [['membership_source', 'IS NOT NULL', '', 1, 0]];
    $selector = new CRM_Member_Selector_Search($params);
    $rows = $selector->getRows(CRM_Core_Permission::VIEW, 0, 25, NULL);
    $this->assertCount(1, $rows);
  }

}
