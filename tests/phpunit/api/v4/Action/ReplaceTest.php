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

use Civi\Api4\CustomField;
use Civi\Api4\CustomGroup;
use Civi\Api4\CustomValue;
use Civi\Api4\Email;
use api\v4\Traits\TableDropperTrait;
use api\v4\Api4TestBase;
use Civi\Api4\Contact;
use Civi\Api4\EntityTag;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class ReplaceTest extends Api4TestBase implements TransactionalInterface {
  use TableDropperTrait;

  /**
   * Set up baseline for testing
   */
  public function tearDown(): void {
    $tablesToTruncate = [
      'civicrm_custom_group',
      'civicrm_custom_field',
      'civicrm_email',
    ];
    $this->dropByPrefix('civicrm_value_replacetest');
    $this->cleanup(['tablesToTruncate' => $tablesToTruncate]);
    parent::tearDown();
  }

  public function testEmailReplace(): void {
    [$cid1, $cid2] = $this->saveTestRecords('Individual', [
      'records' => [
        ['first_name' => 'Lotsa', 'last_name' => 'Emails'],
        ['first_name' => 'Notso', 'last_name' => 'Many'],
      ],
    ])->column('id');
    [$e0, $e1, $e2] = $this->saveTestRecords('Email', [
      'defaults' => ['location_type_id' => 1],
      'records' => [
        ['contact_id' => $cid2, 'email' => 'nosomany@example.com'],
        ['contact_id' => $cid1, 'email' => 'first@example.com'],
        ['contact_id' => $cid1, 'email' => 'second@example.com'],
      ],
    ])->column('id');
    $replacement = [
      ['email' => 'firstedited@example.com', 'id' => $e1],
      ['contact_id' => $cid1, 'email' => 'third@example.com', 'location_type_id' => 1],
    ];
    $replaced = Email::replace()
      ->setRecords($replacement)
      ->addWhere('contact_id', '=', $cid1)
      ->setReload(['id', 'email', 'contact_id.sort_name'])
      ->execute()->indexBy('id');
    // Should have saved 2 records
    $this->assertEquals(2, $replaced->count());
    // Should have updated 1 record
    $this->assertEquals(1, $replaced->countMatched());
    // Should have deleted email2
    $this->assertEquals([['id' => $e2]], $replaced->deleted);
    // Check reloaded values
    foreach ($replaced as $id => $item) {
      $expected = $id === $e1 ? 'firstedited@example.com' : 'third@example.com';
      $this->assertEquals($expected, $item['email']);
      $this->assertEquals('Emails, Lotsa', $item['contact_id.sort_name']);
    }
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

  public function testCustomValueReplace(): void {
    $customGroup = CustomGroup::create(FALSE)
      ->addValue('title', 'replaceTest')
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

    CustomField::create(FALSE)
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

  public function testReplaceEntityTag(): void {
    $t1 = uniqid();
    $t2 = uniqid();
    $t3 = uniqid();
    $this->saveTestRecords('Tag', [
      'records' => [['name' => $t1], ['name' => $t2], ['name' => $t3]],
      'defaults' => ['used_for' => ['civicrm_contact']],
    ]);

    $cid = $this->createTestRecord('Contact')['id'];

    EntityTag::replace(FALSE)
      ->addWhere('entity_id', '=', $cid)
      ->setRecords([['tag_id:name' => $t1], ['tag_id:name' => $t2]])
      ->addDefault('entity_table', 'civicrm_contact')
      ->setMatch(['entity_table', 'entity_id', 'tag_id'])
      ->execute();

    $result = EntityTag::get(FALSE)
      ->addWhere('entity_table', '=', 'civicrm_contact')
      ->addWhere('entity_id', '=', $cid)
      ->addSelect('tag_id:name')
      ->execute()->column('tag_id:name');

    $this->assertEquals([$t1, $t2], $result);

    EntityTag::replace(FALSE)
      ->addWhere('entity_id', '=', $cid)
      ->setRecords([['tag_id:name' => $t1], ['tag_id:name' => $t3]])
      ->addDefault('entity_table', 'civicrm_contact')
      ->setMatch(['entity_table', 'entity_id', 'tag_id'])
      ->execute();

    $result = EntityTag::get(FALSE)
      ->addWhere('entity_table', '=', 'civicrm_contact')
      ->addWhere('entity_id', '=', $cid)
      ->addSelect('tag_id:name')
      ->execute()->column('tag_id:name');

    $this->assertEquals([$t1, $t3], $result);
  }

}
