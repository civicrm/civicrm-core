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
    $c = $this->saveTestRecords('Contact', ['records' => 5])->column('id');

    $sourceContactId = $c[2];
    $targetContactIds = [$c[0], $c[1]];
    $assigneeContactIds = [$c[3], $c[4]];

    // Test that we can write to and read from the virtual fields.
    $activityID = $this->createTestRecord('Activity', [
      'target_contact_id' => $targetContactIds,
      'source_contact_id' => $sourceContactId,
      'subject' => '1234',
    ])['id'];

    $activity = Activity::get(FALSE)
      ->addSelect('source_contact_id', 'target_contact_id', 'assignee_contact_id')
      ->addWhere('id', '=', $activityID)
      ->execute()->first();
    $this->assertEquals($sourceContactId, $activity['source_contact_id']);
    $this->assertEquals($targetContactIds, $activity['target_contact_id']);
    // This field was not set
    $this->assertNull($activity['assignee_contact_id']);

    // Update to set assignee_contact_id
    Activity::update(FALSE)
      ->addWhere('id', '=', $activityID)
      ->addValue('assignee_contact_id', $assigneeContactIds)
      ->execute();

    // Affirm that assignee_contact_id was set and other fields remain unchanged
    $activity = Activity::get(FALSE)
      ->addSelect('source_contact_id', 'target_contact_id', 'assignee_contact_id')
      ->addWhere('id', '=', $activityID)
      ->execute()->single();
    $this->assertEquals($sourceContactId, $activity['source_contact_id']);
    $this->assertEquals($targetContactIds, $activity['target_contact_id']);
    $this->assertEquals($assigneeContactIds, $activity['assignee_contact_id']);

    // Sanity check for https://lab.civicrm.org/dev/core/-/issues/1428
    // Updating nothing should change nothing.
    Activity::update(FALSE)
      ->addWhere('id', '=', $activityID)
      ->addValue('subject', '1234')
      ->execute();

    // Try fetching virtual fields via a join when Activity is not the primary entity
    $contactGet = Contact::get(FALSE)
      ->addSelect('activity.subject', 'activity.source_contact_id', 'activity.target_contact_id', 'activity.assignee_contact_id')
      ->addWhere('id', '=', $sourceContactId)
      ->addJoin('Activity AS activity', 'INNER', 'ActivityContact',
        ['id', '=', 'activity.contact_id'],
        ['activity.record_type_id:name', '=', '"Activity Source"']
      )
      ->execute()->single();
    $this->assertEquals('1234', $contactGet['activity.subject']);
    $this->assertEquals($sourceContactId, $contactGet['activity.source_contact_id']);
    $this->assertEquals($targetContactIds, $contactGet['activity.target_contact_id']);
    $this->assertEquals($assigneeContactIds, $contactGet['activity.assignee_contact_id']);
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
