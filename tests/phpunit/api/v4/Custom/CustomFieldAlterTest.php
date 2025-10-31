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
use Civi\Api4\CustomField;
use Civi\Api4\CustomGroup;
use Civi\Api4\OptionGroup;

/**
 * @group headless
 */
class CustomFieldAlterTest extends Api4TestBase {

  public function testChangeSerialize(): void {
    $contact = $this->createTestRecord('Contact');

    $customGroup = $this->createTestRecord('CustomGroup', [
      'title' => 'MyFieldsToAlter',
      'extends' => 'Activity',
    ]);

    $field1 = CustomField::create(FALSE)->setValues([
      'custom_group_id' => $customGroup['id'],
      'label' => 'TestOptions',
      'html_type' => 'Select',
      'option_values' => [
        1 => 'One',
        2 => 'Two',
        3 => 'Three',
        4 => 'Four',
      ],
    ])->execute()->single();

    $field2 = CustomField::create(FALSE)->setValues([
      'custom_group_id' => $customGroup['id'],
      'label' => 'TestText',
      'html_type' => 'Text',
    ])->execute()->single();

    $field3 = CustomField::create(FALSE)->setValues([
      'custom_group_id' => $customGroup['id'],
      'serialize' => TRUE,
      'label' => 'TestCountry',
      'data_type' => 'Country',
      'html_type' => 'Select',
    ])->execute()->single();

    $field4 = CustomField::create(FALSE)->setValues([
      'custom_group_id' => $customGroup['id'],
      'serialize' => TRUE,
      'is_required' => TRUE,
      'label' => 'TestContact',
      'data_type' => 'ContactReference',
      'html_type' => 'Autocomplete-Select',
    ])->execute()->single();

    $sampeData = [
      ['subject' => 'A1', 'MyFieldsToAlter.TestText' => 'A1', 'MyFieldsToAlter.TestOptions' => '1'],
      ['subject' => 'A2', 'MyFieldsToAlter.TestText' => 'A2', 'MyFieldsToAlter.TestOptions' => '2'],
      ['subject' => 'A3', 'MyFieldsToAlter.TestText' => 'A3', 'MyFieldsToAlter.TestOptions' => ''],
      ['subject' => 'A4', 'MyFieldsToAlter.TestText' => 'A4', 'MyFieldsToAlter.TestCountry' => [1228, 1039]],
    ];
    $this->saveTestRecords('Activity', [
      'defaults' => ['activity_type_id' => 1, 'source_contact_id' => $contact['id']],
      'records' => $sampeData,
    ]);

    $result = Activity::get(FALSE)
      ->addWhere('MyFieldsToAlter.TestText', 'IS NOT NULL')
      ->addSelect('custom.*', 'subject', 'MyFieldsToAlter.TestOptions:label')
      ->execute()->indexBy('subject');

    $this->assertCount(4, $result);
    $this->assertEquals(1, $result['A1']['MyFieldsToAlter.TestOptions']);
    $this->assertEquals(2, $result['A2']['MyFieldsToAlter.TestOptions']);
    $this->assertEquals('One', $result['A1']['MyFieldsToAlter.TestOptions:label']);
    $this->assertEquals('Two', $result['A2']['MyFieldsToAlter.TestOptions:label']);
    $this->assertTrue(empty($result['A3']['MyFieldsToAlter.TestOptions']));
    $this->assertTrue(empty($result['A4']['MyFieldsToAlter.TestOptions']));

    // Change options field to multiselect
    CustomField::update(FALSE)
      ->addWhere('id', '=', $field1['id'])
      ->addValue('serialize', TRUE)
      ->execute();

    $result = Activity::get(FALSE)
      ->addWhere('MyFieldsToAlter.TestText', 'IS NOT NULL')
      ->addSelect('custom.*', 'subject', 'MyFieldsToAlter.TestOptions:label')
      ->execute()->indexBy('subject');

    $this->assertEquals([1], $result['A1']['MyFieldsToAlter.TestOptions']);
    $this->assertEquals([2], $result['A2']['MyFieldsToAlter.TestOptions']);
    $this->assertEquals(['One'], $result['A1']['MyFieldsToAlter.TestOptions:label']);
    $this->assertTrue(empty($result['A3']['MyFieldsToAlter.TestOptions']));
    $this->assertTrue(empty($result['A4']['MyFieldsToAlter.TestOptions']));

    // Change back to single-select
    CustomField::update(FALSE)
      ->addWhere('id', '=', $field1['id'])
      ->addValue('serialize', FALSE)
      ->execute();

    $result = Activity::get(FALSE)
      ->addWhere('MyFieldsToAlter.TestText', 'IS NOT NULL')
      ->addSelect('custom.*', 'subject', 'MyFieldsToAlter.TestOptions:label')
      ->execute()->indexBy('subject');

    $this->assertCount(4, $result);
    $this->assertEquals(1, $result['A1']['MyFieldsToAlter.TestOptions']);
    $this->assertEquals(2, $result['A2']['MyFieldsToAlter.TestOptions']);
    $this->assertEquals('One', $result['A1']['MyFieldsToAlter.TestOptions:label']);
    $this->assertEquals('Two', $result['A2']['MyFieldsToAlter.TestOptions:label']);
    $this->assertTrue(empty($result['A3']['MyFieldsToAlter.TestOptions']));
    $this->assertTrue(empty($result['A4']['MyFieldsToAlter.TestOptions']));

    // Change country field from multiselect to single
    CustomField::update(FALSE)
      ->addWhere('id', '=', $field3['id'])
      ->addValue('serialize', FALSE)
      ->execute();

    $result = Activity::get(FALSE)
      ->addWhere('MyFieldsToAlter.TestCountry', 'IS NOT NULL')
      ->addSelect('custom.*', 'subject')
      ->execute()->indexBy('subject');
    $this->assertCount(1, $result);
    // The two values originally entered will now be one value
    $this->assertEquals(1228, $result['A4']['MyFieldsToAlter.TestCountry']);

    // Change country field from single to multiselect
    CustomField::update(FALSE)
      ->addWhere('id', '=', $field3['id'])
      ->addValue('serialize', TRUE)
      ->execute();

    $result = Activity::get(FALSE)
      ->addWhere('MyFieldsToAlter.TestCountry', 'IS NOT NULL')
      ->addSelect('custom.*', 'subject')
      ->execute()->indexBy('subject');
    $this->assertCount(1, $result);
    // The two values originally entered will now be one value
    $this->assertEquals([1228], $result['A4']['MyFieldsToAlter.TestCountry']);

    // Repeatedly change contact ref field to ensure FK index is correctly added/dropped with no SQL error
    for ($i = 1; $i < 6; ++$i) {
      CustomField::update(FALSE)
        ->addWhere('id', '=', $field4['id'])
        ->addValue('serialize', $i % 2 == 0)
        ->addValue('is_required', $i % 2 == 0)
        ->execute();
    }

    $this->assertCount(1, OptionGroup::get(FALSE)
      ->addWhere('id', '=', $field1['option_group_id'])
      ->selectRowCount()
      ->execute());

    // Check that custom table exists and is then removed when group is deleted
    $this->assertNotNull(\CRM_Core_DAO::singleValueQuery("SHOW TABLES LIKE '{$customGroup['table_name']}';"));

    $columnCheck = "SELECT COUNT(*) as count
      FROM INFORMATION_SCHEMA.COLUMNS
      WHERE table_schema = DATABASE()
      AND table_name = '{$customGroup['table_name']}'
      AND column_name IN ('{$field1['column_name']}', '{$field2['column_name']}', '{$field3['column_name']}', '{$field4['column_name']}')";
    $this->assertEquals('4', \CRM_Core_DAO::singleValueQuery($columnCheck));

    CustomGroup::delete(FALSE)
      ->addWhere('id', '=', $customGroup['id'])
      ->execute();

    // All columns should be gone
    $this->assertEquals('0', \CRM_Core_DAO::singleValueQuery($columnCheck));

    // Option group should be gone
    $this->assertCount(0, OptionGroup::get(FALSE)
      ->addWhere('id', '=', $field1['option_group_id'])
      ->execute());

    // The table should be gone
    $this->assertNull(\CRM_Core_DAO::singleValueQuery("SHOW TABLES LIKE '{$customGroup['table_name']}';"));
  }

  public function testCustomFieldSearchIndex(): void {
    $customGroup = $this->createTestRecord('CustomGroup', [
      'title' => 'CustomFieldIndexTest',
      'extends' => 'Activity',
    ]);

    $field = $this->createTestRecord('CustomField', [
      'custom_group_id' => $customGroup['id'],
      'label' => 'TestOptions',
      'html_type' => 'Text',
    ]);

    // is_searchable defaults to FALSE, so no index
    $query = "SHOW INDEX FROM {$customGroup['table_name']} WHERE Key_name = 'INDEX_{$field['column_name']}'";
    $dao = \CRM_Core_DAO::executeQuery($query);
    $this->assertEquals(0, $dao->N);

    // Change is_searchable to TRUE
    CustomField::update(FALSE)
      ->addWhere('id', '=', $field['id'])
      ->addValue('is_searchable', TRUE)
      ->execute();

    // Index added now that field is_searchable
    $dao = \CRM_Core_DAO::executeQuery($query);
    $this->assertEquals(1, $dao->N);

    // Disable the field
    CustomField::update(FALSE)
      ->addWhere('id', '=', $field['id'])
      ->addValue('is_active', FALSE)
      ->execute();

    // Index removed when field was disabled
    $dao = \CRM_Core_DAO::executeQuery($query);
    $this->assertEquals(0, $dao->N);
  }

}
