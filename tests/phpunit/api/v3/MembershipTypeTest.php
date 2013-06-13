<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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

require_once 'CiviTest/CiviUnitTestCase.php';
class api_v3_MembershipTypeTest extends CiviUnitTestCase {
  protected $_contactID;
  protected $_contributionTypeID;
  protected $_apiversion;
  protected $_entity = 'MembershipType';
  public $_eNoticeCompliant = TRUE;

  function get_info() {
    return array(
      'name' => 'MembershipType Create',
      'description' => 'Test all Membership Type Create API methods.',
      'group' => 'CiviCRM API Tests',
    );
  }

  function setUp() {
    parent::setUp();
    $this->_apiversion = 3;
    $this->_contactID = $this->organizationCreate(NULL);
  }

  function tearDown() {
    $tablesToTruncate = array('civicrm_contact');
    $this->quickCleanup($tablesToTruncate);
  }

  ///////////////// civicrm_membership_type_get methods
  function testGetWithWrongParamsType() {
    $params = 'a string';
    $membershiptype = civicrm_api('membership_type', 'create', $params);
    $this->assertEquals($membershiptype['is_error'], 1,
      "In line " . __LINE__
    );
  }

  function testGetWithEmptyParams() {
    $params = array();
    $membershiptype = civicrm_api('membership_type', 'get', $params);
    $this->assertEquals($membershiptype['is_error'], 1);
    $this->assertEquals($membershiptype['error_message'], 'Mandatory key(s) missing from params array: version');
  }

  function testGetWithoutId() {
    $params = array(
      'name' => '60+ Membership',
      'description' => 'people above 60 are given health instructions',
                       'financial_type_id'    => 1 ,
      'minimum_fee' => '200',
      'duration_unit' => 'month',
      'duration_interval' => '10',
      'visibility' => 'public',
      'version' => $this->_apiversion,
    );

    $membershiptype = civicrm_api('membership_type', 'get', $params);
    $this->assertEquals($membershiptype['is_error'], 0);
    $this->assertEquals($membershiptype['count'], 0);
  }

  function testGet() {
    $id = $this->membershipTypeCreate($this->_contactID, 1);

    $params = array(
      'id' => $id,
      'version' => $this->_apiversion,
    );
    $membershiptype = civicrm_api('membership_type', 'get', $params);
    $this->documentMe($params, $membershiptype, __FUNCTION__, __FILE__);
    $this->assertEquals($membershiptype['is_error'], '0', 'In line ' . __LINE__);
    $this->assertEquals($membershiptype['values'][$id]['name'], 'General', 'In line ' . __LINE__ . " id is " . $id);
    $this->assertEquals($membershiptype['values'][$id]['member_of_contact_id'], $this->_contactID, 'In line ' . __LINE__);
      $this->assertEquals($membershiptype['values'][$id]['financial_type_id'],1, 'In line ' . __LINE__ );
    $this->assertEquals($membershiptype['values'][$id]['duration_unit'], 'year', 'In line ' . __LINE__);
    $this->assertEquals($membershiptype['values'][$id]['duration_interval'], '1', 'In line ' . __LINE__);
    $this->assertEquals($membershiptype['values'][$id]['period_type'], 'rolling', 'In line ' . __LINE__);
    $this->membershipTypeDelete($params);
  }

  ///////////////// civicrm_membership_type_create methods
  function testCreateWithEmptyParams() {
    $params = array('version' => $this->_apiversion);
    $membershiptype = civicrm_api('membership_type', 'create', $params);
    $this->assertEquals($membershiptype['is_error'], 1);
    $this->assertEquals($membershiptype['error_message'],
      'Mandatory key(s) missing from params array: domain_id, member_of_contact_id, financial_type_id, duration_unit, duration_interval, name'
    );
  }

  function testCreateWithWrongParamsType() {
    $params = 'a string';
    $membershiptype = civicrm_api('membership_type', 'create', $params);
    $this->assertEquals($membershiptype['is_error'], 1,
      "In line " . __LINE__
    );
  }

  function testCreateWithoutMemberOfContactId() {
    $params = array(
      'name' => '60+ Membership',
      'description' => 'people above 60 are given health instructions',
                       'financial_type_id'    => 1,
      'domain_id' => '1',
      'minimum_fee' => '200',
      'duration_unit' => 'month',
      'duration_interval' => '10',
      'period_type' => 'rolling',
      'visibility' => 'public',
      'version' => $this->_apiversion,
    );

    $membershiptype = civicrm_api('membership_type', 'create', $params);
    $this->assertEquals($membershiptype['is_error'], 1);
    $this->assertEquals($membershiptype['error_message'], 'Mandatory key(s) missing from params array: member_of_contact_id');
  }

  function testCreateWithoutContributionTypeId() {
    $params = array(
      'name' => '70+ Membership',
      'description' => 'people above 70 are given health instructions',
      'member_of_contact_id' => $this->_contactID,
      'domain_id' => '1',
      'minimum_fee' => '200',
      'duration_unit' => 'month',
      'duration_interval' => '10',
      'period_type' => 'rolling',
      'visibility' => 'public',
      'version' => $this->_apiversion,
    );
    $membershiptype = civicrm_api('membership_type', 'create', $params);
    $this->assertEquals($membershiptype['is_error'], 1);
    $this->assertEquals($membershiptype['error_message'],
      'Mandatory key(s) missing from params array: financial_type_id'
    );
  }

  function testCreateWithoutDurationUnit() {
    $params = array(
      'name' => '80+ Membership',
      'description' => 'people above 80 are given health instructions',
      'member_of_contact_id' => $this->_contactID,
                       'financial_type_id'    => 1,
      'domain_id' => '1',
      'minimum_fee' => '200',
      'duration_interval' => '10',
      'visibility' => 'public',
      'version' => $this->_apiversion,
    );

    $membershiptype = civicrm_api('membership_type', 'create', $params);
    $this->assertEquals($membershiptype['is_error'], 1);
    $this->assertEquals($membershiptype['error_message'],
      'Mandatory key(s) missing from params array: duration_unit'
    );
  }

  function testCreateWithoutDurationInterval() {
    $params = array(
      'name' => '70+ Membership',
      'description' => 'people above 70 are given health instructions',
      'member_of_contact_id' => $this->_contactID,
      'domain_id' => '1',
      'minimum_fee' => '200',
      'duration_unit' => 'month',
      'period_type' => 'rolling',
      'visibility' => 'public',
      'version' => $this->_apiversion,
    );
    $membershiptype = civicrm_api('membership_type', 'create', $params);
    $this->assertEquals($membershiptype['is_error'], 1);
    $this->assertEquals($membershiptype['error_message'],
      'Mandatory key(s) missing from params array: financial_type_id, duration_interval'
    );
  }

  function testCreateWithoutNameandDomainIDandDurationUnit() {
    $params = array(
      'description' => 'people above 50 are given health instructions',
      'member_of_contact_id' => $this->_contactID,
                       'financial_type_id'    => 1,
      'minimum_fee' => '200',
      'duration_interval' => '10',
      'period_type' => 'rolling',
      'visibility' => 'public',
      'version' => $this->_apiversion,
    );

    $membershiptype = civicrm_api('membership_type', 'create', $params);
    $this->assertEquals($membershiptype['is_error'], 1);
    $this->assertEquals($membershiptype['error_message'],
      'Mandatory key(s) missing from params array: domain_id, duration_unit, name'
    );
  }

  function testCreateWithoutName() {
    $params = array(
      'description' => 'people above 50 are given health instructions',
      'member_of_contact_id' => $this->_contactID,
                       'financial_type_id'    => 1,
      'domain_id' => '1',
      'minimum_fee' => '200',
      'duration_unit' => 'month',
      'duration_interval' => '10',
      'period_type' => 'rolling',
      'visibility' => 'public',
      'version' => $this->_apiversion,
    );

    $membershiptype = civicrm_api('membership_type', 'create', $params);
    $this->assertEquals($membershiptype['is_error'], 1);
    $this->assertEquals($membershiptype['error_message'], 'Mandatory key(s) missing from params array: name');
  }

  function testCreate() {
    $params = array(
      'name' => '40+ Membership',
      'description' => 'people above 40 are given health instructions',
      'member_of_contact_id' => $this->_contactID,
                       'financial_type_id'    => 1,
      'domain_id' => '1',
      'minimum_fee' => '200',
      'duration_unit' => 'month',
      'duration_interval' => '10',
      'period_type' => 'rolling',
      'visibility' => 'public',
      'version' => $this->_apiversion,
    );

    $membershiptype = civicrm_api('membership_type', 'create', $params);
    $this->documentMe($params, $membershiptype, __FUNCTION__, __FILE__);
    $this->assertEquals($membershiptype['is_error'], 0);
    $this->assertNotNull($membershiptype['values']);
    $this->membershipTypeDelete(array('id' => $membershiptype['id']));
  }

  ///////////////// civicrm_membership_type_update methods
  function testUpdateWithWrongParamsType() {
    $params = 'a string';
    $membershiptype = civicrm_api('membership_type', 'create', $params);
    $this->assertEquals($membershiptype['is_error'], 1,
      "In line " . __LINE__
    );
  }

  function testUpdateWithEmptyParams() {
    $params = array();
    $membershiptype = civicrm_api('membership_type', 'create', $params);
    $this->assertEquals($membershiptype['is_error'], 1);
    $this->assertEquals($membershiptype['error_message'],
      'Mandatory key(s) missing from params array: version, domain_id, member_of_contact_id, financial_type_id, duration_unit, duration_interval, name'
    );
  }

  function testUpdateWithoutId() {
    $params = array(
      'name' => '60+ Membership',
      'description' => 'people above 60 are given health instructions',
      'member_of_contact_id' => $this->_contactID,
                       'financial_type_id'    => 1,
      'minimum_fee' => '1200',
      'duration_unit' => 'month',
      'duration_interval' => '10',
      'period_type' => 'rolling',
      'visibility' => 'public',
      'version' => $this->_apiversion,
    );

    $membershiptype = civicrm_api('membership_type', 'create', $params);
    $this->assertEquals($membershiptype['is_error'], 1);
    $this->assertEquals($membershiptype['error_message'], 'Mandatory key(s) missing from params array: domain_id');
  }

  function testUpdate() {
    $id = $this->membershipTypeCreate($this->_contactID, 2);
    $newMembOrgParams = array(
      'organization_name' => 'New membership organisation',
      'contact_type' => 'Organization',
      'visibility' => 1,
      'version' => $this->_apiversion,
    );

    // create a new contact to update this membership type to
    $newMembOrgID = $this->organizationCreate($newMembOrgParams);

    $params = array(
      'id' => $id,
      'name' => 'Updated General',
      'member_of_contact_id' => $newMembOrgID,
      'duration_unit' => 'month',
      'duration_interval' => '10',
      'period_type' => 'fixed',
      'domain_id' => 1,
      'version' => $this->_apiversion,
    );
    $membershiptype = civicrm_api('membership_type', 'update', $params);

    $this->getAndCheck($params, $id, $this->_entity);
  }

  ///////////////// civicrm_membership_type_delete methods
  function testDeleteWithWrongParamsType() {
    $params = 'a string';
    $membershiptype = civicrm_api('membership_type', 'delete', $params);
    $this->assertEquals($membershiptype['is_error'], 1,
      "In line " . __LINE__
    );
  }

  function testDeleteWithEmptyParams() {
    $params = array();
    $membershiptype = civicrm_api('membership_type', 'delete', $params);
    $this->assertEquals($membershiptype['is_error'], 1);
    $this->assertEquals($membershiptype['error_message'], 'Mandatory key(s) missing from params array: version, id');
  }

  function testDeleteNotExists() {
    $params = array(
      'id' => 'doesNotExist',
      'version' => $this->_apiversion,
    );
    $membershiptype = civicrm_api('membership_type', 'delete', $params);
    $this->assertEquals($membershiptype['is_error'], 1);
    $this->assertEquals($membershiptype['error_message'], 'Error while deleting membership type. id : ' . $params['id']);
  }

  function testDelete() {
    $orgID            = $this->organizationCreate(NULL);
    $membershipTypeID = $this->membershipTypeCreate($orgID, 1);
    $params           = array(
      'id' => $membershipTypeID,
      'version' => $this->_apiversion,
    );

    $result = civicrm_api('membership_type', 'delete', $params);
    $this->documentMe($params, $result, __FUNCTION__, __FILE__);
    $this->assertAPISuccess($result);
  }
}

