<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 *  @package CiviCRM_APIv3
 *  @subpackage API_Member
 */


require_once 'CiviTest/CiviUnitTestCase.php';

class api_v3_MembershipTest extends CiviUnitTestCase {
  protected $_apiversion;
  protected $_contactID;
  protected $_membershipTypeID;
  protected $_membershipTypeID2;
  protected $_membershipStatusID;
  protected $__membershipID;
  protected $_entity;
  protected $_params;


  public function setUp() {
    //  Connect to the database
    parent::setUp();
    $this->_apiversion = 3;
    $this->_contactID = $this->individualCreate();
    $this->_membershipTypeID = $this->membershipTypeCreate(array('member_of_contact_id' => $this->_contactID));
    $this->_membershipTypeID2 = $this->membershipTypeCreate(array('period_type' => 'fixed','fixed_period_start_day' => '301', 'fixed_period_rollover_day' => '1111'));
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

  function tearDown() {
    $this->quickCleanup(array(
      'civicrm_membership',
      'civicrm_membership_payment',
      'civicrm_membership_log',
      ),
      TRUE
    );
    $this->membershipStatusDelete($this->_membershipStatusID);
    $this->membershipTypeDelete(array('id' => $this->_membershipTypeID2));
    $this->membershipTypeDelete(array('id' => $this->_membershipTypeID));
    $this->contactDelete($this->_contactID);

  }

  /**
   *  Test civicrm_membership_delete()
   */
  function testMembershipDelete() {
    $membershipID = $this->contactMembershipCreate($this->_params);
    $this->assertDBRowExist('CRM_Member_DAO_Membership', $membershipID);
    $params = array(
      'id' => $membershipID
    );
    $result = $this->callAPIAndDocument('membership', 'delete', $params, __FUNCTION__, __FILE__);
    $this->assertDBRowNotExist('CRM_Member_DAO_Membership', $membershipID);
  }

  function testMembershipDeleteEmpty() {
    $params = array();
    $result = $this->callAPIFailure('membership', 'delete', $params);
  }

  function testMembershipDeleteInvalidID() {
    $params = array('id' => 'blah');
    $result = $this->callAPIFailure('membership', 'delete', $params);
  }

  /**
   *  Test civicrm_membership_delete() with invalid Membership Id
   */
  function testMembershipDeleteWithInvalidMembershipId() {
    $membershipId = 'membership';
    $result = $this->callAPIFailure('membership', 'delete', $membershipId);
  }

  /**
   *  All other methods calls MembershipType and MembershipContact
   *  api, but putting simple test methods to control existence of
   *  these methods for backwards compatibility, also verifying basic
   *  behaviour is the same as new methods.
   */
  function testContactMembershipsGet() {
    $this->_membershipID = $this->contactMembershipCreate($this->_params);
    $params = array();
    $result = $this->callAPISuccess('membership', 'get', $params);
    $result = $this->callAPISuccess('Membership', 'Delete', array(
      'id' => $this->_membershipID,
    ));
  }

  /**
   * Test civicrm_membership_get with params not array.
   * Gets treated as contact_id, memberships expected.
   */
  function testGetWithParamsContactId() {
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
   * Gets treated as contact_id, memberships expected.
   */
  function testGetInSyntax() {
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
  function testGetInSyntaxOnContactID() {
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
   * Gets treated as contact_id, memberships expected.
   */

  function testGetWithParamsMemberShipTypeId() {
    $result = $this->callAPISuccess($this->_entity, 'create', $this->_params);
    $params = array(
      'membership_type_id' => $this->_membershipTypeID,
    );
    $membership = $this->callAPISuccess('membership', 'get', $params);
    $result = $this->callAPISuccess('Membership', 'Delete', array(
      'id' => $membership['id'],
    ));
    $result = $membership['values'][$membership['id']];
    $this->assertEquals($result['contact_id'], $this->_contactID, "In line " . __LINE__);
    $this->assertEquals($result['membership_type_id'], $this->_membershipTypeID, "In line " . __LINE__);
    $this->assertEquals($result['status_id'], $this->_membershipStatusID, "In line " . __LINE__);
    $this->assertEquals($result['join_date'], '2009-01-21', "In line " . __LINE__);
    $this->assertEquals($result['start_date'], '2009-01-21', "In line " . __LINE__);
    $this->assertEquals($result['end_date'], '2009-12-21', "In line " . __LINE__);
    $this->assertEquals($result['source'], 'Payment', "In line " . __LINE__);
    $this->assertEquals($result['is_override'], 1, "In line " . __LINE__);
    $this->assertEquals($result['id'], $membership['id']);
  }

  /**
   * check with complete array + custom field
   * Note that the test is written on purpose without any
   * variables specific to participant so it can be replicated into other entities
   * and / or moved to the automated test suite
   */
  function testGetWithParamsMemberShipIdAndCustom() {
    $ids = $this->entityCustomGroupWithSingleFieldCreate(__FUNCTION__, __FILE__);

    $params = $this->_params;
    $params['custom_' . $ids['custom_field_id']] = "custom string";

    $result = $this->callAPISuccess($this->_entity, 'create', $params);

    $getParams = array('membership_type_id' => $params['membership_type_id']);
    $check = $this->callAPIAndDocument($this->_entity, 'get', $getParams, __FUNCTION__, __FILE__);
    $this->assertEquals("custom string", $check['values'][$result['id']]['custom_' . $ids['custom_field_id']], ' in line ' . __LINE__);

    $result = $this->callAPISuccess('Membership', 'Delete', array(
      'id' => $result['id'],
    ));
  }

  /**
   * Test civicrm_membership_get with proper params.
   * Memberships expected.
   */
  function testGet() {
    $membershipID = $this->contactMembershipCreate($this->_params);
    $params = array(
      'contact_id' => $this->_contactID,
    );

    $membership = $this->callAPISuccess('membership', 'get', $params);
    $result = $membership['values'][$membershipID];
    $this->callAPISuccess('Membership', 'Delete', array(
      'id' => $membership['id'],
    ));
    $this->assertEquals($result['join_date'], '2009-01-21', "In line " . __LINE__);
    $this->assertEquals($result['contact_id'], $this->_contactID, "In line " . __LINE__);
    $this->assertEquals($result['membership_type_id'], $this->_membershipTypeID, "In line " . __LINE__);
    $this->assertEquals($result['status_id'], $this->_membershipStatusID, "In line " . __LINE__);

    $this->assertEquals($result['start_date'], '2009-01-21', "In line " . __LINE__);
    $this->assertEquals($result['end_date'], '2009-12-21', "In line " . __LINE__);
    $this->assertEquals($result['source'], 'Payment', "In line " . __LINE__);
    $this->assertEquals($result['is_override'], 1, "In line " . __LINE__);
  }


  /**
   * Test civicrm_membership_get with proper params.
   * Memberships expected.
   */
  function testGetWithId() {
    $membershipID = $this->contactMembershipCreate($this->_params);
    $params = array(
      'contact_id' => $this->_contactID,
      'id' => $this->__membershipID,
      'return' => 'id',
    );
    $result = $this->callAPISuccess('membership', 'get', $params);
    $this->assertEquals($membershipID, $result['id']);
    $params = array(
      'contact_id' => $this->_contactID,
      'membership_id' => $this->__membershipID,
      'return' => 'membership_id',
    );
    $result = $this->callAPISuccess('membership', 'get', $params);
    $this->assertEquals($membershipID, $result['id']);
  }

  /**
   * Test civicrm_membership_get for only active.
   * Memberships expected.
   */
  function testGetOnlyActive() {
    $description          = "Demonstrates use of 'filter' active_only' param";
    $this->_membershipID = $this->contactMembershipCreate($this->_params);
    $subfile             = 'filterIsCurrent';
    $params              = array(
      'contact_id' => $this->_contactID,
      'active_only' => 1,
    );

    $membership = $this->callAPISuccess('membership', 'get', $params);
    $result = $membership['values'][$this->_membershipID];
    $this->assertEquals($membership['values'][$this->_membershipID]['status_id'], $this->_membershipStatusID, "In line " . __LINE__);
    $this->assertEquals($membership['values'][$this->_membershipID]['contact_id'], $this->_contactID, "In line " . __LINE__);
    $params = array(
      'contact_id' => $this->_contactID,
      'filters' => array(
        'is_current' => 1,
      ),
    );

    $membership = $this->callAPIAndDocument('membership', 'get', $params, __FUNCTION__, __FILE__, $description, $subfile);
    $result = $membership['values'][$this->_membershipID];
    $this->assertEquals($membership['values'][$this->_membershipID]['status_id'], $this->_membershipStatusID, "In line " . __LINE__);
    $this->assertEquals($membership['values'][$this->_membershipID]['contact_id'], $this->_contactID, "In line " . __LINE__);


    $result = $this->callAPISuccess('Membership', 'Delete', array(
      'id' => $this->_membershipID,
    ));
  }

  /**
   * Test civicrm_membership_get for non exist contact.
   * empty Memberships.
   */
  function testGetNoContactExists() {
    $params = array(
      'contact_id' => 55555,
    );

    $membership = $this->callAPISuccess('membership', 'get', $params);
    $this->assertEquals($membership['count'], 0, "In line " . __LINE__);
  }

  /**
   * Test civicrm_membership_get with relationship.
   * get Memberships.
   */
  function testGetWithRelationship() {
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
      'financial_type_id'   => 1,
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
    $result = $this->callAPISuccess('Membership', 'Delete', array(
      'id' => $membership['id'],
      ));
    $this->membershipTypeDelete(array('id' => $memType['id']));
    $this->relationshipTypeDelete($relTypeID);
    $this->contactDelete($membershipOrgId);
    $this->contactDelete($memberContactId);
  }

  /**
   * We are checking for no enotices + only id & end_date returned
   */
  function testMembershipGetWithReturn() {
    $membershipID = $this->contactMembershipCreate($this->_params);
    $result = $this->callAPISuccess('membership', 'get', array('return' => 'end_date'));
    foreach ($result['values']  as $membership) {
      $this->assertEquals(array('id', 'end_date'), array_keys($membership));
    }
  }
  ///////////////// civicrm_membership_create methods

  /**
   * Test civicrm_contact_memberships_create with empty params.
   * Error expected.
   */
  function testCreateWithEmptyParams() {
    $params = array();
    $result = $this->callAPIFailure('membership', 'create', $params);
  }

  /**
   * If is_overide is passed in status must also be passed in
   */
  function testCreateOverrideNoStatus() {
    $params = $this->_params;
    unset($params['status_id']);
    $result = $this->callAPIFailure('membership', 'create', $params);
  }

  function testMembershipCreateMissingRequired() {
    $params = array(
      'membership_type_id' => '1',
      'join_date' => '2006-01-21',
      'start_date' => '2006-01-21',
      'end_date' => '2006-12-21',
      'source' => 'Payment',
      'status_id' => '2',
    );

    $result = $this->callAPIFailure('membership', 'create', $params);
  }

  function testMembershipCreate() {
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
  /*
      * Check for useful message if contact doesn't exist
      */
  function testMembershipCreateWithInvalidContact() {
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

    $result = $this->callAPIFailure('membership', 'create', $params,
      'contact_id is not valid : 999'
    );
  }
  function testMembershipCreateWithInvalidStatus() {
    $params = $this->_params;
    $params['status_id'] = 999;
    $result = $this->callAPIFailure('membership', 'create', $params,
      "'999' is not a valid option for field status_id"
    );
  }

  function testMembershipCreateWithInvalidType() {
    $params = $this->_params;
    $params['membership_type_id'] = 999;

    $result = $this->callAPIFailure('membership', 'create', $params,
      "'999' is not a valid option for field membership_type_id"
    );
  }

  /**
   * check with complete array + custom field
   * Note that the test is written on purpose without any
   * variables specific to participant so it can be replicated into other entities
   * and / or moved to the automated test suite
   */
  function testCreateWithCustom() {
    $ids = $this->entityCustomGroupWithSingleFieldCreate(__FUNCTION__, __FILE__);

    $params = $this->_params;
    $params['custom_' . $ids['custom_field_id']] = "custom string";

    $result = $this->callAPIAndDocument($this->_entity, 'create', $params, __FUNCTION__, __FILE__);
    $check = $this->callAPISuccess($this->_entity, 'get', array('id' => $result['id'], 'contact_id' => $this->_contactID));
    $this->assertEquals("custom string", $check['values'][$result['id']]['custom_' . $ids['custom_field_id']], ' in line ' . __LINE__);
  }

  /**
   * Test civicrm_contact_memberships_create with membership id (edit
   * membership).
   * success expected.
   */
  function testMembershipCreateWithId() {
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
  function testMembershipCreateUpdateWithIdNoContact() {
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
  function testMembershipCreateUpdateWithIdNoDates() {
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
  function testMembershipCreateUpdateWithIdNoDatesNoType() {
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
  function testMembershipCreateUpdateWithIDAndSource() {
    $membershipID = $this->contactMembershipCreate($this->_params);
    $params = array(
      'id' => $membershipID,
      'source' => 'changed',
      'contact_id' => $this->_contactID,
      'status_id' => $this->_membershipStatusID,      'membership_type_id' => $this->_membershipTypeID,
      'skipStatusCal' => 1,
    );
    $result = $this->callAPISuccess('membership', 'create', $params);
    $this->assertEquals($result['id'], $membershipID, "in line " . __LINE__);
    $this->callAPISuccess('Membership', 'Delete', array(
      'id' => $result['id'],
      ));
  }

  /**
   * change custom field using update
   */
  function testUpdateWithCustom() {
    $ids = $this->entityCustomGroupWithSingleFieldCreate(__FUNCTION__, __FILE__);

    $params = $this->_params;
    $params['custom_' . $ids['custom_field_id']] = "custom string";
    $result = $this->callAPIAndDocument($this->_entity, 'create', $params, __FUNCTION__, __FILE__);
    $result = $this->callAPISuccess($this->_entity, 'create', array('id' => $result['id'], 'custom_' . $ids['custom_field_id'] => "new custom"));
    $check = $this->callAPISuccess($this->_entity, 'get', array('id' => $result['id'], 'contact_id' => $this->_contactID));

    $this->assertEquals("new custom", $check['values'][$result['id']]['custom_' . $ids['custom_field_id']], ' in line ' . __LINE__);
    $delete = $this->callAPISuccess('Membership', 'Delete', array(
      'id' => $check['id'],
      ));

    $this->customFieldDelete($ids['custom_field_id']);
    $this->customGroupDelete($ids['custom_group_id']);
  }

  /**
   * Test civicrm_contact_memberships_create Invalid membership data
   * Error expected.
   */
  function testMembershipCreateInvalidMemData() {
    //membership_contact_id as string
    $params = array(
      'membership_contact_id' => 'Invalid',
      'membership_type_id' => $this->_membershipTypeID,
      'join_date' => '2011-01-21',
      'start_date' => '2010-01-21',
      'end_date' => '2008-12-21',
      'source' => 'Payment',
      'is_override' => 1,
      'status_id' => $this->_membershipStatusID,    );

    $result = $this->callAPIFailure('membership', 'create', $params);

    //membership_contact_id which is no in contact table
    $params['membership_contact_id'] = 999;
    $result = $this->callAPIFailure('membership', 'create', $params);

    //invalid join date
    unset($params['membership_contact_id']);
    $params['join_date'] = "invalid";
    $result = $this->callAPIFailure('Membership', 'Create', $params);
  }

  /**
   * Test civicrm_contact_memberships_create with membership_contact_id
   * membership).
   * Success expected.
   */
  function testMembershipCreateWithMemContact() {
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

    $result = $this->callAPISuccess('Membership', 'Delete', array(
      'id' => $result['id'],
    ));
  }
  /**
   * Test civicrm_contact_memberships_create with membership_contact_id
   * membership).
   * Success expected.
   */
  function testMembershipCreateValidMembershipTypeString() {
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
    $result = $this->callAPISuccess('Membership', 'Delete', array(
      'id' => $result['id'],
    ));
  }

  /**
   * Test civicrm_contact_memberships_create with membership_contact_id
   * membership).
   * Success expected.
   */
  function testMembershipCreateInValidMembershipTypeString() {
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

    $result = $this->callAPIFailure('membership', 'create', $params);
  }

  /**
   * Test that if membership join date is not set it defaults to today
   */
  function testEmptyJoinDate() {
    unset($this->_params['join_date'], $this->_params['is_override']);
    $result = $this->callAPISuccess($this->_entity, 'create', $this->_params);
    $result = $this->callAPISuccess($this->_entity, 'getsingle', array('id' => $result['id']));
    $this->assertEquals(date('Y-m-d', strtotime('now')), $result['join_date']);
    $this->assertEquals('2009-01-21', $result['start_date']);
    $this->assertEquals('2009-12-21', $result['end_date']);
  }
  /**
   * Test that if membership start date is not set it defaults to correct end date
   *  - fixed
   */
  function testEmptyStartDateFixed() {
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
  function testEmptyStartDateRolling() {
    unset($this->_params['start_date'], $this->_params['is_override']);
    $result = $this->callAPISuccess($this->_entity, 'create', $this->_params);
    $result = $this->callAPISuccess($this->_entity, 'getsingle', array('id' => $result['id']));
    $this->assertEquals('2009-01-21', $result['join_date']);
    $this->assertEquals('2009-01-21', $result['start_date']);
    $this->assertEquals('2009-12-21', $result['end_date']);
  }
  /**
   * Test that if membership end date is not set it defaults to correct end date
   *  - rolling
   */
  function testEmptyEndDateFixed() {
    unset($this->_params['start_date'], $this->_params['is_override'], $this->_params['end_date']);
    $this->_params['membership_type_id'] = $this->_membershipTypeID2;
    $result = $this->callAPISuccess($this->_entity, 'create', $this->_params);
    $result = $this->callAPISuccess($this->_entity, 'getsingle', array('id' => $result['id']));
    $this->assertEquals('2009-01-21', $result['join_date']);
    $this->assertEquals('2008-03-01', $result['start_date']);
    $this->assertEquals('2010-02-28', $result['end_date']);
  }
  /**
   * Test that if membership end date is not set it defaults to correct end date
   *  - rolling
   */
  function testEmptyEndDateRolling() {
    unset($this->_params['is_override'], $this->_params['end_date']);
    $this->_params['membership_type_id'] = $this->_membershipTypeID;
    $result = $this->callAPISuccess($this->_entity, 'create', $this->_params);
    $result = $this->callAPISuccess($this->_entity, 'getsingle', array('id' => $result['id']));
    $this->assertEquals('2009-01-21', $result['join_date']);
    $this->assertEquals('2009-01-21', $result['start_date']);
    $this->assertEquals('2010-01-20', $result['end_date']);
  }


  /**
   * Test that if datesdate are not set they not over-ridden if id is passed in
   */
   function testMembershipDatesNotOverridden() {
    $result = $this->callAPISuccess($this->_entity, 'create', $this->_params);
    unset($this->_params['end_date'], $this->_params['start_date']);
    $this->_params['id'] = $result['id'];
    $this->callAPISuccess($this->_entity, 'create', $this->_params);
    $result = $this->callAPISuccess($this->_entity, 'getsingle', array('id' => $result['id']));
    $this->assertEquals('2009-01-21', $result['join_date']);
    $this->assertEquals('2009-01-21', $result['start_date']);
    $this->assertEquals('2009-12-21', $result['end_date']);

   }
}

