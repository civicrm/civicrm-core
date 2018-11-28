<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
namespace Civi\Api4\Generic;

use Civi\API\Exception\NotImplementedException;
use Civi\Api4\Action\Create;
use Civi\Api4\Action\Delete;
use Civi\Api4\Action\Get;
use Civi\Api4\Action\GetActions;
use Civi\Api4\Action\GetFields;
use Civi\Api4\Action\Update;
use Civi\Api4\Action\Replace;

/**
 * Base class for all api entities.
 *
 * @method static Get get
 * @method static GetFields getFields
 * @method static GetActions getActions
 * @method static Create create
 * @method static Update update
 * @method static Delete delete
 * @method static Replace replace
 */
abstract class AbstractEntity {

  /**
   * Magic method to return the action object for an api.
   *
   * @param string $action
   * @param null $args
   * @return AbstractAction
   * @throws NotImplementedException
   */
  public static function __callStatic($action, $args) {
    // Get entity name from called class
    $entity = substr(static::class, strrpos(static::class, '\\') + 1);
    // Find class for this action
    $entityAction = "\\Civi\\Api4\\Action\\$entity\\" . ucfirst($action);
    $genericAction = '\Civi\Api4\Action\\' . ucfirst($action);
    if (class_exists($entityAction)) {
      $actionObject = new $entityAction($entity);
    }
    elseif (class_exists($genericAction)) {
      $actionObject = new $genericAction($entity);
    }
    else {
      throw new NotImplementedException("Api $entity $action version 4 does not exist.");
    }
    if ($entity === 'CustomValue' && !empty($args[0])) {
      $actionObject->setCustomGroup($args[0]);
    }
    return $actionObject;
  }

  /**
   * Returns a list of permissions needed to access the various actions in this api.
   *
   * @return array
   */
  public static function permissions() {
    // Get entity name from called class
    $entity = substr(static::class, strrpos(static::class, '\\') + 1);
    $permissions = \CRM_Core_Permission::getEntityActionPermissions();

    // For legacy reasons the permissions are keyed by lowercase entity name
    $lcentity = _civicrm_api_get_entity_name_from_camel($entity);
    // Merge permissions for this entity with the defaults
    return \CRM_Utils_Array::value($lcentity, $permissions, []) + $permissions['default'];
  }

}
