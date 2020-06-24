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
class CRM_Member_Import_Parser_MembershipTest extends CiviUnitTestCase {
  /**
   * Membership type name used in test function.
   *
   * @var string
   */
  protected $_membershipTypeName = NULL;

  /**
   * Membership type id used in test function.
   *
   * @var string
   */
  protected $_membershipTypeID = NULL;

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
    $this->_relationshipTypeId = $this->relationshipTypeCreate($params);
    $this->_orgContactID = $this->organizationCreate();
    $this->_financialTypeId = 1;
    $this->_membershipTypeName = 'Mickey Mouse Club Member';
    $params = [
      'name' => $this->_membershipTypeName,
      'description' => NULL,
      'minimum_fee' => 10,
      'duration_unit' => 'year',
      'member_of_contact_id' => $this->_orgContactID,
      'period_type' => 'fixed',
      'duration_interval' => 1,
      'financial_type_id' => $this->_financialTypeId,
      'relationship_type_id' => $this->_relationshipTypeId,
      'visibility' => 'Public',
      'is_active' => 1,
      'fixed_period_start_day' => 101,
      'fixed_period_rollover_day' => 1231,
    ];
    $ids = [];
    $membershipType = CRM_Member_BAO_MembershipType::add($params, $ids);
    $this->_membershipTypeID = $membershipType->id;

    $this->_mebershipStatusID = $this->membershipStatusCreate('test status');
    $session = CRM_Core_Session::singleton();
    $session->set('dateTypes', 1);
  }

  /**
   * Tears down the fixture, for example, closes a network connection.
   * This method is called after a test is executed.
   *
   * @throws \CRM_Core_Exception
   */
  public function tearDown() {
    $tablesToTruncate = [
      'civicrm_membership',
      'civicrm_membership_log',
      'civicrm_contribution',
      'civicrm_membership_payment',
      'civicrm_contact',
    ];
    $this->quickCleanup($tablesToTruncate, TRUE);
    $this->relationshipTypeDelete($this->_relationshipTypeId);
    $this->membershipTypeDelete(['id' => $this->_membershipTypeID]);
    $this->membershipStatusDelete($this->_mebershipStatusID);
  }

  /**
   *  Test Import.
   *
   * @throws \CRM_Core_Exception
   */
  public function testImport() {
    $this->individualCreate();
    $contact2Params = [
      'first_name' => 'Anthonita',
      'middle_name' => 'J.',
      'last_name' => 'Anderson',
      'prefix_id' => 3,
      'suffix_id' => 3,
      'email' => 'b@c.com',
      'contact_type' => 'Individual',
    ];

    $this->individualCreate($contact2Params);
    $year = date('Y') - 1;
    $startDate2 = $year . '-10-09';
    $joinDate2 = $year . '-10-10';
    $params = [
      [
        'anthony_anderson@civicrm.org',
        $this->_membershipTypeID,
        date('Y-m-d'),
        date('Y-m-d'),
      ],
      [
        $contact2Params['email'],
        $this->_membershipTypeName,
        $startDate2,
        $joinDate2,
      ],
    ];

    $importObject = $this->createImportObject(['email', 'membership_type_id', 'membership_start_date', 'membership_join_date']);
    foreach ($params as $values) {
      $this->assertEquals(CRM_Import_Parser::VALID, $importObject->import(CRM_Import_Parser::DUPLICATE_UPDATE, $values), $values[0]);
    }
    $result = $this->callAPISuccess('membership', 'get', ['sequential' => 1])['values'];
    $this->assertCount(2, $result);
    $this->assertEquals($startDate2, $result[1]['start_date']);
    $this->assertEquals($joinDate2, $result[1]['join_date']);
  }

  /**
   * Test overriding a membership but not providing status.
   *
   * @throws \CRM_Core_Exception
   */
  public function testImportOverriddenMembershipButWithoutStatus() {
    $this->individualCreate(['email' => 'anthony_anderson2@civicrm.org']);

    $fieldMapper = [
      'mapper[0][0]' => 'email',
      'mapper[1][0]' => 'membership_type_id',
      'mapper[2][0]' => 'membership_start_date',
      'mapper[3][0]' => 'member_is_override',
    ];
    $membershipImporter = new CRM_Member_Import_Parser_Membership($fieldMapper);
    $membershipImporter->init();
    $membershipImporter->_contactType = 'Individual';

    $importValues = [
      'anthony_anderson2@civicrm.org',
      $this->_membershipTypeID,
      date('Y-m-d'),
      TRUE,
    ];

    $importResponse = $membershipImporter->import(CRM_Import_Parser::DUPLICATE_UPDATE, $importValues);
    $this->assertEquals(CRM_Import_Parser::ERROR, $importResponse);
    $this->assertContains('Required parameter missing: Status', $importValues);
  }

  /**
   * Test that the passed in status is respected.
   *
   * @throws \CRM_Core_Exception
   */
  public function testImportOverriddenMembershipWithStatus() {
    $this->individualCreate(['email' => 'anthony_anderson3@civicrm.org']);
    $membershipImporter = $this->createImportObject([
      'email',
      'membership_type_id',
      'membership_start_date',
      'member_is_override',
      'status_id',
    ]);

    $importValues = [
      'anthony_anderson3@civicrm.org',
      $this->_membershipTypeID,
      date('Y-m-d'),
      TRUE,
      'New',
    ];

    $importResponse = $membershipImporter->import(CRM_Import_Parser::DUPLICATE_UPDATE, $importValues);
    $this->assertEquals(CRM_Import_Parser::VALID, $importResponse);
  }

  public function testImportOverriddenMembershipWithValidOverrideEndDate() {
    $this->individualCreate(['email' => 'anthony_anderson4@civicrm.org']);

    $fieldMapper = [
      'mapper[0][0]' => 'email',
      'mapper[1][0]' => 'membership_type_id',
      'mapper[2][0]' => 'membership_start_date',
      'mapper[3][0]' => 'member_is_override',
      'mapper[4][0]' => 'status_id',
      'mapper[5][0]' => 'status_override_end_date',
    ];
    $membershipImporter = new CRM_Member_Import_Parser_Membership($fieldMapper);
    $membershipImporter->init();
    $membershipImporter->_contactType = 'Individual';

    $importValues = [
      'anthony_anderson4@civicrm.org',
      $this->_membershipTypeID,
      date('Y-m-d'),
      TRUE,
      'New',
      date('Y-m-d'),
    ];

    $importResponse = $membershipImporter->import(CRM_Import_Parser::DUPLICATE_UPDATE, $importValues);
    $this->assertEquals(CRM_Import_Parser::VALID, $importResponse);
  }

  public function testImportOverriddenMembershipWithInvalidOverrideEndDate() {
    $this->individualCreate(['email' => 'anthony_anderson5@civicrm.org']);

    $fieldMapper = [
      'mapper[0][0]' => 'email',
      'mapper[1][0]' => 'membership_type_id',
      'mapper[2][0]' => 'membership_start_date',
      'mapper[3][0]' => 'member_is_override',
      'mapper[4][0]' => 'status_id',
      'mapper[5][0]' => 'status_override_end_date',
    ];
    $membershipImporter = new CRM_Member_Import_Parser_Membership($fieldMapper);
    $membershipImporter->init();
    $membershipImporter->_contactType = 'Individual';

    $importValues = [
      'anthony_anderson5@civicrm.org',
      'New',
      date('Y-m-d'),
      TRUE,
      $this->_mebershipStatusID,
      'abc',
    ];

    $importResponse = $membershipImporter->import(CRM_Import_Parser::DUPLICATE_UPDATE, $importValues);
    $this->assertEquals(CRM_Import_Parser::ERROR, $importResponse);
    $this->assertContains('Required parameter missing: Status', $importValues);
  }

  /**
   * Test that memberships can still be imported if the status is renamed.
   *
   * @throws \CRM_Core_Exception
   */
  public function testImportMembershipWithRenamedStatus() {
    $this->individualCreate(['email' => 'anthony_anderson3@civicrm.org']);

    $this->callAPISuccess('MembershipStatus', 'get', [
      'name' => 'New',
      'api.MembershipStatus.create' => [
        'label' => 'New-renamed',
      ],
    ]);
    $membershipImporter = $this->createImportObject([
      'email',
      'membership_type_id',
      'membership_start_date',
      'member_is_override',
      'status_id',
    ]);

    $importValues = [
      'anthony_anderson3@civicrm.org',
      $this->_membershipTypeID,
      date('Y-m-d'),
      TRUE,
      'New-renamed',
    ];

    $importResponse = $membershipImporter->import(CRM_Import_Parser::DUPLICATE_UPDATE, $importValues);
    $this->assertEquals(CRM_Import_Parser::VALID, $importResponse);
    $createdStatusID = $this->callAPISuccessGetValue('Membership', ['return' => 'status_id']);
    $this->assertEquals(CRM_Core_PseudoConstant::getKey('CRM_Member_BAO_Membership', 'status_id', 'New'), $createdStatusID);
    $this->callAPISuccess('MembershipStatus', 'get', [
      'name' => 'New',
      'api.MembershipStatus.create' => [
        'label' => 'New',
      ],
    ]);
  }

  /**
   * Create an import object.
   *
   * @param array $fields
   *
   * @return \CRM_Member_Import_Parser_Membership
   */
  protected function createImportObject(array $fields): \CRM_Member_Import_Parser_Membership {
    $fieldMapper = [];
    foreach ($fields as $index => $field) {
      $fieldMapper['mapper[' . $index . '][0]'] = $field;
    }
    $membershipImporter = new CRM_Member_Import_Parser_Membership($fieldMapper);
    $membershipImporter->init();
    $membershipImporter->_contactType = 'Individual';
    return $membershipImporter;
  }

}
