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
use Civi\API\Exception\UnauthorizedException;
use Civi\Api4\Group;

/**
 * @group headless
 */
class GroupTest extends Api4TestBase {

  public function testSmartGroupCache(): void {
    \Civi::settings()->set('smartGroupCacheTimeout', 5);
    $savedSearch = $this->createTestRecord('SavedSearch', [
      'api_entity' => 'Contact',
      'api_params' => [
        'version' => 4,
        'select' => ['id'],
        'where' => [],
      ],
    ]);
    $smartGroup = $this->createTestRecord('Group', [
      'saved_search_id' => $savedSearch['id'],
    ]);
    $parentGroup = $this->createTestRecord('Group');
    $childGroup = $this->createTestRecord('Group', [
      'parents' => [$parentGroup['id']],
    ]);
    $groupIds = [$smartGroup['id'], $parentGroup['id'], $childGroup['id']];

    $get = Group::get(FALSE)->addWhere('id', 'IN', $groupIds)
      ->addSelect('id', 'cache_date', 'cache_expired')
      ->execute()->indexBy('id');
    // Static (non-parent) groups should always have a null cache_date and expired should always be false.
    $this->assertNull($get[$childGroup['id']]['cache_date']);
    $this->assertFalse($get[$childGroup['id']]['cache_expired']);
    // The others will start off with no cache date
    $this->assertNull($get[$parentGroup['id']]['cache_date']);
    $this->assertTrue($get[$parentGroup['id']]['cache_expired']);
    $this->assertNull($get[$smartGroup['id']]['cache_date']);
    $this->assertTrue($get[$smartGroup['id']]['cache_expired']);

    $refresh = Group::refresh(FALSE)
      ->addWhere('id', 'IN', $groupIds)
      ->execute();
    $this->assertCount(2, $refresh);

    $get = Group::get(FALSE)->addWhere('id', 'IN', $groupIds)
      ->addSelect('id', 'cache_date', 'cache_expired')
      ->execute()->indexBy('id');
    $this->assertNull($get[$childGroup['id']]['cache_date']);
    $this->assertFalse($get[$childGroup['id']]['cache_expired']);
    $this->assertNotNull($get[$smartGroup['id']]['cache_date']);
    $this->assertFalse($get[$smartGroup['id']]['cache_expired']);

    // Pretend the group was refreshed 6 minutes ago
    $lastRefresh = date('YmdHis', strtotime("-6 minutes"));
    \CRM_Core_DAO::executeQuery("UPDATE civicrm_group SET cache_date = $lastRefresh WHERE id = %1", [
      1 => [$smartGroup['id'], 'Integer'],
    ]);

    $get = Group::get(FALSE)->addWhere('id', 'IN', $groupIds)
      ->addSelect('id', 'cache_date', 'cache_expired')
      ->execute()->indexBy('id');
    $this->assertNull($get[$childGroup['id']]['cache_date']);
    $this->assertFalse($get[$childGroup['id']]['cache_expired']);
    $this->assertNotNull($get[$smartGroup['id']]['cache_date']);
    $this->assertTrue($get[$smartGroup['id']]['cache_expired']);
  }

  public function testCreate(): void {
    $this->createLoggedInUser();
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = [
      'access CiviCRM',
      'edit groups',
    ];

    $types = array_flip(\CRM_Contact_BAO_Group::buildOptions('group_type'));

    Group::create(TRUE)
      ->addValue('title', uniqid())
      ->addValue('group_type:name', 'Access Control')
      ->execute();

    \CRM_Core_Config::singleton()->userPermissionClass->permissions = [
      'access CiviCRM',
      'create mailings',
    ];

    // Cannot create any group other than ['Mailing List'] without 'edit groups'
    try {
      Group::create(TRUE)
        ->addValue('title', uniqid())
        ->addValue('group_type:name', 'Access Control')
        ->execute();
      $this->fail();
    }
    catch (UnauthorizedException $e) {
    }
    try {
      Group::create(TRUE)
        ->addValue('title', uniqid())
        ->execute();
      $this->fail();
    }
    catch (UnauthorizedException $e) {
    }

    // Can create a mailing group without 'edit groups'
    Group::create(TRUE)
      ->addValue('title', uniqid())
      ->addValue('group_type', [$types['Mailing List']])
      ->execute();

    \CRM_Core_Config::singleton()->userPermissionClass->permissions = [
      'access CiviCRM',
      'access CiviMail',
    ];

    // Also works with pseudoconstant notation
    Group::create(TRUE)
      ->addValue('title', uniqid())
      ->addValue('group_type:name', 'Mailing List')
      ->execute();
  }

  public function testParentsInWhereClause(): void {
    // Create 10 groups - at least 1 id will be 2-digit and contain the number 1
    $groups = $this->saveTestRecords('Group', [
      'records' => array_fill(0, 10, []),
    ]);

    $child1 = $this->createTestRecord('Group', [
      'parents' => [$groups[1]['id'], $groups[2]['id']],
    ]);
    $child2 = $this->createTestRecord('Group', [
      'parents' => [$groups[8]['id']],
    ]);
    $child3 = $this->createTestRecord('Group', [
      'parents' => [$groups[8]['id'], $groups[9]['id']],
    ]);

    // Check that a digit of e.g. "1" doesn't match a value of e.g. "10"
    $firstDigit = substr($groups[9]['id'], 0, 1);
    $found = Group::get(FALSE)
      ->addWhere('parents', 'CONTAINS', $firstDigit)
      ->selectRowCount()
      ->execute();
    $this->assertCount(0, $found);

    $found = Group::get(FALSE)
      ->addWhere('parents', 'CONTAINS', $groups[8]['id'])
      ->selectRowCount()
      ->execute();
    $this->assertCount(2, $found);

    $found = Group::get(FALSE)
      ->addWhere('parents', 'CONTAINS', $groups[9]['id'])
      ->execute();
    $this->assertCount(1, $found);
    $this->assertEquals($child3['id'], $found[0]['id']);
  }

  public function testGetParents(): void {
    $parent1 = Group::create(FALSE)
      ->addValue('title', uniqid())
      ->execute()->single();
    $parent2 = Group::create(FALSE)
      ->addValue('title', uniqid())
      ->execute()->single();
    $child1 = Group::create(FALSE)
      ->addValue('title', uniqid())
      ->addValue('parents', [$parent1['id'], $parent2['id']])
      ->execute()->single();
    $child2 = Group::create(FALSE)
      ->addValue('title', uniqid())
      ->addValue('parents', [$parent2['id']])
      ->execute()->single();

    $get = Group::get(FALSE)
      ->addWhere('id', '=', $child1['id'])
      ->addSelect('parents')
      ->execute()->single();
    $this->assertEquals([$parent1['id'], $parent2['id']], $get['parents']);

    $get = Group::get(FALSE)
      ->addWhere('id', '=', $child1['id'])
      ->addSelect('parents:label')
      ->execute()->single();
    $this->assertEquals([$parent1['title'], $parent2['title']], $get['parents:label']);

    $get = Group::get(FALSE)
      ->addWhere('id', '=', $parent1['id'])
      ->addSelect('children')
      ->execute()->single();
    $this->assertEquals([$child1['id']], $get['children']);

    $get = Group::get(FALSE)
      ->addWhere('id', '=', $parent2['id'])
      ->addSelect('children:label')
      ->execute()->single();
    $this->assertEquals([$child1['title'], $child2['title']], $get['children:label']);

    $joined = Group::get(FALSE)
      ->addWhere('id', 'IN', [$parent1['id'], $parent2['id'], $child1['id'], $child2['id']])
      ->addSelect('id', 'child_group.id', 'child_group.title')
      ->addJoin('Group AS child_group', 'INNER', 'GroupNesting', ['id', '=', 'child_group.parent_group_id'])
      ->addOrderBy('id')
      ->addOrderBy('child_group.id')
      ->execute();

    $this->assertCount(3, $joined);
    $this->assertEquals($parent1['id'], $joined[0]['id']);
    $this->assertEquals($child1['title'], $joined[0]['child_group.title']);
    $this->assertEquals($parent2['id'], $joined[1]['id']);
    $this->assertEquals($child1['title'], $joined[1]['child_group.title']);
    $this->assertEquals($parent2['id'], $joined[2]['id']);
    $this->assertEquals($child2['title'], $joined[2]['child_group.title']);
  }

  public function testAddRemoveParents(): void {
    $group1 = Group::create(FALSE)
      ->addValue('title', uniqid())
      ->execute()->single();
    $parent1 = Group::create(FALSE)
      ->addValue('title', uniqid())
      ->execute()->single();
    $parent2 = Group::create(FALSE)
      ->addValue('title', uniqid())
      ->execute()->single();

    // ensure self is not added as parent
    Group::update(FALSE)
      ->addValue('parents', [$group1['id']])
      ->addWhere('id', '=', $group1['id'])
      ->execute();
    $get = Group::get(FALSE)
      ->addWhere('id', '=', $group1['id'])
      ->addSelect('parents')
      ->execute()->single();
    $this->assertEmpty($get['parents']);

    Group::update(FALSE)
      ->addValue('parents', [$parent1['id'], $parent2['id'], $group1['id']])
      ->addWhere('id', '=', $group1['id'])
      ->execute();
    $get = Group::get(FALSE)
      ->addWhere('id', '=', $group1['id'])
      ->addSelect('parents')
      ->execute()->single();
    $this->assertEquals([$parent1['id'], $parent2['id']], $get['parents']);

    // ensure adding something else doesn't impact parents
    Group::update(FALSE)
      ->addValue('title', uniqid())
      ->addWhere('id', '=', $group1['id'])
      ->execute();
    $get = Group::get(FALSE)
      ->addWhere('id', '=', $group1['id'])
      ->addSelect('parents')
      ->execute()->single();
    $this->assertEquals([$parent1['id'], $parent2['id']], $get['parents']);

    // ensure removing parent is working
    Group::update(FALSE)
      ->addValue('parents', [$parent2['id']])
      ->addWhere('id', '=', $group1['id'])
      ->execute();
    $get = Group::get(FALSE)
      ->addWhere('id', '=', $group1['id'])
      ->addSelect('parents')
      ->execute()->single();
    $this->assertEquals([$parent2['id']], $get['parents']);
  }

}
