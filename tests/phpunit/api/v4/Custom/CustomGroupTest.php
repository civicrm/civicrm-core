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

  public function testUpdateCustomGroup() {
    $customGroup1 = $this->createTestRecord('CustomGroup', [
      'extends' => 'Contribution',
      'weight' => 1,
    ]);
    $customGroup2 = $this->createTestRecord('CustomGroup', [
      'extends' => 'Contribution',
    ]);

    CustomGroup::update(FALSE)
      ->addValue('weight', 1)
      ->addWhere('id', '=', $customGroup2['id'])
      ->execute();

    $groups = CustomGroup::get(FALSE)
      ->addWhere('id', 'IN', [$customGroup1['id'], $customGroup2['id']])
      ->addOrderBy('id')
      ->execute();

    $this->assertEquals(1, $groups[1]['weight']);
    $this->assertEquals(2, $groups[0]['weight']);
    $this->assertEquals('Contribution', $groups[0]['extends']);
    $this->assertEquals('Contribution', $groups[1]['extends']);
  }

}
