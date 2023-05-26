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

  public function testCreate() {
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

  public function testGetParents() {
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
      ->execute();

    $this->assertCount(3, $joined);
    $this->assertEquals($parent1['id'], $joined[0]['id']);
    $this->assertEquals($child1['title'], $joined[0]['child_group.title']);
    $this->assertEquals($parent2['id'], $joined[1]['id']);
    $this->assertEquals($child1['title'], $joined[1]['child_group.title']);
    $this->assertEquals($parent2['id'], $joined[2]['id']);
    $this->assertEquals($child2['title'], $joined[2]['child_group.title']);
  }

}
