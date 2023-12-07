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

use api\v4\Custom\CustomTestBase;
use Civi\Api4\Contact;

/**
 * @group headless
 */
class ContactDuplicatesTest extends CustomTestBase {

  public function testGetDuplicatesUnsupervised(): void {
    $email = uniqid('test@');

    $testContacts = $this->saveTestRecords('Contact', [
      'records' => [[], [], [], ['email_primary.email' => 'something@el.se']],
      'defaults' => ['email_primary.email' => $email],
    ])->column('id');

    $found = Contact::getDuplicates(FALSE)
      ->setDedupeRule('Individual.Unsupervised')
      ->addValue('email_primary.email', $email)
      ->execute()->column('id');

    $this->assertCount(3, $found);
    $this->assertNotContains($testContacts[3], $found);
  }

  public function testGetFieldsForContactGetDuplicatesAction(): void {
    $fields = Contact::getFields(FALSE)
      ->setAction('getDuplicates')
      ->execute()
      ->indexBy('name');

    $this->assertEquals('Contact', $fields['contact_type']['entity']);
    $this->assertEquals('Email', $fields['email_primary.email']['entity']);
  }

  public function testGetRuleGroupNames(): void {
    $this->createTestRecord('DedupeRuleGroup', [
      'contact_type' => 'Individual',
      'name' => 'houseRule',
      'used' => 'General',
      'threshold' => 10,
    ]);

    $meta = Contact::getActions(FALSE)
      ->addWhere('name', '=', 'getDuplicates')
      ->execute()
      ->first();

    // Default rules
    $this->assertContains('Individual.Unsupervised', $meta['params']['dedupeRule']['options']);
    $this->assertContains('Individual.Supervised', $meta['params']['dedupeRule']['options']);
    $this->assertContains('Organization.Unsupervised', $meta['params']['dedupeRule']['options']);
    $this->assertContains('Organization.Supervised', $meta['params']['dedupeRule']['options']);

    // The rule we just made up
    $this->assertContains('houseRule', $meta['params']['dedupeRule']['options']);
  }

  public function testDedupeWithCustomFields(): void {
    $customGroup = $this->createTestRecord('CustomGroup', ['name' => 'test1']);

    $customFieldText = $this->createTestRecord('CustomField', [
      'name' => 'text',
      'custom_group_id.name' => 'test1',
    ]);
    $customFieldSelect = $this->createTestRecord('CustomField', [
      'name' => 'select',
      'custom_group_id.name' => 'test1',
      'html_type' => 'Select',
      'option_values' => ['r' => 'Red', 'g' => 'Green', 'b' => 'Blue'],
    ]);

    $customRuleGroup = $this->createTestRecord('DedupeRuleGroup', [
      'contact_type' => 'Individual',
      'name' => 'customRule',
      'used' => 'General',
      'threshold' => 10,
    ]);
    $this->saveTestRecords('DedupeRule', [
      'defaults' => ['dedupe_rule_group_id' => $customRuleGroup['id'], 'rule_weight' => 5],
      'records' => [
        ['rule_table' => 'civicrm_contact', 'rule_field' => 'last_name'],
        ['rule_table' => $customGroup['table_name'], 'rule_field' => $customFieldText['column_name']],
        ['rule_table' => $customGroup['table_name'], 'rule_field' => $customFieldSelect['column_name']],
      ],
    ]);

    $testContacts = $this->saveTestRecords('Contact', [
      'records' => [
        ['last_name' => 'A1', 'test1.select' => 'g'],
        ['last_name' => 'A1', 'test1.text' => 'T1'],
        ['last_name' => 'A2', 'test1.text' => 'T1', 'test1.select' => 'r'],
        ['last_name' => 'A2', 'test1.text' => 'T1', 'test1.select' => 'g'],
        ['last_name' => 'A2', 'test1.text' => 'T2', 'test1.select' => 'g'],
      ],
    ])->column('id');

    $found = Contact::getDuplicates(FALSE)
      ->setDedupeRule('customRule')
      ->addValue('last_name', 'A1')
      ->addValue('test1.text', 'T1')
      ->addValue('test1.select:label', 'Green')
      ->execute()->column('id');

    $this->assertCount(3, $found);
    $this->assertContainsEquals($testContacts[0], $found);
    $this->assertContainsEquals($testContacts[1], $found);
    $this->assertContainsEquals($testContacts[3], $found);

    $found = Contact::getDuplicates(FALSE)
      ->setDedupeRule('customRule')
      ->addValue('last_name', 'A2')
      ->addValue('test1.text', 'T2')
      ->addValue('test1.select:label', 'Red')
      ->execute()->column('id');

    $this->assertCount(2, $found);
    $this->assertContainsEquals($testContacts[2], $found);
    $this->assertContainsEquals($testContacts[4], $found);
  }

  public function testMergeDuplicates():void {
    $email = uniqid('test@');

    $testContacts = $this->saveTestRecords('Contact', [
      'records' => [['first_name' => 'Jo'], ['first_name' => 'Not']],
      'defaults' => ['email_primary.email' => $email],
    ])->column('id');

    // Run merge in "safe mode" which will stop because of the name conflicts
    $result = Contact::mergeDuplicates(FALSE)
      ->setContactId($testContacts[0])
      ->setDuplicateId($testContacts[1])
      ->execute();

    $this->assertCount(0, $result[0]['merged']);
    $this->assertCount(1, $result[0]['skipped']);
    $check = Contact::get(FALSE)
      ->addWhere('is_deleted', '=', FALSE)
      ->addWhere('id', 'IN', $testContacts)
      ->execute();
    $this->assertCount(2, $check);

    // Run merge in "aggressive mode" which will overwrite the name conflicts
    $result = Contact::mergeDuplicates(FALSE)
      ->setContactId($testContacts[0])
      ->setDuplicateId($testContacts[1])
      ->setMode('aggressive')
      ->execute();

    $this->assertCount(1, $result[0]['merged']);
    $this->assertCount(0, $result[0]['skipped']);
    $check = Contact::get(FALSE)
      ->addWhere('is_deleted', '=', FALSE)
      ->addWhere('id', 'IN', $testContacts)
      ->execute();
    $this->assertCount(1, $check);
    $this->assertEquals('Jo', $check[0]['first_name']);
  }

}
