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

use api\v4\Api4TestBase;
use Civi\Api4\Group;

/**
 * @group headless
 */
class GroupContactTest extends Api4TestBase {

  public function testCount(): void {
    $contact = $this->createTestRecord('Contact');
    $group = $this->createTestRecord('Group');

    $count = Group::get(FALSE)
      ->addWhere('id', '=', $group['id'])
      ->addSelect('contact_count')
      ->execute()->single();
    $this->assertSame(0, $count['contact_count']);

    $result = $this->createTestRecord('GroupContact', [
      'group_id' => $group['id'],
      'contact_id' => $contact['id'],
    ]);
    $this->assertSame('Added', $result['status']);

    $count = Group::get(FALSE)
      ->addWhere('id', '=', $group['id'])
      ->addSelect('contact_count')
      ->execute()->single();
    $this->assertSame(1, $count['contact_count']);

    // Deleted contacts should be excluded from contact_count
    $deletedContact = $this->createTestRecord('Contact', ['is_deleted' => TRUE]);
    $this->createTestRecord('GroupContact', [
      'group_id' => $group['id'],
      'contact_id' => $deletedContact['id'],
    ]);

    $count = Group::get(FALSE)
      ->addWhere('id', '=', $group['id'])
      ->addSelect('contact_count')
      ->execute()->single();
    $this->assertSame(1, $count['contact_count']);

    // Also verify civicrm_group_contact_cache excludes deleted contacts
    \CRM_Core_DAO::executeQuery(
      'INSERT INTO civicrm_group_contact_cache (group_id, contact_id) VALUES (%1, %2), (%1, %3)',
      [
        1 => [$group['id'], 'Integer'],
        2 => [$contact['id'], 'Integer'],
        3 => [$deletedContact['id'], 'Integer'],
      ]
    );

    $count = Group::get(FALSE)
      ->addWhere('id', '=', $group['id'])
      ->addSelect('contact_count')
      ->execute()->single();
    $this->assertSame(1, $count['contact_count']);
  }

}
