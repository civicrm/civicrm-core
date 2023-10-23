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

namespace api\v4\Entity;

use api\v4\Api4TestBase;
use Civi\Api4\OptionValue;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class SortableEntityTest extends Api4TestBase implements TransactionalInterface {

  public function testGetFields(): void {
    $fields = OptionValue::getFields(FALSE)
      ->addWhere('type', '=', 'Extra')
      ->execute()
      ->indexBy('name');
    $this->assertEquals('OptionValue', $fields['previous']['fk_entity']);
  }

  public function testPrevNextWeight(): void {
    // Create 2 option groups.
    $this->saveTestRecords('OptionGroup', [
      'records' => [
        ['name' => 'enGroup'],
        ['name' => 'esGroup'],
      ],
    ]);
    $sampleData = [
      ['label' => 'One', 'name' => 'option_1', 'value' => 1],
      ['label' => 'Two', 'name' => 'option_2', 'value' => 2],
      ['label' => 'Three', 'name' => 'option_3', 'value' => 3],
    ];
    OptionValue::save(FALSE)
      ->setRecords($sampleData)
      ->addDefault('option_group_id.name', 'enGroup')
      ->execute();
    $sampleData = [
      ['label' => 'Uno', 'name' => 'option_1', 'value' => 1],
      ['label' => 'Dos', 'name' => 'option_2', 'value' => 2],
      ['label' => 'Tres', 'name' => 'option_3', 'value' => 3],
    ];
    OptionValue::save(FALSE)
      ->setRecords($sampleData)
      ->addDefault('option_group_id.name', 'esGroup')
      ->execute();

    $optionOne = OptionValue::get(FALSE)
      ->addSelect('label', 'previous.label', 'next.label')
      ->addWhere('value', '=', 1)
      ->addWhere('option_group_id:name', '=', 'enGroup')
      ->execute()->single();
    $this->assertNull($optionOne['previous.label']);
    $this->assertEquals('One', $optionOne['label']);
    $this->assertEquals('Two', $optionOne['next.label']);

    $optionTwo = OptionValue::get(FALSE)
      ->addSelect('previous', 'next')
      ->addWhere('value', '=', 2)
      ->addWhere('option_group_id:name', '=', 'esGroup')
      ->execute()->single();
    $this->assertEquals($optionTwo['id'] - 1, $optionTwo['previous']);
    $this->assertEquals($optionTwo['id'] + 1, $optionTwo['next']);

    $optionTres = OptionValue::get(FALSE)
      ->addSelect('label', 'previous.label', 'next.label')
      ->addWhere('value', '=', 3)
      ->addWhere('option_group_id:name', '=', 'esGroup')
      ->execute()->single();
    $this->assertNull($optionTres['next.label']);
    $this->assertEquals('Tres', $optionTres['label']);
    $this->assertEquals('Dos', $optionTres['previous.label']);

    // Insert new option into the es group, before the last item
    OptionValue::create(FALSE)
      ->addValue('option_group_id:name', 'esGroup')
      ->addValue('name', 'option_4')
      ->addValue('label', 'El Extra')
      ->addValue('next.name', 'option_3')
      ->execute();
    $options = OptionValue::get(FALSE)
      ->addSelect('weight', 'label', 'value', 'previous.name', 'next.name')
      ->addWhere('option_group_id:name', '=', 'esGroup')
      ->addOrderBy('weight')
      ->execute();
    $this->assertEquals([1, 2, 4, 3], $options->column('value'));
    $this->assertEquals([1, 2, 3, 4], $options->column('weight'));
    $this->assertEquals(['Uno', 'Dos', 'El Extra', 'Tres'], $options->column('label'));
    $this->assertEquals([NULL, 'option_1', 'option_2', 'option_4'], $options->column('previous.name'));
    $this->assertEquals(['option_2', 'option_4', 'option_3', NULL], $options->column('next.name'));

    // Insert new option into the es group with invalid next.name
    // Since weight does not resolve it will become the last option
    OptionValue::create(FALSE)
      ->addValue('option_group_id:name', 'esGroup')
      ->addValue('name', 'option_5')
      ->addValue('label', 'El Fin')
      ->addValue('next.name', 'does not exist')
      ->execute();
    $options = OptionValue::get(FALSE)
      ->addSelect('weight', 'label', 'value', 'previous.name', 'next.name')
      ->addWhere('option_group_id:name', '=', 'esGroup')
      ->addOrderBy('weight')
      ->execute();
    $this->assertEquals([1, 2, 4, 3, 5], $options->column('value'));
    $this->assertEquals(['Uno', 'Dos', 'El Extra', 'Tres', 'El Fin'], $options->column('label'));

    // Insert new option into the en group, after the first item
    OptionValue::create(FALSE)
      ->addValue('previous.name', 'option_1')
      ->addValue('option_group_id.name', 'enGroup')
      ->addValue('name', 'option_4')
      ->addValue('label', 'Extra')
      ->execute();
    $options = OptionValue::get(FALSE)
      ->addSelect('weight', 'label', 'value', 'previous.name', 'next.name')
      ->addWhere('option_group_id.name', '=', 'enGroup')
      ->addOrderBy('weight')
      ->execute();
    $this->assertEquals([1, 4, 2, 3], $options->column('value'));
    $this->assertEquals([1, 2, 3, 4], $options->column('weight'));
    $this->assertEquals(['One', 'Extra', 'Two', 'Three'], $options->column('label'));
    $this->assertEquals([NULL, 'option_1', 'option_4', 'option_2'], $options->column('previous.name'));
    $this->assertEquals(['option_4', 'option_2', 'option_3', NULL], $options->column('next.name'));
  }

}
