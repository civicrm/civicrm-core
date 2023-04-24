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

use api\v4\Api4TestBase;
use Civi\Api4\Contact;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class SaveTest extends Api4TestBase implements TransactionalInterface {

  public function provideDataForSaveTestWithMatchingCriteria() {
    return [
      'non-empty match value' => ['Foo'],
      'empty string match value' => [''],
      'null match value' => [NULL],
    ];
  }

  /**
   * @return void
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   * @dataProvider provideDataForSaveTestWithMatchingCriteria
   */
  public function testSaveWithMatchingCriteria($matchValueUnderTest) {
    $originalZak = [
      'nick_name' => $matchValueUnderTest,
      'first_name' => 'Zak',
      'last_name' => 'Original',
    ];

    $originalZak['id'] = Contact::create(FALSE)
      ->setValues($originalZak)
      ->execute()->single()['id'];

    $originalBob = [
      'nick_name' => 'bob',
      'first_name' => 'Bob',
      'last_name' => 'Original',
    ];

    $originalBob['id'] = Contact::create(FALSE)
      ->setValues($originalBob)
      ->execute()->single()['id'];

    // This modified version of Zak will match with the existing Zak
    // (nickname and first name both match)

    $sameZakWithChangedLastName = [
      'nick_name' => $originalZak['nick_name'],
      'first_name' => $originalZak['first_name'],
      'last_name' => 'Changed',
    ];

    $sameZakWithChangedLastName['id'] = Contact::save(FALSE)
      ->setMatch(['first_name', 'nick_name'])
      ->setRecords([$sameZakWithChangedLastName])
      ->execute()->single()['id'];

    self::assertEquals($originalZak['id'], $sameZakWithChangedLastName['id']);

    // This new Bob will not match the existing Bob
    // (first name matches, but nickname is different)

    self::assertNotEquals($originalBob['nick_name'], $matchValueUnderTest);

    $otherBob = [
      'nick_name' => $matchValueUnderTest,
      'first_name' => $originalBob['first_name'],
      'last_name' => 'Changed',
    ];

    $otherBob['id'] = Contact::save(FALSE)
      ->setMatch(['first_name', 'nick_name'])
      ->setRecords([$otherBob])
      ->execute()->single()['id'];

    self::assertGreaterThan($originalBob['id'], $otherBob['id']);

    $allContactIds = [
      $originalZak['id'],
      $sameZakWithChangedLastName['id'],
      $originalBob['id'],
      $otherBob['id'],
    ];

    $allCreatedAndSavedContacts = Contact::get(FALSE)
      ->setSelect(['id', 'first_name', 'last_name', 'nick_name'])
      ->addWhere('id', 'IN', $allContactIds)
      ->execute()->indexBy('id');

    self::assertCount(3, $allCreatedAndSavedContacts);

    $expected = [
      $originalZak['id'] => $sameZakWithChangedLastName,
      $originalBob['id'] => $originalBob,
      $otherBob['id'] => $otherBob,
    ];

    self::assertEquals($expected, $allCreatedAndSavedContacts->getArrayCopy());
  }

}
