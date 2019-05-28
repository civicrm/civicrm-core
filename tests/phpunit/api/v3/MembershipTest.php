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
 *  Test APIv3 civicrm_membership functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Member
 */

/**
 * Class api_v3_MembershipTest
 * @group headless
 */
class api_v3_MembershipTest extends CiviUnitTestCase {
  protected $_apiversion;
  protected $_contactID;
  protected $_membershipID;
  protected $_membershipID2;
  protected $_membershipID3;
  protected $_membershipTypeID;
  protected $_membershipTypeID2;
  protected $_membershipStatusID;
  protected $_entity;
  protected $_params;

  /**
   * Set up for tests.
   */
  public function setUp() {
    parent::setUp();
    $this->_apiversion = 3;
    $this->_contactID = $this->individualCreate();
    $this->_membershipTypeID = $this->membershipTypeCreate(array('member_of_contact_id' => $this->_contactID));
    $this->_membershipTypeID2 = $this->membershipTypeCreate(array(
      'period_type' => 'fixed',
       // Ie. 1 March.
      'fixed_period_start_day' => '301',
      // Ie. 11 Nov.
      'fixed_period_rollover_day' => '1111',
      'name' => 'Another one',
    ));
    $this->_membershipStatusID = $this->membershipStatusCreate('test status');

    CRM_Member_PseudoConstant::membershipType(NULL, TRUE);
    CRM_Member_PseudoConstant::membershipStatus(NULL, NULL, 'name', TRUE);
    CRM_Core_PseudoConstant::activityType(TRUE, TRUE, TRUE, 'name');

    $this->_entity = 'Membership';
    $this->_params = array(
      'contact_id' => $this->_contactID,
      'membership_type_id' => $this->_membershipTypeID,
      'join_date' => '2009-01-21',
      'start_date' => '2009-01-21',
      'end_date' => '2009-12-21',
      'source' => 'Payment',
      'is_override' => 1,
      'status_id' => $this->_membershipStatusID,
    );
  }

  /**
   * Clean up after tests.
   *
   * @throws \Exception
   */
  public function tearDown() {
    $this->quickCleanup(array(
      'civicrm_membership',
      'civicrm_membership_payment',
      'civicrm_membership_log',
      'civicrm_uf_match',
    ),
      TRUE
    );
    $this->membershipStatusDelete($this->_membershipStatusID);
    $this->membershipTypeDelete(array('id' => $this->_membershipTypeID2));
    $this->membershipTypeDelete(array('id' => $this->_membershipTypeID));
    $this->contactDelete($this->_contactID);

  }

  /**
   * Test membership deletion.
   */
  public function testMembershipDelete() {
    $membershipID = $this->contactMembershipCreate($this->_params);
    $this->assertDBRowExist('CRM_Member_DAO_Membership', $membershipID);
    $params = array(
      'id' => $membershipID,
    );
    $this->callAPIAndDocument('membership', 'delete', $params, __FUNCTION__, __FILE__);
    $this->assertDBRowNotExist('CRM_Member_DAO_Membership', $membershipID);
  }

  public function testMembershipDeleteEmpty() {
    $this->callAPIFailure('membership', 'delete', array());
  }

  public function testMembershipDeleteInvalidID() {
    $this->callAPIFailure('membership', 'delete', array('id' => 'blah'));
  }

  /**
   * Test civicrm_membership_delete() with invalid Membership Id.
   */
  public function testMembershipDeleteWithInvalidMembershipId() {
    $membershipId = 'membership';
    $this->callAPIFailure('membership', 'delete', $membershipId);
  }

  /**
   * Test membership deletion and with the preserve contribution param.
   */
  public function testMembershipDeletePreserveContribution() {
    //DELETE
    $membershipID = $this->contactMembershipCreate($this->_params);
    //DELETE
    $this->assertDBRowExist('CRM_Member_DAO_Membership', $membershipID);
    $ContributionCreate = $this->callAPISuccess('Contribution', 'create', array(
      'sequential' => 1,
      'financial_type_id' => "Member Dues",
      'total_amount' => 100,
      'contact_id' => $this->_params['contact_id'],
    ));
    $membershipPaymentCreate = $this->callAPISuccess('MembershipPayment', 'create', array(
      'sequential' => 1,
      'contribution_id' => $ContributionCreate['values'][0]['id'],
      'membership_id' => $membershipID,
    ));
    $memParams = array(
      'id' => $membershipID,
      'preserve_contribution' => 1,
    );
    $contribParams = array(
      'id' => $ContributionCreate['values'][0]['id'],
    );
    $this->callAPIAndDocument('membership', 'delete', $memParams, __FUNCTION__, __FILE__);
    $this->assertDBRowNotExist('CRM_Member_DAO_Membership', $membershipID);
    $this->assertDBRowExist('CRM_Contribute_DAO_Contribution', $ContributionCreate['values'][0]['id']);
    $this->callAPISuccess('Contribution', 'delete', $contribParams);
    $this->assertDBRowNotExist('CRM_Contribute_DAO_Contribution', $ContributionCreate['values'][0]['id']);
  }

  /**
   * Test Activity creation on cancellation of membership contribution.
   */
  public function testActivityForCancelledContribution() {
    $contactId = $this->createLoggedInUser();
    $membershipID = $this->contactMembershipCreate($this->_params);
    $this->assertDBRowExist('CRM_Member_DAO_Membership', $membershipID);

    $ContributionCreate = $this->callAPISuccess('Contribution', 'create', array(
      'financial_type_id' => "Member Dues",
      'total_amount' => 100,
      'contact_id' => $this->_params['contact_id'],
    ));
    $membershipPaymentCreate = $this->callAPISuccess('MembershipPayment', 'create', array(
      'sequential' => 1,
      'contribution_id' => $ContributionCreate['id'],
      'membership_id' => $membershipID,
    ));
    $instruments = $this->callAPISuccess('contribution', 'getoptions', array('field' => 'payment_instrument_id'));
    $this->paymentInstruments = $instruments['values'];

    $form = new CRM_Contribute_Form_Contribution();
    $form->_id = $ContributionCreate['id'];
    $form->testSubmit(array(
      'total_amount' => 100,
      'financial_type_id' => 1,
      'contact_id' => $contactId,
      'payment_instrument_id' => array_search('Check', $this->paymentInstruments),
      'contribution_status_id' => 3,
    ),
    CRM_Core_Action::UPDATE);

    $activity = $this->callAPISuccess('Activity', 'get', array(
      'activity_type_id' => "Change Membership Status",
      'source_record_id' => $membershipID,
    ));
    $this->assertNotEmpty($activity['values']);
  }

  /**
   * Test membership get.
   */
  public function testContactMembershipsGet() {
    $this->_membershipID = $this->contactMembershipCreate($this->_params);
    $this->callAPISuccess('membership', 'get', array());
    $this->callAPISuccess('Membership', 'Delete', array('id' => $this->_membershipID));
  }

  /**
   * Test civicrm_membership_get with params not array.
   *
   * Gets treated as contact_id, memberships expected.
   */
  public function testGetWithParamsContactId() {
    $this->_membershipID = $this->contactMembershipCreate($this->_params);
    $params = array(
      'contact_id' => $this->_contactID,
    );
    $membership = $this->callAPISuccess('membership', 'get', $params);

    $result = $membership['values'][$this->_membershipID];
    $this->callAPISuccess('Membership', 'Delete', array(
      'id' => $this->_membershipID,
    ));
    $this->assertEquals($result['contact_id'], $this->_contactID, "In line " . __LINE__);
    $this->assertEquals($result['membership_type_id'], $this->_membershipTypeID, "In line " . __LINE__);
    $this->assertEquals($result['status_id'], $this->_membershipStatusID, "In line " . __LINE__);
    $this->assertEquals($result['join_date'], '2009-01-21', "In line " . __LINE__);
    $this->assertEquals($result['start_date'], '2009-01-21', "In line " . __LINE__);
    $this->assertEquals($result['end_date'], '2009-12-21', "In line " . __LINE__);
    $this->assertEquals($result['source'], 'Payment', "In line " . __LINE__);
    $this->assertEquals($result['is_override'], 1, "In line " . __LINE__);
  }

  /**
   * Test civicrm_membership_get with params not array.
   *
   * Gets treated as contact_id, memberships expected.
   */
  public function testGetInSyntax() {
    $this->_membershipID = $this->contactMembershipCreate($this->_params);
    $this->_membershipID2 = $this->contactMembershipCreate($this->_params);
    $this->_membershipID3 = $this->contactMembershipCreate($this->_params);
    $params = array(
      'id' => array('IN' => array($this->_membershipID, $this->_membershipID3)),
    );
    $membership = $this->callAPISuccess('membership', 'get', $params);
    $this->assertEquals(2, $membership['count']);
    $this->assertEquals(array($this->_membershipID, $this->_membershipID3), array_keys($membership['values']));
    $params = array(
      'id' => array('NOT IN' => array($this->_membershipID, $this->_membershipID3)),
    );
    $membership = $this->callAPISuccess('membership', 'get', $params);
    $this->assertEquals(1, $membership['count']);
    $this->assertEquals(array($this->_membershipID2), array_keys($membership['values']));
  }

  /**
   * Test civicrm_membership_get with params not array.
   * Gets treated as contact_id, memberships expected.
   */
  public function testGetInSyntaxOnContactID() {
    $this->_membershipID = $this->contactMembershipCreate($this->_params);
    $contact2 = $this->individualCreate();
    $contact3 = $this->individualCreate(array('first_name' => 'Scout', 'last_name' => 'Canine'));
    $this->_membershipID2 = $this->contactMembershipCreate(array_merge($this->_params, array('contact_id' => $contact2)));
    $this->_membershipID3 = $this->contactMembershipCreate(array_merge($this->_params, array('contact_id' => $contact3)));
    $params = array(
      'contact_id' => array('IN' => array($this->_contactID, $contact3)),
    );
    $membership = $this->callAPISuccess('membership', 'get', $params);
    $this->assertEquals(2, $membership['count']);
    $this->assertEquals(array($this->_membershipID, $this->_membershipID3), array_keys($membership['values']));
    $params = array(
      'contact_id' => array('NOT IN' => array($this->_contactID, $contact3)),
    );
    $membership = $this->callAPISuccess('membership', 'get', $params);
    $this->assertEquals(1, $membership['count']);
    $this->assertEquals(array($this->_membershipID2), array_keys($membership['values']));
  }

  /**
   * Test civicrm_membership_get with params not array.
   *
   * Gets treated as contact_id, memberships expected.
   */
  public function testGetWithParamsMemberShipTypeId() {
    $this->callAPISuccess($this->_entity, 'create', $this->_params);
    $params = array(
      'membership_type_id' => $this->_membershipTypeID,
    );
    $membership = $this->callAPISuccess('membership', 'get', $params);
    $this->callAPISuccess('Membership', 'Delete', array(
      'id' => $membership['id'],
    ));
    $result = $membership['values'][$membership['id']];
    $this->assertEquals($result['contact_id'], $this->_contactID);
    $this->assertEquals($result['membership_type_id'], $this->_membershipTypeID);
    $this->assertEquals($result['status_id'], $this->_membershipStatusID);
    $this->assertEquals($result['join_date'], '2009-01-21');
    $this->assertEquals($result['start_date'], '2009-01-21');
    $this->assertEquals($result['end_date'], '2009-12-21');
    $this->assertEquals($result['source'], 'Payment');
    $this->assertEquals($result['is_override'], 1);
    $this->assertEquals($result['id'], $membership['id']);
  }

  /**
   * Test civicrm_membership_get with params not array.
   * Gets treated as contact_id, memberships expected.
   */
  public function testGetWithParamsMemberShipTypeIdContactID() {
    $params = $this->_params;
    $this->callAPISuccess($this->_entity, 'create', $params);
    $params['membership_type_id'] = $this->_membershipTypeID2;
    $this->callAPISuccess($this->_entity, 'create', $params);
    $this->callAPISuccessGetCount('membership', array('contact_id' => $this->_contactID), 2);
    $params = array(
      'membership_type_id' => $this->_membershipTypeID,
      'contact_id' => $this->_contactID,
    );
    $result = $this->callAPISuccess('membership', 'getsingle', $params);
    $this->assertEquals($result['contact_id'], $this->_contactID);
    $this->assertEquals($result['membership_type_id'], $this->_membershipTypeID);

    $params = array(
      'membership_type_id' => $this->_membershipTypeID2,
      'contact_id' => $this->_contactID,
    );
    $result = $this->callAPISuccess('membership', 'getsingle', $params);
    $this->assertEquals($result['contact_id'], $this->_contactID);
    $this->assertEquals($result['membership_type_id'], $this->_membershipTypeID2);
  }

  /**
   * Check with complete array + custom field.
   *
   * Note that the test is written on purpose without any
   * variables specific to participant so it can be replicated into other entities
   * and / or moved to the automated test suite
   */
  public function testGetWithParamsMemberShipIdAndCustom() {
    $ids = $this->entityCustomGroupWithSingleFieldCreate(__FUNCTION__, __FILE__);

    $params = $this->_params;
    $params['custom_' . $ids['custom_field_id']] = "custom string";

    $result = $this->callAPISuccess($this->_entity, 'create', $params);

    $getParams = array('membership_type_id' => $params['membership_type_id']);
    $check = $this->callAPIAndDocument($this->_entity, 'get', $getParams, __FUNCTION__, __FILE__);
    $this->assertEquals("custom string", $check['values'][$result['id']]['custom_' . $ids['custom_field_id']], ' in line ' . __LINE__);

    $this->callAPISuccess('Membership', 'Delete', array(
      'id' => $result['id'],
    ));
  }

  /**
   * Test civicrm_membership_get with proper params.
   * Memberships expected.
   */
  public function testGet() {
    $membershipID = $this->contactMembershipCreate($this->_params);
    $params = array(
      'contact_id' => $this->_contactID,
    );

    $membership = $this->callAPISuccess('membership', 'get', $params);
    $result = $membership['values'][$membershipID];
    $this->callAPISuccess('Membership', 'Delete', array(
      'id' => $membership['id'],
    ));
    $this->assertEquals($result['join_date'], '2009-01-21');
    $this->assertEquals($result['contact_id'], $this->_contactID);
    $this->assertEquals($result['membership_type_id'], $this->_membershipTypeID);
    $this->assertEquals($result['status_id'], $this->_membershipStatusID);

    $this->assertEquals($result['start_date'], '2009-01-21');
    $this->assertEquals($result['end_date'], '2009-12-21');
    $this->assertEquals($result['source'], 'Payment');
    $this->assertEquals($result['is_override'], 1);
  }

  /**
   * Test civicrm_membership_get with proper params.
   * Memberships expected.
   */
  public function testGetWithId() {
    $membershipID = $this->contactMembershipCreate($this->_params);
    $params = array(
      'contact_id' => $this->_contactID,
      'id' => $this->_membershipID,
      'return' => 'id',
    );
    $result = $this->callAPISuccess('membership', 'get', $params);
    $this->assertEquals($membershipID, $result['id']);
    $params = array(
      'contact_id' => $this->_contactID,
      'membership_id' => $this->_membershipID,
      'return' => 'membership_id',
    );
    $result = $this->callAPISuccess('membership', 'get', $params);
    $this->assertEquals($membershipID, $result['id']);
  }

  /**
   * Test civicrm_membership_get for only active.
   * Memberships expected.
   */
  public function testGetOnlyActive() {
    $description = "Demonstrates use of 'filter' active_only' param.";
    $this->_membershipID = $this->contactMembershipCreate($this->_params);
    $params = array(
      'contact_id' => $this->_contactID,
      'active_only' => 1,
    );

    $membership = $this->callAPISuccess('membership', 'get', $params);
    $this->assertEquals($membership['values'][$this->_membershipID]['status_id'], $this->_membershipStatusID);
    $this->assertEquals($membership['values'][$this->_membershipID]['contact_id'], $this->_contactID);
    $params = array(
      'contact_id' => $this->_contactID,
      'filters' => array(
        'is_current' => 1,
      ),
    );

    $membership = $this->callAPIAndDocument('membership', 'get', $params, __FUNCTION__, __FILE__, $description, 'FilterIsCurrent');
    $this->assertEquals($membership['values'][$this->_membershipID]['status_id'], $this->_membershipStatusID);
    $this->assertEquals($membership['values'][$this->_membershipID]['contact_id'], $this->_contactID);

    $this->callAPISuccess('Membership', 'Delete', array('id' => $this->_membershipID));
  }

  /**
   * Test civicrm_membership_get for non exist contact.
   * empty Memberships.
   */
  public function testGetNoContactExists() {
    $params = array(
      'contact_id' => 55555,
    );

    $membership = $this->callAPISuccess('membership', 'get', $params);
    $this->assertEquals($membership['count'], 0);
  }

  /**
   * Test civicrm_membership_get with relationship.
   * get Memberships.
   */
  public function testGetWithRelationship() {
    $membershipOrgId = $this->organizationCreate(NULL);
    $memberContactId = $this->individualCreate();

    $relTypeParams = array(
      'name_a_b' => 'Relation 1',
      'name_b_a' => 'Relation 2',
      'description' => 'Testing relationship type',
      'contact_type_a' => 'Organization',
      'contact_type_b' => 'Individual',
      'is_reserved' => 1,
      'is_active' => 1,
    );
    $relTypeID = $this->relationshipTypeCreate($relTypeParams);

    $params = array(
      'name' => 'test General',
      'duration_unit' => 'year',
      'duration_interval' => 1,
      'period_type' => 'rolling',
      'member_of_contact_id' => $membershipOrgId,
      'domain_id' => 1,
      'financial_type_id' => 1,
      'relationship_type_id' => $relTypeID,
      'relationship_direction' => 'b_a',
      'is_active' => 1,
    );
    $memType = $this->callAPISuccess('membership_type', 'create', $params);

    $params = array(
      'contact_id' => $memberContactId,
      'membership_type_id' => $memType['id'],
      'join_date' => '2009-01-21',
      'start_date' => '2009-01-21',
      'end_date' => '2009-12-21',
      'source' => 'Payment',
      'is_override' => 1,
      'status_id' => $this->_membershipStatusID,
    );
    $membershipID = $this->contactMembershipCreate($params);

    $params = array(
      'contact_id' => $memberContactId,
      'membership_type_id' => $memType['id'],
    );

    $result = $this->callAPISuccess('membership', 'get', $params);

    $membership = $result['values'][$membershipID];
    $this->assertEquals($this->_membershipStatusID, $membership['status_id']);
    $this->callAPISuccess('Membership', 'Delete', array(
      'id' => $membership['id'],
    ));
    $this->membershipTypeDelete(array('id' => $memType['id']));
    $this->relationshipTypeDelete($relTypeID);
    $this->contactDelete($membershipOrgId);
    $this->contactDelete($memberContactId);
  }

  /**
   * Test civicrm_membership_create with relationships.
   * create/get Memberships.
   *
   * Test suite for CRM-14758: API ( contact, create ) does not always create related membership
   * and max_related property for Membership_Type and Membership entities
   */
  public function testCreateWithRelationship() {
    // Create membership type: inherited through employment, max_related = 2
    $params = array(
      'name_a_b' => 'Employee of',
    );
    $result = $this->callAPISuccess('relationship_type', 'get', $params);
    $relationshipTypeId = $result['id'];
    $membershipOrgId = $this->organizationCreate();
    $params = array(
      'name' => 'Corporate Membership',
      'duration_unit' => 'year',
      'duration_interval' => 1,
      'period_type' => 'rolling',
      'member_of_contact_id' => $membershipOrgId,
      'domain_id' => 1,
      'financial_type_id' => 1,
      'relationship_type_id' => $relationshipTypeId,
      'relationship_direction' => 'b_a',
      'max_related' => 2,
      'is_active' => 1,
    );
    $result = $this->callAPISuccess('membership_type', 'create', $params);
    $membershipTypeId = $result['id'];

    // Create employer and first employee
    $employerId[0] = $this->organizationCreate(array(), 1);
    $memberContactId[0] = $this->individualCreate(array('employer_id' => $employerId[0]), 0);

    // Create organization's membership
    $params = array(
      'contact_id' => $employerId[0],
      'membership_type_id' => $membershipTypeId,
      'source' => 'Test suite',
      'start_date' => date('Y-m-d'),
      'end_date' => "+1 year",
    );
    $OrganizationMembershipID = $this->contactMembershipCreate($params);

    // Check that the employee inherited the membership
    $params = array(
      'contact_id' => $memberContactId[0],
      'membership_type_id' => $membershipTypeId,
    );

    $result = $this->callAPISuccess('membership', 'get', $params);

    $this->assertEquals(1, $result['count']);
    $result = $result['values'][$result['id']];
    $this->assertEquals($OrganizationMembershipID, $result['owner_membership_id']);

    // Create second employee
    $memberContactId[1] = $this->individualCreate(array('employer_id' => $employerId[0]), 1);

    // Check that the employee inherited the membership
    $params = array(
      'contact_id' => $memberContactId[1],
      'membership_type_id' => $membershipTypeId,
    );
    $result = $this->callAPISuccess('membership', 'get', $params);
    // If it fails here CRM-14758 is not fixed
    $this->assertEquals(1, $result['count']);
    $result = $result['values'][$result['id']];
    $this->assertEquals($OrganizationMembershipID, $result['owner_membership_id']);

    // Create third employee
    $memberContactId[2] = $this->individualCreate(array('employer_id' => $employerId[0]), 2);

    // Check that employee does NOT inherit the membership (max_related = 2)
    $params = array(
      'contact_id' => $memberContactId[2],
      'membership_type_id' => $membershipTypeId,
    );
    $result = $this->callAPISuccess('membership', 'get', $params);
    $this->assertEquals(0, $result['count']);

    // Increase max_related for the employer's membership
    $params = array(
      'id' => $OrganizationMembershipID,
      'max_related' => 3,
    );
    $this->callAPISuccess('Membership', 'create', $params);

    // Check that the employee inherited the membership
    $params = array(
      'contact_id' => $memberContactId[2],
      'membership_type_id' => $membershipTypeId,
    );
    $result = $this->callAPISuccess('membership', 'get', $params);
    $this->assertEquals(1, $result['count']);
    $result = $result['values'][$result['id']];
    $this->assertEquals($OrganizationMembershipID, $result['owner_membership_id']);

    // First employee moves to a new job
    $employerId[1] = $this->organizationCreate(array(), 2);
    $params = array(
      'id' => $memberContactId[0],
      'employer_id' => $employerId[1],
    );
    $this->callAPISuccess('contact', 'create', $params);

    // Check that employee does NO LONGER inherit the membership
    $params = array(
      'contact_id' => $memberContactId[0],
      'membership_type_id' => $membershipTypeId,
    );
    $result = $this->callAPISuccess('membership', 'get', $params);
    $this->assertEquals(0, $result['count']);

    //Create pay_later membership for organization.
    $employerId[2] = $this->organizationCreate(array(), 1);
    $params = array(
      'contact_id' => $employerId[2],
      'membership_type_id' => $membershipTypeId,
      'source' => 'Test pay later suite',
      'is_pay_later' => 1,
      'status_id' => 5,
    );
    $organizationMembership = CRM_Member_BAO_Membership::add($params);
    $organizationMembershipID = $organizationMembership->id;
    $memberContactId[3] = $this->individualCreate(array('employer_id' => $employerId[2]), 0);
    // Check that the employee inherited the membership
    $params = array(
      'contact_id' => $memberContactId[3],
      'membership_type_id' => $membershipTypeId,
    );
    $result = $this->callAPISuccess('membership', 'get', $params);
    $this->assertEquals(1, $result['count']);
    $result = $result['values'][$result['id']];
    $this->assertEquals($organizationMembershipID, $result['owner_membership_id']);

    // Set up params for enable/disable checks
    $relationship1 = $this->callAPISuccess('relationship', 'get', array('contact_id_a' => $memberContactId[1]));
    $params = array(
      'contact_id' => $memberContactId[1],
      'membership_type_id' => $membershipTypeId,
    );

    // Deactivate relationship using create and assert membership is not inherited
    $this->callAPISuccess('relationship', 'create', array('id' => $relationship1['id'], 'is_active' => 0));
    $result = $this->callAPISuccess('membership', 'get', $params);
    $this->assertEquals(0, $result['count']);

    // Re-enable relationship using create and assert membership is inherited
    $this->callAPISuccess('relationship', 'create', array('id' => $relationship1['id'], 'is_active' => 1));
    $result = $this->callAPISuccess('membership', 'get', $params);
    $this->assertEquals(1, $result['count']);

    // Deactivate relationship using setvalue and assert membership is not inherited
    $this->callAPISuccess('relationship', 'setvalue', array('id' => $relationship1['id'], 'field' => 'is_active', 'value' => 0));
    $result = $this->callAPISuccess('membership', 'get', $params);
    $this->assertEquals(0, $result['count']);

    // Re-enable relationship using setvalue and assert membership is inherited
    $this->callAPISuccess('relationship', 'setvalue', array('id' => $relationship1['id'], 'field' => 'is_active', 'value' => 1));
    $result = $this->callAPISuccess('membership', 'get', $params);
    $this->assertEquals(1, $result['count']);

    // Delete relationship and assert membership is not inherited
    $this->callAPISuccess('relationship', 'delete', array('id' => $relationship1['id']));
    $result = $this->callAPISuccess('membership', 'get', $params);
    $this->assertEquals(0, $result['count']);

    // Tear down - reverse of creation to be safe
    $this->contactDelete($memberContactId[2]);
    $this->contactDelete($memberContactId[1]);
    $this->contactDelete($memberContactId[0]);
    $this->contactDelete($employerId[1]);
    $this->contactDelete($employerId[0]);
    $this->membershipTypeDelete(array('id' => $membershipTypeId));
    $this->contactDelete($membershipOrgId);
  }

  /**
   * We are checking for no e-notices + only id & end_date returned
   */
  public function testMembershipGetWithReturn() {
    $this->contactMembershipCreate($this->_params);
    $result = $this->callAPISuccess('membership', 'get', array('return' => 'end_date'));
    foreach ($result['values'] as $membership) {
      $this->assertEquals(array('id', 'end_date'), array_keys($membership));
    }
  }

  ///////////////// civicrm_membership_create methods

  /**
   * Test civicrm_contact_memberships_create with empty params.
   * Error expected.
   */
  public function testCreateWithEmptyParams() {
    $params = array();
    $this->callAPIFailure('membership', 'create', $params);
  }

  /**
   * If is_overide is passed in status must also be passed in.
   */
  public function testCreateOverrideNoStatus() {
    $params = $this->_params;
    unset($params['status_id']);
    $this->callAPIFailure('membership', 'create', $params);
  }

  public function testMembershipCreateMissingRequired() {
    $params = array(
      'membership_type_id' => '1',
      'join_date' => '2006-01-21',
      'start_date' => '2006-01-21',
      'end_date' => '2006-12-21',
      'source' => 'Payment',
      'status_id' => '2',
    );

    $this->callAPIFailure('membership', 'create', $params);
  }

  public function testMembershipCreate() {
    $params = array(
      'contact_id' => $this->_contactID,
      'membership_type_id' => $this->_membershipTypeID,
      'join_date' => '2006-01-21',
      'start_date' => '2006-01-21',
      'end_date' => '2006-12-21',
      'source' => 'Payment',
      'is_override' => 1,
      'status_id' => $this->_membershipStatusID,
    );

    $result = $this->callAPIAndDocument('membership', 'create', $params, __FUNCTION__, __FILE__);
    $this->getAndCheck($params, $result['id'], $this->_entity);
    $this->assertNotNull($result['id']);
    $this->assertEquals($this->_contactID, $result['values'][$result['id']]['contact_id'], " in line " . __LINE__);
    $this->assertEquals($result['id'], $result['values'][$result['id']]['id'], " in line " . __LINE__);
  }

  /**
   * Check for useful message if contact doesn't exist
   */
  public function testMembershipCreateWithInvalidContact() {
    $params = array(
      'contact_id' => 999,
      'membership_type_id' => $this->_membershipTypeID,
      'join_date' => '2006-01-21',
      'start_date' => '2006-01-21',
      'end_date' => '2006-12-21',
      'source' => 'Payment',
      'is_override' => 1,
      'status_id' => $this->_membershipStatusID,
    );

    $this->callAPIFailure('membership', 'create', $params,
      'contact_id is not valid : 999'
    );
  }

  public function testMembershipCreateWithInvalidStatus() {
    $params = $this->_params;
    $params['status_id'] = 999;
    $this->callAPIFailure('membership', 'create', $params,
      "'999' is not a valid option for field status_id"
    );
  }

  public function testMembershipCreateWithInvalidType() {
    $params = $this->_params;
    $params['membership_type_id'] = 999;

    $this->callAPIFailure('membership', 'create', $params,
      "'999' is not a valid option for field membership_type_id"
    );
  }

  /**
   * Check with complete array + custom field
   * Note that the test is written on purpose without any
   * variables specific to participant so it can be replicated into other entities
   * and / or moved to the automated test suite
   */
  public function testCreateWithCustom() {
    $ids = $this->entityCustomGroupWithSingleFieldCreate(__FUNCTION__, __FILE__);

    $params = $this->_params;
    $params['custom_' . $ids['custom_field_id']] = "custom string";

    $result = $this->callAPIAndDocument($this->_entity, 'create', $params, __FUNCTION__, __FILE__, NULL, 'CreateWithCustomData');
    $check = $this->callAPISuccess($this->_entity, 'get', array(
      'id' => $result['id'],
      'contact_id' => $this->_contactID,
    ));
    $this->assertEquals("custom string", $check['values'][$result['id']]['custom_' . $ids['custom_field_id']], ' in line ' . __LINE__);
  }

  /**
   * Search on custom field value.
   */
  public function testSearchWithCustomDataCRM16036() {
    // Create a custom field on membership
    $ids = $this->entityCustomGroupWithSingleFieldCreate(__FUNCTION__, __FILE__);

    // Create a new membership, but don't assign anything to the custom field.
    $params = $this->_params;
    $result = $this->callAPIAndDocument(
      $this->_entity,
      'create',
      $params,
      __FUNCTION__,
      __FILE__,
      NULL,
      'SearchWithCustomData');

    // search memberships with CRM-16036 as custom field value.
    // Since we did not touch the custom field of any membership,
    // this should not return any results.
    $check = $this->callAPISuccess($this->_entity, 'get', array(
      'custom_' . $ids['custom_field_id'] => "CRM-16036",
    ));

    // Cleanup.
    $this->callAPISuccess($this->_entity, 'delete', array(
      'id' => $result['id'],
    ));

    // Assert.
    $this->assertEquals(0, $check['count']);
  }

  /**
   * Test civicrm_contact_memberships_create with membership id (edit
   * membership).
   * success expected.
   */
  public function testMembershipCreateWithId() {
    $membershipID = $this->contactMembershipCreate($this->_params);
    $params = array(
      'id' => $membershipID,
      'contact_id' => $this->_contactID,
      'membership_type_id' => $this->_membershipTypeID,
      'join_date' => '2006-01-21',
      'start_date' => '2006-01-21',
      'end_date' => '2006-12-21',
      'source' => 'Payment',
      'is_override' => 1,
      'status_id' => $this->_membershipStatusID,
    );

    $result = $this->callAPISuccess('membership', 'create', $params);

    //Update Status and check activities created.
    $updateStatus = array(
      'id' => $result['id'],
      'status_id' => CRM_Core_PseudoConstant::getKey('CRM_Member_BAO_Membership', 'status_id', 'Cancelled'),
    );
    $this->callAPISuccess('Membership', 'create', $updateStatus);
    $activities = CRM_Activity_BAO_Activity::getContactActivity($this->_contactID);
    $this->assertEquals(2, count($activities));
    $activityNames = array_flip(CRM_Utils_Array::collect('activity_name', $activities));
    $this->assertArrayHasKey('Membership Signup', $activityNames);
    $this->assertArrayHasKey('Change Membership Status', $activityNames);

    $this->callAPISuccess('Membership', 'Delete', array(
      'id' => $result['id'],
    ));
    $this->assertEquals($result['id'], $membershipID, "in line " . __LINE__);
  }

  /**
   * Test civicrm_contact_memberships_create with membership id (edit
   * membership).
   * success expected.
   */
  public function testMembershipCreateUpdateWithIdNoContact() {
    $membershipID = $this->contactMembershipCreate($this->_params);
    $params = array(
      'id' => $membershipID,
      'membership_type_id' => $this->_membershipTypeID,
      'contact_id' => $this->_contactID,
      'join_date' => '2006-01-21',
      'start_date' => '2006-01-21',
      'end_date' => '2006-12-21',
      'source' => 'Payment',
      'is_override' => 1,
      'status_id' => $this->_membershipStatusID,
    );

    $result = $this->callAPISuccess('membership', 'create', $params);
    $this->callAPISuccess('Membership', 'Delete', array(
      'id' => $result['id'],
    ));

    $this->assertEquals($result['id'], $membershipID, "in line " . __LINE__);
  }

  /**
   * Test civicrm_contact_memberships_create with membership id (edit
   * membership).
   * success expected.
   */
  public function testMembershipCreateUpdateWithIdNoDates() {
    $membershipID = $this->contactMembershipCreate($this->_params);
    $params = array(
      'id' => $membershipID,
      'contact_id' => $this->_contactID,
      'membership_type_id' => $this->_membershipTypeID,
      'source' => 'Payment',
      'is_override' => 1,
      'status_id' => $this->_membershipStatusID,
    );

    $result = $this->callAPISuccess('membership', 'create', $params);
    $this->callAPISuccess('Membership', 'Delete', array(
      'id' => $result['id'],
    ));
    $this->assertEquals($result['id'], $membershipID, "in line " . __LINE__);
  }

  /**
   * Test civicrm_contact_memberships_create with membership id (edit
   * membership).
   * success expected.
   */
  public function testMembershipCreateUpdateWithIdNoDatesNoType() {
    $membershipID = $this->contactMembershipCreate($this->_params);
    $params = array(
      'id' => $membershipID,
      'source' => 'not much here',
      'contact_id' => $this->_contactID,
      'is_override' => 1,
      'status_id' => $this->_membershipStatusID,
    );

    $result = $this->callAPISuccess('membership', 'create', $params);
    $this->callAPISuccess('Membership', 'Delete', array(
      'id' => $result['id'],
    ));
    $this->assertEquals($result['id'], $membershipID, "in line " . __LINE__);
  }

  /**
   * Test civicrm_contact_memberships_create with membership id (edit
   * membership).
   * success expected.
   */
  public function testMembershipCreateUpdateWithIDAndSource() {
    $membershipID = $this->contactMembershipCreate($this->_params);
    $params = array(
      'id' => $membershipID,
      'source' => 'changed',
      'contact_id' => $this->_contactID,
      'status_id' => $this->_membershipStatusID,
      'membership_type_id' => $this->_membershipTypeID,
      'skipStatusCal' => 1,
    );
    $result = $this->callAPISuccess('membership', 'create', $params);
    $this->assertEquals($result['id'], $membershipID, "in line " . __LINE__);
    $this->callAPISuccess('Membership', 'Delete', array(
      'id' => $result['id'],
    ));
  }

  /**
   * Change custom field using update.
   */
  public function testUpdateWithCustom() {
    $ids = $this->entityCustomGroupWithSingleFieldCreate(__FUNCTION__, __FILE__);

    $params = $this->_params;
    $params['custom_' . $ids['custom_field_id']] = "custom string";
    $result = $this->callAPIAndDocument($this->_entity, 'create', $params, __FUNCTION__, __FILE__, NULL, 'UpdateCustomData');
    $result = $this->callAPISuccess($this->_entity, 'create', array(
      'id' => $result['id'],
      'custom_' . $ids['custom_field_id'] => "new custom",
    ));
    $check = $this->callAPISuccess($this->_entity, 'get', array(
      'id' => $result['id'],
      'contact_id' => $this->_contactID,
    ));

    $this->assertEquals("new custom", $check['values'][$result['id']]['custom_' . $ids['custom_field_id']], ' in line ' . __LINE__);
    $this->callAPISuccess('Membership', 'Delete', array(
      'id' => $check['id'],
    ));

    $this->customFieldDelete($ids['custom_field_id']);
    $this->customGroupDelete($ids['custom_group_id']);
  }

  /**
   * per CRM-15746 check that the id can be altered in an update hook
   */
  public function testMembershipUpdateCreateHookCRM15746() {
    $this->hookClass->setHook('civicrm_pre', array($this, 'hook_civicrm_pre_update_create_membership'));
    $result = $this->callAPISuccess('membership', 'create', $this->_params);
    $this->callAPISuccess('membership', 'create', array('id' => $result['id'], 'end_date' => '1 year ago'));
    $this->callAPISuccessGetCount('membership', array(), 2);
    $this->hookClass->reset();
    $this->callAPISuccess('membership', 'create', array('id' => $result['id'], 'end_date' => '1 year ago'));
    $this->callAPISuccessGetCount('membership', array(), 2);
  }

  /**
   * Custom hook for update membership.
   *
   * @param string $op
   * @param object $objectName
   * @param int $id
   * @param array $params
   *
   * @throws \Exception
   */
  public function hook_civicrm_pre_update_create_membership($op, $objectName, $id, &$params) {
    if ($objectName == 'Membership' && $op == 'edit') {
      $existingMembership = $this->callAPISuccessGetSingle('membership', array('id' => $params['id']));
      unset($params['id'], $params['membership_id']);
      $params['join_date'] = $params['membership_start_date'] = $params['start_date'] = date('Ymd000000', strtotime($existingMembership['start_date']));
      $params = array_merge($existingMembership, $params);
      $params['id'] = NULL;
    }
  }

  /**
   * Test civicrm_contact_memberships_create Invalid membership data.
   * Error expected.
   */
  public function testMembershipCreateInvalidMemData() {
    //membership_contact_id as string
    $params = array(
      'membership_contact_id' => 'Invalid',
      'membership_type_id' => $this->_membershipTypeID,
      'join_date' => '2011-01-21',
      'start_date' => '2010-01-21',
      'end_date' => '2008-12-21',
      'source' => 'Payment',
      'is_override' => 1,
      'status_id' => $this->_membershipStatusID,
    );

    $this->callAPIFailure('membership', 'create', $params);

    //membership_contact_id which is no in contact table
    $params['membership_contact_id'] = 999;
    $this->callAPIFailure('membership', 'create', $params);

    //invalid join date
    unset($params['membership_contact_id']);
    $params['join_date'] = "invalid";
    $this->callAPIFailure('Membership', 'Create', $params);
  }

  /**
   * Test civicrm_contact_memberships_create with membership_contact_id
   * membership).
   * Success expected.
   */
  public function testMembershipCreateWithMemContact() {
    $params = array(
      'membership_contact_id' => $this->_contactID,
      'membership_type_id' => $this->_membershipTypeID,
      'join_date' => '2011-01-21',
      'start_date' => '2010-01-21',
      'end_date' => '2008-12-21',
      'source' => 'Payment',
      'is_override' => 1,
      'status_id' => $this->_membershipStatusID,
    );

    $result = $this->callAPISuccess('membership', 'create', $params);

    $this->callAPISuccess('Membership', 'Delete', array(
      'id' => $result['id'],
    ));
  }

  /**
   * Test civicrm_contact_memberships_create with membership_contact_id
   * membership).
   * Success expected.
   */
  public function testMembershipCreateValidMembershipTypeString() {
    $params = array(
      'membership_contact_id' => $this->_contactID,
      'membership_type_id' => 'General',
      'join_date' => '2011-01-21',
      'start_date' => '2010-01-21',
      'end_date' => '2008-12-21',
      'source' => 'Payment',
      'is_override' => 1,
      'status_id' => $this->_membershipStatusID,
    );

    $result = $this->callAPISuccess('membership', 'create', $params);
    $this->assertEquals($this->_membershipTypeID, $result['values'][$result['id']]['membership_type_id']);
    $this->callAPISuccess('Membership', 'Delete', array(
      'id' => $result['id'],
    ));
  }

  /**
   * Test civicrm_contact_memberships_create with membership_contact_id
   * membership).
   * Success expected.
   */
  public function testMembershipCreateInValidMembershipTypeString() {
    $params = array(
      'membership_contact_id' => $this->_contactID,
      'membership_type_id' => 'invalid',
      'join_date' => '2011-01-21',
      'start_date' => '2010-01-21',
      'end_date' => '2008-12-21',
      'source' => 'Payment',
      'is_override' => 1,
      'status_id' => $this->_membershipStatusID,
    );

    $this->callAPIFailure('membership', 'create', $params);
  }

  /**
   * Test that if membership join date is not set it defaults to today.
   */
  public function testEmptyJoinDate() {
    unset($this->_params['join_date'], $this->_params['is_override']);
    $result = $this->callAPISuccess($this->_entity, 'create', $this->_params);
    $result = $this->callAPISuccess($this->_entity, 'getsingle', array('id' => $result['id']));
    $this->assertEquals(date('Y-m-d', strtotime('now')), $result['join_date']);
    $this->assertEquals('2009-01-21', $result['start_date']);
    $this->assertEquals('2009-12-21', $result['end_date']);
  }

  /**
   * Test that if membership start date is not set it defaults to correct end date.
   *  - fixed
   */
  public function testEmptyStartDateFixed() {
    unset($this->_params['start_date'], $this->_params['is_override']);
    $this->_params['membership_type_id'] = $this->_membershipTypeID2;
    $result = $this->callAPISuccess($this->_entity, 'create', $this->_params);
    $result = $this->callAPISuccess($this->_entity, 'getsingle', array('id' => $result['id']));
    $this->assertEquals('2009-01-21', $result['join_date']);
    $this->assertEquals('2008-03-01', $result['start_date']);
    $this->assertEquals('2009-12-21', $result['end_date']);
  }

  /**
   * Test that if membership start date is not set it defaults to correct end date
   *  - fixed
   */
  public function testEmptyStartEndDateFixedOneYear() {
    unset($this->_params['start_date'], $this->_params['is_override'], $this->_params['end_date']);
    $this->callAPISuccess('membership_type', 'create', array('id' => $this->_membershipTypeID2, 'duration_interval' => 1));
    $this->_params['membership_type_id'] = $this->_membershipTypeID2;
    $result = $this->callAPISuccess($this->_entity, 'create', $this->_params);
    $result = $this->callAPISuccess($this->_entity, 'getsingle', array('id' => $result['id']));
    $this->assertEquals('2009-01-21', $result['join_date']);
    $this->assertEquals('2008-03-01', $result['start_date']);
    $this->assertEquals('2010-02-28', $result['end_date']);
  }

  /**
   * Test that if membership start date is not set it defaults to correct end date for fixed multi year memberships.
   */
  public function testEmptyStartEndDateFixedMultiYear() {
    unset($this->_params['start_date'], $this->_params['is_override'], $this->_params['end_date']);
    $this->callAPISuccess('membership_type', 'create', array('id' => $this->_membershipTypeID2, 'duration_interval' => 5));
    $this->_params['membership_type_id'] = $this->_membershipTypeID2;
    $result = $this->callAPISuccess($this->_entity, 'create', $this->_params);
    $result = $this->callAPISuccess($this->_entity, 'getsingle', array('id' => $result['id']));
    $this->assertEquals('2009-01-21', $result['join_date']);
    $this->assertEquals('2008-03-01', $result['start_date']);
    $this->assertEquals('2014-02-28', $result['end_date']);
  }

  /**
   * CRM-18503 - Test membership join date is correctly set for fixed memberships.
   */
  public function testMembershipJoinDateFixed() {
    $memStatus = CRM_Member_PseudoConstant::membershipStatus();
    // Update the fixed membership type to 1 year duration.
    $this->callAPISuccess('membership_type', 'create', array('id' => $this->_membershipTypeID2, 'duration_interval' => 1));
    $contactId = $this->createLoggedInUser();
    // Create membership with 'Pending' status.
    $params = array(
      'contact_id' => $contactId,
      'membership_type_id' => $this->_membershipTypeID2,
      'source' => 'test membership',
      'is_pay_later' => 0,
      'status_id' => array_search('Pending', $memStatus),
      'skipStatusCal' => 1,
      'is_for_organization' => 1,
    );
    $ids = array();
    $membership = CRM_Member_BAO_Membership::create($params, $ids);

    // Update membership to 'Completed' and check the dates.
    $memParams = array(
      'id' => $membership->id,
      'contact_id' => $contactId,
      'is_test' => 0,
      'membership_type_id' => $this->_membershipTypeID2,
      'num_terms' => 1,
      'status_id' => array_search('New', $memStatus),
    );
    $result = $this->callAPISuccess('Membership', 'create', $memParams);

    // Extend duration interval if join_date exceeds the rollover period.
    $joinDate = date('Y-m-d');
    $year = date('Y');
    $startDate = date('Y-m-d', strtotime(date('Y-03-01')));
    $rollOver = TRUE;
    if (strtotime($startDate) > time()) {
      $rollOver = FALSE;
      $startDate = date('Y-m-d', strtotime(date('Y-03-01') . '- 1 year'));
    }
    $membershipTypeDetails = CRM_Member_BAO_MembershipType::getMembershipTypeDetails($this->_membershipTypeID2);
    $fixedPeriodRollover = CRM_Member_BAO_MembershipType::isDuringFixedAnnualRolloverPeriod($joinDate, $membershipTypeDetails, $year, $startDate);
    $y = 1;
    if ($fixedPeriodRollover && $rollOver) {
      $y += 1;
    }

    $expectedDates = array(
      'join_date' => date('Ymd'),
      'start_date' => str_replace('-', '', $startDate),
      'end_date' => date('Ymd', strtotime(date('Y-03-01') . "+ {$y} year - 1 day")),
    );
    foreach ($result['values'] as $values) {
      foreach ($expectedDates as $date => $val) {
        $this->assertEquals($val, $values[$date], "Failed asserting {$date} values");
      }
    }
  }

  /**
   * Test correct end and start dates are calculated for fixed multi year memberships.
   *
   * The empty start date is calculated to be the start_date (1 Jan prior to the join_date - so 1 Jan 15)
   *
   * In this set our start date is after the start day and before the rollover day so we don't get an extra year
   * and we end one day before the rollover day. Start day is 1 Jan so we end on 31 Dec
   * and we add on 4 years rather than 5 because we are not after the rollover day - so we calculate 31 Dec 2019
   */
  public function testFixedMultiYearDateSetTwoEmptyStartEndDate() {
    unset($this->_params['start_date'], $this->_params['is_override'], $this->_params['end_date']);

    $this->callAPISuccess('membership_type', 'create', array(
      'id' => $this->_membershipTypeID2,
      'duration_interval' => 5,
      // Ie 1 Jan.
      'fixed_period_start_day' => '101',
      // Ie. 1 Nov.
      'fixed_period_rollover_day' => '1101',
    ));
    $this->_params['membership_type_id'] = $this->_membershipTypeID2;
    $dates = array(
      'join_date' => '28-Jan 2015',
    );
    $result = $this->callAPISuccess($this->_entity, 'create', array_merge($this->_params, $dates));
    $result = $this->callAPISuccess($this->_entity, 'getsingle', array('id' => $result['id']));
    $this->assertEquals('2015-01-28', $result['join_date']);
    $this->assertEquals('2015-01-01', $result['start_date']);
    $this->assertEquals('2019-12-31', $result['end_date']);
  }

  /**
   * Test that correct end date is calculated for fixed multi year memberships and start date is not changed.
   *
   * In this set our start date is after the start day and before the rollover day so we don't get an extra year
   * and we end one day before the rollover day. Start day is 1 Jan so we end on 31 Dec
   * and we add on 4 years rather than 5 because we are not after the rollover day - so we calculate 31 Dec 2019
   */
  public function testFixedMultiYearDateSetTwoEmptyEndDate() {
    unset($this->_params['start_date'], $this->_params['is_override'], $this->_params['end_date']);

    $this->callAPISuccess('membership_type', 'create', array(
      'id' => $this->_membershipTypeID2,
      'duration_interval' => 5,
      // Ie 1 Jan.
      'fixed_period_start_day' => '101',
      // Ie. 1 Nov.
      'fixed_period_rollover_day' => '1101',
    ));
    $this->_params['membership_type_id'] = $this->_membershipTypeID2;
    $dates = array(
      'start_date' => '28-Jan 2015',
      'join_date' => '28-Jan 2015',
    );
    $result = $this->callAPISuccess($this->_entity, 'create', array_merge($this->_params, $dates));
    $result = $this->callAPISuccess($this->_entity, 'getsingle', array('id' => $result['id']));
    $this->assertEquals('2015-01-28', $result['join_date']);
    $this->assertEquals('2015-01-28', $result['start_date']);
    $this->assertEquals('2019-12-31', $result['end_date']);
  }

  /**
   * Test correct end and start dates are calculated for fixed multi year memberships.
   *
   * The empty start date is calculated to be the start_date (1 Jan prior to the join_date - so 1 Jan 15)
   *
   * In this set our start date is after the start day and before the rollover day so we don't get an extra year
   * and we end one day before the rollover day. Start day is 1 Jan so we end on 31 Dec
   * and we add on <1 years rather than > 1 because we are not after the rollover day - so we calculate 31 Dec 2015
   */
  public function testFixedSingleYearDateSetTwoEmptyStartEndDate() {
    unset($this->_params['start_date'], $this->_params['is_override'], $this->_params['end_date']);

    $this->callAPISuccess('membership_type', 'create', array(
      'id' => $this->_membershipTypeID2,
      'duration_interval' => 1,
      // Ie 1 Jan.
      'fixed_period_start_day' => '101',
      // Ie. 1 Nov.
      'fixed_period_rollover_day' => '1101',
    ));
    $this->_params['membership_type_id'] = $this->_membershipTypeID2;
    $dates = array(
      'join_date' => '28-Jan 2015',
    );
    $result = $this->callAPISuccess($this->_entity, 'create', array_merge($this->_params, $dates));
    $result = $this->callAPISuccess($this->_entity, 'getsingle', array('id' => $result['id']));
    $this->assertEquals('2015-01-28', $result['join_date']);
    $this->assertEquals('2015-01-01', $result['start_date']);
    $this->assertEquals('2015-12-31', $result['end_date']);
  }

  /**
   * Test correct end date for fixed single year memberships is calculated and start_date is not changed.
   *
   * In this set our start date is after the start day and before the rollover day so we don't get an extra year
   * and we end one day before the rollover day. Start day is 1 Jan so we end on 31 Dec
   * and we add on <1 years rather than > 1 because we are not after the rollover day - so we calculate 31 Dec 2015
   */
  public function testFixedSingleYearDateSetTwoEmptyEndDate() {
    unset($this->_params['start_date'], $this->_params['is_override'], $this->_params['end_date']);

    $this->callAPISuccess('membership_type', 'create', array(
      'id' => $this->_membershipTypeID2,
      'duration_interval' => 1,
      // Ie 1 Jan.
      'fixed_period_start_day' => '101',
      // Ie. 1 Nov.
      'fixed_period_rollover_day' => '1101',
    ));
    $this->_params['membership_type_id'] = $this->_membershipTypeID2;
    $dates = array(
      'start_date' => '28-Jan 2015',
      'join_date' => '28-Jan 2015',
    );
    $result = $this->callAPISuccess($this->_entity, 'create', array_merge($this->_params, $dates));
    $result = $this->callAPISuccess($this->_entity, 'getsingle', array('id' => $result['id']));
    $this->assertEquals('2015-01-28', $result['join_date']);
    $this->assertEquals('2015-01-28', $result['start_date']);
    $this->assertEquals('2015-12-31', $result['end_date']);
  }

  /**
   * Test that correct end date is calculated for fixed multi year memberships and start date is not changed.
   *
   * In this set our start date is after the start day and after the rollover day so we do get an extra year
   * and we end one day before the rollover day. Start day is 1 Nov so we end on 31 Oct
   * and we add on 1 year we are after the rollover day - so we calculate 31 Oct 2016
   */
  public function testFixedSingleYearDateSetThreeEmptyEndDate() {
    unset($this->_params['start_date'], $this->_params['is_override'], $this->_params['end_date']);

    $this->callAPISuccess('membership_type', 'create', array(
      'id' => $this->_membershipTypeID2,
      'duration_interval' => 1,
      // Ie. 1 Nov.
      'fixed_period_start_day' => '1101',
      // Ie 1 Jan.
      'fixed_period_rollover_day' => '101',
    ));
    $this->_params['membership_type_id'] = $this->_membershipTypeID2;
    $dates = array(
      'start_date' => '28-Jan 2015',
      'join_date' => '28-Jan 2015',
    );
    $result = $this->callAPISuccess($this->_entity, 'create', array_merge($this->_params, $dates));
    $result = $this->callAPISuccess($this->_entity, 'getsingle', array('id' => $result['id']));
    $this->assertEquals('2015-01-28', $result['join_date']);
    $this->assertEquals('2015-01-28', $result['start_date']);
    $this->assertEquals('2016-10-31', $result['end_date']);
  }

  /**
   * Test correct end and start dates are calculated for fixed multi year memberships.
   *
   * The empty start date is calculated to be the start_date (1 Nov prior to the join_date - so 1 Nov 14)
   *
   * In this set our start date is after the start day and after the rollover day so we do get an extra year
   * and we end one day before the rollover day. Start day is 1 Nov so we end on 31 Oct
   * and we add on 1 year we are after the rollover day - so we calculate 31 Oct 2016
   */
  public function testFixedSingleYearDateSetThreeEmptyStartEndDate() {
    unset($this->_params['start_date'], $this->_params['is_override'], $this->_params['end_date']);

    $this->callAPISuccess('membership_type', 'create', array(
      'id' => $this->_membershipTypeID2,
      'duration_interval' => 1,
      // Ie. 1 Nov.
      'fixed_period_start_day' => '1101',
      // Ie 1 Jan.
      'fixed_period_rollover_day' => '101',
    ));
    $this->_params['membership_type_id'] = $this->_membershipTypeID2;
    $dates = array(
      'join_date' => '28-Jan 2015',
    );
    $result = $this->callAPISuccess($this->_entity, 'create', array_merge($this->_params, $dates));
    $result = $this->callAPISuccess($this->_entity, 'getsingle', array('id' => $result['id']));
    $this->assertEquals('2015-01-28', $result['join_date']);
    $this->assertEquals('2014-11-01', $result['start_date']);
    $this->assertEquals('2016-10-31', $result['end_date']);
  }

  /**
   * Test that correct end date is calculated for fixed multi year memberships and start date is not changed.
   *
   * In this set our start date is after the start day and after the rollover day so we do get an extra year
   * and we end one day before the rollover day. Start day is 1 Nov so we end on 31 Oct
   * and we add on 5 years we are after the rollover day - so we calculate 31 Oct 2020
   */
  public function testFixedMultiYearDateSetThreeEmptyEndDate() {
    unset($this->_params['start_date'], $this->_params['is_override'], $this->_params['end_date']);

    $this->callAPISuccess('membership_type', 'create', array(
      'id' => $this->_membershipTypeID2,
      'duration_interval' => 5,
      // Ie. 1 Nov.
      'fixed_period_start_day' => '1101',
      // Ie 1 Jan.
      'fixed_period_rollover_day' => '101',
    ));
    $this->_params['membership_type_id'] = $this->_membershipTypeID2;
    $dates = array(
      'start_date' => '28-Jan 2015',
      'join_date' => '28-Jan 2015',
    );
    $result = $this->callAPISuccess($this->_entity, 'create', array_merge($this->_params, $dates));
    $result = $this->callAPISuccess($this->_entity, 'getsingle', array('id' => $result['id']));
    $this->assertEquals('2015-01-28', $result['join_date']);
    $this->assertEquals('2015-01-28', $result['start_date']);
    $this->assertEquals('2020-10-31', $result['end_date']);
  }

  /**
   * Test correct end and start dates are calculated for fixed multi year memberships.
   *
   * The empty start date is calculated to be the start_date (1 Nov prior to the join_date - so 1 Nov 14)
   *
   * The empty start date is calculated to be the start_date (1 Nov prior to the join_date - so 1 Nov 14)
   * In this set our join date is after the start day and after the rollover day so we do get an extra year
   * and we end one day before the rollover day. Start day is 1 Nov so we end on 31 Oct
   * and we add on 5 years we are after the rollover day - so we calculate 31 Oct 2020
   */
  public function testFixedMultiYearDateSetThreeEmptyStartEndDate() {
    unset($this->_params['start_date'], $this->_params['is_override'], $this->_params['end_date']);

    $this->callAPISuccess('membership_type', 'create', array(
      'id' => $this->_membershipTypeID2,
      'duration_interval' => 5,
      // Ie. 1 Nov.
      'fixed_period_start_day' => '1101',
      // Ie 1 Jan.
      'fixed_period_rollover_day' => '101',
    ));
    $this->_params['membership_type_id'] = $this->_membershipTypeID2;
    $dates = array(
      'join_date' => '28-Jan 2015',
    );
    $result = $this->callAPISuccess($this->_entity, 'create', array_merge($this->_params, $dates));
    $result = $this->callAPISuccess($this->_entity, 'getsingle', array('id' => $result['id']));
    $this->assertEquals('2015-01-28', $result['join_date']);
    $this->assertEquals('2014-11-01', $result['start_date']);
    $this->assertEquals('2020-10-31', $result['end_date']);
  }

  /**
   * Test that if membership start date is not set it defaults to correct end date for fixed single year memberships.
   */
  public function testEmptyStartDateRolling() {
    unset($this->_params['start_date'], $this->_params['is_override']);
    $result = $this->callAPISuccess($this->_entity, 'create', $this->_params);
    $result = $this->callAPISuccess($this->_entity, 'getsingle', array('id' => $result['id']));
    $this->assertEquals('2009-01-21', $result['join_date']);
    $this->assertEquals('2009-01-21', $result['start_date']);
    $this->assertEquals('2009-12-21', $result['end_date']);
  }

  /**
   * Test that if membership end date is not set it defaults to correct end date.
   *  - rolling
   */
  public function testEmptyEndDateFixed() {
    unset($this->_params['start_date'], $this->_params['is_override'], $this->_params['end_date']);
    $this->_params['membership_type_id'] = $this->_membershipTypeID2;
    $result = $this->callAPISuccess($this->_entity, 'create', $this->_params);
    $result = $this->callAPISuccess($this->_entity, 'getsingle', array('id' => $result['id']));
    $this->assertEquals('2009-01-21', $result['join_date']);
    $this->assertEquals('2008-03-01', $result['start_date']);
    $this->assertEquals('2010-02-28', $result['end_date']);
  }

  /**
   * Test that if membership end date is not set it defaults to correct end date.
   *  - rolling
   */
  public function testEmptyEndDateRolling() {
    unset($this->_params['is_override'], $this->_params['end_date']);
    $this->_params['membership_type_id'] = $this->_membershipTypeID;
    $result = $this->callAPISuccess($this->_entity, 'create', $this->_params);
    $result = $this->callAPISuccess($this->_entity, 'getsingle', array('id' => $result['id']));
    $this->assertEquals('2009-01-21', $result['join_date']);
    $this->assertEquals('2009-01-21', $result['start_date']);
    $this->assertEquals('2010-01-20', $result['end_date']);
  }

  /**
   * Test that if dates are set they not over-ridden if id is passed in
   */
  public function testMembershipDatesNotOverridden() {
    $result = $this->callAPISuccess($this->_entity, 'create', $this->_params);
    unset($this->_params['end_date'], $this->_params['start_date']);
    $this->_params['id'] = $result['id'];
    $this->callAPISuccess($this->_entity, 'create', $this->_params);
    $result = $this->callAPISuccess($this->_entity, 'getsingle', array('id' => $result['id']));
    $this->assertEquals('2009-01-21', $result['join_date']);
    $this->assertEquals('2009-01-21', $result['start_date']);
    $this->assertEquals('2009-12-21', $result['end_date']);

  }

  /**
   * Test that all membership types are returned when getoptions is called.
   *
   * This test locks in current behaviour where types for all domains are returned. It should possibly be domain
   * specific but that should only be done in conjunction with adding a hook to allow that to be altered as the
   * multisite use case expects the master domain to be able to see all sites.
   *
   * See CRM-17075.
   */
  public function testGetOptionsMembershipTypeID() {
    $options = $this->callAPISuccess('Membership', 'getoptions', array('field' => 'membership_type_id'));
    $this->assertEquals('Another one', array_pop($options['values']));
    $this->assertEquals('General', array_pop($options['values']));
    $this->assertEquals(NULL, array_pop($options['values']));
  }

}
