<?php
/**
 * @file
 *  File for the ActivitySearchTest class
 *
 *  (PHP 5)
 *
 * @copyright Copyright CiviCRM LLC (C) 2009
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html
 *              GNU Affero General Public License version 3
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
 *  Include class definitions
 */

/**
 * Test APIv3 civicrm_activity_* functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Activity
 * @group headless
 */
class CRM_Contact_BAO_ActivitySearchTest extends CiviUnitTestCase {
  protected $_contactID;
  protected $_params;
  protected $test_activity_type_value;

  /**
   * Test setup for every test.
   *
   * Connect to the database, truncate the tables that will be used
   * and redirect stdin to a temporary file
   */
  public function setUp() {
    // Connect to the database
    parent::setUp();

    $this->_contactID = $this->individualCreate();
    //create activity types
    $activityTypes = $this->callAPISuccess('option_value', 'create', array(
      'option_group_id' => 2,
      'name' => 'Test activity type',
      'label' => 'Test activity type',
      'sequential' => 1,
    ));
    $this->test_activity_type_id = $activityTypes['id'];
    $this->_params = array(
      'source_contact_id' => $this->_contactID,
      'activity_type_id' => $activityTypes['values'][0]['value'],
      'subject' => 'test activity type id',
      'activity_date_time' => '2011-06-02 14:36:13',
      'status_id' => 2,
      'priority_id' => 1,
      'duration' => 120,
      'location' => 'Pennsylvania',
      'details' => 'a test activity',
    );
    // create a logged in USER since the code references it for source_contact_id
    $this->createLoggedInUser();
  }

  /**
   * Tears down the fixture, for example, closes a network connection.
   *
   * This method is called after a test is executed.
   */
  public function tearDown() {
    $tablesToTruncate = array(
      'civicrm_contact',
      'civicrm_activity',
      'civicrm_activity_contact',
      'civicrm_uf_match',
    );
    $this->quickCleanup($tablesToTruncate, TRUE);
    $type = $this->callAPISuccess('optionValue', 'get', array('id' => $this->test_activity_type_id));
    if (!empty($type['count'])) {
      $this->callAPISuccess('option_value', 'delete', array('id' => $this->test_activity_type_id));
    }
  }

  /**
   * Test that activity.get api works when filtering on subject.
   */
  public function testSearchBySubjectOnly() {
    $subject = 'test activity ' . __FUNCTION__;
    $params = $this->_params;
    $params['subject'] = $subject;
    $this->callAPISuccess('Activity', 'Create', $params);

    $case = array(
      'form_value' => array(
        'activity_text' => $subject,
        'activity_option' => 3,
      ),
      'expected_count' => 1,
      'expected_contact' => array($this->_contactID),
    );
    $query = new CRM_Contact_BAO_Query(CRM_Contact_BAO_Query::convertFormValues($case['form_value']));
    list($select, $from, $where, $having) = $query->query();
    $groupContacts = CRM_Core_DAO::executeQuery("SELECT DISTINCT contact_a.id $from $where")->fetchAll();
    foreach ($groupContacts as $key => $value) {
      $groupContacts[$key] = $value['id'];
    }
    $this->assertEquals($case['expected_count'], count($groupContacts));
    $this->checkArrayEquals($case['expected_contact'], $groupContacts);
  }

  /**
   * Test that activity.get api works when filtering on subject.
   */
  public function testSearchBySubjectBoth() {
    $subject = 'test activity ' . __FUNCTION__;
    $params = $this->_params;
    $params['subject'] = $subject;
    $activity = $this->callAPISuccess('Activity', 'Create', $params);

    $case = array(
      'form_value' => array(
        'activity_text' => $subject,
        'activity_option' => 6,
      ),
      'expected_count' => 1,
      'expected_contact' => array($this->_contactID),
    );
    $query = new CRM_Contact_BAO_Query(CRM_Contact_BAO_Query::convertFormValues($case['form_value']));
    list($select, $from, $where, $having) = $query->query();
    $groupContacts = CRM_Core_DAO::executeQuery("SELECT DISTINCT contact_a.id $from $where")->fetchAll();
    foreach ($groupContacts as $key => $value) {
      $groupContacts[$key] = $value['id'];
    }
    $this->assertEquals($case['expected_count'], count($groupContacts));
    $this->checkArrayEquals($case['expected_contact'], $groupContacts);
  }

  /**
   * Test that activity.get api works when filtering on subject.
   */
  public function testSearchByDetailsOnly() {
    $details = 'test activity ' . __FUNCTION__;
    $params = $this->_params;
    $params['details'] = $details;
    $activity = $this->callAPISuccess('Activity', 'Create', $params);

    $case = array(
      'form_value' => array(
        'activity_text' => $details,
        'activity_option' => 2,
      ),
      'expected_count' => 1,
      'expected_contact' => array($this->_contactID),
    );
    $query = new CRM_Contact_BAO_Query(CRM_Contact_BAO_Query::convertFormValues($case['form_value']));
    list($select, $from, $where, $having) = $query->query();
    $groupContacts = CRM_Core_DAO::executeQuery("SELECT DISTINCT contact_a.id $from $where")->fetchAll();
    foreach ($groupContacts as $key => $value) {
      $groupContacts[$key] = $value['id'];
    }
    $this->assertEquals($case['expected_count'], count($groupContacts));
    $this->checkArrayEquals($case['expected_contact'], $groupContacts);
  }

  /**
   * Test that activity.get api works when filtering on details.
   */
  public function testSearchByDetailsBoth() {
    $details = 'test activity ' . __FUNCTION__;
    $params = $this->_params;
    $params['details'] = $details;
    $activity = $this->callAPISuccess('Activity', 'Create', $params);

    $case = array(
      'form_value' => array(
        'activity_text' => $details,
        'activity_option' => 6,
      ),
      'expected_count' => 1,
      'expected_contact' => array($this->_contactID),
    );
    $query = new CRM_Contact_BAO_Query(CRM_Contact_BAO_Query::convertFormValues($case['form_value']));
    list($select, $from, $where, $having) = $query->query();
    $groupContacts = CRM_Core_DAO::executeQuery("SELECT DISTINCT contact_a.id $from $where")->fetchAll();
    foreach ($groupContacts as $key => $value) {
      $groupContacts[$key] = $value['id'];
    }
    $this->assertEquals($case['expected_count'], count($groupContacts));
    $this->checkArrayEquals($case['expected_contact'], $groupContacts);
  }

}
