<?php

namespace api\v4\Action;

use Civi\Api4\CustomField;
use Civi\Api4\CustomGroup;
use Civi\Api4\OptionGroup;
use Civi\Api4\OptionValue;

/**
 * @group headless
 */
class CreateCustomValueTest extends BaseCustomValueTest {

  public function testGetWithCustomData() {
    $optionValues = ['r' => 'Red', 'g' => 'Green', 'b' => 'Blue'];

    $customGroup = CustomGroup::create()
      ->setCheckPermissions(FALSE)
      ->addValue('name', 'MyContactFields')
      ->addValue('extends', 'Contact')
      ->execute()
      ->first();

    CustomField::create()
      ->setCheckPermissions(FALSE)
      ->addValue('label', 'Color')
      ->addValue('option_values', $optionValues)
      ->addValue('custom_group_id', $customGroup['id'])
      ->addValue('html_type', 'Select')
      ->addValue('data_type', 'String')
      ->execute();

    $customField = CustomField::get()
      ->setCheckPermissions(FALSE)
      ->addWhere('label', '=', 'Color')
      ->execute()
      ->first();

    $this->assertNotNull($customField['option_group_id']);
    $optionGroupId = $customField['option_group_id'];

    $optionGroup = OptionGroup::get()
      ->setCheckPermissions(FALSE)
      ->addWhere('id', '=', $optionGroupId)
      ->execute()
      ->first();

    $this->assertEquals('Color', $optionGroup['title']);

    $createdOptionValues = OptionValue::get()
      ->setCheckPermissions(FALSE)
      ->addWhere('option_group_id', '=', $optionGroupId)
      ->execute()
      ->getArrayCopy();

    $values = array_column($createdOptionValues, 'value');
    $labels = array_column($createdOptionValues, 'label');
    $createdOptionValues = array_combine($values, $labels);

    $this->assertEquals($optionValues, $createdOptionValues);
  }

}
