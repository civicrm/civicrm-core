<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
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
