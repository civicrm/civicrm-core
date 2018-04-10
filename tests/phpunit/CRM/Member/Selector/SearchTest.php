<?php

/**
 * Class CRM_Member_Selector_SearchTest
 * @group headless
 */
class CRM_Member_Selector_SearchTest extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();

    //here, we would create two memberships for the same contact, one expired and one current
    $this->_indiviParams = array(
      'first_name' => 'Foo',
      'last_name' => 'Bar',
      'contact_type' => 'Individual',
    );

    $this->_contactId = $this->individualCreate($this->_indiviParams);

    $this->_membershipTypeID = $this->membershipTypeCreate(array('member_of_contact_id' => $this->_contactId));

    $this->_membershipStatusID = $this->membershipStatusCreate('test status' . rand(1, 1000));

    //expired membership
    $expired_membership_params = array(
      'contact_id' => $this->_contactId,
      'membership_type_id' => $this->_membershipTypeID,
      'join_date' => date('Ymd', strtotime('2007-03-21')),
      'start_date' => date('Ymd', strtotime('2007-03-21')),
      'end_date' => date('Ymd', strtotime('2007-12-21')),
      'source' => 'Payment',
      'is_override' => 1,
      'status_id' => $this->_membershipStatusID,
    );

    //current membership
    $current_membership_params = array(
      'contact_id' => $this->_contactId,
      'membership_type_id' => $this->_membershipTypeID,
      'join_date' => date('Ymd', strtotime('2007-03-21')),
      'start_date' => date('Ymd', strtotime('2007-03-21')),
      'end_date' => date('Ymd', strtotime('+ 5 years')),
      'source' => 'Payment',
      'is_override' => 1,
      'status_id' => $this->_membershipStatusID,
    );

    $ids = array();

    //create expired membership
    CRM_Member_BAO_Membership::create($expired_membership_params, $ids);

    //create current membership
    CRM_Member_BAO_Membership::create($current_membership_params, $ids);
  }

  public function tearDown() {
    parent::setUp();
  }

  /**
   * Search where contact has multiple memberships but at least one is active
   */
  public function testSearchMultipleMembershipWithAtLeastOneActive() {

    $activeMembership = civicrm_api3('Membership', 'getcount', array(
        'contact_id' => $this->_contactID,
        'active_only' => 1,
    ));

    $this->assertEquals($activeMembership, true);

  }


}
