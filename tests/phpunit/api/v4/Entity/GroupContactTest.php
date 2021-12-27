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

use api\v4\UnitTestCase;
use Civi\Api4\GroupContact;

/**
 * @group headless
 */
class GroupContactTest extends UnitTestCase {

  public function testCreate() {
    $contact = $this->createEntity(['type' => 'Individual']);
    $group = $this->createEntity(['type' => 'Group']);
    $result = GroupContact::create(FALSE)
      ->addValue('group_id', $group['id'])
      ->addValue('contact_id', $contact['id'])
      ->execute()
      ->first();
    $this->assertEquals('Added', $result['status']);
  }

}
