<?php
/**
 *  File for the TestActionSchedule class
 *
 *  (PHP 5)
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
require_once 'CiviTest/CiviUnitTestCase.php';


/**
 *  Test APIv3 civicrm_action_schedule functions
 *
 *  @package CiviCRM_APIv3
 *  @subpackage API_ActionSchedule
 */

class api_v3_ActionScheduleTest extends CiviUnitTestCase {
  protected $_params;
  protected $_params2;
  protected $_entity = 'action_schedule';
  protected $_apiversion = 3;



  /**
   *  Test setup for every test
   *
   *  Connect to the database, truncate the tables that will be used
   *  and redirect stdin to a temporary file
   */
  public function setUp() {
    //  Connect to the database
    parent::setUp();

  }

  /**
   * Tears down the fixture, for example, closes a network connection.
   * This method is called after a test is executed.
   *
   * @access protected
   */
  function tearDown() {
    $tablesToTruncate = array(
      'civicrm_action_schedule',
    );
    $this->quickCleanup($tablesToTruncate, TRUE);
  }


  function testSimpleActionScheduleCreate() {
    $oldCount = CRM_Core_DAO::singleValueQuery('select count(*) from civicrm_action_schedule');
    $activityContacts = CRM_Core_OptionGroup::values('activity_contacts', FALSE, FALSE, FALSE, NULL, 'name');
    $assigneeID = CRM_Utils_Array::key('Activity Assignees', $activityContacts);
    $targetID = CRM_Utils_Array::key('Activity Targets', $activityContacts);
    $scheduledStatus = CRM_Core_OptionGroup::getValue('activity_status', 'Scheduled', 'name');
    $mappingId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_ActionMapping', 'activity_type', 'id', 'entity_value');
    $activityTypeId = CRM_Core_OptionGroup::getValue('activity_type', "Meeting", 'name');
    $title = "simpleActionSchedule".substr(sha1(rand()), 0, 7);
    $params = array(
      'title' => $title,
      'recipient' => $assigneeID,
      'limit_to' => 1,
      'entity_value' => $activityTypeId,
      'entity_status' => $scheduledStatus,
      'is_active' => 1,
      'record_activity' => 1,
      'start_action_date' => 'activity_date_time',
      'mapping_id' => $mappingId,
    );
    $actionSchedule = $this->callAPISuccess('action_schedule', 'create', $params);
    $this->assertTrue(is_numeric($actionSchedule['id']), "In line " . __LINE__);
    $this->assertTrue($actionSchedule['id'] > 0, "In line " . __LINE__);
    $newCount = CRM_Core_DAO::singleValueQuery('select count(*) from civicrm_action_schedule');
    $this->assertEquals($oldCount+1, $newCount);
  }

  /**
   * check if required fields are not passed
   */
  function testActionScheduleCreateWithoutRequired() {
    $params = array(
        'subject' => 'this case should fail',
        'scheduled_date_time' => date('Ymd'),
    );
    $result = $this->callAPIFailure('activity', 'create', $params);
  }

  function testActionScheduleWithScheduledDatesCreate() {
    $oldCount = CRM_Core_DAO::singleValueQuery('select count(*) from civicrm_action_schedule');
    $activityContacts = CRM_Core_OptionGroup::values('activity_contacts', FALSE, FALSE, FALSE, NULL, 'name');
    $assigneeID = CRM_Utils_Array::key('Activity Assignees', $activityContacts);
    $targetID = CRM_Utils_Array::key('Activity Targets', $activityContacts);
    $scheduledStatus = CRM_Core_OptionGroup::getValue('activity_status', 'Scheduled', 'name');
    $mappingId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_ActionMapping', 'activity_type', 'id', 'entity_value');
    $activityTypeId = CRM_Core_OptionGroup::getValue('activity_type', "Meeting", 'name');
    $title = "simpleActionSchedule".substr(sha1(rand()), 0, 7);
    $params = array(
      'title' => $title,
      'recipient' => $assigneeID,
      'limit_to' => 1,
      'entity_value' => $activityTypeId,
      'entity_status' => $scheduledStatus,
      'is_active' => 1,
      'record_activity' => 1,
      'mapping_id' => $mappingId,
      'start_action_offset' => 3,
      'start_action_unit' => 'day',
      'start_action_condition' => 'before',
      'start_action_date' => 'activity_date_time',
      'is_repeat' => 1,
      'repetition_frequency_unit'=> 'day',
      'repetition_frequency_interval' => 3,
      'end_frequency_unit' => 'hour',
      'end_frequency_interval' => 0,
      'end_action' => 'before',
      'end_date' => 'activity_date_time',
      'body_html' => 'Test description',
      'subject' => 'Test subject'
    );
    $actionSchedule = $this->callAPISuccess('action_schedule', 'create', $params);
    $this->assertTrue(is_numeric($actionSchedule['id']), "In line " . __LINE__);
    $this->assertTrue($actionSchedule['id'] > 0, "In line " . __LINE__);
    $this->assertEquals($actionSchedule['values'][$actionSchedule['id']]['start_action_offset'][0], $params['start_action_offset']);
    $newCount = CRM_Core_DAO::singleValueQuery('select count(*) from civicrm_action_schedule');
    $this->assertEquals($oldCount+1, $newCount);

  }

}
