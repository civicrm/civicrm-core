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
 * $Id$
 *
 */


namespace api\v4\Action;

use Civi\Api4\CustomField;
use Civi\Api4\CustomGroup;
use Civi\Api4\CustomValue;
use Civi\Api4\Contact;

/**
 * @group headless
 */
class CustomValueTest extends BaseCustomValueTest {

  protected $contactID;

  /**
   * Test CustomValue::GetFields/Get/Create/Update/Replace/Delete
   */
  public function testCRUD() {
    $optionValues = ['r' => 'Red', 'g' => 'Green', 'b' => 'Blue'];

    $group = uniqid('groupc');
    $colorField = uniqid('colorc');
    $multiField = uniqid('chkbx');
    $textField = uniqid('txt');

    $customGroup = CustomGroup::create()
      ->setCheckPermissions(FALSE)
      ->addValue('name', $group)
      ->addValue('extends', 'Contact')
      ->addValue('is_multiple', TRUE)
      ->execute()
      ->first();

    CustomField::create()
      ->setCheckPermissions(FALSE)
      ->addValue('label', $colorField)
      ->addValue('options', $optionValues)
      ->addValue('custom_group_id', $customGroup['id'])
      ->addValue('html_type', 'Select')
      ->addValue('data_type', 'String')
      ->execute();

    CustomField::create()
      ->setCheckPermissions(FALSE)
      ->addValue('label', $multiField)
      ->addValue('options', $optionValues)
      ->addValue('custom_group_id', $customGroup['id'])
      ->addValue('html_type', 'CheckBox')
      ->addValue('data_type', 'String')
      ->execute();

    CustomField::create()
      ->setCheckPermissions(FALSE)
      ->addValue('label', $textField)
      ->addValue('custom_group_id', $customGroup['id'])
      ->addValue('html_type', 'Text')
      ->addValue('data_type', 'String')
      ->execute();

    $this->contactID = Contact::create()
      ->setCheckPermissions(FALSE)
      ->addValue('first_name', 'Johann')
      ->addValue('last_name', 'Tester')
      ->addValue('contact_type', 'Individual')
      ->execute()
      ->first()['id'];

    // Retrieve and check the fields of CustomValue = Custom_$group
    $fields = CustomValue::getFields($group)->execute();
    $expectedResult = [
      [
        'custom_group' => $group,
        'name' => $colorField,
        'title' => $colorField,
        'entity' => "Custom_$group",
        'data_type' => 'String',
        'fk_entity' => NULL,
        'serialize' => NULL,
      ],
      [
        'custom_group' => $group,
        'name' => $multiField,
        'title' => $multiField,
        'entity' => "Custom_$group",
        'data_type' => 'String',
        'fk_entity' => NULL,
        'serialize' => 1,
      ],
      [
        'custom_group' => $group,
        'name' => $textField,
        'title' => $textField,
        'entity' => "Custom_$group",
        'data_type' => 'String',
        'fk_entity' => NULL,
        'serialize' => NULL,
      ],
      [
        'name' => 'id',
        'title' => ts('Custom Value ID'),
        'entity' => "Custom_$group",
        'data_type' => 'Integer',
        'fk_entity' => NULL,
      ],
      [
        'name' => 'entity_id',
        'title' => ts('Entity ID'),
        'entity' => "Custom_$group",
        'data_type' => 'Integer',
        'fk_entity' => 'Contact',
      ],
    ];

    foreach ($expectedResult as $key => $field) {
      foreach ($field as $attr => $value) {
        $this->assertEquals($expectedResult[$key][$attr], $fields[$key][$attr]);
      }
    }

    // CASE 1: Test CustomValue::create
    // Create two records for a single contact and using CustomValue::get ensure that two records are created
    CustomValue::create($group)
      ->addValue($colorField, 'g')
      ->addValue("entity_id", $this->contactID)
      ->execute();
    CustomValue::create($group)
      ->addValue($colorField, 'r')
      ->addValue("entity_id", $this->contactID)
      ->execute();
    // fetch custom values using API4 CustomValue::get
    $result = CustomValue::get($group)->execute();

    // check if two custom values are created
    $this->assertEquals(2, count($result));
    $expectedResult = [
      [
        'id' => 1,
        $colorField => 'g',
        'entity_id' => $this->contactID,
      ],
      [
        'id' => 2,
        $colorField => 'r',
        'entity_id' => $this->contactID,
      ],
    ];
    // match the data
    foreach ($expectedResult as $key => $field) {
      foreach ($field as $attr => $value) {
        $this->assertEquals($expectedResult[$key][$attr], $result[$key][$attr]);
      }
    }

    // CASE 2: Test CustomValue::update
    // Update a records whose id is 1 and change the custom field (name = Color) value to 'Blue' from 'Green'
    CustomValue::update($group)
      ->addWhere("id", "=", 1)
      ->addValue($colorField, 'b')
      ->execute();

    // ensure that the value is changed for id = 1
    $color = CustomValue::get($group)
      ->addWhere("id", "=", 1)
      ->execute()
      ->first()[$colorField];
    $this->assertEquals('b', $color);

    // CASE 3: Test CustomValue::replace
    // create a second contact which will be used to replace the custom values, created earlier
    $secondContactID = Contact::create()
      ->setCheckPermissions(FALSE)
      ->addValue('first_name', 'Adam')
      ->addValue('last_name', 'Tester')
      ->addValue('contact_type', 'Individual')
      ->execute()
      ->first()['id'];
    // Replace all the records which was created earlier with entity_id = first contact
    //  with custom record [$colorField => 'g', 'entity_id' => $secondContactID]
    CustomValue::replace($group)
      ->setRecords([[$colorField => 'g', $multiField => ['r', 'g'], 'entity_id' => $secondContactID]])
      ->addWhere('entity_id', '=', $this->contactID)
      ->execute();

    // Check the two records created earlier is replaced by new contact
    $result = CustomValue::get($group)->execute();
    $this->assertEquals(1, count($result));

    $expectedResult = [
      [
        'id' => 3,
        $colorField => 'g',
        $multiField => ['r', 'g'],
        'entity_id' => $secondContactID,
      ],
    ];
    foreach ($expectedResult as $key => $field) {
      foreach ($field as $attr => $value) {
        $this->assertEquals($expectedResult[$key][$attr], $result[$key][$attr]);
      }
    }

    // CASE 4: Test CustomValue::delete
    // There is only record left whose id = 3, delete that record on basis of criteria id = 3
    CustomValue::delete($group)->addWhere("id", "=", 3)->execute();
    $result = CustomValue::get($group)->execute();
    // check that there are no custom values present
    $this->assertEquals(0, count($result));
  }

}
