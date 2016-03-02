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
   * @var String
   */
  protected $_membershipTypeName = NULL;

  /**
   * Membership type id used in test function.
   * @var String
   */
  protected $_membershipTypeID = NULL;

  public function setUp() {
    parent::setUp();

    $params = array(
      'contact_type_a' => 'Individual',
      'contact_type_b' => 'Organization',
      'name_a_b' => 'Test Employee of',
      'name_b_a' => 'Test Employer of',
    );
    $this->_relationshipTypeId = $this->relationshipTypeCreate($params);
    $this->_orgContactID = $this->organizationCreate();
    $this->_financialTypeId = 1;
    $this->_membershipTypeName = 'Mickey Mouse Club Member';
    $params = array(
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
    );
    $ids = array();
    $membershipType = CRM_Member_BAO_MembershipType::add($params, $ids);
    $this->_membershipTypeID = $membershipType->id;

    $this->_mebershipStatusID = $this->membershipStatusCreate('test status');
    $session = CRM_Core_Session::singleton();
    $session->set('dateTypes', 1);
  }

  /**
   * Tears down the fixture, for example, closes a network connection.
   * This method is called after a test is executed.
   */
  public function tearDown() {
    $tablesToTruncate = array(
      'civicrm_membership',
      'civicrm_membership_log',
      'civicrm_contribution',
      'civicrm_membership_payment',
    );
    $this->quickCleanup($tablesToTruncate);
    $this->relationshipTypeDelete($this->_relationshipTypeId);
    $this->membershipTypeDelete(array('id' => $this->_membershipTypeID));
    $this->membershipStatusDelete($this->_mebershipStatusID);
    $this->contactDelete($this->_orgContactID);
  }

  /**
   *  Test Import.
   */
  public function testImport() {
    $contactId = $this->individualCreate();
    $contact2Params = array(
      'first_name' => 'Anthonita',
      'middle_name' => 'J.',
      'last_name' => 'Anderson',
      'prefix_id' => 3,
      'suffix_id' => 3,
      'email' => 'b@c.com',
      'contact_type' => 'Individual',
    );
    $contactId = $this->individualCreate($contact2Params);
    $year = date('Y') - 1;
    $startDate2 = date('Y-m-d', mktime(0, 0, 0, 9, 10, $year));
    $params = array(
      array(
        'anthony_anderson@civicrm.org',
        $this->_membershipTypeID,
        date('Y-m-d'),
      ),
      array(
        $contact2Params['email'],
        $this->_membershipTypeName,
        $startDate2,
      ),
    );
    $fieldMapper = array(
      'mapper[0][0]' => 'email',
      'mapper[1][0]' => 'membership_type_id',
      'mapper[2][0]' => 'membership_start_date',
    );
    /*

    $params = array(
    'contact_id' => $contactId,
    'membership_type_id' => $this->_membershipTypeID,
    'join_date' => '2006-01-21',
    'start_date' => '2006-01-21',
    'end_date' => '2006-12-21',
    'source' => 'Payment',
    'is_override' => 1,
    'status_id' => $this->_mebershipStatusID,
    );
     */
    $importObject = new CRM_Member_Import_Parser_Membership($fieldMapper);
    $importObject->init();
    $importObject->_contactType = 'Individual';
    foreach ($params as $values) {
      $this->assertEquals(CRM_Import_Parser::VALID, $importObject->import(CRM_Import_Parser::DUPLICATE_UPDATE, $values), $values[0]);
    }
    $result = $this->callAPISuccess('membership', 'get', array());
    $this->assertEquals(2, $result['count']);
  }

}
