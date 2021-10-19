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

  public function testSaveWithMatchingCriteria() {
    $records = [
      ['first_name' => 'One', 'last_name' => 'Test', 'external_identifier' => 'abc'],
      ['first_name' => 'Two', 'last_name' => 'Test', 'external_identifier' => 'def'],
    ];

    $contacts = Contact::save(FALSE)
      ->setRecords($records)
      ->execute();

    $records[0]['last_name'] = $records[1]['last_name'] = 'Changed';
    $records[0]['external_identifier'] = 'ghi';

    $modified = Contact::save(FALSE)
      ->setRecords($records)
      ->setMatch(['first_name', 'external_identifier'])
      ->execute();

    $this->assertGreaterThan($contacts[0]['id'], $modified[0]['id']);
    $this->assertEquals($contacts[1]['id'], $modified[1]['id']);

    $ids = [$contacts[0]['id'], $modified[0]['id'], $contacts[1]['id']];
    $get = Contact::get(FALSE)
      ->setSelect(['id', 'first_name', 'last_name', 'external_identifier'])
      ->addWhere('id', 'IN', $ids)
      ->addOrderBy('id')
      ->execute();
    $expected = [
      // Original insert
      ['id' => $contacts[0]['id'], 'first_name' => 'One', 'last_name' => 'Test', 'external_identifier' => 'abc'],
      // Match+update
      ['id' => $contacts[1]['id'], 'first_name' => 'Two', 'last_name' => 'Changed', 'external_identifier' => 'def'],
      // Subsequent insert
      ['id' => $modified[0]['id'], 'first_name' => 'One', 'last_name' => 'Changed', 'external_identifier' => 'ghi'],
    ];
    $this->assertEquals($expected, (array) $get);
  }

}
