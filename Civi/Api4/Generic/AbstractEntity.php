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
namespace Civi\Api4\Generic;

use Civi\API\Exception\NotImplementedException;
use Civi\Api4\Utils\ReflectionUtils;

/**
 * Base class for all api entities.
 *
 * This is the most generic of 3 possible base classes for an APIv4 Entity
 * (the other 2, which extend this class, are `BasicEntity` and `DAOEntity`).
 *
 * Implementing an API by extending this class directly is appropriate when it does not implement
 * all of the CRUD actions, or only a subset like `get` without `create`, `update` or `delete`;
 * for example the RelationshipCache entity.
 *
 * For all other APIs that do implement CRUD it is recommended to use:
 * 1. `DAOEntity` for all entities with a DAO (sql table).
 * 2. `BasicEntity` for all others, e.g. file-based entities.
 *
 * An entity which extends this class directly must, at minimum, implement the `getFields` action.
 *
 * @see https://lab.civicrm.org/extensions/api4example
 */
abstract class AbstractEntity {

  /**
   * @param bool $checkPermissions
   * @return \Civi\Api4\Action\GetActions
   */
  public static function getActions($checkPermissions = TRUE) {
    return (new \Civi\Api4\Action\GetActions(static::getEntityName(), __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @return \Civi\Api4\Generic\BasicGetFieldsAction
   */
  abstract public static function getFields();

  /**
   * @return \Civi\Api4\Generic\CheckAccessAction
   */
  public static function checkAccess() {
    return new CheckAccessAction(static::getEntityName(), __FUNCTION__);
  }

  /**
   * Returns a list of permissions needed to access the various actions in this api.
   *
   * @return array
   */
  public static function permissions() {
    $permissions = \CRM_Core_Permission::getEntityActionPermissions();

    // For legacy reasons the permissions are keyed by lowercase entity name
    $lcentity = \CRM_Core_DAO_AllCoreTables::convertEntityNameToLower(static::getEntityName());
    // Merge permissions for this entity with the defaults
    return ($permissions[$lcentity] ?? []) + $permissions['default'];
  }

  /**
   * Get entity name from called class
   *
   * @return string
   */
  protected static function getEntityName() {
    return self::stripNamespace(static::class);
  }

  /**
   * Overridable function to return a localized title for this entity.
   *
   * @param bool $plural
   *   Whether to return a plural title.
   * @return string
   */
  protected static function getEntityTitle($plural = FALSE) {
    $name = static::getEntityName();
    $dao = \CRM_Core_DAO_AllCoreTables::getFullName($name);
    return $dao ? $dao::getEntityTitle($plural) : ($plural ? \CRM_Utils_String::pluralize($name) : $name);
  }

  /**
   * Overridable function to return menu paths related to this entity.
   *
   * @return array
   */
  protected static function getEntityPaths() {
    return [];
  }

  /**
   * Magic method to return the action object for an api.
   *
   * @param string $action
   * @param array $args
   * @return AbstractAction
   * @throws NotImplementedException
   */
  public static function __callStatic($action, $args) {
    $entity = static::getEntityName();
    $nameSpace = str_replace('Civi\Api4\\', 'Civi\Api4\Action\\', static::class);
    // Find class for this action
    $entityAction = "$nameSpace\\" . ucfirst($action);
    if (class_exists($entityAction)) {
      $actionObject = new $entityAction($entity, $action);
      if (isset($args[0]) && $args[0] === FALSE) {
        $actionObject->setCheckPermissions(FALSE);
      }
    }
    else {
      throw new NotImplementedException("Api $entity $action version 4 does not exist.");
    }
    return $actionObject;
  }

  /**
   * Reflection function called by Entity::get()
   *
   * @see \Civi\Api4\Action\Entity\Get
   * @return array{name: string, title: string, description: string, title_plural: string, type: string, paths: array, class: string, primary_key: array, searchable: string, dao: string, label_field: string, icon: string}
   */
  public static function getInfo() {
    $entityName = static::getEntityName();
    $info = [
      'name' => $entityName,
      'title' => static::getEntityTitle(),
      'title_plural' => static::getEntityTitle(TRUE),
      'type' => [self::stripNamespace(get_parent_class(static::class))],
      'paths' => static::getEntityPaths(),
      'class' => static::class,
      'primary_key' => ['id'],
      // Entities without a @searchable annotation will default to secondary,
      // which makes them visible in SearchKit but not at the top of the list.
      'searchable' => 'secondary',
    ];
    // Add info for entities with a corresponding DAO
    $dao = \CRM_Core_DAO_AllCoreTables::getFullName($info['name']);
    if ($dao) {
      $info['paths'] = $dao::getEntityPaths();
      $info['primary_key'] = $dao::$_primaryKey;
      $info['icon'] = $dao::$_icon;
      $info['label_field'] = $dao::$_labelField;
      $info['dao'] = $dao;
      $info['table_name'] = $dao::$_tableName;
      $info['icon_field'] = (array) ($dao::fields()['icon']['name'] ?? NULL);
    }
    foreach (ReflectionUtils::getTraits(static::class) as $trait) {
      $info['type'][] = self::stripNamespace($trait);
    }
    // Get DocBlock from APIv4 Entity class
    $reflection = new \ReflectionClass(static::class);
    $docBlock = ReflectionUtils::getCodeDocs($reflection, NULL, ['entity' => $info['name']]);
    // Convert docblock keys to snake_case
    foreach ($docBlock as $key => $val) {
      $docBlock[\CRM_Utils_String::convertStringToSnakeCase($key)] = $val;
    }
    // Filter docblock to only declared entity fields
    foreach (\Civi\Api4\Entity::$entityFields as $field) {
      if (isset($docBlock[$field['name']])) {
        $val = $docBlock[$field['name']];
        // Convert to array if data_type == Array
        if (isset($field['data_type']) && $field['data_type'] === 'Array' && is_string($val)) {
          $val = \CRM_Core_DAO::unSerializeField($val, \CRM_Core_DAO::SERIALIZE_COMMA);
        }
        $info[$field['name']] = $val;
      }
    }

    if ($dao) {
      $info['description'] = $dao::getEntityDescription() ?? $info['description'] ?? NULL;
    }

    return $info;
  }

  /**
   * Remove namespace prefix from a class name
   *
   * @param string $className
   * @return string
   */
  private static function stripNamespace($className) {
    return substr($className, strrpos($className, '\\') + 1);
  }

}
