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
 * CustomGroup entity.
 *
 * @package Civi\Api4
 */
class CustomValue extends Generic\AbstractEntity {

  /**
   * @param string $customGroup
   * @return Action\CustomValue\Get
   */
  public static function get($customGroup) {
    return new Action\CustomValue\Get($customGroup, __FUNCTION__);
  }

  /**
   * @param string $customGroup
   * @return Action\CustomValue\GetFields
   */
  public static function getFields($customGroup = NULL) {
    return new Action\CustomValue\GetFields($customGroup, __FUNCTION__);
  }

  /**
   * @param string $customGroup
   * @return Action\CustomValue\Save
   */
  public static function save($customGroup) {
    return new Action\CustomValue\Save($customGroup, __FUNCTION__);
  }

  /**
   * @param string $customGroup
   * @return Action\CustomValue\Create
   */
  public static function create($customGroup) {
    return new Action\CustomValue\Create($customGroup, __FUNCTION__);
  }

  /**
   * @param string $customGroup
   * @return Action\CustomValue\Update
   */
  public static function update($customGroup) {
    return new Action\CustomValue\Update($customGroup, __FUNCTION__);
  }

  /**
   * @param string $customGroup
   * @return Action\CustomValue\Delete
   */
  public static function delete($customGroup) {
    return new Action\CustomValue\Delete($customGroup, __FUNCTION__);
  }

  /**
   * @param string $customGroup
   * @return Action\CustomValue\Replace
   */
  public static function replace($customGroup) {
    return new Action\CustomValue\Replace($customGroup, __FUNCTION__);
  }

  /**
   * @param string $customGroup
   * @return Action\CustomValue\GetActions
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
