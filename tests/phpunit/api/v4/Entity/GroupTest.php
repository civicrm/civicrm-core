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

}
