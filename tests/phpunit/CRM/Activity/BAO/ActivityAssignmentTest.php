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
   *  Pass zero as an id and make sure no Assignees are retrieved.
   */
  public function testGetAssigneeNamesNoId(): void {
    $activity = $this->activityCreate();
    $assignees = CRM_Activity_BAO_ActivityAssignment::getAssigneeNames(0);

    $this->assertEquals(count($assignees), 0, '0 assignee names retrieved');
  }

  /**
   *  Pass Null as an id and make sure no Assignees are retrieved.
   */
  public function testGetAssigneeNamesNullId(): void {
    $activity = $this->activityCreate();
    $assignees = CRM_Activity_BAO_ActivityAssignment::getAssigneeNames(NULL);

    $this->assertEquals(count($assignees), 0, '0 assignee names retrieved');
  }

  /**
   *  Pass a known activity id as an id and make sure 1 Assignees is retrieved
   */
  public function testGetAssigneeNamesOneId(): void {
    $activity = $this->activityCreate();
    $assignees = CRM_Activity_BAO_ActivityAssignment::getAssigneeNames([$activity['id']]);
    $this->assertEquals(count($assignees), 1, '1 assignee names retrieved');
  }

}
