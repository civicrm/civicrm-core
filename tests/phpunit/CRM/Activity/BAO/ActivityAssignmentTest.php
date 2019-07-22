<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * Test class for CRM_Activity_BAO_ActivityAssignment BAO
 *
 * @package   CiviCRM
 * @group headless
 */
class CRM_Activity_BAO_ActivityAssignmentTest extends CiviUnitTestCase {

  /**
   * Sets up the fixture, for example, opens a network connection.
   * This method is called before a test is executed.
   */
  protected function setUp() {
    parent::setUp();
  }

  /**
   * Tears down the fixture, for example, closes a network connection.
   * This method is called after a test is executed.
   */
  protected function tearDown() {
  }

  /**
   *  Pass zero as an id and make sure no Assignees are retrieved.
   */
  public function testRetrieveAssigneeIdsByActivityIdNoId() {
    $activity = $this->activityCreate();
    $activityId = CRM_Activity_BAO_ActivityAssignment::retrieveAssigneeIdsByActivityId(0);

    $this->assertEquals(count($activityId), 0, '0 assignees retrieved');
  }

  /**
   *  Pass null as an id and make sure no Assignees are retrieved.
   */
  public function testRetrieveAssigneeIdsByActivityIdNullId() {
    $activity = $this->activityCreate();
    $activityId = CRM_Activity_BAO_ActivityAssignment::retrieveAssigneeIdsByActivityId(NULL);

    $this->assertEquals(count($activityId), 0, '0 assignees retrieved using null');
  }

  /**
   *  Pass a string as an id and make sure no Assignees are retrieved.
   */
  public function testRetrieveAssigneeIdsByActivityIdString() {
    $activity = $this->activityCreate();
    $activityId = CRM_Activity_BAO_ActivityAssignment::retrieveAssigneeIdsByActivityId('test');

    $this->assertEquals(count($activityId), 0, '0 assignees retrieved using string');
  }

  /**
   *  Pass a known activity id as an id and make sure 1 Assignees is retrieved
   */
  public function testRetrieveAssigneeIdsByActivityIdOneId() {
    $activity = $this->activityCreate();
    $activityId = CRM_Activity_BAO_ActivityAssignment::retrieveAssigneeIdsByActivityId($activity['id']);

    $this->assertEquals(count($activityId), 1, 'One record retrieved');
  }

  /**
   *  Pass zero as an id and make sure no Assignees are retrieved.
   */
  public function testGetAssigneeNamesNoId() {
    $activity = $this->activityCreate();
    $assignees = CRM_Activity_BAO_ActivityAssignment::getAssigneeNames(0);

    $this->assertEquals(count($assignees), 0, '0 assignee names retrieved');
  }

  /**
   *  Pass Null as an id and make sure no Assignees are retrieved.
   */
  public function testGetAssigneeNamesNullId() {
    $activity = $this->activityCreate();
    $assignees = CRM_Activity_BAO_ActivityAssignment::getAssigneeNames(NULL);

    $this->assertEquals(count($assignees), 0, '0 assignee names retrieved');
  }

  /**
   *  Pass a known activity id as an id and make sure 1 Assignees is retrieved
   */
  public function testGetAssigneeNamesOneId() {
    $activity = $this->activityCreate();
    $assignees = CRM_Activity_BAO_ActivityAssignment::getAssigneeNames([$activity['id']]);
    $this->assertEquals(count($assignees), 1, '1 assignee names retrieved');
  }

}
