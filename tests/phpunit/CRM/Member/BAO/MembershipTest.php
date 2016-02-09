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
    Contact::delete($this->_contactID);

    $this->_contactID = $this->_membershipStatusID = $this->_membershipTypeID = NULL;
  }

  public function testCreate() {

    $contactId = Contact::createIndividual();

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
    Contact::delete($contactId);
  }

  public function testGetValues() {
    //        $this->markTestSkipped( 'causes mysterious exit, needs fixing!' );
    //  Calculate membership dates based on the current date
    $now = time();
    $year_from_now = $now + (365 * 24 * 60 * 60);
    $last_month = $now - (30 * 24 * 60 * 60);
    $year_from_last_month = $last_month + (365 * 24 * 60 * 60);

    $contactId = Contact::createIndividual();

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
    Contact::delete($contactId);
  }

  public function testRetrieve() {
    $contactId = Contact::createIndividual();

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
    $contactId = Contact::createIndividual();

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
    Contact::delete($contactId);
  }

  public function testDeleteMembership() {
    $contactId = Contact::createIndividual();

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

    $membershipId = $this->assertDBNull('CRM_Member_BAO_Membership', $contactId, 'id',
      'contact_id', 'Database check for deleted membership.'
    );
    Contact::delete($contactId);
  }

  public function testGetContactMembership() {
    $contactId = Contact::createIndividual();

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
    Contact::delete($contactId);
  }


  /**
   * Get the contribution.
   * page id from the membership record
   */
  public function testgetContributionPageId() {
    $contactId = Contact::createIndividual();

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
    Contact::delete($contactId);
  }

  /**
   * Get membership joins/renewals
   * for a specified membership
   * type.
   */
  public function testgetMembershipStarts() {
    $contactId = Contact::createIndividual();

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
    Contact::delete($contactId);
  }

  /**
   * Get a count of membership for a specified membership type,
   * optionally for a specified date.
   */
  public function testGetMembershipCount() {
    $contactId = Contact::createIndividual();

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
    Contact::delete($contactId);
  }


  /**
   * Take sort name of contact during
   * Update multiple memberships
   */
  public function testsortName() {
    $contactId = Contact::createIndividual();

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

    CRM_Member_BAO_Membership::sortName($membershipId);

    $this->assertDBCompareValue('CRM_Contact_DAO_Contact', $contactId, 'sort_name', 'id', 'Doe, John',
      'Database check for sort name record.'
    );

    $this->membershipDelete($membershipId);
    Contact::delete($contactId);
  }

  /**
   * Delete related memberships.
   */
  public function testdeleteRelatedMemberships() {
    $contactId = Contact::createIndividual();

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
    Contact::delete($contactId);
  }

  /**
   * Renew membership with change in membership type.
   */
  public function testRenewMembership() {
    $contactId = Contact::createIndividual();
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
    list($MembershipRenew) = CRM_Member_BAO_Membership::renewMembership(
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
    Contact::delete($contactId);
  }

  /**
   * Renew stale membership.
   */
  public function testStaleMembership() {
    $statusId = 3;
    $contactId = Contact::createIndividual();
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
    list($MembershipRenew) = CRM_Member_BAO_Membership::renewMembership(
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
    Contact::delete($contactId);
  }

}
