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
  }

}
