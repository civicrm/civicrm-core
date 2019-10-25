<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
 * $Id$
 *
 */


namespace api\v4\Action;

use Civi\Api4\CustomField;
use Civi\Api4\CustomGroup;
use Civi\Api4\CustomValue;
use Civi\Api4\Email;
use api\v4\Traits\TableDropperTrait;
use api\v4\UnitTestCase;
use Civi\Api4\Contact;

/**
 * @group headless
 */
class ReplaceTest extends UnitTestCase {
  use TableDropperTrait;

  /**
   * Set up baseline for testing
   */
  public function setUp() {
    $tablesToTruncate = [
      'civicrm_custom_group',
      'civicrm_custom_field',
      'civicrm_email',
    ];
    $this->dropByPrefix('civicrm_value_replacetest');
    $this->cleanup(['tablesToTruncate' => $tablesToTruncate]);
    parent::setUp();
  }

  public function testEmailReplace() {
    $cid1 = Contact::create()
      ->addValue('first_name', 'Lotsa')
      ->addValue('last_name', 'Emails')
      ->execute()
      ->first()['id'];
    $cid2 = Contact::create()
      ->addValue('first_name', 'Notso')
      ->addValue('last_name', 'Many')
      ->execute()
      ->first()['id'];
    $e0 = Email::create()
      ->setValues(['contact_id' => $cid2, 'email' => 'nosomany@example.com', 'location_type_id' => 1])
      ->execute()
      ->first()['id'];
    $e1 = Email::create()
      ->setValues(['contact_id' => $cid1, 'email' => 'first@example.com', 'location_type_id' => 1])
      ->execute()
      ->first()['id'];
    $e2 = Email::create()
      ->setValues(['contact_id' => $cid1, 'email' => 'second@example.com', 'location_type_id' => 1])
      ->execute()
      ->first()['id'];
    $replacement = [
      ['email' => 'firstedited@example.com', 'id' => $e1],
      ['contact_id' => $cid1, 'email' => 'third@example.com', 'location_type_id' => 1],
    ];
    $replaced = Email::replace()
      ->setRecords($replacement)
      ->addWhere('contact_id', '=', $cid1)
      ->execute();
    // Should have saved 2 records
    $this->assertEquals(2, $replaced->count());
    // Should have deleted email2
    $this->assertEquals([['id' => $e2]], $replaced->deleted);
    // Verify contact now has the new email records
    $results = Email::get()
      ->addWhere('contact_id', '=', $cid1)
      ->execute()
      ->indexBy('id');
    $this->assertEquals('firstedited@example.com', $results[$e1]['email']);
    $this->assertEquals(2, $results->count());
    $this->assertArrayNotHasKey($e2, (array) $results);
    $this->assertArrayNotHasKey($e0, (array) $results);
    unset($results[$e1]);
    foreach ($results as $result) {
      $this->assertEquals('third@example.com', $result['email']);
    }
    // Validate our other contact's email did not get deleted
    $c2email = Email::get()
      ->addWhere('contact_id', '=', $cid2)
      ->execute()
      ->first();
    $this->assertEquals('nosomany@example.com', $c2email['email']);
  }

  public function testCustomValueReplace() {
    $customGroup = CustomGroup::create()
      ->setCheckPermissions(FALSE)
      ->addValue('name', 'replaceTest')
      ->addValue('extends', 'Contact')
      ->addValue('is_multiple', TRUE)
      ->execute()
      ->first();

    CustomField::create()
      ->addValue('label', 'Custom1')
      ->addValue('custom_group_id', $customGroup['id'])
      ->addValue('html_type', 'String')
      ->addValue('data_type', 'String')
      ->execute();

    CustomField::create()
      ->setCheckPermissions(FALSE)
      ->addValue('label', 'Custom2')
      ->addValue('custom_group_id', $customGroup['id'])
      ->addValue('html_type', 'String')
      ->addValue('data_type', 'String')
      ->execute();

    $cid1 = Contact::create()
      ->addValue('first_name', 'Lotsa')
      ->addValue('last_name', 'Data')
      ->execute()
      ->first()['id'];
    $cid2 = Contact::create()
      ->addValue('first_name', 'Notso')
      ->addValue('last_name', 'Much')
      ->execute()
      ->first()['id'];

    // Contact 2 gets one row
    CustomValue::create('replaceTest')
      ->setCheckPermissions(FALSE)
      ->addValue('Custom1', "2 1")
      ->addValue('Custom2', "2 1")
      ->addValue('entity_id', $cid2)
      ->execute();

    // Create 3 rows for contact 1
    foreach ([1, 2, 3] as $i) {
      CustomValue::create('replaceTest')
        ->setCheckPermissions(FALSE)
        ->addValue('Custom1', "1 $i")
        ->addValue('Custom2', "1 $i")
        ->addValue('entity_id', $cid1)
        ->execute();
    }

    $cid1Records = CustomValue::get('replaceTest')
      ->setCheckPermissions(FALSE)
      ->addWhere('entity_id', '=', $cid1)
      ->execute();

    $this->assertCount(3, $cid1Records);
    $this->assertCount(1, CustomValue::get('replaceTest')->setCheckPermissions(FALSE)->addWhere('entity_id', '=', $cid2)->execute());

    $result = CustomValue::replace('replaceTest')
      ->addWhere('entity_id', '=', $cid1)
      ->addRecord(['Custom1' => 'new one', 'Custom2' => 'new two'])
      ->addRecord(['id' => $cid1Records[0]['id'], 'Custom1' => 'changed one', 'Custom2' => 'changed two'])
      ->execute();

    $this->assertCount(2, $result);
    $this->assertCount(2, $result->deleted);

    $newRecords = CustomValue::get('replaceTest')
      ->setCheckPermissions(FALSE)
      ->addWhere('entity_id', '=', $cid1)
      ->execute()
      ->indexBy('id');

    $this->assertEquals('new one', $newRecords->last()['Custom1']);
    $this->assertEquals('new two', $newRecords->last()['Custom2']);
    $this->assertEquals('changed one', $newRecords[$cid1Records[0]['id']]['Custom1']);
    $this->assertEquals('changed two', $newRecords[$cid1Records[0]['id']]['Custom2']);
  }

}
