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

  public function testGetWithLimit() {
    $last_name = uniqid('getWithLimitTest');

    $bob = Contact::create()
      ->setValues(['first_name' => 'Bob', 'last_name' => $last_name])
      ->execute()->first();

    $jan = Contact::create()
      ->setValues(['first_name' => 'Jan', 'last_name' => $last_name])
      ->execute()->first();

    $dan = Contact::create()
      ->setValues(['first_name' => 'Dan', 'last_name' => $last_name])
      ->execute()->first();

    $num = Contact::get(FALSE)->selectRowCount()->execute()->count();

    // The object's count() method will account for all results, ignoring limit & offset, while the array results are limited
    $offset1 = Contact::get(FALSE)->setOffset(1)->execute();
    $this->assertCount($num, $offset1);
    $this->assertCount($num - 1, (array) $offset1);
    $offset2 = Contact::get(FALSE)->setOffset(2)->execute();
    $this->assertCount($num - 2, (array) $offset2);
    $this->assertCount($num, $offset2);
    // With limit, it doesn't fetch total count by default
    $limit2 = Contact::get(FALSE)->setLimit(2)->execute();
    $this->assertCount(2, (array) $limit2);
    $this->assertCount(2, $limit2);
    // With limit, you have to trigger the full row count manually
    $limit2 = Contact::get(FALSE)->setLimit(2)->addSelect('sort_name', 'row_count')->execute();
    $this->assertCount(2, (array) $limit2);
    $this->assertCount($num, $limit2);
  }

}
