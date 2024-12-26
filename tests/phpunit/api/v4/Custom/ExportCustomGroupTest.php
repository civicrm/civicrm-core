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

/**
 * @group headless
 */
class ExportCustomGroupTest extends CustomTestBase {

  public function testExportCustomGroupWithFieldOptions(): void {
    $optionValues = ['r' => 'Red', 'g' => 'Green', 'b' => 'Blue'];

    $customGroup = CustomGroup::create(FALSE)
      ->addValue('title', 'exportTest')
      ->addValue('extends', 'Individual')
      ->addChain('field1', CustomField::create()
        ->addValue('custom_group_id', '$id')
        ->addValue('option_values', $optionValues)
        ->addValue('label', 'Color')
        ->addValue('html_type', 'Select'), 0
      )->addChain('field2', CustomField::create()
        ->addValue('custom_group_id', '$id')
        ->addValue('data_type', 'Boolean')
        ->addValue('label', 'On Off')
        ->addValue('html_type', 'CheckBox'), 0
      )->execute()->single();

    // Add a 3rd fields that shares the same option group as field1
    CustomField::create(FALSE)
      ->addValue('custom_group_id', $customGroup['id'])
      ->addValue('label', 'Color2')
      ->addValue('html_type', 'Select')
      ->addValue('option_group_id', $customGroup['field1']['option_group_id'])
      ->execute();

    $export = CustomGroup::export(FALSE)
      ->setId($customGroup['id'])
      ->execute();

    // 1 custom group + 1 option group + 3 options + 3 fields
    $this->assertCount(8, $export);
    $this->assertEquals('CustomGroup', $export[0]['entity']);
    $this->assertEquals('OptionGroup', $export[1]['entity']);
    $this->assertEquals('OptionValue', $export[2]['entity']);
    $this->assertEquals('OptionValue', $export[3]['entity']);
    $this->assertEquals('OptionValue', $export[4]['entity']);
    $this->assertEquals('CustomField', $export[5]['entity']);
    $this->assertEquals('CustomField', $export[6]['entity']);
    $this->assertEquals('CustomField', $export[7]['entity']);
    // 2 fields share an option group
    $this->assertEquals($export[5]['params']['values']['option_group_id.name'], $export[7]['params']['values']['option_group_id.name']);
    // Option group name matches
    $this->assertEquals($export[5]['params']['values']['option_group_id.name'], $export[1]['params']['values']['name']);
    // Should be only name, not id
    $this->assertArrayNotHasKey('option_group_id', $export[5]['params']['values']);
    // Field with no options
    $this->assertTrue(!isset($export[6]['params']['values']['option_group_id']));
    $this->assertArrayNotHasKey('option_group_id.name', $export[6]['params']['values']);
    $this->assertArrayNotHasKey('option_values', $export[6]['params']['values']);

    // Match customGroup by name
    $this->assertEquals(['name'], $export[0]['params']['match']);
    // Match optionGroup by name
    $this->assertEquals(['name'], $export[1]['params']['match']);
    // Match optionValue by name and option_group_id
    sort($export[2]['params']['match']);
    $this->assertEquals(['name', 'option_group_id', 'value'], $export[2]['params']['match']);
    // Match customField by name and custom_group_id
    sort($export[5]['params']['match']);
    $this->assertEquals(['custom_group_id', 'name'], $export[5]['params']['match']);
  }

}
