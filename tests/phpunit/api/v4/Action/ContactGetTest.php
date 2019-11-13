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
 * $Id$
 *
 */


namespace api\v4\Action;

use Civi\Api4\Contact;

/**
 * @group headless
 */
class ContactGetTest extends \api\v4\UnitTestCase {

  public function testGetDeletedContacts() {
    $last_name = uniqid('deleteContactTest');

    $bob = Contact::create()
      ->setValues(['first_name' => 'Bob', 'last_name' => $last_name])
      ->execute()->first();

    $jan = Contact::create()
      ->setValues(['first_name' => 'Jan', 'last_name' => $last_name])
      ->execute()->first();

    $del = Contact::create()
      ->setValues(['first_name' => 'Del', 'last_name' => $last_name, 'is_deleted' => 1])
      ->execute()->first();

    // Deleted contacts are not fetched by default
    $this->assertCount(2, Contact::get()->addWhere('last_name', '=', $last_name)->selectRowCount()->execute());

    // You can search for them specifically
    $contacts = Contact::get()->addWhere('last_name', '=', $last_name)->addWhere('is_deleted', '=', 1)->addSelect('id')->execute();
    $this->assertEquals($del['id'], $contacts->first()['id']);

    // Or by id
    $this->assertCount(3, Contact::get()->addWhere('id', 'IN', [$bob['id'], $jan['id'], $del['id']])->selectRowCount()->execute());

    // Putting is_deleted anywhere in the where clause will disable the default
    $contacts = Contact::get()->addClause('OR', ['last_name', '=', $last_name], ['is_deleted', '=', 0])->addSelect('id')->execute();
    $this->assertContains($del['id'], $contacts->column('id'));
  }

}
