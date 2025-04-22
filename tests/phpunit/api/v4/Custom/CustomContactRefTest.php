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

use api\v4\Api4TestBase;
use Civi\Api4\Activity;
use Civi\Api4\Contact;
use Civi\Api4\CustomField;
use Civi\Api4\Utils\CoreUtil;

/**
 * @group headless
 */
class CustomContactRefTest extends Api4TestBase {

  public function testGetWithJoin(): void {
    $firstName = uniqid('fav');

    $customGroup = $this->createTestRecord('CustomGroup', [
      'title' => 'MyContactRef',
      'extends' => 'Contact',
    ]);

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

    $customGroup = $this->createTestRecord('CustomGroup', [
      'title' => 'MyContactRef',
      'extends' => 'Contact',
    ]);

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

  public function testGetRefCount(): void {
    $customGroup = $this->createTestRecord('CustomGroup', [
      'title' => 'CountThis',
      'extends' => 'Activity',
    ]);

    $this->createTestRecord('CustomField', [
      'label' => 'CountMe',
      'custom_group_id' => $customGroup['id'],
      'data_type' => 'EntityReference',
      'html_type' => 'Autocomplete-Select',
      'fk_entity' => 'Contact',
    ]);

    $this->createTestRecord('CustomField', [
      'label' => 'CountUs',
      'custom_group_id' => $customGroup['id'],
      'data_type' => 'ContactReference',
      'html_type' => 'Autocomplete-Select',
      'serialize' => 1,
    ]);

    $cid1 = $this->createTestRecord('Contact')['id'];
    $cid2 = $this->createTestRecord('Contact')['id'];

    $this->checkRefCountTotal('Contact', $cid1, 0);
    $this->checkRefCountTotal('Contact', $cid2, 0);

    $activity = $this->createTestRecord('Activity', [
      'source_contact_id' => $cid1,
      'CountThis.CountMe' => $cid2,
      'CountThis.CountUs' => [$cid1, $cid2],
    ]);

    $this->checkRefCountTotal('Contact', $cid1, 2);
    $this->checkRefCountTotal('Contact', $cid2, 2);

    Activity::update(FALSE)
      ->addWhere('id', '=', $activity['id'])
      ->addValue('CountThis.CountUs', [$cid1])
      ->execute();

    $this->checkRefCountTotal('Contact', $cid1, 2);
    $this->checkRefCountTotal('Contact', $cid2, 1);

    $this->createTestRecord('Tag', [
      'name' => 'abcde',
      'used_for' => ['civicrm_contact'],
      'created_id' => $cid1,
    ]);

    $this->checkRefCountTotal('Contact', $cid1, 3);
    $this->checkRefCountTotal('Contact', $cid2, 1);

    $this->createTestRecord('EntityTag', [
      'entity_id' => $cid2,
      'tag_id.name' => 'abcde',
    ]);

    $this->checkRefCountTotal('Contact', $cid1, 3);
    $this->checkRefCountTotal('Contact', $cid2, 2);
  }

  /**
   *
   */
  private function checkRefCountTotal(string $entityName, int $entityId, int $expectedCount): void {
    $count = 0;
    foreach (CoreUtil::getRefCount($entityName, $entityId) as $ref) {
      // For now, getRefCount includes references from the Log table...
      // TODO: that's probably something we should consider excluding from refCounts!
      if ($ref['name'] !== 'Log') {
        $count += $ref['count'];
      }
    }
    $this->assertEquals($expectedCount, $count);
  }

}
