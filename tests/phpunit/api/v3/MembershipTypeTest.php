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
 * Class api_v3_MembershipTypeTest
 */
class api_v3_MembershipTypeTest extends CiviUnitTestCase {
  protected $_contactID;
  protected $_contributionTypeID;
  protected $_apiversion;
  protected $_entity = 'MembershipType';

  /**
   * Set up for tests.
   */
  public function setUp() {
    parent::setUp();
    $this->useTransaction(TRUE);
    $this->_apiversion = 3;
    $this->_contactID = $this->organizationCreate();
  }

  /**
   * Get the membership without providing an ID.
   *
   * This should return an empty array but not an error.
   */
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

    $membershipType = $this->callAPISuccess('membership_type', 'get', $params);
    $this->assertEquals($membershipType['count'], 0);
  }

  /**
   * Test get works.
   */
  public function testGet() {
    $id = $this->membershipTypeCreate(array('member_of_contact_id' => $this->_contactID));

    $params = array(
      'id' => $id,
    );
    $membershipType = $this->callAPIAndDocument('membership_type', 'get', $params, __FUNCTION__, __FILE__);
    $this->assertEquals($membershipType['values'][$id]['name'], 'General');
    $this->assertEquals($membershipType['values'][$id]['member_of_contact_id'], $this->_contactID);
    $this->assertEquals($membershipType['values'][$id]['financial_type_id'], 1);
    $this->assertEquals($membershipType['values'][$id]['duration_unit'], 'year');
    $this->assertEquals($membershipType['values'][$id]['duration_interval'], '1');
    $this->assertEquals($membershipType['values'][$id]['period_type'], 'rolling');
    $this->membershipTypeDelete($params);
  }

  /**
   * Test create with missing mandatory field.
   */
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

    $this->callAPIFailure('membership_type', 'create', $params, 'Mandatory key(s) missing from params array: member_of_contact_id');
  }

  /**
   * Test successful create.
   */
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

  /**
   * Test update.
   */
  public function testUpdate() {
    $id = $this->membershipTypeCreate(array('member_of_contact_id' => $this->_contactID, 'financial_type_id' => 2));
    $newMemberOrgParams = array(
      'organization_name' => 'New membership organisation',
      'contact_type' => 'Organization',
      'visibility' => 1,
    );

    $params = array(
      'id' => $id,
      'name' => 'Updated General',
      'member_of_contact_id' => $this->organizationCreate($newMemberOrgParams),
      'duration_unit' => 'month',
      'duration_interval' => '10',
      'period_type' => 'fixed',
      'domain_id' => 1,
    );

    $this->callAPISuccess('membership_type', 'update', $params);

    $this->getAndCheck($params, $id, $this->_entity);
  }

  /**
   * Test successful delete.
   */
  public function testDelete() {
    $membershipTypeID = $this->membershipTypeCreate(array('member_of_contact_id' => $this->organizationCreate()));
    $params = array(
      'id' => $membershipTypeID,
    );

    $this->callAPIAndDocument('membership_type', 'delete', $params, __FUNCTION__, __FILE__);
  }

  /**
   * Delete test that could do with a decent comment block.
   *
   * I can't skim this & understand it so if anyone does explain it here.
   */
  public function testDeleteRelationshipTypesUsedByMembershipType() {
    $rel1 = $this->relationshipTypeCreate(array(
      'name_a_b' => 'abcde',
      'name_b_a' => 'abcde',
    ));
    $rel2 = $this->relationshipTypeCreate(array(
      'name_a_b' => 'fghij',
      'name_b_a' => 'fghij',
    ));
    $rel3 = $this->relationshipTypeCreate(array(
      'name_a_b' => 'lkmno',
      'name_b_a' => 'lkmno',
    ));
    $id = $this->membershipTypeCreate(array(
      'member_of_contact_id' => $this->_contactID,
      'relationship_type_id' => array($rel1, $rel2, $rel3),
      'relationship_direction' => array('a_b', 'a_b', 'b_a'),
    ));

    $this->callAPISuccess('RelationshipType', 'delete', array('id' => $rel2));
    $newValues = $this->callAPISuccess('MembershipType', 'getsingle', array('id' => $id));
    $this->assertEquals(array($rel1, $rel3), $newValues['relationship_type_id']);
    $this->assertEquals(array('a_b', 'b_a'), $newValues['relationship_direction']);

    $this->callAPISuccess('RelationshipType', 'delete', array('id' => $rel1));
    $newValues = $this->callAPISuccess('MembershipType', 'getsingle', array('id' => $id));
    $this->assertEquals(array($rel3), $newValues['relationship_type_id']);
    $this->assertEquals(array('b_a'), $newValues['relationship_direction']);

    $this->callAPISuccess('RelationshipType', 'delete', array('id' => $rel3));
    $newValues = $this->callAPISuccess('MembershipType', 'getsingle', array('id' => $id));
    $this->assertTrue(empty($newValues['relationship_type_id']));
    $this->assertTrue(empty($newValues['relationship_direction']));
  }

  /**
   * Test that membership type getlist returns an array of enabled membership types.
   */
  public function testMembershipTypeGetList() {
    $this->membershipTypeCreate();
    $this->membershipTypeCreate(array('name' => 'cheap-skates'));
    $this->membershipTypeCreate(array('name' => 'disabled cheap-skates', 'is_active' => 0));
    $result = $this->callAPISuccess('MembershipType', 'getlist', array());
    $this->assertEquals(2, $result['count']);
    $this->assertEquals('cheap-skates', $result['values'][0]['label']);
    $this->assertEquals('General', $result['values'][1]['label']);
  }

}
