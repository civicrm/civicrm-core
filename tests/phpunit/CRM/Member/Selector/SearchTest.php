<?php

/**
 * Class CRM_Member_Selector_SearchTest
 * @group headless
 */
class CRM_Member_Selector_SearchTest extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();
  }

  public function tearDown() {
    parent::setUp();
  }

  /**
   * Search where contact has multiple memberships but at least one is active
   */
  public function testSearchMultipleMembershipWithAtLeastOneActive() {

    //here, we would create two memberships for the same contact, one expired and one current
    $_contactID = $this->individualCreate();

    $_membershipTypeID = $this->membershipTypeCreate(array('member_of_contact_id' => $_contactID));

    $_membershipStatusID = $this->membershipStatusCreate('test status' . rand(1, 1000));

    //expired membership
    $expired_membership_params = array(
      'contact_id' => $_contactID,
      'membership_type_id' => $_membershipTypeID,
      'join_date' => date('Ymd', strtotime('2007-03-21')),
      'start_date' => date('Ymd', strtotime('2007-03-21')),
      'end_date' => date('Ymd', strtotime('2007-12-21')),
      'source' => 'Payment',
      'is_override' => 1,
      'status_id' => $_membershipStatusID,
    );

    //current membership
    $current_membership_params = array(
      'contact_id' => $_contactID,
      'membership_type_id' => $_membershipTypeID,
      'join_date' => date('Ymd', strtotime('2007-03-21')),
      'start_date' => date('Ymd', strtotime('2007-03-21')),
      'end_date' => date('Ymd', strtotime('+ 5 years')),
      'source' => 'Payment',
      'is_override' => 1,
      'status_id' => $_membershipStatusID,
    );

    $ids = array();

    //create expired membership
    CRM_Member_BAO_Membership::create($expired_membership_params, $ids);

    //create current membership
    CRM_Member_BAO_Membership::create($current_membership_params, $ids);

    $activeMembership = civicrm_api3('Membership', 'getcount', array(
        'contact_id' => $_contactID,
        'active_only' => 1,
    ));

    $this->assertEquals($activeMembership, TRUE);

  }

}
