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

use Civi\Api4\SubscriptionHistory;
use Civi\Api4\GroupContact;
use api\v4\Api4TestBase;

/**
 * @group headless
 */
class SubscriptionHistoryTest extends Api4TestBase {

  public function testGet(): void {
    $contact = $this->createTestRecord('Contact');
    $group = $this->createTestRecord('Group');
    $timeAdded = time();
    $groupContact = $this->createTestRecord('GroupContact', [
      'group_id' => $group['id'],
      'contact_id' => $contact['id'],
    ]);
    $historyAdded = SubscriptionHistory::get()
      ->addSelect('*')
      ->addWhere('group_id', '=', $group['id'])
      ->addWhere('status', '=', 'Added')
      ->addWhere('contact_id', '=', $contact['id'])
      ->execute();
    $this->assertCount(1, $historyAdded);
    $this->assertGreaterThanOrEqual($timeAdded, strtotime($historyAdded->single()['date']));
    $this->assertLessThanOrEqual(time(), strtotime($historyAdded->single()['date']));

    $timeRemoved = time();
    GroupContact::update()
      ->addValue('status', 'Removed')
      ->addWhere('id', '=', $groupContact['id'])
      ->execute();
    $historyRemoved = SubscriptionHistory::get()
      ->addSelect('*')
      ->addWhere('group_id', '=', $group['id'])
      ->addWhere('status', '=', 'Removed')
      ->addWhere('contact_id', '=', $contact['id'])
      ->execute();
    $this->assertCount(1, $historyRemoved);
    $this->assertGreaterThanOrEqual($timeRemoved, strtotime($historyRemoved->single()['date']));
    $this->assertLessThanOrEqual(time(), strtotime($historyRemoved->single()['date']));

    $timeDeleted = time();
    GroupContact::delete()
      ->addWhere('id', '=', $groupContact['id'])
      ->execute();
    $historyDeleted = SubscriptionHistory::get()
      ->addSelect('*')
      ->addWhere('group_id', '=', $group['id'])
      ->addWhere('status', '=', 'Deleted')
      ->addWhere('contact_id', '=', $contact['id'])
      ->execute();
    $this->assertCount(1, $historyDeleted);
    $this->assertGreaterThanOrEqual($timeDeleted, strtotime($historyDeleted->single()['date']));
    $this->assertLessThanOrEqual(time(), strtotime($historyDeleted->single()['date']));
  }

  public function testGetPermissions(): void {
    $this->createLoggedInUser();

    $contact = $this->createTestRecord('Contact');
    $group = $this->createTestRecord('Group');
    $groupContact = $this->createTestRecord('GroupContact', [
      'group_id' => $group['id'],
      'contact_id' => $contact['id'],
    ]);

    \CRM_Core_Config::singleton()->userPermissionClass->permissions = [
      'access CiviCRM',
      'view all contacts',
    ];

    $historyAdded = SubscriptionHistory::get()
      ->addSelect('*')
      ->addWhere('group_id', '=', $group['id'])
      ->addWhere('status', '=', 'Added')
      ->addWhere('contact_id', '=', $contact['id'])
      ->execute();
    $this->assertCount(1, $historyAdded);

    \CRM_Core_Config::singleton()->userPermissionClass->permissions = [];

    $historyAdded = SubscriptionHistory::get()
      ->addSelect('*')
      ->addWhere('group_id', '=', $group['id'])
      ->addWhere('status', '=', 'Added')
      ->addWhere('contact_id', '=', $contact['id'])
      ->execute();
    $this->assertCount(0, $historyAdded);

  }

}
