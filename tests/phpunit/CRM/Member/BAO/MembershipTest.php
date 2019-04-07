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
 * Class CRM_Member_BAO_MembershipTest
 * @group headless
 */
class CRM_Member_BAO_MembershipTest extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();
    // FIXME: something NULLs $GLOBALS['_HTML_QuickForm_registered_rules'] when the tests are ran all together
    $GLOBALS['_HTML_QuickForm_registered_rules'] = array(
      'required' => array('html_quickform_rule_required', 'HTML/QuickForm/Rule/Required.php'),
      'maxlength' => array('html_quickform_rule_range', 'HTML/QuickForm/Rule/Range.php'),
      'minlength' => array('html_quickform_rule_range', 'HTML/QuickForm/Rule/Range.php'),
      'rangelength' => array('html_quickform_rule_range', 'HTML/QuickForm/Rule/Range.php'),
      'email' => array('html_quickform_rule_email', 'HTML/QuickForm/Rule/Email.php'),
      'regex' => array('html_quickform_rule_regex', 'HTML/QuickForm/Rule/Regex.php'),
      'lettersonly' => array('html_quickform_rule_regex', 'HTML/QuickForm/Rule/Regex.php'),
      'alphanumeric' => array('html_quickform_rule_regex', 'HTML/QuickForm/Rule/Regex.php'),
      'numeric' => array('html_quickform_rule_regex', 'HTML/QuickForm/Rule/Regex.php'),
      'nopunctuation' => array('html_quickform_rule_regex', 'HTML/QuickForm/Rule/Regex.php'),
      'nonzero' => array('html_quickform_rule_regex', 'HTML/QuickForm/Rule/Regex.php'),
      'callback' => array('html_quickform_rule_callback', 'HTML/QuickForm/Rule/Callback.php'),
      'compare' => array('html_quickform_rule_compare', 'HTML/QuickForm/Rule/Compare.php'),
    );

    $this->_contactID = $this->organizationCreate();
    $this->_membershipTypeID = $this->membershipTypeCreate(array('member_of_contact_id' => $this->_contactID));
    // add a random number to avoid silly conflicts with old data
    $this->_membershipStatusID = $this->membershipStatusCreate('test status' . rand(1, 1000));
  }

  /**
   * Tears down the fixture, for example, closes a network connection.
   * This method is called after a test is executed.
   */
  public function tearDown() {
    $this->membershipTypeDelete(array('id' => $this->_membershipTypeID));
    $this->membershipStatusDelete($this->_membershipStatusID);
    $this->contactDelete($this->_contactID);

    $this->_contactID = $this->_membershipStatusID = $this->_membershipTypeID = NULL;
  }

  /**
   * Create membership type using given organization id.
   * @param $organizationId
   * @param bool $withRelationship
   * @return array|int
   */
  private function createMembershipType($organizationId, $withRelationship = FALSE) {
    $membershipType = $this->callAPISuccess('MembershipType', 'create', array(
      //Default domain ID
      'domain_id' => 1,
      'member_of_contact_id' => $organizationId,
      'financial_type_id' => "Member Dues",
      'duration_unit' => "year",
      'duration_interval' => 1,
      'period_type' => "rolling",
      'name' => "Organiation Membership Type",
      'relationship_type_id' => ($withRelationship) ? 5 : NULL,
      'relationship_direction' => ($withRelationship) ? 'b_a' : NULL,
    ));
    return $membershipType["values"][$membershipType["id"]];
  }

  /**
   * Get count of related memberships by parent membership id.
   * @param $membershipId
   * @return array|int
   */
  private function getRelatedMembershipsCount($membershipId) {
    return $this->callAPISuccess("Membership", "getcount", array(
      'owner_membership_id' => $membershipId,
    ));
  }

  /**
   * Test to delete related membership when type of parent memebrship is changed which does not have relation type associated.
   * @throws CRM_Core_Exception
   */
  public function testDeleteRelatedMembershipsOnParentTypeChanged() {

    $contactId = $this->individualCreate();
    $membershipOrganizationId = $this->organizationCreate();
    $organizationId = $this->organizationCreate();

    // Create relationship between organization and individual contact
    $this->callAPISuccess('Relationship', 'create', array(
      // Employer of relationship
      'relationship_type_id' => 5,
      'contact_id_a'         => $contactId,
      'contact_id_b'         => $organizationId,
      'is_active'            => 1,
    ));

    // Create two membership types one with relationship and one without.
    $membershipTypeWithRelationship = $this->createMembershipType($membershipOrganizationId, TRUE);
    $membershipTypeWithoutRelationship = $this->createMembershipType($membershipOrganizationId);

    // Creating membership of organisation
    $membership = $this->callAPISuccess("Membership", "create", array(
      'membership_type_id' => $membershipTypeWithRelationship["id"],
      'contact_id'         => $organizationId,
      'status_id'          => $this->_membershipStatusID,
    ));

    $membership = $membership["values"][$membership["id"]];

    // Check count of related memberships. It should be one for individual contact.
    $relatedMembershipsCount = $this->getRelatedMembershipsCount($membership["id"]);
    $this->assertEquals(1, $relatedMembershipsCount, 'Related membership count should be 1.');

    // Update membership by changing it's type. New membership type is without relationship.
    $membership["membership_type_id"] = $membershipTypeWithoutRelationship["id"];
    $updatedMembership = $this->callAPISuccess("Membership", "create", $membership);

    // Check count of related memberships again. It should be zero as we changed the membership type.
    $relatedMembershipsCount = $this->getRelatedMembershipsCount($membership["id"]);
    $this->assertEquals(0, $relatedMembershipsCount, 'Related membership count should be 0.');

    // Clean up: Delete membership
    $this->membershipDelete($membership["id"]);
  }

  public function testCreate() {

    $contactId = $this->individualCreate();

    $params = array(
      'contact_id' => $contactId,
      'membership_type_id' => $this->_membershipTypeID,
      'join_date' => date('Ymd', strtotime('2006-01-21')),
      'start_date' => date('Ymd', strtotime('2006-01-21')),
      'end_date' => date('Ymd', strtotime('2006-12-21')),
      'source' => 'Payment',
      'is_override' => 1,
      'status_id' => $this->_membershipStatusID,
    );
    $ids = array();
    CRM_Member_BAO_Membership::create($params, $ids);

    $membershipId = $this->assertDBNotNull('CRM_Member_BAO_Membership', $contactId, 'id',
      'contact_id', 'Database check for created membership.'
    );

    // Now call create() to modify an existing Membership

    $params = array();
    $params = array(
      'contact_id' => $contactId,
      'membership_type_id' => $this->_membershipTypeID,
      'join_date' => date('Ymd', strtotime('2006-01-21')),
      'start_date' => date('Ymd', strtotime('2006-01-21')),
      'end_date' => date('Ymd', strtotime('2006-12-21')),
      'source' => 'Payment',
      'is_override' => 1,
      'status_id' => $this->_membershipStatusID,
    );
    $ids = array(
      'membership' => $membershipId,
    );
    CRM_Member_BAO_Membership::create($params, $ids);

    $membershipTypeId = $this->assertDBNotNull('CRM_Member_BAO_Membership', $contactId,
      'membership_type_id', 'contact_id',
      'Database check on updated membership record.'
    );
    $this->assertEquals($membershipTypeId, $this->_membershipTypeID, 'Verify membership type id is fetched.');

    $this->membershipDelete($membershipId);
    $this->contactDelete($contactId);
  }

  public function testGetValues() {
    //        $this->markTestSkipped( 'causes mysterious exit, needs fixing!' );
    //  Calculate membership dates based on the current date
    $now = time();
    $year_from_now = $now + (365 * 24 * 60 * 60);
    $last_month = $now - (30 * 24 * 60 * 60);
    $year_from_last_month = $last_month + (365 * 24 * 60 * 60);

    $contactId = $this->individualCreate();

    $params = array(
      'contact_id' => $contactId,
      'membership_type_id' => $this->_membershipTypeID,
      'join_date' => date('Ymd'),
      'start_date' => date('Ymd'),
      'end_date' => date('Ymd', $year_from_now),
      'source' => 'Payment',
      'is_override' => 1,
      'status_id' => $this->_membershipStatusID,
    );

    $ids = array();
    CRM_Member_BAO_Membership::create($params, $ids);

    $membershipId1 = $this->assertDBNotNull('CRM_Member_BAO_Membership', $contactId, 'id',
      'contact_id', 'Database check for created membership.'
    );

    $params = array(
      'contact_id' => $contactId,
      'membership_type_id' => $this->_membershipTypeID,
      'join_date' => date('Ymd', $last_month),
      'start_date' => date('Ymd', $last_month),
      'end_date' => date('Ymd', $year_from_last_month),
      'source' => 'Source123',
      'is_override' => 0,
      'status_id' => $this->_membershipStatusID,
    );
    $ids = array();
    CRM_Member_BAO_Membership::create($params, $ids);

    $membershipId2 = $this->assertDBNotNull('CRM_Member_BAO_Membership', 'source123', 'id',
      'source', 'Database check for created membership.'
    );

    $membership = array('contact_id' => $contactId);
    $membershipValues = array();
    CRM_Member_BAO_Membership::getValues($membership, $membershipValues, TRUE);

    $this->assertEquals($membershipValues[$membershipId1]['membership_id'], $membershipId1, 'Verify membership record 1 is fetched.');

    $this->assertEquals($membershipValues[$membershipId2]['membership_id'], $membershipId2, 'Verify membership record 2 is fetched.');

    $this->membershipDelete($membershipId1);
    $this->membershipDelete($membershipId2);
    $this->contactDelete($contactId);
  }

  public function testRetrieve() {
    $contactId = $this->individualCreate();

    $params = array(
      'contact_id' => $contactId,
      'membership_type_id' => $this->_membershipTypeID,
      'join_date' => date('Ymd', strtotime('2006-01-21')),
      'start_date' => date('Ymd', strtotime('2006-01-21')),
      'end_date' => date('Ymd', strtotime('2006-12-21')),
      'source' => 'Payment',
      'is_override' => 1,
      'status_id' => $this->_membershipStatusID,
    );
    $ids = array();
    CRM_Member_BAO_Membership::create($params, $ids);

    $membershipId = $this->assertDBNotNull('CRM_Member_BAO_Membership', $contactId, 'id',
      'contact_id', 'Database check for created membership.'
    );
    $params = array('id' => $membershipId);
    $values = array();
    CRM_Member_BAO_Membership::retrieve($params, $values);
    $this->assertEquals($values['id'], $membershipId, 'Verify membership record is retrieved.');

    $this->membershipDelete($membershipId);
    $this->contactDelete($contactId);
  }

  public function testActiveMembers() {
    $contactId = $this->individualCreate();

    $params = array(
      'contact_id' => $contactId,
      'membership_type_id' => $this->_membershipTypeID,
      'join_date' => date('Ymd', strtotime('2006-01-21')),
      'start_date' => date('Ymd', strtotime('2006-01-21')),
      'end_date' => date('Ymd', strtotime('2006-12-21')),
      'source' => 'Payment',
      'is_override' => 1,
      'status_id' => $this->_membershipStatusID,
    );
    $ids = array();
    CRM_Member_BAO_Membership::create($params, $ids);

    $membershipId1 = $this->assertDBNotNull('CRM_Member_BAO_Membership', $contactId, 'id',
      'contact_id', 'Database check for created membership.'
    );

    $params = array('id' => $membershipId1);
    $values1 = array();
    CRM_Member_BAO_Membership::retrieve($params, $values1);
    $membership = array($membershipId1 => $values1);

    $params = array(
      'contact_id' => $contactId,
      'membership_type_id' => $this->_membershipTypeID,
      'join_date' => date('Ymd', strtotime('2006-01-21')),
      'start_date' => date('Ymd', strtotime('2006-01-21')),
      'end_date' => date('Ymd', strtotime('2006-12-21')),
      'source' => 'PaySource',
      'is_override' => 1,
      'status_id' => $this->_membershipStatusID,
    );
    $ids = array();
    CRM_Member_BAO_Membership::create($params, $ids);

    $membershipId2 = $this->assertDBNotNull('CRM_Member_BAO_Membership', 'PaySource', 'id',
      'source', 'Database check for created membership.'
    );

    $params = array('id' => $membershipId2);
    $values2 = array();
    CRM_Member_BAO_Membership::retrieve($params, $values2);
    $membership[$membershipId2] = $values2;

    $activeMembers = CRM_Member_BAO_Membership::activeMembers($membership);
    $inActiveMembers = CRM_Member_BAO_Membership::activeMembers($membership, 'inactive');

    $this->assertEquals($activeMembers[$membershipId1]['id'], $membership[$membershipId1]['id'], 'Verify active membership record is retrieved.');
    $this->assertEquals($activeMembers[$membershipId2]['id'], $membership[$membershipId2]['id'], 'Verify active membership record is retrieved.');

    $this->assertEquals(0, count($inActiveMembers), 'Verify No inactive membership record is retrieved.');

    $this->membershipDelete($membershipId1);
    $this->membershipDelete($membershipId2);
    $this->contactDelete($contactId);
  }

  public function testDeleteMembership() {
    $contactId = $this->individualCreate();

    $params = array(
      'contact_id' => $contactId,
      'membership_type_id' => $this->_membershipTypeID,
      'join_date' => date('Ymd', strtotime('2006-01-21')),
      'start_date' => date('Ymd', strtotime('2006-01-21')),
      'end_date' => date('Ymd', strtotime('2006-12-21')),
      'source' => 'Payment',
      'is_override' => 1,
      'status_id' => $this->_membershipStatusID,
    );
    $ids = array();
    CRM_Member_BAO_Membership::create($params, $ids);

    $membershipId = $this->assertDBNotNull('CRM_Member_BAO_Membership', $contactId, 'id',
      'contact_id', 'Database check for created membership.'
    );
    CRM_Member_BAO_Membership::del($membershipId);

    $this->assertDBNull('CRM_Member_BAO_Membership', $contactId, 'id',
      'contact_id', 'Database check for deleted membership.'
    );
    $this->contactDelete($contactId);
  }

  public function testGetContactMembership() {
    $contactId = $this->individualCreate();

    $params = array(
      'contact_id' => $contactId,
      'membership_type_id' => $this->_membershipTypeID,
      'join_date' => date('Ymd', strtotime('2006-01-21')),
      'start_date' => date('Ymd', strtotime('2006-01-21')),
      'end_date' => date('Ymd', strtotime('2006-12-21')),
      'source' => 'Payment',
      'is_override' => 1,
      'status_id' => $this->_membershipStatusID,
    );
    $ids = array();
    CRM_Member_BAO_Membership::create($params, $ids);

    $membershipId = $this->assertDBNotNull('CRM_Member_BAO_Membership', $contactId, 'id',
      'contact_id', 'Database check for created membership.'
    );

    $membership = CRM_Member_BAO_Membership::getContactMembership($contactId, $this->_membershipTypeID, FALSE);

    $this->assertEquals($membership['id'], $membershipId, 'Verify membership record is retrieved.');

    $this->membershipDelete($membershipId);
    $this->contactDelete($contactId);
  }

  /**
   * Get the contribution.
   * page id from the membership record
   */
  public function testgetContributionPageId() {
    $contactId = $this->individualCreate();

    $params = array(
      'contact_id' => $contactId,
      'membership_type_id' => $this->_membershipTypeID,
      'join_date' => date('Ymd', strtotime('2006-01-21')),
      'start_date' => date('Ymd', strtotime('2006-01-21')),
      'end_date' => date('Ymd', strtotime('2006-12-21')),
      'source' => 'Payment',
      'is_override' => 1,
      'status_id' => $this->_membershipStatusID,
    );
    $ids = array();
    CRM_Member_BAO_Membership::create($params, $ids);

    $membershipId = $this->assertDBNotNull('CRM_Member_BAO_Membership', $contactId, 'id',
      'contact_id', 'Database check for created membership.'
    );
    $membership[$membershipId]['renewPageId'] = CRM_Member_BAO_Membership::getContributionPageId($membershipId);

    $this->membershipDelete($membershipId);
    $this->contactDelete($contactId);
  }

  /**
   * Get membership joins/renewals
   * for a specified membership
   * type.
   */
  public function testgetMembershipStarts() {
    $contactId = $this->individualCreate();

    $params = array(
      'contact_id' => $contactId,
      'membership_type_id' => $this->_membershipTypeID,
      'join_date' => date('Ymd', strtotime('2006-01-21')),
      'start_date' => date('Ymd', strtotime('2006-01-21')),
      'end_date' => date('Ymd', strtotime('2006-12-21')),
      'source' => 'Payment',
      'is_override' => 1,
      'status_id' => $this->_membershipStatusID,
    );
    $ids = array();
    CRM_Member_BAO_Membership::create($params, $ids);

    $membershipId = $this->assertDBNotNull('CRM_Member_BAO_Membership', $contactId, 'id',
      'contact_id', 'Database check for created membership.'
    );
    $yearStart = date('Y') . '0101';
    $currentDate = date('Ymd');
    CRM_Member_BAO_Membership::getMembershipStarts($this->_membershipTypeID, $yearStart, $currentDate);

    $this->membershipDelete($membershipId);
    $this->contactDelete($contactId);
  }

  /**
   * Get a count of membership for a specified membership type,
   * optionally for a specified date.
   */
  public function testGetMembershipCount() {
    $contactId = $this->individualCreate();

    $params = array(
      'contact_id' => $contactId,
      'membership_type_id' => $this->_membershipTypeID,
      'join_date' => date('Ymd', strtotime('2006-01-21')),
      'start_date' => date('Ymd', strtotime('2006-01-21')),
      'end_date' => date('Ymd', strtotime('2006-12-21')),
      'source' => 'Payment',
      'is_override' => 1,
      'status_id' => $this->_membershipStatusID,
    );
    $ids = array();
    CRM_Member_BAO_Membership::create($params, $ids);

    $membershipId = $this->assertDBNotNull('CRM_Member_BAO_Membership', $contactId, 'id',
      'contact_id', 'Database check for created membership.'
    );
    $currentDate = date('Ymd');
    $test = 0;
    CRM_Member_BAO_Membership::getMembershipCount($this->_membershipTypeID, $currentDate, $test);

    $this->membershipDelete($membershipId);
    $this->contactDelete($contactId);
  }

  /**
   * Checkup sort name function.
   */
  public function testSortName() {
    $contactId = $this->individualCreate();

    $params = array(
      'contact_id' => $contactId,
      'membership_type_id' => $this->_membershipTypeID,
      'join_date' => '2006-01-21',
      'start_date' => '2006-01-21',
      'end_date' => '2006-12-21',
      'source' => 'Payment',
      'is_override' => 1,
      'status_id' => $this->_membershipStatusID,
    );

    $membership = $this->callAPISuccess('Membership', 'create', $params);

    $this->assertEquals('Anderson, Anthony', CRM_Member_BAO_Membership::sortName($membership['id']));

    $this->membershipDelete($membership['id']);
    $this->contactDelete($contactId);
  }

  /**
   * Delete related memberships.
   */
  public function testdeleteRelatedMemberships() {
    $contactId = $this->individualCreate();

    $params = array(
      'contact_id' => $contactId,
      'membership_type_id' => $this->_membershipTypeID,
      'join_date' => date('Ymd', strtotime('2006-01-21')),
      'start_date' => date('Ymd', strtotime('2006-01-21')),
      'end_date' => date('Ymd', strtotime('2006-12-21')),
      'source' => 'Payment',
      'is_override' => 1,
      'status_id' => $this->_membershipStatusID,
    );
    $ids = array();

    CRM_Member_BAO_Membership::create($params, $ids);

    $membershipId = $this->assertDBNotNull('CRM_Member_BAO_Membership', $contactId, 'id',
      'contact_id', 'Database check for created membership.'
    );

    CRM_Member_BAO_Membership::deleteRelatedMemberships($membershipId);

    $this->membershipDelete($membershipId);
    $this->contactDelete($contactId);
  }

  /**
   * Renew membership with change in membership type.
   */
  public function testRenewMembership() {
    $contactId = $this->individualCreate();
    $joinDate = $startDate = date("Ymd", strtotime(date("Ymd") . " -6 month"));
    $endDate = date("Ymd", strtotime($joinDate . " +1 year -1 day"));
    $params = array(
      'contact_id' => $contactId,
      'membership_type_id' => $this->_membershipTypeID,
      'join_date' => $joinDate,
      'start_date' => $startDate,
      'end_date' => $endDate,
      'source' => 'Payment',
      'is_override' => 1,
      'status_id' => $this->_membershipStatusID,
    );
    $ids = array();
    $membership = CRM_Member_BAO_Membership::create($params, $ids);
    $membershipId = $this->assertDBNotNull('CRM_Member_BAO_Membership', $contactId, 'id',
      'contact_id', 'Database check for created membership.'
    );

    $this->assertDBNotNull('CRM_Member_BAO_MembershipLog',
      $membership->id,
      'id',
      'membership_id',
      'Database checked on membershiplog record.'
    );

    // this is a test and we dont want qfKey generation / validation
    // easier to suppress it, than change core code
    $config = CRM_Core_Config::singleton();
    $config->keyDisable = TRUE;

    $isTestMembership = 0;
    list($MembershipRenew) = CRM_Member_BAO_Membership::processMembership(
      $contactId,
      $this->_membershipTypeID,
      $isTestMembership,
      NULL,
      NULL,
      NULL,
      1,
      FALSE,
      NULL,
      NULL,
      FALSE,
      NULL,
      NULL
    );
    $endDate = date("Y-m-d", strtotime($membership->end_date . " +1 year"));

    $this->assertDBNotNull('CRM_Member_BAO_MembershipLog',
      $MembershipRenew->id,
      'id',
      'membership_id',
      'Database checked on membershiplog record.'
    );
    $this->assertEquals($this->_membershipTypeID, $MembershipRenew->membership_type_id, 'Verify membership type is changed during renewal.');
    $this->assertEquals($endDate, $MembershipRenew->end_date, 'Verify correct end date is calculated after membership renewal');

    $this->membershipDelete($membershipId);
    $this->contactDelete($contactId);
  }

  /**
   * Renew stale membership.
   */
  public function testStaleMembership() {
    $statusId = 3;
    $contactId = $this->individualCreate();
    $joinDate = $startDate = date("Ymd", strtotime(date("Ymd") . " -1 year -15 days"));
    $endDate = date("Ymd", strtotime($joinDate . " +1 year -1 day"));
    $params = array(
      'contact_id' => $contactId,
      'membership_type_id' => $this->_membershipTypeID,
      'join_date' => $joinDate,
      'start_date' => $startDate,
      'end_date' => $endDate,
      'source' => 'Payment',
      'status_id' => $statusId,
    );

    $ids = array();
    $membership = CRM_Member_BAO_Membership::create($params, $ids);

    $membershipId = $this->assertDBNotNull('CRM_Member_BAO_Membership', $contactId, 'id',
      'contact_id', 'Database check for created membership.'
    );

    $this->assertEquals($membership->status_id, $statusId, 'Verify correct status id is calculated.');
    $this->assertEquals($membership->membership_type_id, $this->_membershipTypeID,
      'Verify correct membership type id.'
    );

    //verify all dates.
    $dates = array(
      'startDate' => 'start_date',
      'joinDate' => 'join_date',
      'endDate' => 'end_date',
    );

    foreach ($dates as $date => $dbDate) {
      $this->assertEquals($membership->$dbDate, $$date,
        "Verify correct {$date} is present."
      );
    }

    $this->assertDBNotNull('CRM_Member_BAO_MembershipLog',
      $membership->id,
      'id',
      'membership_id',
      'Database checked on membershiplog record.'
    );

    // this is a test and we dont want qfKey generation / validation
    // easier to suppress it, than change core code
    $config = CRM_Core_Config::singleton();
    $config->keyDisable = TRUE;

    $membershipRenewal = new CRM_Core_Form();
    $membershipRenewal->controller = new CRM_Core_Controller();
    list($MembershipRenew) = CRM_Member_BAO_Membership::processMembership(
      $contactId,
      $this->_membershipTypeID,
      FALSE,
      $membershipRenewal,
      NULL,
      NULL,
      NULL,
      1,
      NULL,
      NULL,
      NULL,
      FALSE,
      NULL
    );

    $this->assertDBNotNull('CRM_Member_BAO_MembershipLog',
      $MembershipRenew->id,
      'id',
      'membership_id',
      'Database checked on membershiplog record.'
    );

    $this->membershipDelete($membershipId);
    $this->contactDelete($contactId);
  }

  public function testUpdateAllMembershipStatusConvertExpiredOverriddenStatusToNormal() {
    $params = array(
      'contact_id' => $this->individualCreate(),
      'membership_type_id' => $this->_membershipTypeID,
      'join_date' => date('Ymd', time()),
      'start_date' => date('Ymd', time()),
      'end_date' => date('Ymd', strtotime('+1 year')),
      'source' => 'Payment',
      'is_override' => 1,
      'status_override_end_date' => date('Ymd', strtotime('-1 day')),
      'status_id' => $this->_membershipStatusID,
    );
    $ids = array();
    $createdMembership = CRM_Member_BAO_Membership::create($params, $ids);

    CRM_Member_BAO_Membership::updateAllMembershipStatus();

    $membershipAfterProcess = civicrm_api3('Membership', 'get', array(
      'sequential' => 1,
      'id' => $createdMembership->id,
      'return' => array('id', 'is_override', 'status_override_end_date'),
    ))['values'][0];

    $this->assertEquals($createdMembership->id, $membershipAfterProcess['id']);
    $this->assertArrayNotHasKey('is_override', $membershipAfterProcess);
    $this->assertArrayNotHasKey('status_override_end_date', $membershipAfterProcess);
  }

  public function testUpdateAllMembershipStatusHandleOverriddenWithEndOverrideDateEqualTodayAsExpired() {
    $params = array(
      'contact_id' => $this->individualCreate(),
      'membership_type_id' => $this->_membershipTypeID,
      'join_date' => date('Ymd', time()),
      'start_date' => date('Ymd', time()),
      'end_date' => date('Ymd', strtotime('+1 year')),
      'source' => 'Payment',
      'is_override' => 1,
      'status_override_end_date' => date('Ymd', time()),
      'status_id' => $this->_membershipStatusID,
    );
    $ids = array();
    $createdMembership = CRM_Member_BAO_Membership::create($params, $ids);

    CRM_Member_BAO_Membership::updateAllMembershipStatus();

    $membershipAfterProcess = civicrm_api3('Membership', 'get', array(
      'sequential' => 1,
      'id' => $createdMembership->id,
      'return' => array('id', 'is_override', 'status_override_end_date'),
    ))['values'][0];

    $this->assertEquals($createdMembership->id, $membershipAfterProcess['id']);
    $this->assertArrayNotHasKey('is_override', $membershipAfterProcess);
    $this->assertArrayNotHasKey('status_override_end_date', $membershipAfterProcess);
  }

  public function testUpdateAllMembershipStatusDoesNotConvertOverridenMembershipWithoutEndOverrideDateToNormal() {
    $params = array(
      'contact_id' => $this->individualCreate(),
      'membership_type_id' => $this->_membershipTypeID,
      'join_date' => date('Ymd', time()),
      'start_date' => date('Ymd', time()),
      'end_date' => date('Ymd', strtotime('+1 year')),
      'source' => 'Payment',
      'is_override' => 1,
      'status_id' => $this->_membershipStatusID,
    );
    $ids = array();
    $createdMembership = CRM_Member_BAO_Membership::create($params, $ids);

    CRM_Member_BAO_Membership::updateAllMembershipStatus();

    $membershipAfterProcess = civicrm_api3('Membership', 'get', array(
      'sequential' => 1,
      'id' => $createdMembership->id,
      'return' => array('id', 'is_override', 'status_override_end_date'),
    ))['values'][0];

    $this->assertEquals($createdMembership->id, $membershipAfterProcess['id']);
    $this->assertEquals(1, $membershipAfterProcess['is_override']);
  }

}
