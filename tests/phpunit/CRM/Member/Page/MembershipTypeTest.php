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
 *  Test CRM_Member_Form_Membership functions.
 *
 * @package   CiviCRM
 * @group headless
 */
class CRM_Member_Page_MembershipTypeTest extends CiviUnitTestCase {

  /**
   * Test the membership type page loads correctly.
   */
  public function testMembershipTypePage(): void {
    $page = new CRM_Member_Page_MembershipType();
    $id = $this->membershipTypeCreate(['weight' => 1]);
    $page->browse();
    $assigned = CRM_Core_Smarty::singleton()->get_template_vars();
    $this->assertEquals([
      $id => [
        'id' => '1',
        'domain_id' => '1',
        'name' => 'General',
        'member_of_contact_id' => '3',
        'financial_type_id' => '2',
        'minimum_fee' => 0.0,
        'duration_unit' => 'year',
        'duration_interval' => '1',
        'period_type' => 'Rolling',
        'visibility' => 'Public',
        'weight' => '1',
        'auto_renew' => FALSE,
        'is_active' => TRUE,
        'fixed_period_start_day' => NULL,
        'fixed_period_rollover_day' => NULL,
        'max_related' => NULL,
        'relationship_type_id' => NULL,
        'relationship_direction' => NULL,
        'period_type:label' => 'Rolling',
        'visibility:label' => 'Public',
        'relationshipTypeName' => NULL,
        'order' => NULL,
        'action' => '<span><a href="/index.php?q=civicrm/admin/member/membershipType/add&amp;action=update&amp;id=' . $id . '&amp;reset=1" class="action-item crm-hover-button" title=\'Edit Membership Type\' >Edit</a><a href="#" class="action-item crm-hover-button crm-enable-disable" title=\'Disable Membership Type\' >Disable</a><a href="/index.php?q=civicrm/admin/member/membershipType/add&amp;action=delete&amp;id=' . $id . '" class="action-item crm-hover-button small-popup" title=\'Delete Membership Type\' >Delete</a></span>',
      ],
    ], $assigned['rows']);
  }

}
