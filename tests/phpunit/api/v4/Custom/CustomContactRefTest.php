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


namespace api\v4\Custom;

use Civi\Api4\Contact;
use Civi\Api4\CustomField;
use Civi\Api4\CustomGroup;

/**
 * @group headless
 */
class CustomContactRefTest extends CustomTestBase {

  public function testGetWithJoin(): void {
    $firstName = uniqid('fav');

    $customGroup = CustomGroup::create(FALSE)
      ->addValue('title', 'MyContactRef')
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

    $favPersonId = $this->createTestRecord('Contact', [
      'first_name' => $firstName,
      'last_name' => 'Person',
      'contact_type' => 'Individual',
    ])['id'];

    $favPeopleId1 = $this->createTestRecord('Contact', [
      'first_name' => 'FirstFav',
      'last_name' => 'People1',
      'contact_type' => 'Individual',
    ])['id'];

    $favPeopleId2 = $this->createTestRecord('Contact', [
      'first_name' => 'SecondFav',
      'last_name' => 'People2',
      'contact_type' => 'Individual',
    ])['id'];

    $contactId1 = $this->createTestRecord('Contact', [
      'first_name' => 'Mya',
      'last_name' => 'Tester',
      'contact_type' => 'Individual',
      'MyContactRef.FavPerson' => $favPersonId,
      'MyContactRef.FavPeople' => [$favPeopleId2, $favPeopleId1],
    ])['id'];

    $contactId2 = $this->createTestRecord('Contact', [
      'first_name' => 'Bea',
      'last_name' => 'Tester',
      'contact_type' => 'Individual',
      'MyContactRef.FavPeople' => [$favPeopleId2],
    ])['id'];

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
      ->addWhere('MyContactRef.FavPeople.first_name', 'CONTAINS', 'FirstFav')
      ->execute()
      ->single();

    $this->assertEquals($contactId1, $result['id']);

    $result = Contact::get(FALSE)
      ->addSelect('id')
      ->addWhere('MyContactRef.FavPeople.first_name', 'CONTAINS', 'SecondFav')
      ->execute();

    $this->assertCount(2, $result);
  }

  public function testCurrentUser(): void {
    $currentUser = $this->createLoggedInUser();

    $customGroup = CustomGroup::create(FALSE)
      ->addValue('title', 'MyContactRef')
      ->addValue('extends', 'Individual')
      ->execute()
      ->first();

    CustomField::create(FALSE)
      ->addValue('label', 'FavPerson')
      ->addValue('custom_group_id', $customGroup['id'])
      ->addValue('html_type', 'Autocomplete-Select')
      ->addValue('data_type', 'ContactReference')
      ->execute();

    $contactId = $this->createTestRecord('Contact', [
      'first_name' => 'Mya',
      'last_name' => 'Tester',
      'contact_type' => 'Individual',
      'MyContactRef.FavPerson' => 'user_contact_id',
    ])['id'];

    $contact = Contact::get(FALSE)
      ->addSelect('display_name')
      ->addSelect('MyContactRef.FavPerson')
      ->addWhere('id', '=', $contactId)
      ->execute()
      ->first();

    $this->assertEquals($currentUser, $contact['MyContactRef.FavPerson']);
  }

}
