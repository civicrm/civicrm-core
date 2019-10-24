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

namespace Civi\Api4\Generic;

use Civi\API\Exception\NotImplementedException;

/**
 * Base class for all api entities.
 *
 * When adding your own api from an extension, extend this class only
 * if your entity does not have an associated DAO. Otherwise extend DAOEntity.
 *
 * The recommended way to create a non-DAO-based api is to extend this class
 * and then add a getFields function and any other actions you wish, e.g.
 * - a get() function which returns BasicGetAction using your custom getter callback
 * - a create() function which returns BasicCreateAction using your custom setter callback
 * - an update() function which returns BasicUpdateAction using your custom setter callback
 * - a delete() function which returns BasicBatchAction using your custom delete callback
 * - a replace() function which returns BasicReplaceAction (no callback needed but
 *   depends on the existence of get, create, update & delete actions)
 *
 * Note that you can use the same setter callback function for update as create -
 * that function can distinguish between new & existing records by checking if the
 * unique identifier has been set (identifier field defaults to "id" but you can change
 * that when constructing BasicUpdateAction)
 */
abstract class AbstractEntity {

  /**
   * @return \Civi\Api4\Action\GetActions
   */
  public static function getActions() {
    return new \Civi\Api4\Action\GetActions(self::getEntityName(), __FUNCTION__);
  }

  /**
   * Should return \Civi\Api4\Generic\BasicGetFieldsAction
   * @todo make this function abstract when we require php 7.
   * @throws \Civi\API\Exception\NotImplementedException
   */
  public static function getFields() {
    throw new NotImplementedException(self::getEntityName() . ' should implement getFields action.');
  }

  /**
   * Returns a list of permissions needed to access the various actions in this api.
   *
   * @return array
   */
  public static function permissions() {
    $permissions = \CRM_Core_Permission::getEntityActionPermissions();

    // For legacy reasons the permissions are keyed by lowercase entity name
    // Note: Convert to camel & back in order to circumvent all the api3 naming oddities
    $lcentity = _civicrm_api_get_entity_name_from_camel(\CRM_Utils_String::convertStringToCamel(self::getEntityName()));
    // Merge permissions for this entity with the defaults
    return \CRM_Utils_Array::value($lcentity, $permissions, []) + $permissions['default'];
  }

  /**
   * Get entity name from called class
   *
   * @return string
   */
  protected static function getEntityName() {
    return substr(static::class, strrpos(static::class, '\\') + 1);
  }

  /**
   * Magic method to return the action object for an api.
   *
   * @param string $action
   * @param null $args
   * @return AbstractAction
   * @throws NotImplementedException
   */
  public static function __callStatic($action, $args) {
    $entity = self::getEntityName();
    // Find class for this action
    $entityAction = "\\Civi\\Api4\\Action\\$entity\\" . ucfirst($action);
    if (class_exists($entityAction)) {
      $actionObject = new $entityAction($entity, $action);
    }
    else {
      throw new NotImplementedException("Api $entity $action version 4 does not exist.");
    }
    return $actionObject;
  }

}
