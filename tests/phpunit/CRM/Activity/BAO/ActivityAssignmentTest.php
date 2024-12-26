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
   * API version in use.
   *
   * @var int
   */
  protected $_apiversion = 4;

  /**
   *  Pass zero as an id and make sure no Assignees are retrieved.
   */
  public function testGetAssigneeNamesNoId(): void {
    $this->activityCreate();
    $assignees = CRM_Activity_BAO_ActivityAssignment::getAssigneeNames(0);
    $this->assertCount(0, $assignees, '0 assignee names retrieved');
  }

  /**
   *  Pass Null as an id and make sure no Assignees are retrieved.
   */
  public function testGetAssigneeNamesNullId(): void {
    $this->activityCreate();
    $assignees = CRM_Activity_BAO_ActivityAssignment::getAssigneeNames(NULL);
    $this->assertCount(0, $assignees, '0 assignee names retrieved');
  }

  /**
   *  Pass a known activity id as an id and make sure 1 Assignees is retrieved
   */
  public function testGetAssigneeNamesOneId(): void {
    $activity = $this->activityCreate();
    $assignees = CRM_Activity_BAO_ActivityAssignment::getAssigneeNames([$activity['id']]);
    $this->assertCount(1, $assignees, '1 assignee names retrieved');
  }

}
