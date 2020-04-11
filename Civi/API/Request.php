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
namespace Civi\API;

/**
 * Class Request
 * @package Civi\API
 */
class Request {
  private static $nextId = 1;

  /**
   * Create a formatted/normalized request object.
   *
   * @param string $entity
   *   API entity name.
   * @param string $action
   *   API action name.
   * @param array $params
   *   API parameters.
   *
   * @throws \Civi\API\Exception\NotImplementedException
   * @return \Civi\Api4\Generic\AbstractAction|array
   */
  public static function create(string $entity, string $action, array $params) {
    switch ($params['version'] ?? NULL) {
      case 3:
        return [
          'id' => self::getNextId(),
          'version' => 3,
          'params' => $params,
          'fields' => NULL,
          'entity' => self::normalizeEntityName($entity),
          'action' => self::normalizeActionName($action),
        ];

      case 4:
        // For custom pseudo-entities
        if (strpos($entity, 'Custom_') === 0) {
          $apiRequest = \Civi\Api4\CustomValue::$action(substr($entity, 7));
        }
        else {
          $callable = ["\\Civi\\Api4\\$entity", $action];
          if (!is_callable($callable)) {
            throw new \Civi\API\Exception\NotImplementedException("API ($entity, $action) does not exist (join the API team and implement it!)");
          }
          $apiRequest = call_user_func($callable);
        }
        foreach ($params as $name => $param) {
          $setter = 'set' . ucfirst($name);
          $apiRequest->$setter($param);
        }
        return $apiRequest;

      default:
        throw new \Civi\API\Exception\NotImplementedException("Unknown api version");
    }
  }

  /**
   * Normalize entity to be CamelCase.
   *
   * APIv1-v3 munges entity/action names, and accepts any mixture of case and underscores.
   *
   * @param string $entity
   * @return string
   */
  public static function normalizeEntityName($entity) {
    return \CRM_Utils_String::convertStringToCamel(\CRM_Utils_String::munge($entity));
  }

  /**
   * Normalize api action name to be lowercase.
   *
   * APIv1-v3 munges entity/action names, and accepts any mixture of case and underscores.
   *
   * @param $action
   * @param $version
   * @return string
   */
  public static function normalizeActionName($action) {
    return strtolower(\CRM_Utils_String::munge($action));
  }

  public static function getNextId() {
    return self::$nextId++;
  }

}
