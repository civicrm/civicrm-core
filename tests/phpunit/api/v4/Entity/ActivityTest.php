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
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

namespace api\v4\Entity;

use Civi\Api4\Contact;
use api\v4\Api4TestBase;
use Civi\Api4\Activity;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class ActivityTest extends Api4TestBase implements TransactionalInterface {

  public function testActivityContactVirtualFields(): void {
    $c = $this->saveTestRecords('Contact', ['records' => 4])->column('id');
    $uid = $this->createLoggedInUser();

    $sourceContactId = $c[2];
    $targetContactIds = [$c[0], $c[1]];
    // Ensure the 'user_contact_id' placeholder works for both read & write
    $assigneeContactIds = [$c[3], 'user_contact_id'];
    $expectedAssigneeContactIds = [$c[3], $uid];
    $expectedAllContactIds = array_merge($c, [$uid]);

    // Test that we can write to and read from the virtual fields.
    $activityID = $this->createTestRecord('Activity', [
      'target_contact_id' => $targetContactIds,
      'source_contact_id' => $sourceContactId,
      'subject' => '1234',
    ])['id'];

    $activity = Activity::get(FALSE)
      ->addSelect('source_contact_id', 'target_contact_id', 'assignee_contact_id')
      ->addSelect('target_contact_count', 'assignee_contact_count')
      ->addWhere('target_contact_id', 'CONTAINS', $targetContactIds)
      ->execute()->single();
    $this->assertEquals($sourceContactId, $activity['source_contact_id']);
    $this->assertEquals($targetContactIds, $activity['target_contact_id']);
    $this->assertEquals(2, $activity['target_contact_count']);
    // This field was not set
    $this->assertNull($activity['assignee_contact_id']);
    $this->assertEquals(0, $activity['assignee_contact_count']);

    // Update to set assignee_contact_id
    $activity = Activity::update(FALSE)
      ->addWhere('source_contact_id', '=', $sourceContactId)
      ->addWhere('all_contact_id', 'CONTAINS ONE OF', $targetContactIds)
      ->addWhere('assignee_contact_id', 'IS NULL')
      ->addValue('assignee_contact_id', $assigneeContactIds)
      ->execute()->single();
    $this->assertSame($activityID, $activity['id']);

    // Affirm that assignee_contact_id was set and other fields remain unchanged
    $activity = Activity::get(FALSE)
      ->addSelect('id', 'source_contact_id', 'target_contact_id', 'assignee_contact_id', 'all_contact_id')
      ->addWhere('all_contact_id', 'CONTAINS ONE OF', ['user_contact_id'])
      ->addWhere('assignee_contact_id', 'IS NOT NULL')
      ->execute()->single();
    $this->assertSame($activityID, $activity['id']);
    $this->assertEquals($sourceContactId, $activity['source_contact_id']);
    $this->assertEquals($targetContactIds, $activity['target_contact_id']);
    $this->assertSame($expectedAssigneeContactIds, $activity['assignee_contact_id']);
    sort($activity['all_contact_id']);
    $this->assertSame($expectedAllContactIds, $activity['all_contact_id']);

    // Sanity check for https://lab.civicrm.org/dev/core/-/issues/1428
    // Updating nothing should change nothing.
    Activity::update(FALSE)
      ->addWhere('id', '=', $activityID)
      ->addValue('subject', '1234')
      ->execute();

    // Try fetching virtual fields via a join when Activity is not the primary entity
    $contactGet = Contact::get(FALSE)
      ->addSelect('activity.subject', 'activity.source_contact_id', 'activity.target_contact_id', 'activity.assignee_contact_id', 'activity.assignee_contact_count')
      ->addWhere('id', '=', $sourceContactId)
      ->addJoin('Activity AS activity', 'INNER', 'ActivityContact',
        ['id', '=', 'activity.contact_id'],
        ['activity.record_type_id:name', '=', '"Activity Source"']
      )
      ->execute()->single();
    $this->assertEquals('1234', $contactGet['activity.subject']);
    $this->assertEquals($sourceContactId, $contactGet['activity.source_contact_id']);
    $this->assertEquals($targetContactIds, $contactGet['activity.target_contact_id']);
    $this->assertEquals($expectedAssigneeContactIds, $contactGet['activity.assignee_contact_id']);
    $this->assertSame(2, $contactGet['activity.assignee_contact_count']);

    // Test the negative operators
    $result = Activity::get(FALSE)
      ->addSelect('id')
      ->addWhere('all_contact_id', 'NOT CONTAINS ONE OF', $targetContactIds)
      ->execute()->column('id');
    $this->assertNotContains($activityID, $result);

    $result = Activity::get(FALSE)
      ->addSelect('id')
      ->addWhere('all_contact_id', 'NOT CONTAINS', $assigneeContactIds)
      ->execute()->column('id');
    $this->assertNotContains($activityID, $result);

    $result = Activity::get(FALSE)
      ->addSelect('id')
      ->addWhere('source_contact_id', '!=', $sourceContactId)
      ->execute()->column('id');
    $this->assertNotContains($activityID, $result);
  }

  public function testAllowedActivityTypes(): void {
    // No access to CiviCase, etc will result in a limited number of activity types
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM', 'view all contacts', 'view debug output'];
    $result = Activity::get()
      ->setDebug(TRUE)
      ->execute();
    // SQL includes a constraint listing some activity type ids
    $this->assertMatchesRegularExpression('/activity_type_id[` ]*IN[ ]*\([ \d,]{9}/', $result->debug['sql'][0]);
    $result = Activity::get()
      ->addWhere('activity_type_id:name', '=', 'Meeting')
      ->setDebug(TRUE)
      ->execute();
    // Constraint is redundant with WHERE clause so should not have been included
    $this->assertDoesNotMatchRegularExpression('/activity_type_id[` ]*IN[ ]*\([ \d,]{9}/', $result->debug['sql'][0]);
  }

}
