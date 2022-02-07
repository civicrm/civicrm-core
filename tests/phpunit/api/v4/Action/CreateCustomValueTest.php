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
use Civi\Api4\OptionGroup;
use Civi\Api4\OptionValue;

/**
 * @group headless
 */
class CreateCustomValueTest extends BaseCustomValueTest {

  public function testGetWithCustomData() {
    $optionValues = ['r' => 'Red', 'g' => 'Green', 'b' => 'Blue'];

    $customGroup = CustomGroup::create(FALSE)
      ->addValue('title', 'MyContactFields')
      ->addValue('extends', 'Contact')
      ->execute()
      ->first();

    CustomField::create(FALSE)
      ->addValue('label', 'Color')
      ->addValue('option_values', $optionValues)
      ->addValue('custom_group_id', $customGroup['id'])
      ->addValue('html_type', 'Select')
      ->addValue('data_type', 'String')
      ->execute();

    $customField = CustomField::get(FALSE)
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
  }

  /**
   * Test setting/getting a multivalue customfield with date+time
   */
  public function testCustomDataWithDateTime() {
    CustomGroup::create(FALSE)
      ->addValue('title', 'MyContactDateFields')
      ->addValue('name', 'MyContactDateFields')
      ->addValue('extends', 'Contact')
      ->addValue('is_multiple', TRUE)
      ->execute();

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

    $contactID = $this->createEntity(['type' => 'Individual'])['id'];

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

}
