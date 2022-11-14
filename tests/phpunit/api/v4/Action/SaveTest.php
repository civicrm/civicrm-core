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

  /**
   * @return void
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function testSaveWithMatchingCriteria() {
    foreach (['Kiddo', '', NULL] as $testValue) {
      $zakOriginal = [
        'nick_name' => $testValue,
        'first_name' => 'Zak',
        'last_name' => 'Original',
      ];

      $zakOriginal['id'] = Contact::create(FALSE)
        ->setValues($zakOriginal)
        ->execute()->single()['id'];

      $bobOriginal = [
        'nick_name' => 'bob',
        'first_name' => 'Bob',
        'last_name' => 'Original',
      ];

      $bobOriginal['id'] = Contact::create(FALSE)
        ->setValues($bobOriginal)
        ->execute()->single()['id'];

      // This modified version of Zak will match with the existing Zak
      // (nickname and first name both match)

      $sameZakWithChangedLastName = [
        'nick_name' => $zakOriginal['nick_name'],
        'first_name' => $zakOriginal['first_name'],
        'last_name' => 'Changed',
      ];

      $sameZakWithChangedLastName['id'] = Contact::save(FALSE)
        ->setMatch(['first_name', 'nick_name'])
        ->setRecords([$sameZakWithChangedLastName])
        ->execute()->single()['id'];

      self::assertEquals($zakOriginal['id'], $sameZakWithChangedLastName['id']);

      // This new Bob will not match the existing Bob
      // (first name matches, but nickname is different)

      self::assertNotEquals($bobOriginal['nick_name'], $testValue);

      $anotherBob = [
        'nick_name' => $testValue,
        'first_name' => $bobOriginal['first_name'],
        'last_name' => 'Changed',
      ];

      $anotherBob['id'] = Contact::save(FALSE)
        ->setMatch(['first_name', 'nick_name'])
        ->setRecords([$anotherBob])
        ->execute()->single()['id'];

      self::assertGreaterThan($bobOriginal['id'], $anotherBob['id']);

      $allContactIds = [
        $zakOriginal['id'],
        $sameZakWithChangedLastName['id'],
        $bobOriginal['id'],
        $anotherBob['id'],
      ];

      $allCreatedAndSavedContacts = Contact::get(FALSE)
        ->setSelect(['id', 'first_name', 'last_name', 'nick_name'])
        ->addWhere('id', 'IN', $allContactIds)
        ->execute()->indexBy('id');

      self::assertCount(3, $allCreatedAndSavedContacts);

      $expected = [
        $zakOriginal['id'] => $sameZakWithChangedLastName,
        $bobOriginal['id'] => $bobOriginal,
        $anotherBob['id'] => $anotherBob,
      ];

      self::assertEquals($expected, $allCreatedAndSavedContacts->getArrayCopy());

      Contact::delete(FALSE)
        ->setUseTrash(FALSE)
        ->addWhere('id', 'IN', $allContactIds)
        ->execute();
    }
  }

}
