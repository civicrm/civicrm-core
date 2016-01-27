<?php
require_once 'Contact.php';

/**
 * Class Membership
 */
class Membership extends PHPUnit_Framework_Testcase {
  /**
   * Helper function to create membership type.
   */
  public function createMembershipType() {
    $orgId = Contact::createOrganisation();

    $ids = array('memberOfContact' => $orgId);

    $params = array(
      'name' => 'Test Type',
      'description' => 'test membership type',
      'minimum_fee' => 111,
      'duration_unit' => 'year',
      'period_type' => 'rolling',
      'duration_interval' => 1,
      'member_org' => NULL,
      'fixed_period_start_day' => NULL,
      'fixed_period_rollover_day' => NULL,
      'action' => 1,
      'financial_type_id' => 1,
      'relationship_type_id' => 4,
      'visibility' => 'Public',
      'weight' => 4,
      'is_active' => 1,
      'contact_check' => 1,
      'relationship_direction' => 'a_b',
      'member_of_contact_id' => $orgId,
    );

    $membershipType = CRM_Member_BAO_MembershipType::add($params, $ids);
    $membershipType->orgnizationID = $orgId;
    return $membershipType;
  }

  /**
   * Helper function to create membership block for contribution page.
   * @param $membershipType
   * @param $contributionPageId
   * @return $this
   */
  public function createMembershipBlock($membershipType, $contributionPageId) {
    $param = array(
      'is_active' => 1,
      'new_title' => 'Membership Fees',
      'new_text' => 'text for membership fees',
      'renewal_title' => 'Membership Renewal title',
      'renewal_text' => 'Membership renewal text',
      'is_required' => 1,
      'display_min_fee' => 1,
      'membership_type' => array(
        $membershipType => 1,
      ),
      'membership_type_default' => NULL,
      'membership_types' => $membershipType,
      'is_separate_payment' => 0,
      'entity_table' => 'civicrm_contribution_page',
      'entity_id' => $contributionPageId,
    );

    $dao = new CRM_Member_DAO_MembershipBlock();
    $dao->copyValues($param);
    return $dao->save();
  }

  /**
   * Helper function to delete the membership block.
   * @param $blcokId
   */
  public function deleteMembershipBlock($blcokId) {
    $dao = new CRM_Member_DAO_MembershipBlock();
    $dao->id = $blcokId;
    if ($dao->find(TRUE)) {
      $dao->delete();
    }
  }

}
