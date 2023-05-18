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
    $child = Group::create(FALSE)
      ->addValue('title', uniqid())
      ->addValue('parents', [$parent1['id'], $parent2['id']])
      ->execute()->single();

    $get = Group::get(FALSE)
      ->addWhere('id', '=', $child['id'])
      ->addSelect('parents')
      ->execute()->single();
    $this->assertEquals([$parent1['id'], $parent2['id']], $get['parents']);

    $get = Group::get(FALSE)
      ->addWhere('id', '=', $child['id'])
      ->addSelect('parents:label')
      ->execute()->single();
    $this->assertEquals([$parent1['title'], $parent2['title']], $get['parents:label']);
  }

  public function testAddRemoveParents() {
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
