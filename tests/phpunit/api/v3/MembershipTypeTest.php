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

require_once 'CiviTest/CiviUnitTestCase.php';

/**
 * Class api_v3_MembershipTypeTest
 */
class api_v3_MembershipTypeTest extends CiviUnitTestCase {
  protected $_contactID;
  protected $_contributionTypeID;
  protected $_apiversion;
  protected $_entity = 'MembershipType';

  public function setUp() {
    parent::setUp();
    $this->useTransaction(TRUE);
    $this->_apiversion = 3;
    $this->_contactID = $this->organizationCreate(NULL);
  }

  public function testGetWithoutId() {
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

  public function testGet() {
    $id = $this->membershipTypeCreate(array('member_of_contact_id' => $this->_contactID));

    $params = array(
      'id' => $id,
    );
    $membershiptype = $this->callAPIAndDocument('membership_type', 'get', $params, __FUNCTION__, __FILE__);
    $this->assertEquals($membershiptype['values'][$id]['name'], 'General');
    $this->assertEquals($membershiptype['values'][$id]['member_of_contact_id'], $this->_contactID);
    $this->assertEquals($membershiptype['values'][$id]['financial_type_id'], 1);
    $this->assertEquals($membershiptype['values'][$id]['duration_unit'], 'year');
    $this->assertEquals($membershiptype['values'][$id]['duration_interval'], '1');
    $this->assertEquals($membershiptype['values'][$id]['period_type'], 'rolling');
    $this->membershipTypeDelete($params);
  }

  /**
   * Civicrm_membership_type_create methods.
   */
  public function testCreateWithEmptyParams() {
    $membershiptype = $this->callAPIFailure('membership_type', 'create', array());
    $this->assertEquals($membershiptype['error_message'],
      'Mandatory key(s) missing from params array: domain_id, member_of_contact_id, financial_type_id, duration_unit, duration_interval, name'
                       );
  }

  public function testCreateWithoutMemberOfContactId() {
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

  public function testCreateWithoutContributionTypeId() {
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
    );
    $membershiptype = $this->callAPIFailure('membership_type', 'create', $params);
    $this->assertEquals($membershiptype['error_message'],
      'Mandatory key(s) missing from params array: financial_type_id'
                       );
  }

  public function testCreateWithoutDurationUnit() {
    $params = array(
      'name' => '80+ Membership',
      'description' => 'people above 80 are given health instructions',
      'member_of_contact_id' => $this->_contactID,
      'financial_type_id' => 1,
      'domain_id' => '1',
      'minimum_fee' => '200',
      'duration_interval' => '10',
      'visibility' => 'public',
    );

    $membershiptype = $this->callAPIFailure('membership_type', 'create', $params);
    $this->assertEquals($membershiptype['error_message'],
      'Mandatory key(s) missing from params array: duration_unit'
                       );
  }

  public function testCreateWithoutDurationInterval() {
    $params = array(
      'name' => '70+ Membership',
      'description' => 'people above 70 are given health instructions',
      'member_of_contact_id' => $this->_contactID,
      'domain_id' => '1',
      'minimum_fee' => '200',
      'duration_unit' => 'month',
      'period_type' => 'rolling',
      'visibility' => 'public',
    );
    $membershiptype = $this->callAPIFailure('membership_type', 'create', $params);
    $this->assertEquals($membershiptype['error_message'],
      'Mandatory key(s) missing from params array: financial_type_id, duration_interval'
                       );
  }

  public function testCreateWithoutNameandDomainIDandDurationUnit() {
    $params = array(
      'description' => 'people above 50 are given health instructions',
      'member_of_contact_id' => $this->_contactID,
      'financial_type_id' => 1,
      'minimum_fee' => '200',
      'duration_interval' => '10',
      'period_type' => 'rolling',
      'visibility' => 'public',
    );

    $membershiptype = $this->callAPIFailure('membership_type', 'create', $params);
    $this->assertEquals($membershiptype['error_message'],
      'Mandatory key(s) missing from params array: domain_id, duration_unit, name'
                       );
  }

  public function testCreateWithoutName() {
    $params = array(
      'description' => 'people above 50 are given health instructions',
      'member_of_contact_id' => $this->_contactID,
      'financial_type_id' => 1,
      'domain_id' => '1',
      'minimum_fee' => '200',
      'duration_unit' => 'month',
      'duration_interval' => '10',
      'period_type' => 'rolling',
      'visibility' => 'public',
    );

    $membershiptype = $this->callAPIFailure('membership_type', 'create', $params);
    $this->assertEquals($membershiptype['error_message'], 'Mandatory key(s) missing from params array: name');
  }

  public function testCreate() {
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

    $membershipType = $this->callAPIAndDocument('membership_type', 'create', $params, __FUNCTION__, __FILE__);
    $this->assertNotNull($membershipType['values']);
    $this->membershipTypeDelete(array('id' => $membershipType['id']));
  }


  /**
   * Test mandatory parameter check.
   */
  public function testUpdateWithEmptyParams() {
    $this->callAPIFailure('membership_type', 'create', array());
  }

  /**
   * Test update fails with no ID.
   */
  public function testUpdateWithoutId() {
    $params = array(
      'name' => '60+ Membership',
      'description' => 'people above 60 are given health instructions',
      'member_of_contact_id' => $this->_contactID,
      'financial_type_id' => 1,
      'minimum_fee' => '1200',
      'duration_unit' => 'month',
      'duration_interval' => '10',
      'period_type' => 'rolling',
      'visibility' => 'public',
    );

    $membershipType = $this->callAPIFailure('membership_type', 'create', $params);
    $this->assertEquals($membershipType['error_message'], 'Mandatory key(s) missing from params array: domain_id');
  }

  public function testUpdate() {
    $id = $this->membershipTypeCreate(array('member_of_contact_id' => $this->_contactID, 'financial_type_id' => 2));
    $newMembOrgParams = array(
      'organization_name' => 'New membership organisation',
      'contact_type' => 'Organization',
      'visibility' => 1,
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
    );

    $this->callAPISuccess('membership_type', 'update', $params);

    $this->getAndCheck($params, $id, $this->_entity);
  }

  /**
   * Test for failure when id is not valid.
   */
  public function testDeleteNotExists() {
    $params = array(
      'id' => 'doesNotExist',
    );
    $this->callAPIFailure('membership_type', 'delete', $params,
      'Error while deleting membership type. id : ' . $params['id']
    );
  }

  public function testDelete() {
    $orgID = $this->organizationCreate(NULL);
    $membershipTypeID = $this->membershipTypeCreate(array('member_of_contact_id' => $orgID));
    $params = array(
      'id' => $membershipTypeID,
    );

    $this->callAPIAndDocument('membership_type', 'delete', $params, __FUNCTION__, __FILE__);
  }

}
