<?php
/**
 * @file
 * File for the TestCase class
 *
 *  (PHP 5)
 *
 * @author Walt Haas <walt@dharmatech.org> (801) 534-1262
 * @copyright Copyright CiviCRM LLC (C) 2009
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html
 *              GNU Affero General Public License version 3
 * @version   $Id: ActivityTest.php 31254 2010-12-15 10:09:29Z eileen $
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
 * Include class definitions
 */
require_once 'CiviTest/CiviCaseTestCase.php';

/**
 *  Test APIv3 civicrm_case_* functions
 *
 * @package CiviCRM_APIv3
 */
class api_v3_CaseTest extends CiviCaseTestCase {
  protected $_params;
  protected $_entity;
  protected $_apiversion = 3;
  protected $followup_activity_type_value;
  /**
   * Activity ID of created case.
   *
   * @var int
   */
  protected $_caseActivityId;

  /**
   * Test setup for every test.
   *
   * Connect to the database, truncate the tables that will be used
   * and redirect stdin to a temporary file.
   */
  public function setUp() {
    $this->_entity = 'case';

    parent::setUp();

    $activityTypes = $this->callAPISuccess('option_value', 'get', array(
      'option_group_id' => 2,
      'name' => 'Follow Up',
      'label' => 'Follow Up',
      'sequential' => 1,
    ));
    $this->followup_activity_type_value = $activityTypes['values'][0]['value'];

    $this->_params = array(
      'case_type_id' => $this->caseTypeId,
      'subject' => 'Test case',
      'contact_id' => 17,
    );
  }

  /**
   * Check with empty array.
   */
  public function testCaseCreateEmpty() {
    $this->callAPIFailure('case', 'create', array());
  }

  /**
   * Check if required fields are not passed.
   */
  public function testCaseCreateWithoutRequired() {
    $params = array(
      'subject' => 'this case should fail',
      'case_type_id' => 1,
    );

    $this->callAPIFailure('case', 'create', $params);
  }

  /**
   * Test create function with valid parameters.
   */
  public function testCaseCreate() {
    $params = $this->_params;
    // Test using label instead of value.
    unset($params['case_type_id']);
    $params['case_type'] = $this->caseType;
    $result = $this->callAPIAndDocument('case', 'create', $params, __FUNCTION__, __FILE__);
    $id = $result['id'];

    // Check result
    $result = $this->callAPISuccess('case', 'get', array('id' => $id));
    $this->assertEquals($result['values'][$id]['id'], $id);
    $this->assertEquals($result['values'][$id]['case_type_id'], $this->caseTypeId);
    $this->assertEquals($result['values'][$id]['subject'], $params['subject']);
  }

  /**
   * Test update (create with id) function with valid parameters.
   */
  public function testCaseUpdate() {
    $params = $this->_params;
    // Test using name instead of value
    unset($params['case_type_id']);
    $params['case_type'] = $this->caseType;
    $result = $this->callAPISuccess('case', 'create', $params);
    $id = $result['id'];
    $result = $this->callAPISuccess('case', 'get', array('id' => $id));
    $case = $result['values'][$id];

    // Update Case.
    $params = array('id' => $id);
    $params['subject'] = $case['subject'] = 'Something Else';
    $this->callAPISuccess('case', 'create', $params);

    // Verify that updated case is exactly equal to the original with new subject.
    $result = $this->callAPISuccess('case', 'get', array('case_id' => $id));
    $this->assertEquals($result['values'][$id], $case);
  }

  /**
   * Test delete function with valid parameters.
   */
  public function testCaseDelete() {
    // Create Case
    $result = $this->callAPISuccess('case', 'create', $this->_params);

    // Move Case to Trash
    $id = $result['id'];
    $result = $this->callAPISuccess('case', 'delete', array('id' => $id, 'move_to_trash' => 1));

    // Check result - also check that 'case_id' works as well as 'id'
    $result = $this->callAPISuccess('case', 'get', array('case_id' => $id));
    $this->assertEquals(1, $result['values'][$id]['is_deleted']);

    // Delete Case Permanently - also check that 'case_id' works as well as 'id'
    $result = $this->callAPISuccess('case', 'delete', array('case_id' => $id));

    // Check result - case should no longer exist
    $result = $this->callAPISuccess('case', 'get', array('id' => $id));
    $this->assertEquals(0, $result['count']);
  }

  /**
   * Test get function based on activity.
   */
  public function testCaseGetByActivity() {
    // Create Case
    $result = $this->callAPISuccess('case', 'create', $this->_params);
    $id = $result['id'];

    // Check result - we should get a list of activity ids
    $result = $this->callAPISuccess('case', 'get', array('id' => $id));
    $case = $result['values'][$id];
    $activity = $case['activities'][0];

    // Fetch case based on an activity id
    $result = $this->callAPISuccess('case', 'get', array(
        'activity_id' => $activity,
        'return' => 'activities,contacts',
      ));
    $this->assertEquals(FALSE, empty($result['values'][$id]));
    $this->assertEquals($result['values'][$id], $case);
  }

  /**
   * Test get function based on contact id.
   */
  public function testCaseGetByContact() {
    // Create Case
    $result = $this->callAPISuccess('case', 'create', $this->_params);
    $id = $result['id'];

    // Store result for later
    $case = $this->callAPISuccess('case', 'getsingle', array('id' => $id));

    // Fetch case based on client contact id
    $result = $this->callAPISuccess('case', 'get', array(
        'client_id' => $this->_params['contact_id'],
        'return' => array('activities', 'contacts'),
      ));
    $this->assertAPIArrayComparison($result['values'][$id], $case);
  }

  /**
   * Test get function based on subject.
   */
  public function testCaseGetBySubject() {
    // Create Case
    $result = $this->callAPISuccess('case', 'create', $this->_params);
    $id = $result['id'];

    // Store result for later
    $case = $this->callAPISuccess('case', 'getsingle', array('id' => $id));

    // Fetch case based on client contact id
    $result = $this->callAPISuccess('case', 'get', array(
        'subject' => $this->_params['subject'],
        'return' => array('activities', 'contacts'),
      ));
    $this->assertAPIArrayComparison($result['values'][$id], $case);
  }

  /**
   * Test get function based on wrong subject.
   */
  public function testCaseGetByWrongSubject() {
    $result = $this->callAPISuccess('case', 'create', $this->_params);

    // Append 'wrong' to subject so that it is no longer the same.
    $result = $this->callAPISuccess('case', 'get', array(
        'subject' => $this->_params['subject'] . 'wrong',
        'return' => array('activities', 'contacts'),
      ));
    $this->assertEquals(0, $result['count']);
  }

  /**
   * Test get function with no criteria.
   */
  public function testCaseGetNoCriteria() {
    $result = $this->callAPISuccess('case', 'create', $this->_params);
    $id = $result['id'];

    // Store result for later
    $case = $this->callAPISuccess('case', 'getsingle', array('id' => $id));

    $result = $this->callAPISuccess('case', 'get', array('return' => array('activities', 'contacts')));
    $this->assertAPIArrayComparison($result['values'][$id], $case);
  }

  /**
   * Test activity api create for case activities.
   */
  public function testCaseActivityCreate() {
    $params = $this->_params;
    $this->callAPISuccess('case', 'create', $params);
    $params = array(
      'case_id' => 1,
      // follow up
      'activity_type_id' => $this->followup_activity_type_value,
      'subject' => 'Test followup',
      'source_contact_id' => $this->_loggedInUser,
      'target_contact_id' => $this->_params['contact_id'],
    );
    $result = $this->callAPISuccess('activity', 'create', $params);
    $this->assertEquals($result['values'][$result['id']]['activity_type_id'], $params['activity_type_id']);

    // might need this for other tests that piggyback on this one
    $this->_caseActivityId = $result['values'][$result['id']]['id'];

    // Check other DB tables populated properly - is there a better way to do this? assertDBState() requires that we know the id already.
    $dao = new CRM_Case_DAO_CaseActivity();
    $dao->case_id = 1;
    $dao->activity_id = $this->_caseActivityId;
    $this->assertEquals($dao->find(), 1, 'case_activity table not populated correctly in line ' . __LINE__);
    $dao->free();

    $dao = new CRM_Activity_DAO_ActivityContact();
    $dao->activity_id = $this->_caseActivityId;
    $dao->contact_id = $this->_params['contact_id'];
    $dao->record_type_id = 3;
    $this->assertEquals($dao->find(), 1, 'activity_contact table not populated correctly in line ' . __LINE__);
    $dao->free();

    // TODO: There's more things we could check
  }

  /**
   * Test activity api update for case activities.
   */
  public function testCaseActivityUpdate() {
    // Need to create the case and activity before we can update it
    $this->testCaseActivityCreate();

    $params = array(
      'activity_id' => $this->_caseActivityId,
      'case_id' => 1,
      'activity_type_id' => 14,
      'source_contact_id' => $this->_loggedInUser,
      'subject' => 'New subject',
    );
    $result = $this->callAPISuccess('activity', 'create', $params);

    $this->assertEquals($result['values'][$result['id']]['subject'], $params['subject']);

    // id should be one greater, since this is a new revision
    $this->assertEquals($result['values'][$result['id']]['id'],
      $this->_caseActivityId + 1,
      'in line ' . __LINE__
    );
    $this->assertEquals($result['values'][$result['id']]['original_id'],
      $this->_caseActivityId,
      'in line ' . __LINE__
    );

    // Check revision is as expected
    $revParams = array(
      'activity_id' => $this->_caseActivityId,
    );
    $revActivity = $this->callAPISuccess('activity', 'get', $revParams);
    $this->assertEquals($revActivity['values'][$this->_caseActivityId]['is_current_revision'],
      0);
    $this->assertEquals($revActivity['values'][$this->_caseActivityId]['is_deleted'],
      0
    );

    //TODO: check some more things
  }

  public function testCaseActivityUpdateCustom() {
    // Create a case first
    $result = $this->callAPISuccess('case', 'create', $this->_params);

    // Create custom field group
    // Note the second parameter is Activity on purpose, not Case.
    $custom_ids = $this->entityCustomGroupWithSingleFieldCreate(__FUNCTION__, 'ActivityTest.php');

    // create activity
    $params = array(
      'case_id' => $result['id'],
      // follow up
      'activity_type_id' => 14,
      'subject' => 'Test followup',
      'source_contact_id' => $this->_loggedInUser,
      'target_contact_id' => $this->_params['contact_id'],
      'custom_' . $custom_ids['custom_field_id'] => "custom string",
    );
    $result = $this->callAPISuccess('activity', 'create', $params);

    $aid = $result['values'][$result['id']]['id'];

    // Update activity
    $params = array(
      'activity_id' => $aid,
      'case_id' => 1,
      'activity_type_id' => 14,
      'source_contact_id' => $this->_loggedInUser,
      'subject' => 'New subject',
    );
    $this->callAPISuccess('activity', 'create', $params);

    // Retrieve revision and check custom fields got copied.
    $revParams = array(
      'activity_id' => $aid + 1,
      'return.custom_' . $custom_ids['custom_field_id'] => 1,
    );
    $revAct = $this->callAPISuccess('activity', 'get', $revParams);

    $this->assertEquals($revAct['values'][$aid + 1]['custom_' . $custom_ids['custom_field_id']], "custom string",
      "Error message: " . CRM_Utils_Array::value('error_message', $revAct));

    $this->customFieldDelete($custom_ids['custom_field_id']);
    $this->customGroupDelete($custom_ids['custom_group_id']);
  }

  public function testCaseGetByStatus() {
    // Create 2 cases with different status ids.
    $case1 = $this->callAPISuccess('Case', 'create', array(
      'contact_id' => 17,
      'subject' => "Test case 1",
      'case_type_id' => $this->caseTypeId,
      'status_id' => "Open",
      'sequential' => 1,
    ));
    $this->callAPISuccess('Case', 'create', array(
      'contact_id' => 17,
      'subject' => "Test case 2",
      'case_type_id' => $this->caseTypeId,
      'status_id' => "Urgent",
      'sequential' => 1,
    ));
    $result = $this->callAPISuccessGetSingle('Case', array(
      'sequential' => 1,
      'contact_id' => 17,
      'status_id' => "Open",
    ));
    $this->assertEquals($case1['id'], $result['id']);
  }

}
