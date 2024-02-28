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

use Civi\Api4\CustomField;
use Civi\Api4\CustomGroup;
use Civi\Api4\CustomValue;
use Civi\Api4\Entity;

/**
 * @group headless
 */
class CustomValueTest extends CustomTestBase {

  /**
   * Test CustomValue::GetFields/Get/Create/Update/Replace/Delete
   */
  public function testCRUD(): void {
    $optionValues = ['r' => 'Red', 'g' => 'Green', 'b' => 'Blue'];

    $group = uniqid('groupc');
    $colorFieldName = uniqid('colorc');
    $multiFieldName = uniqid('chkbx');
    $refFieldName = uniqid('txt');

    $customGroup = CustomGroup::create(FALSE)
      ->addValue('title', $group)
      ->addValue('extends', 'Contact')
      ->addValue('is_multiple', TRUE)
      ->execute()
      ->first();

    $colorField = CustomField::create(FALSE)
      ->addValue('label', $colorFieldName)
      ->addValue('option_values', $optionValues)
      ->addValue('custom_group_id', $customGroup['id'])
      ->addValue('html_type', 'Select')
      ->addValue('data_type', 'String')
      ->execute()->first();

    $multiField = CustomField::create(FALSE)
      ->addValue('label', $multiFieldName)
      ->addValue('option_values', $optionValues)
      ->addValue('custom_group_id', $customGroup['id'])
      ->addValue('html_type', 'CheckBox')
      ->addValue('data_type', 'String')
      ->execute()->first();

    $refField = CustomField::create(FALSE)
      ->addValue('label', $refFieldName)
      ->addValue('custom_group_id', $customGroup['id'])
      ->addValue('html_type', 'Autocomplete-Select')
      ->addValue('data_type', 'EntityReference')
      ->addValue('fk_entity', 'Address')
      ->execute()->first();

    $cid = $this->createTestRecord('Contact', [
      'first_name' => 'Johann',
      'last_name' => 'Tester',
      'contact_type' => 'Individual',
    ])['id'];
    $address1 = $this->createTestRecord('Address')['id'];
    $address2 = $this->createTestRecord('Address')['id'];

    // Ensure virtual api entity has been created
    $entity = Entity::get(FALSE)
      ->addWhere('name', '=', "Custom_$group")
      ->execute()->single();
    $this->assertEquals(['CustomValue', 'DAOEntity'], $entity['type']);
    $this->assertEquals(['id'], $entity['primary_key']);
    $this->assertEquals($customGroup['table_name'], $entity['table_name']);
    $this->assertEquals('Civi\Api4\CustomValue', $entity['class']);
    $this->assertEquals([$group], $entity['class_args']);
    $this->assertEquals('secondary', $entity['searchable']);

    // Retrieve and check the fields of CustomValue = Custom_$group
    $fields = CustomValue::getFields($group, FALSE)->setLoadOptions(TRUE)->execute();
    $expectedResult = [
      [
        'custom_group' => $group,
        'type' => 'Field',
        'name' => $colorFieldName,
        'title' => $colorFieldName,
        'entity' => "Custom_$group",
        'table_name' => $customGroup['table_name'],
        'column_name' => $colorField['column_name'],
        'data_type' => 'String',
        'fk_entity' => NULL,
        'serialize' => 0,
        'options' => $optionValues,
      ],
      [
        'custom_group' => $group,
        'type' => 'Field',
        'name' => $multiFieldName,
        'title' => $multiFieldName,
        'entity' => "Custom_$group",
        'table_name' => $customGroup['table_name'],
        'column_name' => $multiField['column_name'],
        'data_type' => 'String',
        'fk_entity' => NULL,
        'serialize' => 1,
        'options' => $optionValues,
      ],
      [
        'custom_group' => $group,
        'type' => 'Field',
        'name' => $refFieldName,
        'title' => $refFieldName,
        'entity' => "Custom_$group",
        'table_name' => $customGroup['table_name'],
        'column_name' => $refField['column_name'],
        'data_type' => 'Integer',
        'fk_entity' => 'Address',
        'serialize' => 0,
      ],
      [
        'name' => 'id',
        'type' => 'Field',
        'title' => ts('Custom Value ID'),
        'entity' => "Custom_$group",
        'table_name' => $customGroup['table_name'],
        'column_name' => 'id',
        'nullable' => FALSE,
        'data_type' => 'Integer',
        'fk_entity' => NULL,
      ],
      [
        'name' => 'entity_id',
        'type' => 'Field',
        'title' => ts('Entity ID'),
        'table_name' => $customGroup['table_name'],
        'column_name' => 'entity_id',
        'entity' => "Custom_$group",
        'nullable' => FALSE,
        'data_type' => 'Integer',
        'fk_entity' => 'Contact',
      ],
    ];

    foreach ($expectedResult as $key => $field) {
      foreach ($field as $attr => $value) {
        $this->assertEquals($expectedResult[$key][$attr], $fields[$key][$attr], "$key $attr");
      }
    }

    // CASE 1: Test CustomValue::create
    // Create two records for a single contact and using CustomValue::get ensure that two records are created
    $created = [
      CustomValue::create($group)
        ->addValue($colorFieldName, 'g')
        ->addValue($refFieldName, $address1)
        ->addValue("entity_id", $cid)
        ->execute()->first(),
      CustomValue::create($group)
        ->addValue($colorFieldName . ':label', 'Red')
        ->addValue("entity_id", $cid)
        ->execute()->first(),
    ];
    // fetch custom values using API4 CustomValue::get
    $result = CustomValue::get($group)
      ->addSelect('id', 'entity_id', $colorFieldName, $colorFieldName . ':label', $refFieldName)
      ->addOrderBy($colorFieldName, 'ASC')
      ->execute();

    // check if two custom values are created
    $this->assertEquals(2, count($result));
    $expectedResult = [
      [
        'id' => 1,
        $colorFieldName => 'g',
        $colorFieldName . ':label' => 'Green',
        $refFieldName => $address1,
        'entity_id' => $cid,
      ],
      [
        'id' => 2,
        $colorFieldName => 'r',
        $colorFieldName . ':label' => 'Red',
        'entity_id' => $cid,
      ],
    ];
    // match the data
    foreach ($expectedResult as $key => $field) {
      foreach ($field as $attr => $value) {
        $this->assertEquals($expectedResult[$key][$attr], $result[$key][$attr]);
        if (!strpos($attr, ':')) {
          $this->assertEquals($expectedResult[$key][$attr], $created[$key][$attr]);
        }
      }
    }

    // CASE 2: Test CustomValue::update
    // Update a records whose id is 1 and change the custom field (name = Color) value to 'Blue' from 'Green'
    CustomValue::update($group)
      ->addWhere("id", "=", 1)
      ->addValue($colorFieldName . ':label', 'Blue')
      ->addValue($refFieldName, NULL)
      ->execute();

    // ensure that the value is changed for id = 1
    $result = CustomValue::get($group)
      ->addWhere("id", "=", 1)
      ->execute()
      ->first();
    $this->assertEquals('b', $result[$colorFieldName]);
    $this->assertEquals(NULL, $result[$refFieldName]);

    // CASE 3: Test CustomValue::replace
    // create a second contact which will be used to replace the custom values, created earlier
    $secondContactID = $this->createTestRecord('Contact', [
      'first_name' => 'Adam',
      'last_name' => 'Tester',
      'contact_type' => 'Individual',
    ])['id'];
    // Replace all the records which was created earlier with entity_id = first contact
    //  with custom record [$colorField => 'g', 'entity_id' => $secondContactID]
    CustomValue::replace($group)
      ->setRecords([[$colorFieldName => 'g', $multiFieldName . ':label' => ['Red', 'Green'], $refFieldName => $address2, 'entity_id' => $secondContactID]])
      ->addWhere('entity_id', '=', $cid)
      ->execute();

    // Check the two records created earlier is replaced by new contact
    $result = CustomValue::get($group)
      ->addSelect('id', 'entity_id', $colorFieldName, $colorFieldName . ':label', $multiFieldName, $multiFieldName . ':label', $refFieldName)
      ->execute();
    $this->assertEquals(1, count($result));

    $expectedResult = [
      [
        'id' => 3,
        $colorFieldName => 'g',
        $colorFieldName . ':label' => 'Green',
        $multiFieldName => ['r', 'g'],
        $multiFieldName . ':label' => ['Red', 'Green'],
        $refFieldName => $address2,
        'entity_id' => $secondContactID,
      ],
    ];
    foreach ($expectedResult as $key => $field) {
      foreach ($field as $attr => $value) {
        $this->assertEquals($expectedResult[$key][$attr], $result[$key][$attr]);
      }
    }

    // Disable a field
    CustomField::update(FALSE)
      ->addValue('is_active', FALSE)
      ->addWhere('id', '=', $multiField['id'])
      ->execute();

    $result = CustomValue::get($group)->execute()->single();
    $this->assertArrayHasKey($colorFieldName, $result);
    $this->assertArrayNotHasKey($multiFieldName, $result);

    // CASE 4: Test CustomValue::delete
    // There is only record left whose id = 3, delete that record on basis of criteria id = 3
    CustomValue::delete($group)->addWhere("id", "=", 3)->execute();
    $result = CustomValue::get($group)->execute();
    // check that there are no custom values present
    $this->assertEquals(0, count($result));
  }

  /**
   * Whenever a CustomGroup toggles the `is_multiple` flag, the entity-list should be updated.
   *
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function testEntityRefresh(): void {
    $groupName = uniqid('groupc');

    $this->assertNotContains("Custom_$groupName", Entity::get()->execute()->column('name'));

    $customGroup = CustomGroup::create(FALSE)
      ->addValue('title', $groupName)
      ->addValue('extends', 'Contact')
      ->addValue('is_multiple', FALSE)
      ->execute()->single();

    $this->assertNotContains("Custom_$groupName", Entity::get()->execute()->column('name'));

    CustomGroup::update(FALSE)
      ->addWhere('name', '=', $groupName)
      ->addValue('is_multiple', TRUE)
      ->execute();
    $this->assertContains("Custom_$groupName", Entity::get()->execute()->column('name'));

    $links = CustomValue::getLinks($groupName, FALSE)
      ->addValue('id', 3)
      ->execute()->indexBy('ui_action');
    $this->assertStringContainsString('gid=' . $customGroup['id'], $links['view']['path']);
    $this->assertStringContainsString('recId=3', $links['view']['path']);

    CustomGroup::update(FALSE)
      ->addWhere('name', '=', $groupName)
      ->addValue('is_multiple', FALSE)
      ->execute();
    $this->assertNotContains("Custom_$groupName", Entity::get()->execute()->column('name'));
  }

}
