<?php
/**
 * @file
 *  File for the TestActivity class
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
 *  Include class definitions
 */

/**
 * Test APIv3 civicrm_activity_* functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Activity
 * @group headless
 */
class api_v3_ActivityTest extends CiviUnitTestCase {
  protected $_params;
  protected $_params2;
  protected $_entity = 'activity';
  protected $_apiversion = 3;
  protected $test_activity_type_value;
  protected $_contactID;
  /**
   * Activity type id created for use in this test class.
   *
   * @var int
   */
  protected $test_activity_type_id;

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
    $this->test_activity_type_value = 9999;
    $activityTypes = $this->callAPISuccess('option_value', 'create', array(
      'option_group_id' => 2,
      'name' => 'Test activity type',
      'label' => 'Test activity type',
      'value' => $this->test_activity_type_value,
      'sequential' => 1,
    ));
    $this->test_activity_type_id = $activityTypes['id'];
    $this->_params = array(
      'source_contact_id' => $this->_contactID,
      'activity_type_id' => 'Test activity type',
      'subject' => 'test activity type id',
      'activity_date_time' => '2011-06-02 14:36:13',
      'status_id' => 2,
      'priority_id' => 1,
      'duration' => 120,
      'location' => 'Pennsylvania',
      'details' => 'a test activity',
    );
    $this->_params2 = array(
      'source_contact_id' => $this->_contactID,
      'subject' => 'Eat & drink',
      'activity_date_time' => date('Ymd'),
      'duration' => 120,
      'location' => 'Napier',
      'details' => 'discuss & eat',
      'status_id' => 1,
      'activity_type_id' => $this->test_activity_type_value,
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
   * Check fails with empty array.
   */
  public function testActivityCreateEmpty() {
    $this->callAPIFailure('activity', 'create', array());
  }

  /**
   * Check if required fields are not passed.
   */
  public function testActivityCreateWithoutRequired() {
    $params = array(
      'subject' => 'this case should fail',
      'scheduled_date_time' => date('Ymd'),
    );
    $this->callAPIFailure('activity', 'create', $params);
  }

  /**
   * Test civicrm_activity_create() with mismatched activity_type_id
   * and activity_name.
   */
  public function testActivityCreateMismatchNameType() {
    $params = array(
      'source_contact_id' => $this->_contactID,
      'subject' => 'Test activity',
      'activity_date_time' => date('Ymd'),
      'duration' => 120,
      'location' => 'Pennsylvania',
      'details' => 'a test activity',
      'status_id' => 1,
      'activity_name' => 'Fubar activity type',
      'activity_type_id' => 5,
      'scheduled_date_time' => date('Ymd'),
    );

    $this->callAPIFailure('activity', 'create', $params);
  }

  /**
   * Test civicrm_activity_id() with missing source_contact_id is put with the current user.
   */
  public function testActivityCreateWithMissingContactId() {
    $params = array(
      'subject' => 'Make-it-Happen Meeting',
      'activity_date_time' => date('Ymd'),
      'duration' => 120,
      'location' => 'Pennsylvania',
      'details' => 'a test activity',
      'status_id' => 1,
      'activity_name' => 'Test activity type',
    );

    $this->callAPISuccess('activity', 'create', $params);
  }

  /**
   * CRM-20316 this should fail based on validation with no logged in user.
   *
   * Since the field is required the validation should reject the default.
   */
  public function testActivityCreateWithMissingContactIdNoLoggedInUser() {
    CRM_Core_Session::singleton()->set('userID', NULL);
    $params = array(
      'subject' => 'Make-it-Happen Meeting',
      'activity_date_time' => date('Ymd'),
      'duration' => 120,
      'location' => 'Pennsylvania',
      'details' => 'a test activity',
      'status_id' => 1,
      'activity_name' => 'Test activity type',
    );

    $this->callAPIFailure('activity', 'create', $params, 'source_contact_id is not a valid integer');
  }

  /**
   * Test civicrm_activity_id() with non-numeric source_contact_id.
   */
  public function testActivityCreateWithNonNumericContactId() {
    $params = array(
      'source_contact_id' => 'fubar',
      'subject' => 'Make-it-Happen Meeting',
      'activity_date_time' => date('Ymd'),
      'duration' => 120,
      'location' => 'Pennsylvania',
      'details' => 'a test activity',
      'status_id' => 1,
      'activity_name' => 'Test activity type',
    );

    $this->callAPIFailure('activity', 'create', $params);
  }

  /**
   * Ensure that an invalid activity type causes failure.
   *
   * Oddly enough this test was failing because the creation of the invalid type
   * got added to the set up routine. Probably a mis-fix on a test
   */
  public function testActivityCreateWithNonNumericActivityTypeId() {
    $params = array(
      'source_contact_id' => $this->_contactID,
      'subject' => 'Make-it-Happen Meeting',
      'activity_date_time' => date('Ymd'),
      'duration' => 120,
      'location' => 'Pennsylvania',
      'details' => 'a test activity',
      'status_id' => 1,
      'activity_type_id' => 'Invalid Test activity type',
    );

    $this->callAPIFailure('activity', 'create', $params);
  }

  /**
   * Check with incorrect required fields.
   */
  public function testActivityCreateWithUnknownActivityTypeId() {
    $this->callAPIFailure('activity', 'create', [
      'source_contact_id' => $this->_contactID,
      'subject' => 'Make-it-Happen Meeting',
      'activity_date_time' => date('Ymd'),
      'duration' => 120,
      'location' => 'Pennsylvania',
      'details' => 'a test activity',
      'status_id' => 1,
      'activity_type_id' => 699,
    ]);
  }

  public function testActivityCreateWithInvalidPriority() {
    $params = array(
      'source_contact_id' => $this->_contactID,
      'subject' => 'Make-it-Happen Meeting',
      'activity_date_time' => date('Ymd'),
      'duration' => 120,
      'location' => 'Pennsylvania',
      'details' => 'a test activity',
      'status_id' => 1,
      'priority_id' => 44,
      'activity_type_id' => 1,
    );

    $result = $this->callAPIFailure('activity', 'create', $params,
      "'44' is not a valid option for field priority_id");
    $this->assertEquals('priority_id', $result['error_field']);
  }

  /**
   * Test create succeeds with valid string for priority.
   */
  public function testActivityCreateWithValidStringPriority() {
    $params = array(
      'source_contact_id' => $this->_contactID,
      'subject' => 'Make-it-Happen Meeting',
      'activity_date_time' => date('Ymd'),
      'duration' => 120,
      'location' => 'Pennsylvania',
      'details' => 'a test activity',
      'status_id' => 1,
      'priority_id' => 'Urgent',
      'activity_type_id' => 1,
    );

    $result = $this->callAPISuccess('activity', 'create', $params);
    $this->assertEquals(1, $result['values'][$result['id']]['priority_id']);
  }

  /**
   * Test create fails with invalid priority string.
   */
  public function testActivityCreateWithInValidStringPriority() {
    $params = array(
      'source_contact_id' => $this->_contactID,
      'subject' => 'Make-it-Happen Meeting',
      'activity_date_time' => date('Ymd'),
      'duration' => 120,
      'location' => 'Pennsylvania',
      'details' => 'a test activity',
      'status_id' => 1,
      'priority_id' => 'ergUrgent',
      'activity_type_id' => 1,
    );

    $this->callAPIFailure('activity', 'create', $params,
      "'ergUrgent' is not a valid option for field priority_id");
  }

  /**
   * Test civicrm_activity_create() with valid parameters.
   */
  public function testActivityCreate() {

    $this->callAPISuccess('activity', 'create', $this->_params);
    $result = $this->callAPISuccess('activity', 'get', $this->_params);
    $this->assertEquals($result['values'][$result['id']]['duration'], 120);
    $this->assertEquals($result['values'][$result['id']]['subject'], 'test activity type id');
    $this->assertEquals($result['values'][$result['id']]['activity_date_time'], '2011-06-02 14:36:13');
    $this->assertEquals($result['values'][$result['id']]['location'], 'Pennsylvania');
    $this->assertEquals($result['values'][$result['id']]['details'], 'a test activity');
    $this->assertEquals($result['values'][$result['id']]['status_id'], 2);
    $this->assertEquals($result['values'][$result['id']]['id'], $result['id']);
  }

  /**
   * Test civicrm_activity_create() with valid parameters - use type_id.
   */
  public function testActivityCreateCampaignTypeID() {
    $this->enableCiviCampaign();

    $params = array(
      'source_contact_id' => $this->_contactID,
      'subject' => 'Make-it-Happen Meeting',
      'activity_date_time' => '20110316',
      'duration' => 120,
      'location' => 'Pennsylvania',
      'details' => 'a test activity',
      'status_id' => 1,
      'activity_type_id' => 29,
    );

    $result = $this->callAPISuccess('activity', 'create', $params);

    $result = $this->callAPISuccess('activity', 'getsingle', array('id' => $result['id']));
    $this->assertEquals($result['duration'], 120);
    $this->assertEquals($result['subject'], 'Make-it-Happen Meeting');
    $this->assertEquals($result['activity_date_time'], '2011-03-16 00:00:00');
    $this->assertEquals($result['location'], 'Pennsylvania');
    $this->assertEquals($result['details'], 'a test activity');
    $this->assertEquals($result['status_id'], 1);

    $priorities = $this->callAPISuccess('activity', 'getoptions', array('field' => 'priority_id'));
    $this->assertEquals($result['priority_id'], array_search('Normal', $priorities['values']));
  }

  /**
   * Test get returns target and assignee contacts.
   */
  public function testActivityReturnTargetAssignee() {

    $description = "Demonstrates setting & retrieving activity target & source.";
    $subfile = "GetTargetandAssignee";
    $params = array(
      'source_contact_id' => $this->_contactID,
      'subject' => 'Make-it-Happen Meeting',
      'activity_date_time' => '20110316',
      'duration' => 120,
      'location' => 'Pennsylvania',
      'details' => 'a test activity',
      'status_id' => 1,
      'activity_type_id' => 1,
      'priority_id' => 1,
      'target_contact_id' => $this->_contactID,
      'assignee_contact_id' => $this->_contactID,
    );

    $result = $this->callAPIAndDocument('activity', 'create', $params, __FUNCTION__, __FILE__, $description, $subfile);
    $result = $this->callAPISuccess('activity', 'get', array(
      'id' => $result['id'],
      'version' => $this->_apiversion,
      'return.assignee_contact_id' => 1,
      'return.target_contact_id' => 1,
    ));

    $this->assertEquals($this->_contactID, $result['values'][$result['id']]['assignee_contact_id'][0]);
    $this->assertEquals($this->_contactID, $result['values'][$result['id']]['target_contact_id'][0]);
  }

  /**
   * Test get returns target and assignee contact names.
   */
  public function testActivityReturnTargetAssigneeName() {

    $description = "Demonstrates retrieving activity target & source contact names.";
    $subfile = "GetTargetandAssigneeName";
    $target1 = $this->callAPISuccess('Contact', 'create', array('contact_type' => 'Individual', 'first_name' => 'A', 'last_name' => 'Cat'));
    $target2 = $this->callAPISuccess('Contact', 'create', array('contact_type' => 'Individual', 'first_name' => 'B', 'last_name' => 'Good'));
    $assignee = $this->callAPISuccess('Contact', 'create', array('contact_type' => 'Individual', 'first_name' => 'C', 'last_name' => 'Shore'));
    $source = $this->callAPISuccess('Contact', 'create', array('contact_type' => 'Individual', 'first_name' => 'D', 'last_name' => 'Bug'));

    $params = array(
      'source_contact_id' => $source['id'],
      'subject' => 'Make-it-Happen Meeting',
      'activity_date_time' => '20170316',
      'status_id' => 1,
      'activity_type_id' => 1,
      'target_contact_id' => array($target1['id'], $target2['id']),
      'assignee_contact_id' => $assignee['id'],
    );

    $result = $this->callAPISuccess('activity', 'create', $params);
    $result = $this->callAPIAndDocument('activity', 'getsingle', array(
      'id' => $result['id'],
      'return' => array('source_contact_name', 'target_contact_name', 'assignee_contact_name', 'subject'),
    ), __FUNCTION__, __FILE__, $description, $subfile);

    $this->assertEquals($params['subject'], $result['subject']);
    $this->assertEquals($source['id'], $result['source_contact_id']);
    $this->assertEquals('D Bug', $result['source_contact_name']);
    $this->assertEquals('A Cat', $result['target_contact_name'][$target1['id']]);
    $this->assertEquals('B Good', $result['target_contact_name'][$target2['id']]);
    $this->assertEquals('C Shore', $result['assignee_contact_name'][$assignee['id']]);
    $this->assertEquals($assignee['id'], $result['assignee_contact_id'][0]);
  }

  /**
   * Test civicrm_activity_create() with valid parameters and custom data.
   */
  public function testActivityCreateCustom() {
    $ids = $this->entityCustomGroupWithSingleFieldCreate(__FUNCTION__, __FILE__);
    $params = $this->_params;
    $params['custom_' . $ids['custom_field_id']] = "custom string";
    $result = $this->callAPIAndDocument($this->_entity, 'create', $params, __FUNCTION__, __FILE__);
    $result = $this->callAPISuccess($this->_entity, 'get', array(
      'return.custom_' . $ids['custom_field_id'] => 1,
      'id' => $result['id'],
    ));
    $this->assertEquals("custom string", $result['values'][$result['id']]['custom_' . $ids['custom_field_id']], ' in line ' . __LINE__);

    $this->customFieldDelete($ids['custom_field_id']);
    $this->customGroupDelete($ids['custom_group_id']);
  }

  /**
   * Test civicrm_activity_create() using example code.
   */
  public function testActivityCreateExample() {
    require_once 'api/v3/examples/Activity/Create.php';
    $result = activity_create_example();
    $expectedResult = activity_create_expectedresult();
    // Compare everything *except* timestamps.
    unset($result['values'][1]['created_date']);
    unset($result['values'][1]['modified_date']);
    unset($expectedResult['values'][1]['created_date']);
    unset($expectedResult['values'][1]['modified_date']);
    $this->assertEquals($result, $expectedResult);
  }

  /**
   * Test civicrm_activity_create() with valid parameters and custom data.
   */
  public function testActivityCreateCustomSubType() {
    $ids = $this->entityCustomGroupWithSingleFieldCreate(__FUNCTION__, __FILE__);
    $this->callAPISuccess('CustomGroup', 'create', array(
      'extends_entity_column_value' => $this->test_activity_type_value,
      'id' => $ids['custom_group_id'],
      'extends' => 'Activity',
      'is_active' => TRUE,
    ));
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
   * Test civicrm_activity_create() with valid parameters and custom data.
   */
  public function testActivityCreateCustomContactRefField() {

    $this->callAPISuccess('contact', 'create', array('id' => $this->_contactID, 'sort_name' => 'Contact, Test'));
    $subfile = 'ContactRefCustomField';
    $description = "Demonstrates create with Contact Reference Custom Field.";
    $ids = $this->entityCustomGroupWithSingleFieldCreate(__FUNCTION__, __FILE__);
    $params = array(
      'custom_group_id' => $ids['custom_group_id'],
      'name' => 'Worker_Lookup',
      'label' => 'Worker Lookup',
      'html_type' => 'Autocomplete-Select',
      'data_type' => 'ContactReference',
      'weight' => 4,
      'is_searchable' => 1,
      'is_active' => 1,
    );

    $customField = $this->callAPISuccess('custom_field', 'create', $params);
    $params = $this->_params;
    $params['custom_' . $customField['id']] = "$this->_contactID";

    $result = $this->callAPIAndDocument($this->_entity, 'create', $params, __FUNCTION__, __FILE__, $description, $subfile);
    $result = $this->callAPIAndDocument($this->_entity, 'get', array(
      'return.custom_' . $customField['id'] => 1,
      'id' => $result['id'],
    ), __FUNCTION__, __FILE__, 'Get with Contact Ref Custom Field', 'ContactRefCustomFieldGet');

    $this->assertEquals('Anderson, Anthony', $result['values'][$result['id']]['custom_' . $customField['id']]);
    $this->assertEquals($this->_contactID, $result['values'][$result['id']]['custom_' . $customField['id'] . "_id"], ' in line ' . __LINE__);
    $this->assertEquals('Anderson, Anthony', $result['values'][$result['id']]['custom_' . $customField['id'] . '_1'], ' in line ' . __LINE__);
    $this->assertEquals($this->_contactID, $result['values'][$result['id']]['custom_' . $customField['id'] . "_1_id"], ' in line ' . __LINE__);
    $this->customFieldDelete($ids['custom_field_id']);
    $this->customGroupDelete($ids['custom_group_id']);
  }

  /**
   * Test civicrm_activity_create() with an invalid text status_id.
   */
  public function testActivityCreateBadTextStatus() {

    $params = array(
      'source_contact_id' => $this->_contactID,
      'subject' => 'Discussion on Apis for v3',
      'activity_date_time' => date('Ymd'),
      'duration' => 120,
      'location' => 'Pennsylvania',
      'details' => 'a test activity',
      'status_id' => 'Invalid',
      'activity_name' => 'Test activity type',
    );

    $this->callAPIFailure('activity', 'create', $params);
  }

  /**
   * Test civicrm_activity_create() with an invalid text status_id.
   */
  public function testActivityCreateSupportActivityStatus() {

    $params = array(
      'source_contact_id' => $this->_contactID,
      'subject' => 'Discussion on Apis for v3',
      'activity_date_time' => date('Ymd'),
      'duration' => 120,
      'location' => 'Pennsylvania',
      'details' => 'a test activity',
      'activity_status_id' => 'Invalid',
      'activity_name' => 'Test activity type',
    );

    $this->callAPIFailure('activity', 'create', $params,
      "'Invalid' is not a valid option for field status_id");
  }

  /**
   * Test civicrm_activity_create() with using a text status_id.
   */
  public function testActivityCreateTextStatus() {

    $params = array(
      'source_contact_id' => $this->_contactID,
      'subject' => 'Make-it-Happen Meeting',
      'activity_date_time' => date('Ymd'),
      'duration' => 120,
      'location' => 'Pennsylvania',
      'details' => 'a test activity',
      'status_id' => 'Scheduled',
      'activity_name' => 'Test activity type',
    );

    $result = $this->callAPISuccess('activity', 'create', $params);
    $this->assertEquals($result['values'][$result['id']]['duration'], 120);
    $this->assertEquals($result['values'][$result['id']]['subject'], 'Make-it-Happen Meeting');
    $this->assertEquals($result['values'][$result['id']]['activity_date_time'], date('Ymd') . '000000');
    $this->assertEquals($result['values'][$result['id']]['location'], 'Pennsylvania');
    $this->assertEquals($result['values'][$result['id']]['details'], 'a test activity');
  }

  /**
   * Test civicrm_activity_get() with no params
   */
  public function testActivityGetEmpty() {
    $this->callAPISuccess('activity', 'get', array());
  }

  /**
   * Test civicrm_activity_get() with a good activity ID
   */
  public function testActivityGetGoodID1() {
    // Insert rows in civicrm_activity creating activities 4 and 13
    $description = "Demonstrates getting assignee_contact_id & using it to get the contact.";
    $subfile = 'ReturnAssigneeContact';
    $activity = $this->callAPISuccess('activity', 'create', $this->_params);

    $contact = $this->callAPISuccess('Contact', 'Create', array(
      'first_name' => "The Rock",
      'last_name' => 'roccky',
      'contact_type' => 'Individual',
      'version' => 3,
      'api.activity.create' => array(
        'id' => $activity['id'],
        'assignee_contact_id' => '$value.id',
      ),
    ));

    $params = array(
      'activity_id' => $activity['id'],
      'version' => $this->_apiversion,
      'sequential' => 1,
      'return.assignee_contact_id' => 1,
      'api.contact.get' => array(
        'id' => '$value.source_contact_id',
      ),
    );

    $result = $this->callAPIAndDocument('Activity', 'Get', $params, __FUNCTION__, __FILE__, $description, $subfile);

    $this->assertEquals($activity['id'], $result['id']);

    $this->assertEquals($contact['id'], $result['values'][0]['assignee_contact_id'][0]);

    $this->assertEquals($this->_contactID, $result['values'][0]['api.contact.get']['values'][0]['contact_id']);
    $this->assertEquals($this->test_activity_type_value, $result['values'][0]['activity_type_id']);
    $this->assertEquals("test activity type id", $result['values'][0]['subject']);
  }

  /**
   * test that get functioning does filtering.
   */
  public function testGetFilter() {
    $params = array(
      'source_contact_id' => $this->_contactID,
      'subject' => 'Make-it-Happen Meeting',
      'activity_date_time' => '20110316',
      'duration' => 120,
      'location' => 'Pennsylvania',
      'details' => 'a test activity',
      'status_id' => 1,
      'activity_name' => 'Test activity type',
      'priority_id' => 1,
    );
    $result = $this->callAPISuccess('Activity', 'Create', $params);
    $this->callAPISuccess('Activity', 'Get', array('subject' => 'Make-it-Happen Meeting'));
    $this->assertEquals(1, $result['count']);
    $this->assertEquals('Make-it-Happen Meeting', $result['values'][$result['id']]['subject']);
    $this->callAPISuccess('Activity', 'Delete', array('id' => $result['id']));
  }

  /**
   * Test civicrm_activity_get() with filter target_contact_id
   */
  public function testActivityGetTargetFilter() {
    $params = $this->_params;
    $contact1Params = array(
      'first_name' => 'John',
      'middle_name' => 'J.',
      'last_name' => 'Anderson',
      'prefix_id' => 3,
      'suffix_id' => 3,
      'email' => 'john_anderson@civicrm.org',
      'contact_type' => 'Individual',
    );

    $contact1 = $this->individualCreate($contact1Params);
    $contact2Params = array(
      'first_name' => 'Michal',
      'middle_name' => 'J.',
      'last_name' => 'Anderson',
      'prefix_id' => 3,
      'suffix_id' => 3,
      'email' => 'michal_anderson@civicrm.org',
      'contact_type' => 'Individual',
    );

    $contact2 = $this->individualCreate($contact2Params);

    $this->callAPISuccess('OptionValue', 'get', ['name' => 'Activity Targets', 'api.OptionValue.create' => ['label' => 'oh so creative']]);

    $params['assignee_contact_id'] = array($contact1, $contact2);
    $params['target_contact_id'] = array($contact2 => $contact2);
    $activity = $this->callAPISuccess('Activity', 'Create', $params);

    $activityGet = $this->callAPISuccess('Activity', 'get', array(
      'id' => $activity['id'],
      'target_contact_id' => $contact2,
      'return.target_contact_id' => 1,
    ));
    $this->assertEquals($activity['id'], $activityGet['id']);
    $this->assertEquals($contact2, $activityGet['values'][$activityGet['id']]['target_contact_id'][0]);

    $activityGet = $this->callAPISuccess('activity', 'get', array(
      'target_contact_id' => $this->_contactID,
      'return.target_contact_id' => 1,
      'id' => $activity['id'],
    ));
    if ($activityGet['count'] > 0) {
      $this->assertNotEquals($contact2, $activityGet['values'][$activityGet['id']]['target_contact_id'][0]);
    }
  }

  /**
   * Test that activity.get api works when filtering on subject.
   */
  public function testActivityGetSubjectFilter() {
    $subject = 'test activity ' . __FUNCTION__ . mt_rand();
    $params = $this->_params;
    $params['subject'] = $subject;
    $this->callAPISuccess('Activity', 'Create', $params);
    $activityGet = $this->callAPISuccess('activity', 'getsingle', array(
      'subject' => $subject,
    ));
    $this->assertEquals($activityGet['subject'], $subject);
  }

  /**
   * Test that activity.get api works when filtering on details.
   */
  public function testActivityGetDetailsFilter() {
    $details = 'test activity ' . __FUNCTION__ . mt_rand();
    $params = $this->_params;
    $params['details'] = $details;
    $activity = $this->callAPISuccess('Activity', 'Create', $params);
    $activityget = $this->callAPISuccess('activity', 'getsingle', array(
      'details' => $details,
    ));
    $this->assertEquals($activityget['details'], $details);
  }

  /**
   * Test that activity.get api works when filtering on tag.
   */
  public function testActivityGetTagFilter() {
    $tag = $this->callAPISuccess('Tag', 'create', array('name' => mt_rand(), 'used_for' => 'Activities'));
    $activity = $this->callAPISuccess('Activity', 'Create', $this->_params);
    $this->callAPISuccess('EntityTag', 'create', array('entity_table' => 'civicrm_activity', 'tag_id' => $tag['id'], 'entity_id' => $activity['id']));
    $activityget = $this->callAPISuccess('activity', 'getsingle', array(
      'tag_id' => $tag['id'],
    ));
    $this->assertEquals($activityget['id'], $activity['id']);
  }

  /**
   * Return tag info
   */
  public function testJoinOnTags() {
    $tagName = 'act_tag_nm_' . mt_rand();
    $tagDescription = 'act_tag_ds_' . mt_rand();
    $tagColor = '#' . substr(md5(mt_rand()), 0, 6);
    $tag = $this->callAPISuccess('Tag', 'create', array('name' => $tagName, 'color' => $tagColor, 'description' => $tagDescription, 'used_for' => 'Activities'));
    $activity = $this->callAPISuccess('Activity', 'Create', $this->_params);
    $this->callAPISuccess('EntityTag', 'create', array('entity_table' => 'civicrm_activity', 'tag_id' => $tag['id'], 'entity_id' => $activity['id']));
    $activityget = $this->callAPISuccess('activity', 'getsingle', array(
      'id' => $activity['id'],
      'return' => array('tag_id.name', 'tag_id.description', 'tag_id.color'),
    ));
    $this->assertEquals($tagName, $activityget['tag_id'][$tag['id']]['tag_id.name']);
    $this->assertEquals($tagColor, $activityget['tag_id'][$tag['id']]['tag_id.color']);
    $this->assertEquals($tagDescription, $activityget['tag_id'][$tag['id']]['tag_id.description']);
  }

  /**
   * Test that activity.get api works to filter on and return files.
   */
  public function testActivityGetFile() {
    $activity = $this->callAPISuccess('Activity', 'create', $this->_params);
    $activity2 = $this->callAPISuccess('Activity', 'create', $this->_params2);
    $file = $this->callAPISuccess('Attachment', 'create', array(
      'name' => 'actAttachment.txt',
      'mime_type' => 'text/plain',
      'description' => 'My test description',
      'content' => 'My test content',
      'entity_table' => 'civicrm_activity',
      'entity_id' => $activity2['id'],
    ));
    $activityget = $this->callAPISuccess('activity', 'getsingle', array(
      'file_id' => $file['id'],
      'return' => 'file_id',
    ));
    $this->assertEquals($activityget['id'], $activity2['id']);
    $this->assertEquals($file['id'], $activityget['file_id'][0]);
  }

  /**
   * test that get functioning does filtering.
   */
  public function testGetStatusID() {
    $params = array(
      'source_contact_id' => $this->_contactID,
      'subject' => 'Make-it-Happen Meeting',
      'activity_date_time' => '20110316',
      'duration' => 120,
      'location' => 'Pennsylvania',
      'details' => 'a test activity',
      'status_id' => 1,
      'activity_name' => 'Test activity type',
      'priority_id' => 1,
    );
    $this->callAPISuccess('Activity', 'Create', $params);
    $result = $this->callAPISuccess('Activity', 'Get', array('activity_status_id' => '1'));
    $this->assertEquals(1, $result['count'], 'one activity of status 1 should exist');

    $result = $this->callAPISuccess('Activity', 'Get', array('status_id' => '1'));
    $this->assertEquals(1, $result['count'], 'status_id should also work');

    $result = $this->callAPISuccess('Activity', 'Get', array('activity_status_id' => '2'));
    $this->assertEquals(0, $result['count'], 'No activities of status 1 should exist');
    $result = $this->callAPISuccess('Activity', 'Get', array(
      'version' => $this->_apiversion,
      'status_id' => '2',
    ));
    $this->assertEquals(0, $result['count'], 'No activities of status 1 should exist');

  }

  /**
   * test that get functioning does filtering.
   */
  public function testGetFilterMaxDate() {
    $params = array(
      'source_contact_id' => $this->_contactID,
      'subject' => 'Make-it-Happen Meeting',
      'activity_date_time' => '20110101',
      'duration' => 120,
      'location' => 'Pennsylvania',
      'details' => 'a test activity',
      'status_id' => 1,
      'activity_name' => 'Test activity type',
      'version' => $this->_apiversion,
      'priority_id' => 1,
    );
    $activityOne = $this->callAPISuccess('Activity', 'Create', $params);
    $params['activity_date_time'] = 20120216;
    $activityTwo = $this->callAPISuccess('Activity', 'Create', $params);
    $result = $this->callAPISuccess('Activity', 'Get', array(
      'version' => 3,
    ));
    $description = "Demonstrates _low filter (at time of writing doesn't work if contact_id is set.";
    $subfile = "DateTimeLow";
    $this->assertEquals(2, $result['count']);
    $params = array(
      'version' => 3,
      'filter.activity_date_time_low' => '20120101000000',
      'sequential' => 1,
    );
    $result = $this->callAPIAndDocument('Activity', 'Get', $params, __FUNCTION__, __FILE__, $description, $subfile);
    $this->assertEquals(1, $result['count']);
    $description = "Demonstrates _high filter (at time of writing doesn't work if contact_id is set.";
    $subfile = "DateTimeHigh";
    $this->assertEquals('2012-02-16 00:00:00', $result['values'][0]['activity_date_time']);
    $params = array(
      'source_contact_id' => $this->_contactID,
      'version' => 3,
      'filter.activity_date_time_high' => '20120101000000',
      'sequential' => 1,
    );
    $result = $this->callAPIAndDocument('Activity', 'Get', $params, __FUNCTION__, __FILE__, $description, $subfile);

    $this->assertEquals(1, $result['count']);
    $this->assertEquals('2011-01-01 00:00:00', $result['values'][0]['activity_date_time']);

    $this->callAPISuccess('Activity', 'Delete', array('version' => 3, 'id' => $activityOne['id']));
    $this->callAPISuccess('Activity', 'Delete', array('version' => 3, 'id' => $activityTwo['id']));
  }

  /**
   * Test civicrm_activity_get() with a good activity ID which
   * has associated custom data
   */
  public function testActivityGetGoodIDCustom() {
    $ids = $this->entityCustomGroupWithSingleFieldCreate(__FUNCTION__, __FILE__);

    $params = $this->_params;
    $params['custom_' . $ids['custom_field_id']] = "custom string";

    $this->callAPISuccess($this->_entity, 'create', $params);

    // Retrieve the test value.
    $params = array(
      'activity_type_id' => $this->test_activity_type_value,
      'sequential' => 1,
      'return.custom_' . $ids['custom_field_id'] => 1,
    );
    $result = $this->callAPIAndDocument('activity', 'get', $params, __FUNCTION__, __FILE__);
    $this->assertEquals("custom string", $result['values'][0]['custom_' . $ids['custom_field_id']]);

    $this->assertEquals($this->test_activity_type_value, $result['values'][0]['activity_type_id']);
    $this->assertEquals('test activity type id', $result['values'][0]['subject']);
    $this->customFieldDelete($ids['custom_field_id']);
    $this->customGroupDelete($ids['custom_group_id']);
  }

  /**
   * Test civicrm_activity_get() with a good activity ID which
   * has associated custom data
   */
  public function testActivityGetContact_idCustom() {
    $ids = $this->entityCustomGroupWithSingleFieldCreate(__FUNCTION__, __FILE__);

    $params = $this->_params;
    $params['custom_' . $ids['custom_field_id']] = "custom string";

    $result = $this->callAPISuccess($this->_entity, 'create', $params);
    // Retrieve the test value
    $params = array(
      'contact_id' => $this->_params['source_contact_id'],
      'activity_type_id' => $this->test_activity_type_value,
      'sequential' => 1,
      'return.custom_' . $ids['custom_field_id'] => 1,
    );
    $result = $this->callAPIAndDocument('activity', 'get', $params, __FUNCTION__, __FILE__);
    $this->assertEquals("custom string", $result['values'][0]['custom_' . $ids['custom_field_id']], ' in line ' . __LINE__);

    $this->assertEquals($this->test_activity_type_value, $result['values'][0]['activity_type_id']);
    $this->assertEquals('test activity type id', $result['values'][0]['subject']);
    $this->assertEquals($result['values'][0]['id'], $result['id']);
  }

  /**
   * Check activity deletion without activity id.
   */
  public function testDeleteActivityWithoutId() {
    $this->callAPIFailure('activity', 'delete', ['activity_name' => 'Meeting'], 'Mandatory key(s) missing from params array: id');
  }

  /**
   * Check activity deletion without activity type.
   */
  public function testDeleteActivityWithInvalidID() {
    $this->callAPIFailure('activity', 'delete', ['id' => 1], 'Could not delete Activity: 1');
  }

  /**
   * Check activity deletion with correct data.
   */
  public function testDeleteActivity() {
    $result = $this->callAPISuccess('activity', 'create', $this->_params);
    $params = ['id' => $result['id']];
    $this->callAPIAndDocument('activity', 'delete', $params, __FUNCTION__, __FILE__);
  }

  /**
   * Check if required fields are not passed.
   */
  public function testActivityUpdateWithoutRequired() {
    $this->callAPIFailure('activity', 'create', [
      'subject' => 'this case should fail',
      'scheduled_date_time' => date('Ymd'),
    ]);
  }

  /**
   * Test civicrm_activity_update() with non-numeric id
   */
  public function testActivityUpdateWithNonNumericId() {
    $params = array(
      'id' => 'lets break it',
      'activity_name' => 'Meeting',
      'subject' => 'this case should fail',
      'scheduled_date_time' => date('Ymd'),
    );

    $result = $this->callAPIFailure('activity', 'create', $params);
  }

  /**
   * Check with incorrect required fields.
   */
  public function testActivityUpdateWithIncorrectContactActivityType() {
    $params = array(
      'id' => 1,
      'activity_name' => 'Test Activity',
      'subject' => 'this case should fail',
      'scheduled_date_time' => date('Ymd'),
      'source_contact_id' => $this->_contactID,
    );

    $result = $this->callAPIFailure('activity', 'create', $params,
      'Invalid Activity Id');
  }

  /**
   * Test civicrm_activity_update() to update an existing activity
   */
  public function testActivityUpdate() {
    $result = $this->callAPISuccess('activity', 'create', $this->_params);
    $this->_contactID2 = $this->individualCreate();

    $params = array(
      'id' => $result['id'],
      'subject' => 'Make-it-Happen Meeting',
      'activity_date_time' => '20091011123456',
      'duration' => 120,
      'location' => '21, Park Avenue',
      'details' => 'Lets update Meeting',
      'status_id' => 1,
      'source_contact_id' => $this->_contactID,
      'assignee_contact_id' => $this->_contactID2,
      'priority_id' => 1,
    );

    $result = $this->callAPISuccess('activity', 'create', $params);
    //hack on date comparison - really we should make getAndCheck smarter to handle dates
    $params['activity_date_time'] = '2009-10-11 12:34:56';
    // we also unset source_contact_id since it is stored in an aux table
    unset($params['source_contact_id']);
    //Check if assignee created.
    $assignee = $this->callAPISuccess('ActivityContact', 'get', array(
      'activity_id' => $result['id'],
      'return' => array("contact_id"),
      'record_type_id' => "Activity Assignees",
    ));
    $this->assertNotEmpty($assignee['values']);

    //clear assignee contacts.
    $updateParams = array(
      'id' => $result['id'],
      'assignee_contact_id' => array(),
    );
    $activity = $this->callAPISuccess('activity', 'create', $updateParams);
    $assignee = $this->callAPISuccess('ActivityContact', 'get', array(
      'activity_id' => $activity['id'],
      'return' => array("contact_id"),
      'record_type_id' => "Activity Assignees",
    ));
    $this->assertEmpty($assignee['values']);
    $this->getAndCheck($params, $result['id'], 'activity');
  }

  /**
   * Test civicrm_activity_update() with valid parameters
   * and some custom data
   */
  public function testActivityUpdateCustom() {
    $ids = $this->entityCustomGroupWithSingleFieldCreate(__FUNCTION__, __FILE__);

    $params = $this->_params;

    // Create an activity with custom data
    //this has been updated from the previous 'old format' function - need to make it work
    $params = array(
      'source_contact_id' => $this->_contactID,
      'subject' => 'Make-it-Happen Meeting',
      'activity_date_time' => '2009-10-18',
      'duration' => 120,
      'location' => 'Pennsylvania',
      'details' => 'a test activity to check the update api',
      'status_id' => 1,
      'activity_name' => 'Test activity type',
      'version' => $this->_apiversion,
      'custom_' . $ids['custom_field_id'] => 'custom string',
    );
    $result = $this->callAPISuccess('activity', 'create', $params);

    $activityId = $result['id'];
    $result = $this->callAPISuccess($this->_entity, 'get', array(
      'return.custom_' . $ids['custom_field_id'] => 1,
      'version' => 3,
      'id' => $result['id'],
    ));
    $this->assertEquals("custom string", $result['values'][$result['id']]['custom_' . $ids['custom_field_id']]);
    $this->assertEquals("2009-10-18 00:00:00", $result['values'][$result['id']]['activity_date_time']);
    $fields = $this->callAPISuccess('activity', 'getfields', array('version' => $this->_apiversion));
    $this->assertTrue(is_array($fields['values']['custom_' . $ids['custom_field_id']]));

    // Update the activity with custom data.
    $params = array(
      'id' => $activityId,
      'source_contact_id' => $this->_contactID,
      'subject' => 'Make-it-Happen Meeting',
      'status_id' => 1,
      'activity_name' => 'Test activity type',
      // add this since dates are messed up
      'activity_date_time' => date('Ymd'),
      'custom_' . $ids['custom_field_id'] => 'Updated my test data',
      'version' => $this->_apiversion,
    );
    $result = $this->callAPISuccess('Activity', 'Create', $params);

    $result = $this->callAPISuccess($this->_entity, 'get', array(
      'return.custom_' . $ids['custom_field_id'] => 1,
      'version' => 3,
      'id' => $result['id'],
    ));
    $this->assertEquals("Updated my test data", $result['values'][$result['id']]['custom_' . $ids['custom_field_id']]);
  }

  /**
   * Test civicrm_activity_update() for core activity fields
   * and some custom data
   */
  public function testActivityUpdateCheckCoreFields() {
    $params = $this->_params;
    $contact1Params = array(
      'first_name' => 'John',
      'middle_name' => 'J.',
      'last_name' => 'Anderson',
      'prefix_id' => 3,
      'suffix_id' => 3,
      'email' => 'john_anderson@civicrm.org',
      'contact_type' => 'Individual',
    );

    $contact1 = $this->individualCreate($contact1Params);
    $contact2Params = array(
      'first_name' => 'Michal',
      'middle_name' => 'J.',
      'last_name' => 'Anderson',
      'prefix_id' => 3,
      'suffix_id' => 3,
      'email' => 'michal_anderson@civicrm.org',
      'contact_type' => 'Individual',
    );

    $contact2 = $this->individualCreate($contact2Params);

    $params['assignee_contact_id'] = array($contact1, $contact2);
    $params['target_contact_id'] = array($contact2 => $contact2);
    $result = $this->callAPISuccess('Activity', 'Create', $params);

    $activityId = $result['id'];
    $getParams = array(
      'return.assignee_contact_id' => 1,
      'return.target_contact_id' => 1,
      'version' => $this->_apiversion,
      'id' => $activityId,
    );
    $result = $this->callAPISuccess($this->_entity, 'get', $getParams);
    $assignee = $result['values'][$result['id']]['assignee_contact_id'];
    $target = $result['values'][$result['id']]['target_contact_id'];
    $this->assertEquals(2, count($assignee), ' in line ' . __LINE__);
    $this->assertEquals(1, count($target), ' in line ' . __LINE__);
    $this->assertEquals(TRUE, in_array($contact1, $assignee), ' in line ' . __LINE__);
    $this->assertEquals(TRUE, in_array($contact2, $target), ' in line ' . __LINE__);

    $contact3Params = array(
      'first_name' => 'Jijo',
      'middle_name' => 'J.',
      'last_name' => 'Anderson',
      'prefix_id' => 3,
      'suffix_id' => 3,
      'email' => 'jijo_anderson@civicrm.org',
      'contact_type' => 'Individual',
    );

    $contact4Params = array(
      'first_name' => 'Grant',
      'middle_name' => 'J.',
      'last_name' => 'Anderson',
      'prefix_id' => 3,
      'suffix_id' => 3,
      'email' => 'grant_anderson@civicrm.org',
      'contact_type' => 'Individual',
    );

    $contact3 = $this->individualCreate($contact3Params);
    $contact4 = $this->individualCreate($contact4Params);

    $params = array();
    $params['id'] = $activityId;
    $params['assignee_contact_id'] = array($contact3 => $contact3);
    $params['target_contact_id'] = array($contact4 => $contact4);

    $result = $this->callAPISuccess('activity', 'create', $params);

    $this->assertEquals($activityId, $result['id'], ' in line ' . __LINE__);

    $result = $this->callAPISuccess(
      $this->_entity,
      'get',
      array(
        'return.assignee_contact_id' => 1,
        'return.target_contact_id' => 1,
        'return.source_contact_id' => 1,
        'id' => $result['id'],
      )
    );

    $assignee = $result['values'][$result['id']]['assignee_contact_id'];
    $target = $result['values'][$result['id']]['target_contact_id'];

    $this->assertEquals(1, count($assignee), ' in line ' . __LINE__);
    $this->assertEquals(1, count($target), ' in line ' . __LINE__);
    $this->assertEquals(TRUE, in_array($contact3, $assignee), ' in line ' . __LINE__);
    $this->assertEquals(TRUE, in_array($contact4, $target), ' in line ' . __LINE__);
    $this->_params['activity_type_id'] = $this->test_activity_type_value;
    foreach ($this->_params as $fld => $val) {
      $this->assertEquals($val, $result['values'][$result['id']][$fld]);
    }
  }

  /**
   * Test civicrm_activity_update() where the DB has a date_time
   * value and there is none in the update params.
   */
  public function testActivityUpdateNotDate() {
    $result = $this->callAPISuccess('activity', 'create', $this->_params);

    $params = array(
      'id' => $result['id'],
      'subject' => 'Make-it-Happen Meeting',
      'duration' => 120,
      'location' => '21, Park Avenue',
      'details' => 'Lets update Meeting',
      'status_id' => 1,
      'source_contact_id' => $this->_contactID,
      'priority_id' => 1,
    );

    $result = $this->callAPISuccess('activity', 'create', $params);
    //hack on date comparison - really we should make getAndCheck smarter to handle dates
    $params['activity_date_time'] = $this->_params['activity_date_time'];
    // we also unset source_contact_id since it is stored in an aux table
    unset($params['source_contact_id']);
    $this->getAndCheck($params, $result['id'], 'activity');
  }

  /**
   * Check activity update with status.
   */
  public function testActivityUpdateWithStatus() {
    $activity = $this->callAPISuccess('activity', 'create', $this->_params);
    $params = array(
      'id' => $activity['id'],
      'source_contact_id' => $this->_contactID,
      'subject' => 'Hurry update works',
      'status_id' => 1,
      'activity_name' => 'Test activity type',
    );

    $result = $this->callAPISuccess('activity', 'create', $params);
    $this->assertEquals($result['id'], $activity['id']);
    $this->assertEquals($result['values'][$activity['id']]['subject'], 'Hurry update works');
    $this->assertEquals($result['values'][$activity['id']]['status_id'], 1
    );
  }

  /**
   * Test civicrm_activity_update() where the source_contact_id
   * is not in the update params.
   */
  public function testActivityUpdateKeepSource() {
    $activity = $this->callAPISuccess('activity', 'create', $this->_params);
    // Updating the activity but not providing anything for the source contact
    // (It was set as $this->_contactID earlier.)
    $params = array(
      'id' => $activity['id'],
      'subject' => 'Updated Make-it-Happen Meeting',
      'duration' => 120,
      'location' => '21, Park Avenue',
      'details' => 'Lets update Meeting',
      'status_id' => 1,
      'activity_name' => 'Test activity type',
      'priority_id' => 1,
    );

    $result = $this->callAPISuccess('activity', 'create', $params);
    $findactivity = $this->callAPISuccess('Activity', 'Get', array('id' => $activity['id']));
  }

  /**
   * Test civicrm_activities_contact_get()
   */
  public function testActivitiesContactGet() {
    $activity = $this->callAPISuccess('activity', 'create', $this->_params);
    $activity2 = $this->callAPISuccess('activity', 'create', $this->_params2);
    // Get activities associated with contact $this->_contactID.
    $params = array(
      'contact_id' => $this->_contactID,
    );
    $result = $this->callAPISuccess('activity', 'get', $params);

    $this->assertEquals(2, $result['count']);
    $this->assertEquals($this->test_activity_type_value, $result['values'][$activity['id']]['activity_type_id']);
    $this->assertEquals('Test activity type', $result['values'][$activity['id']]['activity_name']);
    $this->assertEquals('Test activity type', $result['values'][$activity2['id']]['activity_name']);
  }

  /**
   * Test chained Activity format.
   */
  public function testChainedActivityGet() {

    $activity = $this->callAPISuccess('Contact', 'Create', array(
      'display_name' => "bob brown",
      'contact_type' => 'Individual',
      'api.activity_type.create' => array(
        'weight' => '2',
        'label' => 'send out letters',
        'filter' => 0,
        'is_active' => 1,
        'is_optgroup' => 1,
        'is_default' => 0,
      ),
      'api.activity.create' => array(
        'subject' => 'send letter',
        'activity_type_id' => '$value.api.activity_type.create.values.0.value',
      ),
    ));

    $result = $this->callAPISuccess('Activity', 'Get', array(
      'id' => $activity['id'],
      'return.assignee_contact_id' => 1,
      'api.contact.get' => array('api.pledge.get' => 1),
    ));
  }

  /**
   * Test civicrm_activity_contact_get() with invalid Contact ID.
   */
  public function testActivitiesContactGetWithInvalidContactId() {
    $params = array('contact_id' => 'contact');
    $this->callAPIFailure('activity', 'get', $params);
  }

  /**
   * Test civicrm_activity_contact_get() with contact having no Activity.
   */
  public function testActivitiesContactGetHavingNoActivity() {
    $params = array(
      'first_name' => 'dan',
      'last_name' => 'conberg',
      'email' => 'dan.conberg@w.co.in',
      'contact_type' => 'Individual',
    );

    $contact = $this->callAPISuccess('contact', 'create', $params);
    $params = array(
      'contact_id' => $contact['id'],
    );
    $result = $this->callAPISuccess('activity', 'get', $params);
    $this->assertEquals($result['count'], 0);
  }

  /**
   * Test getfields function.
   */
  public function testGetFields() {
    $params = array('action' => 'create');
    $result = $this->callAPIAndDocument('activity', 'getfields', $params, __FUNCTION__, __FILE__, NULL, NULL);
    $this->assertTrue(is_array($result['values']), 'get fields doesn\'t return values array');
    foreach ($result['values'] as $key => $value) {
      $this->assertTrue(is_array($value), $key . " is not an array");
    }
  }

  /**
   * Test or operator in api params
   */
  public function testGetWithOr() {
    $acts = array(
      'test or 1' => 'orOperator',
      'test or 2' => 'orOperator',
      'test or 3' => 'nothing',
    );
    foreach ($acts as $subject => $details) {
      $params = $this->_params;
      $params['subject'] = $subject;
      $params['details'] = $details;
      $this->callAPISuccess('Activity', 'create', $params);
    }
    $result = $this->callAPISuccess('Activity', 'get', array(
      'details' => 'orOperator',
    ));
    $this->assertEquals(2, $result['count']);
    $result = $this->callAPISuccess('Activity', 'get', array(
      'details' => 'orOperator',
      'subject' => 'test or 3',
    ));
    $this->assertEquals(0, $result['count']);
    $result = $this->callAPISuccess('Activity', 'get', array(
      'details' => 'orOperator',
      'subject' => 'test or 3',
      'options' => array('or' => array(array('details', 'subject'))),
    ));
    $this->assertEquals(3, $result['count']);
  }

  /**
   * Test handling of is_overdue calculated field
   */
  public function testGetOverdue() {
    $overdueAct = $this->callAPISuccess('Activity', 'create', array(
      'activity_date_time' => 'now - 1 week',
      'status_id' => 'Scheduled',
    ) + $this->_params);
    $completedAct = $this->callAPISuccess('Activity', 'create', array(
      'activity_date_time' => 'now - 1 week',
      'status_id' => 'Completed',
    ) + $this->_params);
    $ids = array($overdueAct['id'], $completedAct['id']);

    // Test sorting
    $completedFirst = $this->callAPISuccess('Activity', 'get', array(
      'id' => array('IN' => $ids),
      'options' => array('sort' => 'is_overdue ASC'),
    ));
    $this->assertEquals(array_reverse($ids), array_keys($completedFirst['values']));
    $overdueFirst = $this->callAPISuccess('Activity', 'get', array(
      'id' => array('IN' => $ids),
      'options' => array('sort' => 'is_overdue DESC'),
      'return' => 'is_overdue',
    ));
    $this->assertEquals($ids, array_keys($overdueFirst['values']));

    // Test return value
    $this->assertEquals(1, $overdueFirst['values'][$overdueAct['id']]['is_overdue']);
    $this->assertEquals(0, $overdueFirst['values'][$completedAct['id']]['is_overdue']);

    // Test filtering
    $onlyOverdue = $this->callAPISuccess('Activity', 'get', array(
      'id' => array('IN' => $ids),
      'is_overdue' => 1,
    ));
    $this->assertEquals(array($overdueAct['id']), array_keys($onlyOverdue['values']));
  }

}
