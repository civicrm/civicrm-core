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

use Civi\Api4\CustomGroup;

/**
 * @group headless
 */
class CustomGroupTest extends CustomTestBase {

  public function testUpdateCustomGroup(): void {
    $this->createTestRecord('ContactType', [
      'parent_id:name' => 'Individual',
      'label' => 'Tester',
    ]);
    $financialType = $this->createTestRecord('FinancialType');
    $customGroup = $this->saveTestRecords('CustomGroup', [
      'records' => [
        [
          'extends' => 'Contribution',
          'weight' => 1,
        ],
        [
          'extends' => 'Contribution',
          'extends_entity_column_value' => [$financialType['id']],
          'weight' => 2,
        ],
        [
          'extends' => 'Individual',
          'extends_entity_column_value' => ['Tester'],
          'weight' => 3,
        ],
      ],
    ]);
    $id = $customGroup->column('id');

    // Change weight of 2nd group
    CustomGroup::update(FALSE)
      ->addValue('weight', 1)
      ->addWhere('id', '=', $id[1])
      ->execute();

    // Verify new weights
    $groups = CustomGroup::get(FALSE)
      ->addWhere('id', 'IN', $id)
      ->execute()->indexBy('id');
    $this->assertEquals(1, $groups[$id[1]]['weight']);
    $this->assertEquals(2, $groups[$id[0]]['weight']);
    $this->assertEquals(3, $groups[$id[2]]['weight']);

    // Change weight of 3rd group
    CustomGroup::update(FALSE)
      ->addValue('weight', 1)
      ->addWhere('id', '=', $id[2])
      ->execute();

    // Verify new weights
    $groups = CustomGroup::get(FALSE)
      ->addWhere('id', 'IN', $id)
      ->execute()->indexBy('id');
    $this->assertEquals(1, $groups[$id[2]]['weight']);
    $this->assertEquals(2, $groups[$id[1]]['weight']);
    $this->assertEquals(3, $groups[$id[0]]['weight']);

    // Verify that other values were not interfered with
    $this->assertEquals('Contribution', $groups[$id[0]]['extends']);
    $this->assertEquals('Contribution', $groups[$id[1]]['extends']);
    $this->assertEquals([$financialType['id']], $groups[$id[1]]['extends_entity_column_value']);
    $this->assertEquals('Individual', $groups[$id[2]]['extends']);
    $this->assertEquals(['Tester'], $groups[$id[2]]['extends_entity_column_value']);
  }

  public function testGetExtendsEntityColumnValuePseudoconstant(): void {
    $activityTypeName = uniqid();
    $activityType = $this->createTestRecord('OptionValue', [
      'option_group_id:name' => 'activity_type',
      'name' => $activityTypeName,
    ]);
    $customGroup1 = $this->createTestRecord('CustomGroup', [
      'extends' => 'Activity',
      'extends_entity_column_value:name' => [$activityTypeName],
    ]);
    $customGroup2 = $this->createTestRecord('CustomGroup', [
      'extends' => 'Activity',
    ]);
    $this->assertEquals([$activityType['value']], $customGroup1['extends_entity_column_value']);

    $result = CustomGroup::get(FALSE)
      ->addWhere('extends_entity_column_value:name', 'CONTAINS', $activityTypeName)
      ->addWhere('extends:name', '=', 'Activities')
      ->execute()->single();

    $this->assertEquals([$activityType['value']], $result['extends_entity_column_value']);
  }

}
