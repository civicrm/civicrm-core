<?php

/**
 *  File for the TestActivityType class
 *
 *  (PHP 5)
 *
 * @package   CiviCRM
 *
 *   This file is part of CiviCRM
 *
 *   CiviCRM is free software; you can redistribute it and/or
 *   modify it under the terms of the GNU Affero General Public License
 *   as published by the Free Software Foundation; either version 3 of
 *   the License, or (at your option) any later version.
 *
 *   CiviCRM is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU Affero General Public License for more details.
 *
 *   You should have received a copy of the GNU Affero General Public
 *   License along with this program.  If not, see
 *   <http://www.gnu.org/licenses/>.
 */

/**
 *  Test CRM/Member/BAO Membership Log add , delete functions
 *
 * @package   CiviCRM
 * @group headless
 */
class CRM_Member_BAO_MembershipLogTest extends CiviUnitTestCase {

  /**
   * @var int
   */
  private $relationshipTypeID;

  /**
   * @var int
   */
  private $organizationContactID;

  /**
   * @var int
   */
  private $financialTypeID;

  /**
   * @var int
   */
  private $membershipStatusID;

  /**
   * @var int
   */
  private $membershipTypeID;

  /**
   * Set up for test.
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function setUp() {
    parent::setUp();

    $params = [
      'contact_type_a' => 'Individual',
      'contact_type_b' => 'Organization',
      'name_a_b' => 'Test Employee of',
      'name_b_a' => 'Test Employer of',
    ];
    $this->relationshipTypeID = $this->relationshipTypeCreate($params);
    $this->organizationContactID = $this->organizationCreate();
    $this->financialTypeID = 1;

    $params = [
      'name' => 'test type',
      'description' => NULL,
      'minimum_fee' => 10,
      'duration_unit' => 'year',
      'member_of_contact_id' => $this->organizationContactID,
      'period_type' => 'fixed',
      'fixed_period_start_day' => '0101',
      'fixed_period_rollover_day' => '0101',
      'duration_interval' => 1,
      'financial_type_id' => $this->financialTypeID,
      'relationship_type_id' => $this->relationshipTypeID,
      'visibility' => 'Public',
      'is_active' => 1,
    ];
    $membershipType = CRM_Member_BAO_MembershipType::add($params);
    $this->membershipTypeID = $membershipType->id;
    $this->membershipStatusID = $this->membershipStatusCreate('test status');
  }

  /**
   * Tears down the fixture.
   *
   * @throws \CRM_Core_Exception
   */
  public function tearDown() {
    $this->relationshipTypeDelete($this->relationshipTypeID);
    $this->quickCleanUpFinancialEntities();
    $this->restoreMembershipTypes();
    $this->contactDelete($this->organizationContactID);
    parent::tearDown();
  }

  /**
   *  Test del function.
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testDel() {
    list($contactID, $membershipID) = $this->setupMembership();
    CRM_Member_BAO_MembershipLog::del($membershipID);
    $this->assertDBNull('CRM_Member_BAO_MembershipLog', $membershipID, 'membership_id',
      'id', 'Database check for deleted membership log.'
    );

    $this->membershipDelete($membershipID);
    $this->contactDelete($contactID);
  }

  /**
   *  Test reset modified ID.
   *
   * @throws \CRM_Core_Exception
   */
  public function testResetModifiedID() {
    list($contactID, $membershipID) = $this->setupMembership();
    CRM_Member_BAO_MembershipLog::resetModifiedID($contactID);
    $this->assertDBNull('CRM_Member_BAO_MembershipLog', $contactID, 'modified_id',
      'modified_id', 'Database check for NULL modified id.'
    );

    $this->membershipDelete($membershipID);
    $this->contactDelete($contactID);
  }

  /**
   * Test that the value for modified_id can be set.
   *
   * @throws \CRM_Core_Exception
   */
  public function testCreateMembershipWithPassedInModifiedID() {
    $modifier = $this->individualCreate();
    $membershipID = $this->setupMembership($modifier)[1];
    $this->assertEquals($modifier, $this->callAPISuccessGetValue('MembershipLog', ['membership_id' => $membershipID, 'return' => 'modified_id']));
  }

  /**
   * Set up membership.
   *
   * @param int|null $modifiedID
   *
   * @return array
   *
   * @throws \CRM_Core_Exception
   */
  private function setupMembership($modifiedID = NULL): array {
    $contactID = $this->individualCreate();
    $modifiedID = $modifiedID ?? $contactID;

    $params = [
      'contact_id' => $contactID,
      'membership_type_id' => $this->membershipTypeID,
      'join_date' => date('Ymd', strtotime('2006-01-21')),
      'start_date' => date('Ymd', strtotime('2006-01-21')),
      'end_date' => date('Ymd', strtotime('2006-12-21')),
      'source' => 'Payment',
      'is_override' => 1,
      'status_id' => $this->membershipStatusID,
      'modified_id' => $modifiedID,
    ];

    $membershipID = $this->callAPISuccess('Membership', 'create', $params)['id'];
    $this->assertEquals($modifiedID, CRM_Core_DAO::singleValueQuery(
      'SELECT modified_id FROM civicrm_membership_log WHERE membership_id = %1',
      [1 => [$membershipID, 'Integer']]
    ));
    return [$contactID, $membershipID];
  }

}
