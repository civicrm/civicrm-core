<?php
/**
 * @file
 * File for the TestActionSchedule class
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

/**
 *  Test APIv3 civicrm_action_schedule functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_ActionSchedule
 * @group headless
 */
class api_v3_ActionScheduleTest extends CiviUnitTestCase {
  protected $_params;
  protected $_params2;
  protected $_entity = 'action_schedule';

  /**
   * Test setup for every test.
   */
  public function setUp(): void {
    parent::setUp();
    $this->useTransaction(TRUE);
  }

  /**
   * Test simple create action schedule.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testSimpleActionScheduleCreate($version) {
    $this->_apiversion = $version;
    $oldCount = CRM_Core_DAO::singleValueQuery('select count(*) from civicrm_action_schedule');
    $activityContacts = CRM_Activity_BAO_ActivityContact::buildOptions('record_type_id', 'validate');
    $assigneeID = CRM_Utils_Array::key('Activity Assignees', $activityContacts);
    $title = "simpleActionSchedule" . substr(sha1(rand()), 0, 7);
    $params = [
      'title' => $title,
      'recipient' => $assigneeID,
      'limit_to' => 1,
      'entity_value' => 'Meeting',
      'entity_status' => 'Scheduled',
      'is_active' => 1,
      'record_activity' => 1,
      'start_action_date' => 'activity_date_time',
      'mapping_id' => CRM_Activity_ActionMapping::ACTIVITY_MAPPING_ID,
    ];
    $actionSchedule = $this->callAPISuccess('action_schedule', 'create', $params);
    $this->assertTrue(is_numeric($actionSchedule['id']));
    $this->assertTrue($actionSchedule['id'] > 0);
    $newCount = CRM_Core_DAO::singleValueQuery('select count(*) from civicrm_action_schedule');
    $this->assertEquals($oldCount + 1, $newCount);
  }

  /**
   * Check if required fields are not passed.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testActionScheduleCreateWithoutRequired($version) {
    $this->_apiversion = $version;
    $params = [
      'subject' => 'this case should fail',
      'scheduled_date_time' => date('Ymd'),
    ];
    $this->callAPIFailure('activity', 'create', $params);
  }

  /**
   * Test create with scheduled dates.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testActionScheduleWithScheduledDatesCreate($version) {
    $this->_apiversion = $version;
    $oldCount = CRM_Core_DAO::singleValueQuery('select count(*) from civicrm_action_schedule');
    $activityContacts = CRM_Activity_BAO_ActivityContact::buildOptions('record_type_id', 'validate');
    $assigneeID = CRM_Utils_Array::key('Activity Assignees', $activityContacts);
    $title = "simpleActionSchedule" . substr(sha1(rand()), 0, 7);
    $params = [
      'title' => $title,
      'recipient' => $assigneeID,
      'limit_to' => 1,
      'entity_value' => 'Meeting',
      'entity_status' => 'Scheduled',
      'is_active' => 1,
      'record_activity' => 1,
      'mapping_id' => CRM_Activity_ActionMapping::ACTIVITY_MAPPING_ID,
      'start_action_offset' => 3,
      'start_action_unit' => 'day',
      'start_action_condition' => 'before',
      'start_action_date' => 'activity_date_time',
      'is_repeat' => 1,
      'repetition_frequency_unit' => 'day',
      'repetition_frequency_interval' => 3,
      'end_frequency_unit' => 'hour',
      'end_frequency_interval' => 0,
      'end_action' => 'before',
      'end_date' => 'activity_date_time',
      'body_html' => 'Test description',
      'subject' => 'Test subject',
    ];
    $actionSchedule = $this->callAPISuccess('action_schedule', 'create', $params);
    $this->assertTrue(is_numeric($actionSchedule['id']));
    $this->assertTrue($actionSchedule['id'] > 0);
    $this->assertEquals($actionSchedule['values'][$actionSchedule['id']]['start_action_offset'], $params['start_action_offset']);
    $newCount = CRM_Core_DAO::singleValueQuery('select count(*) from civicrm_action_schedule');
    $this->assertEquals($oldCount + 1, $newCount);

  }

}
