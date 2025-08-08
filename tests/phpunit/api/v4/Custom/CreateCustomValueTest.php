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
use Civi\Api4\Contact;
use Civi\Api4\CustomField;
use Civi\Api4\CustomValue;
use Civi\Api4\IM;
use Civi\Api4\OptionGroup;
use Civi\Api4\OptionValue;
use Civi\Api4\Activity;

/**
 * @group headless
 */
class CreateCustomValueTest extends Api4TestBase {

  public function testGetWithCustomData(): void {
    $optionValues = ['r' => 'Red', 'g' => 'Green', 'b' => 'Blue'];

    $customGroup = $this->createTestRecord('CustomGroup', [
      'title' => 'MyContactFields',
      'extends' => 'Contact',
    ]);

    CustomField::create(FALSE)
      ->addValue('label', 'Color')
      ->addValue('option_values', $optionValues)
      ->addValue('custom_group_id', $customGroup['id'])
      ->addValue('html_type', 'CheckBox')
      ->addValue('data_type', 'String')
      ->execute();

    $customField = CustomField::get(FALSE)
      ->addWhere('custom_group_id', '=', $customGroup['id'])
      ->addWhere('label', '=', 'Color')
      ->execute()
      ->first();

    $this->assertNotNull($customField['option_group_id']);
    $optionGroupId = $customField['option_group_id'];

    $optionGroup = OptionGroup::get(FALSE)
      ->addWhere('id', '=', $optionGroupId)
      ->execute()
      ->first();

    $this->assertEquals('MyContactFields :: Color', $optionGroup['title']);

    $createdOptionValues = OptionValue::get(FALSE)
      ->addWhere('option_group_id', '=', $optionGroupId)
      ->execute()
      ->getArrayCopy();

    $values = array_column($createdOptionValues, 'value');
    $labels = array_column($createdOptionValues, 'label');
    $createdOptionValues = array_combine($values, $labels);

    $this->assertEquals($optionValues, $createdOptionValues);

    $fieldName = $customGroup['name'] . '.' . $customField['name'];

    // Test that failing to pass value as array will still serialize correctly
    $contact = $this->createTestRecord('Contact', [$fieldName => 'r']);

    $contact = Contact::get(FALSE)
      ->addSelect($fieldName)
      ->addWhere('id', '=', $contact['id'])
      ->execute()->single();
    $this->assertSame(['r'], $contact[$fieldName]);

    // Ensure serialization really did happen correctly in the DB
    $serializedValue = \CRM_Core_DAO::singleValueQuery("SELECT {$customField['column_name']} FROM {$customGroup['table_name']} WHERE id = 1");
    $this->assertSame(\CRM_Core_DAO::VALUE_SEPARATOR . 'r' . \CRM_Core_DAO::VALUE_SEPARATOR, $serializedValue);
  }

  /**
   * Test setting/getting a multivalue customfield with date+time
   */
  public function testCustomDataWithDateTime(): void {
    $this->createTestRecord('CustomGroup', [
      'title' => 'MyContactDateFields',
      'extends' => 'Contact',
      'is_multiple' => TRUE,
    ]);

    CustomField::create(FALSE)
      ->addValue('custom_group_id:name', 'MyContactDateFields')
      ->addValue('label', 'Date field')
      ->addValue('name', 'date_field')
      ->addValue('data_type', 'Date')
      ->addValue('html_type', 'Select Date')
      ->addValue('date_format', 'yy-mm-dd')
      ->execute();

    CustomField::create(FALSE)
      ->addValue('custom_group_id:name', 'MyContactDateFields')
      ->addValue('label', 'Date time field')
      ->addValue('name', 'date_time_field')
      ->addValue('data_type', 'Date')
      ->addValue('html_type', 'Select Date')
      ->addValue('date_format', 'yy-mm-dd')
      ->addValue('time_format', 2)
      ->execute();

    $contactID = $this->createTestRecord('Contact')['id'];

    CustomValue::create('MyContactDateFields', FALSE)
      ->addValue('date_field', '2022-02-02')
      ->addValue('date_time_field', '2022-02-02 12:07:31')
      ->addValue('entity_id', $contactID)
      ->execute();
    $result = CustomValue::get('MyContactDateFields', FALSE)
      ->execute()
      ->first();

    $this->assertEquals('2022-02-02', $result['date_field']);
    $this->assertEquals('2022-02-02 12:07:31', $result['date_time_field']);

  }

  public function testEmptyValueArrayForCustomFields(): void {
    $contactID = $this->createTestRecord('Contact')['id'];
    $this->createTestRecord('CustomGroup', [
      'title' => 'MyActivityFields',
      'extends' => 'Activity',
    ]);

    $optionValues = ['r' => 'Red', 'g' => 'Green', 'b' => 'Blue'];
    CustomField::create(FALSE)
      ->addValue('custom_group_id:name', 'MyActivityFields')
      ->addValue('label', 'Activity Checkbox Field')
      ->addValue('name', 'activity_checkbox_field')
      ->addValue('data_type', 'String')
      ->addValue('html_type', 'CheckBox')
      ->addValue('option_values', $optionValues)
      ->execute();

    $optionValues = ['o' => 'Orange', 'p' => 'Purple', 'c' => 'Crimson'];
    CustomField::create(FALSE)
      ->addValue('custom_group_id:name', 'MyActivityFields')
      ->addValue('label', 'Activity Select Field')
      ->addValue('name', 'activity_select_field')
      ->addValue('data_type', 'String')
      ->addValue('html_type', 'CheckBox')
      ->addValue('option_values', $optionValues)
      ->execute();
    Activity::create()
      ->setValues([
        'source_contact_id' => $contactID,
        'target_contact_id' => $contactID,
        'subject' => 'Test Empty Custom Field Test Checkbox',
        'activity_type_id:name' => 'Meeting',
        'MyActivityFields.activity_checkbox_field' => [],
      ])
      ->execute();
    Activity::create()
      ->setValues([
        'source_contact_id' => $contactID,
        'target_contact_id' => $contactID,
        'subject' => 'Test Empty Custom Field Test Select',
        'activity_type_id:name' => 'Meeting',
        'MyActivityFields.activity_select_field' => [],
      ])
      ->execute();
  }

  public function testCustomFieldDefaultsGetWrittenOnCreate(): void {
    $contactID = $this->createTestRecord('Contact')['id'];
    $this->createTestRecord('CustomGroup', [
      'title' => 'IMCustom',
      'extends' => 'IM',
    ]);

    // Set up custom field with default static string value
    $optionValues = ['defaultval' => 'Default', 'otherval' => 'Other'];
    $customSelectFieldDefault = 'defaultval';
    CustomField::create(FALSE)
      ->addValue('custom_group_id:name', 'IMCustom')
      ->addValue('label', 'Select Field')
      ->addValue('name', 'im_select_field')
      ->addValue('data_type', 'String')
      ->addValue('html_type', 'Select')
      ->addValue('option_values', $optionValues)
      ->addValue('default_value', $customSelectFieldDefault)
      ->execute();

    // Set up custom field with default static boolean value
    $customBoolFieldDefault = FALSE;
    CustomField::create(FALSE)
      ->addValue('custom_group_id:name', 'IMCustom')
      ->addValue('label', 'Yes/No Field')
      ->addValue('name', 'im_boolean_field')
      ->addValue('data_type', 'Boolean')
      ->addValue('html_type', 'Radio')
      ->addValue('default_value', $customBoolFieldDefault)
      ->execute();

    // Set up custom field with string value generated by callback
    CustomField::create(FALSE)
      ->addValue('custom_group_id:name', 'IMCustom')
      ->addValue('label', 'Value from Callback')
      ->addValue('name', 'im_callback_field')
      ->addValue('data_type', 'String')
      ->addValue('html_type', 'Text')
      ->addValue('default_callback', ['CRM_Utils_System', 'version'])
      ->execute();

    // Core fields can have default values too, of course
    $coreFieldDefault = IM::getFields(TRUE)
      ->addWhere('name', '=', 'is_billing')
      ->addSelect('default_value')
      ->execute()->single()['default_value'];

    // Test: No custom field values given to Create action
    $result = IM::create()
      ->addValue('contact_id', $contactID)
      ->addValue('location_type_id:name', 'Home')
      ->addChain('created_im', \Civi\Api4\IM::get()
        ->addSelect('is_billing', 'custom.*')
        ->addWhere('id', '=', '$id')
      )
      ->execute()->single()['created_im'][0];

    self::assertEquals($coreFieldDefault, $result['is_billing']);
    self::assertEquals($customSelectFieldDefault, $result['IMCustom.im_select_field']);
    self::assertEquals($customBoolFieldDefault, $result['IMCustom.im_boolean_field']);
    // Apparently 'default_callback' is not supported for custom fields?
    // self::assertEquals(\CRM_Utils_System::version(), $result['IMCustom.im_callback_field']);

    // Test: One custom field value given to Create action, the other left blank
    $result = IM::create()
      ->addValue('contact_id', $contactID)
      ->addValue('location_type_id:name', 'Home')
      ->addValue('IMCustom.im_select_field', 'otherval')
      ->addChain('created_im', \Civi\Api4\IM::get()
        ->addSelect('custom.*')
        ->addWhere('id', '=', '$id')
      )
      ->execute()->single()['created_im'][0];

    self::assertEquals('otherval', $result['IMCustom.im_select_field']);
    self::assertEquals($customBoolFieldDefault, $result['IMCustom.im_boolean_field']);
    // Apparently 'default_callback' is not supported for custom fields?
    // self::assertEquals(\CRM_Utils_System::version(), $result['IMCustom.im_callback_field']);
  }

}
