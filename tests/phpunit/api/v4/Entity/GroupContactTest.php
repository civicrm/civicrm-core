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
    $this->assertEquals(0, $count['contact_count']);

    $result = $this->createTestRecord('GroupContact', [
      'group_id' => $group['id'],
      'contact_id' => $contact['id'],
    ]);
    $this->assertEquals('Added', $result['status']);

    $count = Group::get(FALSE)
      ->addWhere('id', '=', $group['id'])
      ->addSelect('contact_count')
      ->execute()->single();
    $this->assertEquals(1, $count['contact_count']);
  }

}
