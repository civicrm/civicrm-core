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
 *  Test APIv3 civicrm_case_* functions
 *
 * @package CiviCRM_APIv3
 * @group headless
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
   * @var \Civi\Core\SettingsStack
   */
  protected $settingsStack;

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

    $this->settingsStack = new \Civi\Core\SettingsStack();
  }

  public function tearDown() {
    $this->settingsStack->popAll();
    parent::tearDown();
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
   * Test Getlist with id and case_id
   */
  public function testCaseGetListById() {
    $params = $this->_params;
    $params['contact_id'] = $this->individualCreate();

    //Create 3 sample Cases.
    $case1 = $this->callAPISuccess('case', 'create', $params);
    $params['subject'] = 'Test Case 2';
    $case2 = $this->callAPISuccess('case', 'create', $params);
    $params['subject'] = 'Test Case 3';
    $this->callAPISuccess('case', 'create', $params);

    $getParams = array(
      'id' => array($case1['id']),
      'extra' => array('contact_id'),
      'params' => array(
        'version' => 3,
        'case_id' => array('!=' => $case2['id']),
        'case_id.is_deleted' => 0,
        'case_id.status_id' => array('!=' => "Closed"),
        'case_id.end_date' => array('IS NULL' => 1),
      ),
    );
    $result = $this->callAPISuccess('case', 'getlist', $getParams);

    //Only 1 case should be returned.
    $this->assertEquals(count($result['values']), 1);
    $this->assertEquals($result['values'][0]['id'], $case1['id']);
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
   * Test create function with resolved status.
   */
  public function testCaseCreateWithResolvedStatus() {
    $params = $this->_params;
    // Test using label instead of value.
    unset($params['case_type_id']);
    $params['case_type'] = $this->caseType;
    $params['status_id'] = 'Closed';
    $result = $this->callAPISuccess('case', 'create', $params);
    $id = $result['id'];

    // Check result
    $result = $this->callAPISuccess('case', 'get', array('id' => $id));
    $this->assertEquals($result['values'][$id]['id'], $id);
    $this->assertEquals($result['values'][$id]['case_type_id'], $this->caseTypeId);
    $this->assertEquals($result['values'][$id]['subject'], $params['subject']);
    $this->assertEquals($result['values'][$id]['end_date'], date('Y-m-d'));

    //Check all relationship end dates are set to case end date.
    $relationships = $this->callAPISuccess('Relationship', 'get', array(
      'sequential' => 1,
      'case_id' => $id,
    ));
    foreach ($relationships['values'] as $key => $values) {
      $this->assertEquals($values['end_date'], date('Y-m-d'));
    }

    //Verify there are no active relationships.
    $activeCaseRelationships = CRM_Case_BAO_Case::getCaseRoles($result['values'][$id]['client_id'][1], $id);
    $this->assertEquals(count($activeCaseRelationships), 0, "Checking for empty array");

    //Check if getCaseRoles() is able to return inactive relationships.
    $caseRelationships = CRM_Case_BAO_Case::getCaseRoles($result['values'][$id]['client_id'][1], $id, NULL, FALSE);
    $this->assertEquals(count($caseRelationships), 1);
  }

  /**
   * Test case create with valid parameters and custom data.
   */
  public function testCaseCreateCustom() {
    $ids = $this->entityCustomGroupWithSingleFieldCreate(__FUNCTION__, __FILE__);
    $params = $this->_params;
    $params['custom_' . $ids['custom_field_id']] = "custom string";
    $result = $this->callAPIAndDocument($this->_entity, 'create', $params, __FUNCTION__, __FILE__);
    $result = $this->callAPISuccess($this->_entity, 'get', array(
      'return.custom_' . $ids['custom_field_id'] => 1,
      'id' => $result['id'],
    ));
    $this->assertEquals("custom string", $result['values'][$result['id']]['custom_' . $ids['custom_field_id']]);

    $this->customFieldDelete($ids['custom_field_id']);
    $this->customGroupDelete($ids['custom_group_id']);
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
    $case = $this->callAPISuccess('case', 'getsingle', array('id' => $id));

    // Update Case.
    $params = array('id' => $id);
    $params['subject'] = $case['subject'] = 'Something Else';
    $this->callAPISuccess('case', 'create', $params);

    // Verify that updated case is equal to the original with new subject.
    $result = $this->callAPISuccessGetSingle('Case', array('case_id' => $id));
    // Modification dates are likely to differ by 0-2 sec. Check manually.
    $this->assertGreaterThanOrEqual($case['modified_date'], $result['modified_date']);
    unset($result['modified_date'], $case['modified_date']);
    // Everything else should be identical.
    $this->assertAPIArrayComparison($result, $case);
  }

  /**
   * Test update (create with id) function with valid parameters.
   */
  public function testCaseUpdateWithExistingCaseContact() {
    $params = $this->_params;
    // Test using name instead of value
    unset($params['case_type_id']);
    $params['case_type'] = $this->caseType;
    $result = $this->callAPISuccess('case', 'create', $params);
    $id = $result['id'];
    $case = $this->callAPISuccess('case', 'getsingle', array('id' => $id));

    // Update Case, we specify existing case ID and existing contact ID to verify that CaseContact.create is not called
    $params = $this->_params;
    $params['id'] = $id;
    $this->callAPISuccess('case', 'create', $params);

    // Verify that updated case is equal to the original with new subject.
    $result = $this->callAPISuccessGetSingle('Case', array('case_id' => $id));
    // Modification dates are likely to differ by 0-2 sec. Check manually.
    $this->assertGreaterThanOrEqual($case['modified_date'], $result['modified_date']);
    unset($result['modified_date'], $case['modified_date']);
    // Everything else should be identical.
    $this->assertAPIArrayComparison($result, $case);
  }

  /**
   * Test case update with custom data
   */
  public function testCaseUpdateCustom() {
    $ids = $this->entityCustomGroupWithSingleFieldCreate(__FUNCTION__, __FILE__);
    $params = $this->_params;

    // Create a case with custom data
    $params['custom_' . $ids['custom_field_id']] = 'custom string';
    $result = $this->callAPISuccess($this->_entity, 'create', $params);

    $caseId = $result['id'];
    $result = $this->callAPISuccess($this->_entity, 'get', array(
      'return.custom_' . $ids['custom_field_id'] => 1,
      'version' => 3,
      'id' => $result['id'],
    ));
    $this->assertEquals("custom string", $result['values'][$result['id']]['custom_' . $ids['custom_field_id']]);
    $fields = $this->callAPISuccess($this->_entity, 'getfields', array('version' => $this->_apiversion));
    $this->assertTrue(is_array($fields['values']['custom_' . $ids['custom_field_id']]));

    // Update the activity with custom data.
    $params = array(
      'id' => $caseId,
      'custom_' . $ids['custom_field_id'] => 'Updated my test data',
      'version' => $this->_apiversion,
    );
    $result = $this->callAPISuccess($this->_entity, 'create', $params);

    $result = $this->callAPISuccess($this->_entity, 'get', array(
      'return.custom_' . $ids['custom_field_id'] => 1,
      'version' => 3,
      'id' => $result['id'],
    ));
    $this->assertEquals("Updated my test data", $result['values'][$result['id']]['custom_' . $ids['custom_field_id']]);
  }

  /**
   * Test delete function with valid parameters.
   */
  public function testCaseDelete() {
    // Create Case
    $result = $this->callAPISuccess('case', 'create', $this->_params);

    // Move Case to Trash
    $id = $result['id'];
    $this->callAPISuccess('case', 'delete', array('id' => $id, 'move_to_trash' => 1));

    // Check result - also check that 'case_id' works as well as 'id'
    $result = $this->callAPISuccess('case', 'get', array('case_id' => $id));
    $this->assertEquals(1, $result['values'][$id]['is_deleted']);

    // Restore Case from Trash
    $this->callAPISuccess('case', 'restore', array('id' => $id));

    // Check result
    $result = $this->callAPISuccess('case', 'get', array('case_id' => $id));
    $this->assertEquals(0, $result['values'][$id]['is_deleted']);

    // Delete Case Permanently
    $this->callAPISuccess('case', 'delete', array('case_id' => $id));

    // Check result - case should no longer exist
    $result = $this->callAPISuccess('case', 'get', array('id' => $id));
    $this->assertEquals(0, $result['count']);
  }

  /**
   * Test Case role relationship is correctly created
   * for contacts.
   */
  public function testCaseRoleRelationships() {
    // Create Case
    $case = $this->callAPISuccess('case', 'create', $this->_params);
    $relType = $this->relationshipTypeCreate(array('name_a_b' => 'Test AB', 'name_b_a' => 'Test BA', 'contact_type_b' => 'Individual'));
    $relContact = $this->individualCreate(array('first_name' => 'First', 'last_name' => 'Last'));

    $_REQUEST = array(
      'rel_type' => "{$relType}_b_a",
      'rel_contact' => $relContact,
      'case_id' => $case['id'],
      'is_unit_test' => TRUE,
    );
    $ret = CRM_Contact_Page_AJAX::relationship();
    $this->assertEquals(0, $ret['is_error']);
    //Check if relationship exist for the case.
    $relationship = $this->callAPISuccess('Relationship', 'get', array(
      'sequential' => 1,
      'relationship_type_id' => $relType,
      'case_id' => $case['id'],
    ));
    $this->assertEquals($relContact, $relationship['values'][0]['contact_id_a']);
    $this->assertEquals($this->_params['contact_id'], $relationship['values'][0]['contact_id_b']);

    //Check if activity is assigned to correct contact.
    $activity = $this->callAPISuccess('Activity', 'get', array(
      'subject' => 'Test BA : Mr. First Last II',
    ));
    $this->callAPISuccess('ActivityContact', 'get', array(
      'contact_id' => $relContact,
      'activity_id' => $activity['id'],
    ));
  }

  /**
   * Test get function based on activity.
   */
  public function testCaseGetByActivity() {
    // Create Case
    $result = $this->callAPISuccess('case', 'create', $this->_params);
    $id = $result['id'];

    // Check result - we should get a list of activity ids
    $result = $this->callAPISuccess('case', 'get', array('id' => $id, 'return' => 'activities'));
    $case = $result['values'][$id];
    $activity = $case['activities'][0];

    // Fetch case based on an activity id
    $result = $this->callAPISuccess('case', 'get', array(
      'activity_id' => $activity,
      'return' => 'activities',
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
    $case = $this->callAPISuccessGetSingle('case', array('id' => $id, 'return' => array('activities', 'contacts')));

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
    $case = $this->callAPISuccessGetSingle('Case', array('id' => $id, 'return' => 'subject'));

    // Fetch case based on client contact id
    $result = $this->callAPISuccess('case', 'get', array(
      'subject' => $this->_params['subject'],
      'return' => array('subject'),
    ));
    $this->assertAPIArrayComparison($result['values'][$id], $case);
  }

  /**
   * Test get function based on wrong subject.
   */
  public function testCaseGetByWrongSubject() {
    $this->callAPISuccess('case', 'create', $this->_params);

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
    $case = $this->callAPISuccessGetSingle('Case', array('id' => $id, 'return' => 'contact_id'));

    $result = $this->callAPISuccess('case', 'get', array('limit' => 0, 'return' => array('contact_id')));
    $this->assertAPIArrayComparison($result['values'][$id], $case);
  }

  /**
   * Test activity api create for case activities.
   */
  public function testCaseActivityCreate() {
    $params = $this->_params;
    $case = $this->callAPISuccess('case', 'create', $params);
    $params = array(
      'case_id' => $case['id'],
      // follow up
      'activity_type_id' => $this->followup_activity_type_value,
      'subject' => 'Test followup 123',
      'source_contact_id' => $this->_loggedInUser,
      'target_contact_id' => $this->_params['contact_id'],
    );
    $result = $this->callAPISuccess('activity', 'create', $params);
    $this->assertEquals($result['values'][$result['id']]['activity_type_id'], $params['activity_type_id']);

    // might need this for other tests that piggyback on this one
    $this->_caseActivityId = $result['values'][$result['id']]['id'];

    // Check other DB tables populated properly - is there a better way to do this? assertDBState() requires that we know the id already.
    $dao = new CRM_Case_DAO_CaseActivity();
    $dao->case_id = $case['id'];
    $dao->activity_id = $this->_caseActivityId;
    $this->assertEquals($dao->find(), 1, 'case_activity table not populated correctly');

    $dao = new CRM_Activity_DAO_ActivityContact();
    $dao->activity_id = $this->_caseActivityId;
    $dao->contact_id = $this->_params['contact_id'];
    $dao->record_type_id = 3;
    $this->assertEquals($dao->find(), 1, 'activity_contact table not populated correctly');

    // Check that fetching an activity by case id works, as well as returning case_id
    $result = $this->callAPISuccessGetSingle('Activity', array(
      'case_id' => $case['id'],
      'activity_type_id' => $this->followup_activity_type_value,
      'subject' => 'Test followup 123',
      'return' => array('case_id'),
    ));
    $this->assertContains($case['id'], $result['case_id']);
  }

  /**
   * Test activity api update for case activities.
   */
  public function testCaseActivityUpdate_Tracked() {
    $this->settingsStack->push('civicaseActivityRevisions', TRUE);

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
    $this->assertEquals($result['values'][$result['id']]['id'], $this->_caseActivityId + 1);
    $this->assertEquals($result['values'][$result['id']]['original_id'], $this->_caseActivityId);

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
  }

  /**
   * If you disable `civicaseActivityRevisions`, then editing an activity
   * will *not* create or change IDs.
   */
  public function testCaseActivityUpdate_Untracked() {
    $this->settingsStack->push('civicaseActivityRevisions', FALSE);

    //  Need to create the case and activity before we can update it
    $this->testCaseActivityCreate();

    $oldIDs = CRM_Utils_SQL_Select::from('civicrm_activity')
      ->select('id, original_id, is_current_revision')
      ->orderBy('id')
      ->execute()->fetchAll();

    $params = array(
      'activity_id' => $this->_caseActivityId,
      'case_id' => 1,
      'activity_type_id' => 14,
      'source_contact_id' => $this->_loggedInUser,
      'subject' => 'New subject',
    );
    $result = $this->callAPISuccess('activity', 'create', $params);
    $this->assertEquals($result['values'][$result['id']]['subject'], $params['subject']);

    // id should not change because we've opted out.
    $this->assertEquals($this->_caseActivityId, $result['values'][$result['id']]['id']);
    $this->assertEmpty($result['values'][$result['id']]['original_id']);

    $newIDs = CRM_Utils_SQL_Select::from('civicrm_activity')
      ->select('id, original_id, is_current_revision')
      ->orderBy('id')
      ->execute()->fetchAll();
    $this->assertEquals($oldIDs, $newIDs);
  }

  public function testCaseActivityUpdateCustom() {
    $this->settingsStack->push('civicaseActivityRevisions', TRUE);

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

  public function testCaseGetWithRoles() {
    $case1 = $this->callAPISuccess('Case', 'create', array(
      'contact_id' => 17,
      'subject' => "Test case with roles",
      'case_type_id' => $this->caseTypeId,
      'status_id' => "Open",
    ));
    $result = $this->callAPISuccessGetSingle('Case', array(
      'id' => $case1['id'],
      'status_id' => "Open",
      'return' => array('contacts'),
    ));
    foreach ($result['contacts'] as $contact) {
      if ($contact['role'] == 'Client') {
        $this->assertEquals(17, $contact['contact_id']);
      }
      elseif ($contact['role'] == 'Homeless Services Coordinator') {
        $this->assertEquals(1, $contact['creator']);
        $this->assertEquals(1, $contact['manager']);
      }
    }
  }

  public function testCaseGetWithDefinition() {
    $case1 = $this->callAPISuccess('Case', 'create', array(
      'contact_id' => 17,
      'subject' => "Test case with definition",
      'case_type_id' => $this->caseTypeId,
      'status_id' => "Open",
    ));
    $result1 = $this->callAPISuccessGetSingle('Case', array(
      'id' => $case1['id'],
      'status_id' => "Open",
      'return' => array('case_type_id.definition'),
    ));
    $result2 = $this->callAPISuccessGetSingle('Case', array(
      'id' => $case1['id'],
      'status_id' => "Open",
      'return' => array('case_type_id', 'case_type_id.definition'),
    ));
    $this->assertEquals($result1['case_type_id.definition'], $result2['case_type_id.definition']);
    $def = $result1['case_type_id.definition'];
    $this->assertEquals(array('name' => 'Open Case', 'max_instances' => 1), $def['activityTypes'][0]);
    $this->assertNotEmpty($def['activitySets'][0]['activityTypes']);
    $this->assertNotEmpty($def['caseRoles'][0]['manager']);
    $this->assertNotEmpty($def['caseRoles'][0]['creator']);
  }

  public function testCaseGetTags() {
    $case1 = $this->callAPISuccess('Case', 'create', array(
      'contact_id' => 17,
      'subject' => "Test case with tags",
      'case_type_id' => $this->caseTypeId,
      'status_id' => "Open",
    ));
    $tag1 = $this->tagCreate(array(
      'name' => 'CaseTag1',
      'used_for' => 'civicrm_case',
    ));
    $tag2 = $this->tagCreate(array(
      'name' => 'CaseTag2',
      'used_for' => 'civicrm_case',
    ));
    $this->callAPISuccess('EntityTag', 'create', array(
      'entity_table' => 'civicrm_case',
      'entity_id' => $case1['id'],
      'tag_id' => $tag1['id'],
    ));
    $this->callAPIFailure('Case', 'getsingle', array(
      'tag_id' => $tag2['id'],
    ));
    $result = $this->callAPISuccessGetSingle('Case', array(
      'tag_id' => $tag1['id'],
      'return' => 'tag_id.name',
    ));
    $this->assertEquals('CaseTag1', $result['tag_id'][$tag1['id']]['tag_id.name']);
  }

  /**
   * Test that a chained api call can use the operator syntax.
   *
   * E.g. array('IN' => $value.contact_id)
   *
   * @throws \Exception
   */
  public function testCaseGetChainedOp() {
    $contact1 = $this->individualCreate(array(), 1);
    $contact2 = $this->individualCreate(array(), 2);
    $case1 = $this->callAPISuccess('Case', 'create', array(
      'contact_id' => $contact1,
      'subject' => "Test case 1",
      'case_type_id' => $this->caseTypeId,
    ));
    $case2 = $this->callAPISuccess('Case', 'create', array(
      'contact_id' => $contact2,
      'subject' => "Test case 2",
      'case_type_id' => $this->caseTypeId,
    ));
    $case3 = $this->callAPISuccess('Case', 'create', array(
      'contact_id' => array($contact1, $contact2),
      'subject' => "Test case 3",
      'case_type_id' => $this->caseTypeId,
    ));

    // Fetch case 1 and all cases with the same client. Chained get should return case 3.
    $result = $this->callAPISuccessGetSingle('Case', array(
      'id' => $case1['id'],
      'return' => 'contact_id',
      'api.Case.get' => array(
        'contact_id' => array('IN' => "\$value.contact_id"),
        'id' => array('!=' => "\$value.id"),
      ),
    ));
    $this->assertEquals($case3['id'], $result['api.Case.get']['id']);

    // Fetch case 3 and all cases with the same clients. Chained get should return case 1&2.
    $result = $this->callAPISuccessGetSingle('Case', array(
      'id' => $case3['id'],
      'return' => array('contact_id'),
      'api.Case.get' => array(
        'return' => 'id',
        'contact_id' => array('IN' => "\$value.contact_id"),
        'id' => array('!=' => "\$value.id"),
      ),
    ));
    $this->assertEquals(array($case1['id'], $case2['id']), array_keys(CRM_Utils_Array::rekey($result['api.Case.get']['values'], 'id')));
  }

  /**
   * Test the ability to order by client using the join syntax.
   *
   * For multi-client cases, should order by the first client.
   */
  public function testCaseGetOrderByClient() {
    $contact1 = $this->individualCreate(array('first_name' => 'Aa', 'last_name' => 'Zz'));
    $contact2 = $this->individualCreate(array('first_name' => 'Bb', 'last_name' => 'Zz'));
    $contact3 = $this->individualCreate(array('first_name' => 'Cc', 'last_name' => 'Xx'));

    $case1 = $this->callAPISuccess('Case', 'create', array(
      'contact_id' => $contact1,
      'subject' => "Test case 1",
      'case_type_id' => $this->caseTypeId,
    ));
    $case2 = $this->callAPISuccess('Case', 'create', array(
      'contact_id' => $contact2,
      'subject' => "Test case 2",
      'case_type_id' => $this->caseTypeId,
    ));
    $case3 = $this->callAPISuccess('Case', 'create', array(
      'contact_id' => array($contact3, $contact1),
      'subject' => "Test case 3",
      'case_type_id' => $this->caseTypeId,
    ));

    $result = $this->callAPISuccess('Case', 'get', array(
      'contact_id' => array('IN' => array($contact1, $contact2, $contact3)),
      'sequential' => 1,
      'return' => 'id',
      'options' => array('sort' => 'contact_id.first_name'),
    ));
    $this->assertEquals($case3['id'], $result['values'][2]['id']);
    $this->assertEquals($case2['id'], $result['values'][1]['id']);
    $this->assertEquals($case1['id'], $result['values'][0]['id']);

    $result = $this->callAPISuccess('Case', 'get', array(
      'contact_id' => array('IN' => array($contact1, $contact2, $contact3)),
      'sequential' => 1,
      'return' => 'id',
      'options' => array('sort' => 'contact_id.last_name ASC, contact_id.first_name DESC'),
    ));
    $this->assertEquals($case1['id'], $result['values'][2]['id']);
    $this->assertEquals($case2['id'], $result['values'][1]['id']);
    $this->assertEquals($case3['id'], $result['values'][0]['id']);

    $result = $this->callAPISuccess('Case', 'get', array(
      'contact_id' => array('IN' => array($contact1, $contact2, $contact3)),
      'sequential' => 1,
      'return' => 'id',
      'options' => array('sort' => 'contact_id.first_name DESC'),
    ));
    $this->assertEquals($case1['id'], $result['values'][2]['id']);
    $this->assertEquals($case2['id'], $result['values'][1]['id']);
    $this->assertEquals($case3['id'], $result['values'][0]['id']);

    $result = $this->callAPISuccess('Case', 'get', array(
      'contact_id' => array('IN' => array($contact1, $contact2, $contact3)),
      'sequential' => 1,
      'return' => 'id',
      'options' => array('sort' => 'case_type_id, contact_id DESC, status_id'),
    ));
    $this->assertEquals($case1['id'], $result['values'][2]['id']);
    $this->assertEquals($case2['id'], $result['values'][1]['id']);
    $this->assertEquals($case3['id'], $result['values'][0]['id']);
    $this->assertCount(3, $result['values']);
  }

  /**
   * Test the ability to add a timeline to an existing case.
   *
   * See the case.addtimeline api.
   *
   * @param bool $enableRevisions
   *
   * @dataProvider caseActivityRevisionExamples
   *
   * @throws \Exception
   */
  public function testCaseAddtimeline($enableRevisions) {
    $this->settingsStack->push('civicaseActivityRevisions', $enableRevisions);

    $caseSpec = array(
      'title' => 'Application with Definition',
      'name' => 'Application_with_Definition',
      'is_active' => 1,
      'weight' => 4,
      'definition' => array(
        'activityTypes' => array(
          array('name' => 'Follow up'),
        ),
        'activitySets' => array(
          array(
            'name' => 'set1',
            'label' => 'Label 1',
            'timeline' => 1,
            'activityTypes' => array(
              array('name' => 'Open Case', 'status' => 'Completed'),
            ),
          ),
          array(
            'name' => 'set2',
            'label' => 'Label 2',
            'timeline' => 1,
            'activityTypes' => array(
              array('name' => 'Follow up'),
            ),
          ),
        ),
        'caseRoles' => array(
          array('name' => 'Homeless Services Coordinator', 'creator' => 1, 'manager' => 1),
        ),
      ),
    );
    $cid = $this->individualCreate();
    $caseType = $this->callAPISuccess('CaseType', 'create', $caseSpec);
    $case = $this->callAPISuccess('Case', 'create', array(
      'case_type_id' => $caseType['id'],
      'contact_id' => $cid,
      'subject' => 'Test case with timeline',
    ));
    // Created case should only have 1 activity per the spec
    $result = $this->callAPISuccessGetSingle('Activity', array('case_id' => $case['id'], 'return' => 'activity_type_id.name'));
    $this->assertEquals('Open Case', $result['activity_type_id.name']);
    // Add timeline.
    $this->callAPISuccess('Case', 'addtimeline', array(
      'case_id' => $case['id'],
      'timeline' => 'set2',
    ));
    $result = $this->callAPISuccess('Activity', 'get', array(
      'case_id' => $case['id'],
      'return' => 'activity_type_id.name',
      'sequential' => 1,
      'options' => array('sort' => 'id'),
    ));
    $this->assertEquals(2, $result['count']);
    $this->assertEquals('Follow up', $result['values'][1]['activity_type_id.name']);
  }

  /**
   * Test the case merge function.
   *
   * 2 cases should be mergeable into 1
   *
   * @throws \Exception
   */
  public function testCaseMerge() {
    $contact1 = $this->individualCreate(array(), 1);
    $case1 = $this->callAPISuccess('Case', 'create', array(
      'contact_id' => $contact1,
      'subject' => "Test case 1",
      'case_type_id' => $this->caseTypeId,
    ));
    $case2 = $this->callAPISuccess('Case', 'create', array(
      'contact_id' => $contact1,
      'subject' => "Test case 2",
      'case_type_id' => $this->caseTypeId,
    ));
    $result = $this->callAPISuccess('Case', 'getcount', array('contact_id' => $contact1));
    $this->assertEquals(2, $result);

    $this->callAPISuccess('Case', 'merge', array('case_id_1' => $case1['id'], 'case_id_2' => $case2['id']));

    $result = $this->callAPISuccess('Case', 'getsingle', array('id' => $case2['id']));
    $this->assertEquals(1, $result['is_deleted']);
  }

  /**
   * Get case activity revision sample data.
   *
   * @return array
   */
  public function caseActivityRevisionExamples() {
    $examples = array();
    $examples[] = array(FALSE);
    $examples[] = array(TRUE);
    return $examples;
  }

  public function testTimestamps() {
    $params = $this->_params;
    $case_created = $this->callAPISuccess('case', 'create', $params);

    $case_1 = $this->callAPISuccess('Case', 'getsingle', array(
      'id' => $case_created['id'],
    ));
    $this->assertRegExp(';^\d\d\d\d-\d\d-\d\d \d\d:\d\d;', $case_1['created_date']);
    $this->assertRegExp(';^\d\d\d\d-\d\d-\d\d \d\d:\d\d;', $case_1['modified_date']);
    $this->assertApproxEquals(strtotime($case_1['created_date']), strtotime($case_1['modified_date']), 2);

    $activity_1 = $this->callAPISuccess('activity', 'getsingle', array(
      'case_id' => $case_created['id'],
      'options' => array(
        'limit' => 1,
      ),
    ));
    $this->assertRegExp(';^\d\d\d\d-\d\d-\d\d \d\d:\d\d;', $activity_1['created_date']);
    $this->assertRegExp(';^\d\d\d\d-\d\d-\d\d \d\d:\d\d;', $activity_1['modified_date']);
    $this->assertApproxEquals(strtotime($activity_1['created_date']), strtotime($activity_1['modified_date']), 2);

    usleep(1.5 * 1000000);
    $this->callAPISuccess('activity', 'create', array(
      'id' => $activity_1['id'],
      'subject' => 'Make cheese',
    ));

    $activity_2 = $this->callAPISuccess('activity', 'getsingle', array(
      'id' => $activity_1['id'],
    ));
    $this->assertRegExp(';^\d\d\d\d-\d\d-\d\d \d\d:\d\d;', $activity_2['created_date']);
    $this->assertRegExp(';^\d\d\d\d-\d\d-\d\d \d\d:\d\d;', $activity_2['modified_date']);
    $this->assertNotEquals($activity_2['created_date'], $activity_2['modified_date']);

    $this->assertEquals($activity_1['created_date'], $activity_2['created_date']);
    $this->assertNotEquals($activity_1['modified_date'], $activity_2['modified_date']);
    $this->assertLessThan($activity_2['modified_date'], $activity_1['modified_date'],
      sprintf("Original modification time (%s) should predate later modification time (%s)", $activity_1['modified_date'], $activity_2['modified_date']));

    $case_2 = $this->callAPISuccess('Case', 'getsingle', array(
      'id' => $case_created['id'],
    ));
    $this->assertRegExp(';^\d\d\d\d-\d\d-\d\d \d\d:\d\d;', $case_2['created_date']);
    $this->assertRegExp(';^\d\d\d\d-\d\d-\d\d \d\d:\d\d;', $case_2['modified_date']);
    $this->assertEquals($case_1['created_date'], $case_2['created_date']);
    $this->assertNotEquals($case_2['created_date'], $case_2['modified_date']);
  }

}
