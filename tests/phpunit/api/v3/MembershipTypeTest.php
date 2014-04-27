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

require_once 'CiviTest/CiviUnitTestCase.php';
class api_v3_MembershipTypeTest extends CiviUnitTestCase {
  protected $_contactID;
  protected $_contributionTypeID;
  protected $_apiversion;
  protected $_entity = 'MembershipType';


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

  function testGetWithoutId() {
    $params = array(
      'name' => '60+ Membership',
      'description' => 'people above 60 are given health instructions',
      'financial_type_id' => 1,
      'minimum_fee' => '200',
      'duration_unit' => 'month',
      'duration_interval' => '10',
      'visibility' => 'public',
    );

    $membershiptype = $this->callAPISuccess('membership_type', 'get', $params);
    $this->assertEquals($membershiptype['count'], 0);
  }

  function testGet() {
    $id = $this->membershipTypeCreate(array('member_of_contact_id' => $this->_contactID));

    $params = array(
      'id' => $id,
    );
    $membershiptype = $this->callAPIAndDocument('membership_type', 'get', $params, __FUNCTION__, __FILE__);
    $this->assertEquals($membershiptype['values'][$id]['name'], 'General', 'In line ' . __LINE__ . " id is " . $id);
    $this->assertEquals($membershiptype['values'][$id]['member_of_contact_id'], $this->_contactID, 'In line ' . __LINE__);
    $this->assertEquals($membershiptype['values'][$id]['financial_type_id'], 1, 'In line ' . __LINE__);
    $this->assertEquals($membershiptype['values'][$id]['duration_unit'], 'year', 'In line ' . __LINE__);
    $this->assertEquals($membershiptype['values'][$id]['duration_interval'], '1', 'In line ' . __LINE__);
    $this->assertEquals($membershiptype['values'][$id]['period_type'], 'rolling', 'In line ' . __LINE__);
    $this->membershipTypeDelete($params);
  }

  ///////////////// civicrm_membership_type_create methods
  function testCreateWithEmptyParams() {
    $membershiptype = $this->callAPIFailure('membership_type', 'create', array());
    $this->assertEquals($membershiptype['error_message'],
      'Mandatory key(s) missing from params array: domain_id, member_of_contact_id, financial_type_id, duration_unit, duration_interval, name'
    );
  }

  function testCreateWithoutMemberOfContactId() {
    $params = array(
      'name' => '60+ Membership',
      'description' => 'people above 60 are given health instructions',
      'financial_type_id' => 1,
      'domain_id' => '1',
      'minimum_fee' => '200',
      'duration_unit' => 'month',
      'duration_interval' => '10',
      'period_type' => 'rolling',
      'visibility' => 'public',
    );

    $membershiptype = $this->callAPIFailure('membership_type', 'create', $params,
      'Mandatory key(s) missing from params array: member_of_contact_id'
    );
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
      'visibility' => 'public',    );
    $membershiptype = $this->callAPIFailure('membership_type', 'create', $params);
    $this->assertEquals($membershiptype['error_message'],
      'Mandatory key(s) missing from params array: financial_type_id'
    );
  }

  function testCreateWithoutDurationUnit() {
    $params = array(
      'name' => '80+ Membership',
      'description' => 'people above 80 are given health instructions',
      'member_of_contact_id' => $this->_contactID,
      'financial_type_id' => 1,
      'domain_id' => '1',
      'minimum_fee' => '200',
      'duration_interval' => '10',
      'visibility' => 'public',    );

    $membershiptype = $this->callAPIFailure('membership_type', 'create', $params);
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
      'visibility' => 'public',    );
    $membershiptype = $this->callAPIFailure('membership_type', 'create', $params);
    $this->assertEquals($membershiptype['error_message'],
      'Mandatory key(s) missing from params array: financial_type_id, duration_interval'
    );
  }

  function testCreateWithoutNameandDomainIDandDurationUnit() {
    $params = array(
      'description' => 'people above 50 are given health instructions',
      'member_of_contact_id' => $this->_contactID,
      'financial_type_id' => 1,
      'minimum_fee' => '200',
      'duration_interval' => '10',
      'period_type' => 'rolling',
      'visibility' => 'public',    );

    $membershiptype = $this->callAPIFailure('membership_type', 'create', $params);
    $this->assertEquals($membershiptype['error_message'],
      'Mandatory key(s) missing from params array: domain_id, duration_unit, name'
    );
  }

  function testCreateWithoutName() {
    $params = array(
      'description' => 'people above 50 are given health instructions',
      'member_of_contact_id' => $this->_contactID,
      'financial_type_id' => 1,
      'domain_id' => '1',
      'minimum_fee' => '200',
      'duration_unit' => 'month',
      'duration_interval' => '10',
      'period_type' => 'rolling',
      'visibility' => 'public',    );

    $membershiptype = $this->callAPIFailure('membership_type', 'create', $params);
    $this->assertEquals($membershiptype['error_message'], 'Mandatory key(s) missing from params array: name');
  }

  function testCreate() {
    $params = array(
      'name' => '40+ Membership',
      'description' => 'people above 40 are given health instructions',
      'member_of_contact_id' => $this->_contactID,
      'financial_type_id' => 1,
      'domain_id' => '1',
      'minimum_fee' => '200',
      'duration_unit' => 'month',
      'duration_interval' => '10',
      'period_type' => 'rolling',
      'visibility' => 'public',
    );

    $membershiptype = $this->callAPIAndDocument('membership_type', 'create', $params, __FUNCTION__, __FILE__);
    $this->assertNotNull($membershiptype['values']);
    $this->membershipTypeDelete(array('id' => $membershiptype['id']));
  }


  function testUpdateWithEmptyParams() {
    $params = array();
    $membershiptype = $this->callAPIFailure('membership_type', 'create', $params);
    $this->assertEquals($membershiptype['error_message'],
      'Mandatory key(s) missing from params array: domain_id, member_of_contact_id, financial_type_id, duration_unit, duration_interval, name'
    );
  }

  function testUpdateWithoutId() {
    $params = array(
      'name' => '60+ Membership',
      'description' => 'people above 60 are given health instructions',
      'member_of_contact_id' => $this->_contactID,
      'financial_type_id' => 1,
      'minimum_fee' => '1200',
      'duration_unit' => 'month',
      'duration_interval' => '10',
      'period_type' => 'rolling',
      'visibility' => 'public',    );

    $membershiptype = $this->callAPIFailure('membership_type', 'create', $params);
    $this->assertEquals($membershiptype['error_message'], 'Mandatory key(s) missing from params array: domain_id');
  }

  function testUpdate() {
    $id = $this->membershipTypeCreate(array('member_of_contact_id' => $this->_contactID, 'financial_type_id' => 2));
    $newMembOrgParams = array(
      'organization_name' => 'New membership organisation',
      'contact_type' => 'Organization',
      'visibility' => 1,    );

    // create a new contact to update this membership type to
    $newMembOrgID = $this->organizationCreate($newMembOrgParams);

    $params = array(
      'id' => $id,
      'name' => 'Updated General',
      'member_of_contact_id' => $newMembOrgID,
      'duration_unit' => 'month',
      'duration_interval' => '10',
      'period_type' => 'fixed',
      'domain_id' => 1,    );

    $this->callAPISuccess('membership_type', 'update', $params);

    $this->getAndCheck($params, $id, $this->_entity);
  }

  function testDeleteNotExists() {
    $params = array(
      'id' => 'doesNotExist',
    );
    $membershiptype = $this->callAPIFailure('membership_type', 'delete', $params,
      'Error while deleting membership type. id : ' . $params['id']
    );
  }

  function testDelete() {
    $orgID = $this->organizationCreate(NULL);
    $membershipTypeID = $this->membershipTypeCreate(array('member_of_contact_id' => $orgID));
    $params = array(
      'id' => $membershipTypeID,
    );

    $result = $this->callAPIAndDocument('membership_type', 'delete', $params, __FUNCTION__, __FILE__);
  }
}

