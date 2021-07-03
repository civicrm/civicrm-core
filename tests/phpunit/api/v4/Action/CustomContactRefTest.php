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
      ->addValue('first_name', 'FirstFav')
      ->addValue('last_name', 'People1')
      ->addValue('contact_type', 'Individual')
      ->execute()
      ->first()['id'];

    $favPeopleId2 = Contact::create(FALSE)
      ->addValue('first_name', 'SecondFav')
      ->addValue('last_name', 'People2')
      ->addValue('contact_type', 'Individual')
      ->execute()
      ->first()['id'];

    $contactId1 = Contact::create(FALSE)
      ->addValue('first_name', 'Mya')
      ->addValue('last_name', 'Tester')
      ->addValue('contact_type', 'Individual')
      ->addValue('MyContactRef.FavPerson', $favPersonId)
      ->addValue('MyContactRef.FavPeople', [$favPeopleId2, $favPeopleId1])
      ->execute()
      ->first()['id'];

    $contactId2 = Contact::create(FALSE)
      ->addValue('first_name', 'Bea')
      ->addValue('last_name', 'Tester')
      ->addValue('contact_type', 'Individual')
      ->addValue('MyContactRef.FavPeople', [$favPeopleId2])
      ->execute()
      ->first()['id'];

    $result = Contact::get(FALSE)
      ->addSelect('display_name')
      ->addSelect('MyContactRef.FavPerson.first_name')
      ->addSelect('MyContactRef.FavPerson.last_name')
      ->addSelect('MyContactRef.FavPeople')
      ->addSelect('MyContactRef.FavPeople.last_name')
      ->addWhere('MyContactRef.FavPerson.first_name', '=', $firstName)
      ->execute()
      ->single();

    $this->assertEquals($firstName, $result['MyContactRef.FavPerson.first_name']);
    $this->assertEquals('Person', $result['MyContactRef.FavPerson.last_name']);
    // Ensure serialized values are returned in order
    $this->assertEquals([$favPeopleId2, $favPeopleId1], $result['MyContactRef.FavPeople']);
    // Values returned from virtual join should be in the same order
    $this->assertEquals(['People2', 'People1'], $result['MyContactRef.FavPeople.last_name']);

    $result = Contact::get(FALSE)
      ->addSelect('id')
      ->addWhere('MyContactRef.FavPeople.first_name', 'CONTAINS', 'First')
      ->execute()
      ->single();

    $this->assertEquals($contactId1, $result['id']);

    $result = Contact::get(FALSE)
      ->addSelect('id')
      ->addWhere('MyContactRef.FavPeople.first_name', 'CONTAINS', 'Second')
      ->execute();

    $this->assertCount(2, $result);
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
