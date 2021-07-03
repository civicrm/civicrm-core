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
class CustomContactRefTest extends BaseCustomValueTest {

  public function testGetWithJoin() {
    $firstName = uniqid('fav');

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

    CustomField::create(FALSE)
      ->addValue('label', 'FavPeople')
      ->addValue('custom_group_id', $customGroup['id'])
      ->addValue('html_type', 'Autocomplete-Select')
      ->addValue('data_type', 'ContactReference')
      ->addValue('serialize', 1)
      ->execute();

    $favPersonId = Contact::create(FALSE)
      ->addValue('first_name', $firstName)
      ->addValue('last_name', 'Person')
      ->addValue('contact_type', 'Individual')
      ->execute()
      ->first()['id'];

    $favPeopleId1 = Contact::create(FALSE)
      ->addValue('first_name', 'Favorite1')
      ->addValue('last_name', 'People1')
      ->addValue('contact_type', 'Individual')
      ->execute()
      ->first()['id'];

    $favPeopleId2 = Contact::create(FALSE)
      ->addValue('first_name', 'Favorite2')
      ->addValue('last_name', 'People2')
      ->addValue('contact_type', 'Individual')
      ->execute()
      ->first()['id'];

    $contactId1 = Contact::create(FALSE)
      ->addValue('first_name', 'Mya')
      ->addValue('last_name', 'Tester')
      ->addValue('contact_type', 'Individual')
      ->addValue('MyContactRef.FavPerson', $favPersonId)
      ->addValue('MyContactRef.FavPeople', [$favPeopleId1, $favPeopleId2])
      ->execute()
      ->first()['id'];

    $contact = Contact::get(FALSE)
      ->addSelect('display_name')
      ->addSelect('MyContactRef.FavPerson.first_name')
      ->addSelect('MyContactRef.FavPerson.last_name')
      ->addSelect('MyContactRef.FavPeople')
      ->addWhere('MyContactRef.FavPerson.first_name', '=', $firstName)
      ->execute()
      ->first();

    $this->assertEquals($firstName, $contact['MyContactRef.FavPerson.first_name']);
    $this->assertEquals('Person', $contact['MyContactRef.FavPerson.last_name']);
    $this->assertEquals([$favPeopleId1, $favPeopleId2], $contact['MyContactRef.FavPeople']);
  }

  public function testCurrentUser() {
    $currentUser = $this->createLoggedInUser();

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

    $contactId = Contact::create(FALSE)
      ->addValue('first_name', 'Mya')
      ->addValue('last_name', 'Tester')
      ->addValue('contact_type', 'Individual')
      ->addValue('MyContactRef.FavPerson', 'user_contact_id')
      ->execute()
      ->first()['id'];

    $contact = Contact::get(FALSE)
      ->addSelect('display_name')
      ->addSelect('MyContactRef.FavPerson')
      ->addWhere('id', '=', $contactId)
      ->execute()
      ->first();

    $this->assertEquals($currentUser, $contact['MyContactRef.FavPerson']);
  }

}
