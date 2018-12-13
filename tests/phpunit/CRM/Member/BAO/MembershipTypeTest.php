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
 * Class CRM_Member_BAO_MembershipTypeTest
 * @group headless
 */
class CRM_Member_BAO_MembershipTypeTest extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();

    //create relationship
    $params = array(
      'name_a_b' => 'Relation 1',
      'name_b_a' => 'Relation 2',
      'contact_type_a' => 'Individual',
      'contact_type_b' => 'Organization',
      'is_reserved' => 1,
      'is_active' => 1,
    );
    $this->_relationshipTypeId = $this->relationshipTypeCreate($params);
    $this->_orgContactID = $this->organizationCreate();
    $this->_indiviContactID = $this->individualCreate();
    $this->_financialTypeId = 1;
    $this->_membershipStatusID = $this->membershipStatusCreate('test status');
  }

  /**
   * Tears down the fixture, for example, closes a network connection.
   * This method is called after a test is executed.
   */
  public function tearDown() {
    $this->relationshipTypeDelete($this->_relationshipTypeId);
    $this->membershipStatusDelete($this->_membershipStatusID);
    $this->contactDelete($this->_orgContactID);
    $this->contactDelete($this->_indiviContactID);
  }

  /**
   * check function add()
   *
   */
  public function testAdd() {
    $ids = array();
    $params = array(
      'name' => 'test type',
      'domain_id' => 1,
      'description' => NULL,
      'minimum_fee' => 10,
      'duration_unit' => 'year',
      'member_of_contact_id' => $this->_orgContactID,
      'period_type' => 'fixed',
      'duration_interval' => 1,
      'financial_type_id' => $this->_financialTypeId,
      'relationship_type_id' => $this->_relationshipTypeId,
      'visibility' => 'Public',
    );

    $membershipType = CRM_Member_BAO_MembershipType::add($params, $ids);

    $membership = $this->assertDBNotNull('CRM_Member_BAO_MembershipType', $this->_orgContactID,
      'name', 'member_of_contact_id',
      'Database check on updated membership record.'
    );

    $this->assertEquals($membership, 'test type', 'Verify membership type name.');
    $this->membershipTypeDelete(array('id' => $membershipType->id));
  }

  /**
   * check function retrive()
   *
   */
  public function testRetrieve() {
    $ids = array();
    $params = array(
      'name' => 'General',
      'description' => NULL,
      'domain_id' => 1,
      'minimum_fee' => 100,
      'duration_unit' => 'year',
      'period_type' => 'fixed',
      'member_of_contact_id' => $this->_orgContactID,
      'duration_interval' => 1,
      'financial_type_id' => $this->_financialTypeId,
      'relationship_type_id' => $this->_relationshipTypeId,
      'visibility' => 'Public',
    );
    $membershipType = CRM_Member_BAO_MembershipType::add($params, $ids);

    $params = array('name' => 'General');
    $default = array();
    $result = CRM_Member_BAO_MembershipType::retrieve($params, $default);
    $this->assertEquals($result->name, 'General', 'Verify membership type name.');
    $this->membershipTypeDelete(array('id' => $membershipType->id));
  }

  /**
   * check function isActive()
   *
   */
  public function testSetIsActive() {
    $ids = array();
    $params = array(
      'name' => 'General',
      'description' => NULL,
      'domain_id' => 1,
      'minimum_fee' => 100,
      'duration_unit' => 'year',
      'period_type' => 'fixed',
      'duration_interval' => 1,
      'member_of_contact_id' => $this->_orgContactID,
      'financial_type_id' => $this->_financialTypeId,
      'relationship_type_id' => $this->_relationshipTypeId,
      'visibility' => 'Public',
      'is_active' => 1,
    );
    $membership = CRM_Member_BAO_MembershipType::add($params, $ids);

    CRM_Member_BAO_MembershipType::setIsActive($membership->id, 0);

    $isActive = $this->assertDBNotNull('CRM_Member_BAO_MembershipType', $membership->id,
      'is_active', 'id',
      'Database check on membership type status.'
    );

    $this->assertEquals($isActive, 0, 'Verify membership type status.');
    $this->membershipTypeDelete(array('id' => $membership->id));
  }

  /**
   * check function del()
   *
   */
  public function testdel() {
    $ids = array();
    $params = array(
      'name' => 'General',
      'description' => NULL,
      'minimum_fee' => 100,
      'domain_id' => 1,
      'duration_unit' => 'year',
      'period_type' => 'fixed',
      'member_of_contact_id' => $this->_orgContactID,
      'duration_interval' => 1,
      'financial_type_id' => $this->_financialTypeId,
      'relationship_type_id' => $this->_relationshipTypeId,
      'visibility' => 'Public',
      'is_active' => 1,
    );
    $membership = CRM_Member_BAO_MembershipType::add($params, $ids);

    $result = CRM_Member_BAO_MembershipType::del($membership->id);

    $this->assertEquals($result, TRUE, 'Verify membership deleted.');
  }

  /**
   * check function convertDayFormat( )
   *
   */
  public function testConvertDayFormat() {
    $ids = array();
    $params = array(
      'name' => 'General',
      'description' => NULL,
      'minimum_fee' => 100,
      'domain_id' => 1,
      'duration_unit' => 'year',
      'period_type' => 'fixed',
      'member_of_contact_id' => $this->_orgContactID,
      'fixed_period_start_day' => 1213,
      'fixed_period_rollover_day' => 1214,
      'duration_interval' => 1,
      'financial_type_id' => $this->_financialTypeId,
      'relationship_type_id' => $this->_relationshipTypeId,
      'visibility' => 'Public',
      'is_active' => 1,
    );
    $membership = CRM_Member_BAO_MembershipType::add($params, $ids);
    $membershipType[$membership->id] = $params;

    CRM_Member_BAO_MembershipType::convertDayFormat($membershipType);

    $this->assertEquals($membershipType[$membership->id]['fixed_period_rollover_day'], 'Dec 14', 'Verify memberFixed Period Rollover Day.');
    $this->membershipTypeDelete(array('id' => $membership->id));
  }

  /**
   * check function getMembershipTypes( )
   *
   */
  public function testGetMembershipTypes() {
    $ids = array();
    $params = array(
      'name' => 'General',
      'description' => NULL,
      'minimum_fee' => 100,
      'domain_id' => 1,
      'duration_unit' => 'year',
      'member_of_contact_id' => $this->_orgContactID,
      'period_type' => 'fixed',
      'duration_interval' => 1,
      'financial_type_id' => $this->_financialTypeId,
      'relationship_type_id' => $this->_relationshipTypeId,
      'visibility' => 'Public',
      'is_active' => 1,
    );
    $membership = CRM_Member_BAO_MembershipType::add($params, $ids);
    $result = CRM_Member_BAO_MembershipType::getMembershipTypes();
    $this->assertEquals($result[$membership->id], 'General', 'Verify membership types.');
    $this->membershipTypeDelete(array('id' => $membership->id));
  }

  /**
   * check function getMembershipTypeDetails( )
   *
   */
  public function testGetMembershipTypeDetails() {
    $ids = array();
    $params = array(
      'name' => 'General',
      'description' => NULL,
      'minimum_fee' => 100,
      'domain_id' => 1,
      'duration_unit' => 'year',
      'period_type' => 'fixed',
      'member_of_contact_id' => $this->_orgContactID,
      'duration_interval' => 1,
      'financial_type_id' => $this->_financialTypeId,
      'relationship_type_id' => $this->_relationshipTypeId,
      'visibility' => 'Public',
      'is_active' => 1,
    );
    $membership = CRM_Member_BAO_MembershipType::add($params, $ids);
    $result = CRM_Member_BAO_MembershipType::getMembershipTypeDetails($membership->id);

    $this->assertEquals($result['name'], 'General', 'Verify membership type details.');
    $this->assertEquals($result['duration_unit'], 'year', 'Verify membership types details.');
    $this->membershipTypeDelete(array('id' => $membership->id));
  }

  /**
   * check function getDatesForMembershipType( )
   *
   */
  public function testGetDatesForMembershipType() {
    $ids = array();
    $params = array(
      'name' => 'General',
      'description' => NULL,
      'minimum_fee' => 100,
      'domain_id' => 1,
      'duration_unit' => 'year',
      'member_of_contact_id' => $this->_orgContactID,
      'period_type' => 'rolling',
      'duration_interval' => 1,
      'financial_type_id' => $this->_financialTypeId,
      'relationship_type_id' => $this->_relationshipTypeId,
      'visibility' => 'Public',
      'is_active' => 1,
    );
    $membership = CRM_Member_BAO_MembershipType::add($params, $ids);

    $membershipDates = CRM_Member_BAO_MembershipType::getDatesForMembershipType($membership->id);
    $this->assertEquals($membershipDates['start_date'], date('Ymd'), 'Verify membership types details.');
    $this->membershipTypeDelete(array('id' => $membership->id));
  }

  /**
   * check function getRenewalDatesForMembershipType( )
   *
   */
  public function testGetRenewalDatesForMembershipType() {
    $ids = array();
    $params = array(
      'name' => 'General',
      'domain_id' => 1,
      'description' => NULL,
      'minimum_fee' => 100,
      'duration_unit' => 'year',
      'member_of_contact_id' => $this->_orgContactID,
      'period_type' => 'rolling',
      'duration_interval' => 1,
      'financial_type_id' => $this->_financialTypeId,
      'relationship_type_id' => $this->_relationshipTypeId,
      'visibility' => 'Public',
      'is_active' => 1,
    );
    $membershipType = CRM_Member_BAO_MembershipType::add($params, $ids);

    $params = array(
      'contact_id' => $this->_indiviContactID,
      'membership_type_id' => $membershipType->id,
      'join_date' => '20060121000000',
      'start_date' => '20060121000000',
      'end_date' => '20070120000000',
      'source' => 'Payment',
      'is_override' => 1,
      'status_id' => $this->_membershipStatusID,
    );
    $ids = array();
    $membership = CRM_Member_BAO_Membership::create($params, $ids);

    $membershipRenewDates = CRM_Member_BAO_MembershipType::getRenewalDatesForMembershipType($membership->id);

    $this->assertEquals($membershipRenewDates['start_date'], '20060121', 'Verify membership renewal start date.');
    $this->assertEquals($membershipRenewDates['end_date'], '20080120', 'Verify membership renewal end date.');

    $this->membershipDelete($membership->id);
    $this->membershipTypeDelete(array('id' => $membershipType->id));
  }

  /**
   * check function getMembershipTypesByOrg( )
   *
   */
  public function testGetMembershipTypesByOrg() {
    $ids = array();
    $params = array(
      'name' => 'General',
      'description' => NULL,
      'domain_id' => 1,
      'minimum_fee' => 100,
      'duration_unit' => 'year',
      'member_of_contact_id' => $this->_orgContactID,
      'period_type' => 'rolling',
      'duration_interval' => 1,
      'financial_type_id' => $this->_financialTypeId,
      'relationship_type_id' => $this->_relationshipTypeId,
      'visibility' => 'Public',
      'is_active' => 1,
    );
    $membershipType = CRM_Member_BAO_MembershipType::add($params, $ids);

    $membershipTypesResult = civicrm_api3('MembershipType', 'get', array(
      'member_of_contact_id' => $this->_orgContactID,
      'options' => array(
        'limit' => 0,
      ),
    ));
    $result = CRM_Utils_Array::value('values', $membershipTypesResult, NULL);
    $this->assertEquals(empty($result), FALSE, 'Verify membership types for organization.');

    $membershipTypesResult = civicrm_api3('MembershipType', 'get', array(
      'member_of_contact_id' => 501,
      'options' => array(
        'limit' => 0,
      ),
    ));
    $result = CRM_Utils_Array::value('values', $membershipTypesResult, NULL);
    $this->assertEquals(empty($result), TRUE, 'Verify membership types for organization.');

    $this->membershipTypeDelete(array('id' => $membershipType->id));
  }

}
