<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
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
