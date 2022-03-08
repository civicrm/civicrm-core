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

use api\v4\UnitTestCase;
use Civi\Api4\Contact;

/**
 * @group headless
 */
class SaveTest extends UnitTestCase {

  /**
   * @dataProvider getMatchingCriteriaDataProvider
   * @return void
   * @throws \API_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function testSaveWithMatchingCriteria($matchCriteria, $records, $changes, $expected) {
    $contacts = Contact::save(FALSE)
      ->setRecords($records)
      ->execute();

    $modified = Contact::save(FALSE)
      ->setRecords($changes)
      ->setMatch($matchCriteria)
      ->execute();

    $this->assertGreaterThan($contacts[0]['id'], $modified[0]['id']);
    $this->assertEquals($contacts[1]['id'], $modified[1]['id']);

    $ids = [$contacts[0]['id'], $modified[0]['id'], $contacts[1]['id']];
    $get = Contact::get(FALSE)
      ->setSelect(['id', 'first_name', 'last_name', 'external_identifier'])
      ->addWhere('id', 'IN', $ids)
      ->addOrderBy('id')
      ->execute();

    for ($index = 0; $index < count($expected); $index++) {
      $expected[$index]['id'] = $contacts[0]['id'] + $index;
    }
    $this->assertEquals($expected, (array) $get);
  }

  public function getMatchingCriteriaDataProvider() {
    // data = [ match criteria, records, modifiedRecords, expected results ]
    $data[] = [
      ['first_name', 'external_identifier'],
      [
        ['first_name' => 'One', 'last_name' => 'Test', 'external_identifier' => 'abc'],
        ['first_name' => 'Two', 'last_name' => 'Test', 'external_identifier' => 'def'],
      ],
      [
        ['first_name' => 'One', 'last_name' => 'Changed', 'external_identifier' => 'ghi'],
        ['first_name' => 'Two', 'last_name' => 'Changed', 'external_identifier' => 'def'],
      ],
      [
        // Original insert
        ['first_name' => 'One', 'last_name' => 'Test', 'external_identifier' => 'abc'],
        // Match+update
        ['first_name' => 'Two', 'last_name' => 'Changed', 'external_identifier' => 'def'],
        // Subsequent insert
        ['first_name' => 'One', 'last_name' => 'Changed', 'external_identifier' => 'ghi'],
      ],
    ];
    // Test that we get a match on an empty string (eg. external_identifier => '')
    $data[] = [
      ['first_name', 'last_name', 'external_identifier'],
      [
        ['first_name' => 'One', 'last_name' => 'Test', 'external_identifier' => 'abc'],
        ['first_name' => 'Two', 'last_name' => 'Test', 'external_identifier' => ''],
      ],
      [
        ['first_name' => 'One', 'last_name' => 'Test', 'external_identifier' => 'ghi'],
        ['first_name' => 'Two', 'last_name' => 'Test', 'external_identifier' => ''],
      ],
      [
        // Original insert
        ['first_name' => 'One', 'last_name' => 'Test', 'external_identifier' => 'abc'],
        // Match+update
        ['first_name' => 'Two', 'last_name' => 'Test', 'external_identifier' => ''],
        // Subsequent insert
        ['first_name' => 'One', 'last_name' => 'Test', 'external_identifier' => 'ghi'],
      ],
    ];
    return $data;
  }

}
