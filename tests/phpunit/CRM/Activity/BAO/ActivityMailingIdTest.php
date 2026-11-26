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

use Civi\Api4\Activity;
use Civi\Api4\Contact;
use Civi\Api4\Mailing;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class CRM_Activity_BAO_ActivityMailingIdTest extends \api\v4\Api4TestBase implements TransactionalInterface {

  protected ?array $enableComponents = ['CiviMail'];

  public function testGetActivitiesSetsMailingIdForBulkEmail(): void {
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = [
      'view all contacts',
      'access CiviCRM',
      'access CiviMail',
    ];

    $sourceContactId = Contact::create(FALSE)
      ->setValues([
        'contact_type' => 'Individual',
        'first_name' => 'Bulk',
        'last_name' => 'Source',
      ])
      ->execute()
      ->first()['id'];
    $targetContactId = Contact::create(FALSE)
      ->setValues([
        'contact_type' => 'Individual',
        'first_name' => 'Bulk',
        'last_name' => 'Target',
      ])
      ->execute()
      ->first()['id'];
    $mailingId = Mailing::create(FALSE)
      ->setValues([
        'name' => 'Bulk email activity test',
        'subject' => 'Bulk email activity test',
        'body_text' => 'Bulk email activity test',
      ])
      ->execute()
      ->first()['id'];
    $activityId = Activity::create(FALSE)
      ->setValues([
        'activity_type_id:name' => 'Bulk Email',
        'source_contact_id' => $sourceContactId,
        'source_record_id' => $mailingId,
        'target_contact_id' => [$targetContactId],
        'subject' => 'Bulk email activity test',
      ])
      ->execute()
      ->first()['id'];

    $activities = CRM_Activity_BAO_Activity::getActivities([
      'contact_id' => $sourceContactId,
      'context' => 'activity',
    ]);

    $this->assertArrayHasKey('mailingId', $activities[$activityId]);
    $this->assertTrue($activities[$activityId]['mailingId']);

    \CRM_Core_Config::singleton()->userPermissionClass->permissions = [
      'view all contacts',
      'access CiviCRM',
    ];
    $activities = CRM_Activity_BAO_Activity::getActivities([
      'contact_id' => $sourceContactId,
      'context' => 'activity',
    ]);

    $this->assertArrayHasKey('mailingId', $activities[$activityId]);
    $this->assertFalse($activities[$activityId]['mailingId']);
  }

}
