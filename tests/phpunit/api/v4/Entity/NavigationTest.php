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
use Civi\Api4\Navigation;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class NavigationTest extends Api4TestBase implements TransactionalInterface {

  public function testCreate(): void {
    $created = Navigation::create(FALSE)
      ->addValue('permission', ['administer CiviCRM', 'access CiviCRM'])
      ->addValue('name', 'Test menu item')
      ->execute()->single();

    $fetched = Navigation::get(FALSE)
      ->addWhere('id', '=', $created['id'])
      ->execute()->single();

    $this->assertEquals(['administer CiviCRM', 'access CiviCRM'], $created['permission']);
    $this->assertEquals(\CRM_Core_Config::domainID(), $fetched['domain_id']);
    $this->assertGreaterThan(0, $fetched['weight']);
  }

  public function testUpdateWeights(): void {
    $item1 = $this->createTestRecord('Navigation', [
      'name' => uniqid(),
      'weight' => 1,
    ]);
    $item2 = $this->createTestRecord('Navigation', [
      'name' => uniqid(),
      'weight' => 1,
    ]);
    $result = Navigation::get(FALSE)
      ->addWhere('id', 'IN', [$item1['id'], $item2['id']])
      ->execute()->indexBy('id');
    // Item 2 should have displaced item 1
    $this->assertEquals(2, $result[$item1['id']]['weight']);
    $this->assertEquals(1, $result[$item2['id']]['weight']);

    $item3 = $this->createTestRecord('Navigation', [
      'name' => uniqid(),
      'parent_id' => $item1['id'],
    ]);
    $this->assertEquals(1, $item3['weight']);
    // Move item2 into item1
    $result = Navigation::update(FALSE)
      ->addValue('id', $item2['id'])
      ->addValue('parent_id', $item1['id'])
      ->addValue('weight', 1)
      ->execute()->single();

    $item4 = $this->createTestRecord('Navigation', [
      'name' => uniqid(),
      'parent_id' => $item1['id'],
    ]);

    // Fetch children of item1
    $result = Navigation::get(FALSE)
      ->addWhere('parent_id', '=', $item1['id'])
      ->execute()->indexBy('id');
    $this->assertEquals(1, $result[$item2['id']]['weight']);
    $this->assertEquals(2, $result[$item3['id']]['weight']);
    $this->assertEquals(3, $result[$item4['id']]['weight']);

    // Move item4 to the top level
    $result = Navigation::update(FALSE)
      ->addValue('id', $item4['id'])
      ->addValue('parent_id', NULL)
      ->addValue('weight', 2)
      ->execute()->single();

    // Fetch top level items
    $result = Navigation::get(FALSE)
      ->addWhere('id', 'IN', [$item1['id'], $item4['id']])
      ->execute()->indexBy('id');
    // Item 4 should have displaced item 1
    $this->assertEquals(3, $result[$item1['id']]['weight']);
    $this->assertEquals(2, $result[$item4['id']]['weight']);
    $this->assertNull($result[$item1['id']]['parent_id']);
    $this->assertNull($result[$item4['id']]['parent_id']);
  }

}
