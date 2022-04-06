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
use Civi\Api4\RecentItem;
use Civi\Api4\Contact;
use Civi\Test\TransactionalInterface;

/**
 * Test Address functionality
 *
 * @group headless
 */
class RecentItemTest extends UnitTestCase implements TransactionalInterface {

  /**
   *
   */
  public function testRecentContact() {
    $cid = Contact::create(FALSE)
      ->addValue('first_name', 'Hello')
      ->execute()->single()['id'];

    $this->createLoggedInUser();

    RecentItem::create(FALSE)
      ->addValue('entity_type', 'Contact')
      ->addValue('entity_id', $cid)
      ->execute();

    $item = RecentItem::get(FALSE)
      ->addWhere('entity_type', '=', 'Contact')
      ->addWhere('entity_id', '=', $cid)
      ->execute()->single();

    $this->assertEquals('Hello', $item['title']);
    $this->assertEquals('fa-user', $item['icon']);
    $this->assertEquals(\CRM_Utils_System::url('civicrm/contact/view?reset=1&cid=' . $cid), $item['view_url']);

    RecentItem::delete(FALSE)
      ->addWhere('entity_type', '=', 'Contact')
      ->addWhere('entity_id', '=', $cid)
      ->execute();

    $this->assertCount(0, RecentItem::get(FALSE)
      ->addWhere('entity_type', '=', 'Contact')
      ->addWhere('entity_id', '=', $cid)
      ->execute());

    RecentItem::create(FALSE)
      ->addValue('entity_type', 'Contact')
      ->addValue('entity_id', $cid)
      ->execute();

    $this->assertCount(1, RecentItem::get(FALSE)
      ->addWhere('entity_type', '=', 'Contact')
      ->addWhere('entity_id', '=', $cid)
      ->execute());

    // Move contact to trash
    Contact::delete(FALSE)->addWhere('id', '=', $cid)->execute();
    $item = RecentItem::get(FALSE)
      ->addWhere('entity_type', '=', 'Contact')
      ->addWhere('entity_id', '=', $cid)
      ->execute()->single();
    $this->assertEquals('Hello', $item['title']);
    $this->assertTrue($item['is_deleted']);

    // Delete contact
    Contact::delete(FALSE)->setUseTrash(FALSE)->addWhere('id', '=', $cid)->execute();

    $this->assertCount(0, RecentItem::get(FALSE)
      ->addWhere('entity_type', '=', 'Contact')
      ->addWhere('entity_id', '=', $cid)
      ->execute());
  }

}
