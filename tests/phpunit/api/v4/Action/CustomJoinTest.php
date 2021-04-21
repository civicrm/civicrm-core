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
use Civi\Api4\CustomField;
use Civi\Api4\CustomGroup;

/**
 * @group headless
 */
class CustomJoinTest extends BaseCustomValueTest {

  public function testGetWithJoin() {

    $customGroup = CustomGroup::create(FALSE)
      ->addValue('name', 'MyContactRef')
      ->addValue('extends', 'Individual')
      ->execute()
      ->first();

    CustomField::create(FALSE)
      ->addValue('label', 'FavPerson')
      ->addValue('custom_group_id', $customGroup['id'])
      ->addValue('html_type', 'Autocomplete-Select')
      ->addValue('data_type', 'ContactReference')
      ->execute();

    $favPersonId = Contact::create(FALSE)
      ->addValue('first_name', 'Favorite')
      ->addValue('last_name', 'Person')
      ->addValue('contact_type', 'Individual')
      ->execute()
      ->first()['id'];

    $contactId = Contact::create(FALSE)
      ->addValue('first_name', 'Mya')
      ->addValue('last_name', 'Tester')
      ->addValue('contact_type', 'Individual')
      ->addValue('MyContactRef.FavPerson', $favPersonId)
      ->execute()
      ->first()['id'];

    $contact = Contact::get(FALSE)
      ->addSelect('display_name')
      ->addSelect('MyContactRef.FavPerson.first_name')
      ->addSelect('MyContactRef.FavPerson.last_name')
      ->addWhere('id', '=', $contactId)
      ->execute()
      ->first();

    $this->assertEquals('Favorite', $contact['MyContactRef.FavPerson.first_name']);
    $this->assertEquals('Person', $contact['MyContactRef.FavPerson.last_name']);
  }

}
