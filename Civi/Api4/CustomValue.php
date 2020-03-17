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
 * $Id$
 *
 */


namespace Civi\Api4;

/**
 * Provides virtual api entities for every multi-record custom group.
 *
 * This class is different from other apis in that it is not itself an entity, but allows every
 * multi-record custom group to act like an entity.
 *
 * Each action takes the name of the custom group as a parameter, or in traditional syntax the entity is prefixed with 'Custom_'
 *
 * **Ex. OOP:** `\Civi\Api4\CustomValue::get('MyStuff')->addWhere('id', '=', 123);`
 * **Non-OOP:** `civicrm_api4('Custom_MyStuff', 'get', ['where' => [['id', '=', 123]]]);`
 *
 * Note: This class does NOT extend AbstractEntity so it doesn't get mistaken for a "real" entity.
 * @package Civi\Api4
 */
class CustomValue {

  /**
   * @param string $customGroup
   * @return Action\CustomValue\Get
   * @throws \API_Exception
   */
  public static function get($customGroup) {
    return new Action\CustomValue\Get($customGroup, __FUNCTION__);
  }

  /**
   * @param string $customGroup
   * @return Action\CustomValue\GetFields
   * @throws \API_Exception
   */
  public static function getFields($customGroup = NULL) {
    return new Action\CustomValue\GetFields($customGroup, __FUNCTION__);
  }

  /**
   * @param string $customGroup
   * @return Action\CustomValue\Save
   * @throws \API_Exception
   */
  public static function save($customGroup) {
    return new Action\CustomValue\Save($customGroup, __FUNCTION__);
  }

  /**
   * @param string $customGroup
   * @return Action\CustomValue\Create
   * @throws \API_Exception
   */
  public static function create($customGroup) {
    return new Action\CustomValue\Create($customGroup, __FUNCTION__);
  }

  /**
   * @param string $customGroup
   * @return Action\CustomValue\Update
   * @throws \API_Exception
   */
  public static function update($customGroup) {
    return new Action\CustomValue\Update($customGroup, __FUNCTION__);
  }

  /**
   * @param string $customGroup
   * @return Action\CustomValue\Delete
   * @throws \API_Exception
   */
  public static function delete($customGroup) {
    return new Action\CustomValue\Delete($customGroup, __FUNCTION__);
  }

  /**
   * @param string $customGroup
   * @return Action\CustomValue\Replace
   * @throws \API_Exception
   */
  public static function replace($customGroup) {
    return new Action\CustomValue\Replace($customGroup, __FUNCTION__);
  }

  /**
   * @param string $customGroup
   * @return Action\CustomValue\GetActions
   * @throws \API_Exception
   */
  public static function getActions($customGroup = NULL) {
    return new Action\CustomValue\GetActions($customGroup, __FUNCTION__);
  }

  /**
   * @inheritDoc
   */
  public static function permissions() {
    $entity = 'contact';
    $permissions = \CRM_Core_Permission::getEntityActionPermissions();

    // Merge permissions for this entity with the defaults
    return \CRM_Utils_Array::value($entity, $permissions, []) + $permissions['default'];
  }

}
