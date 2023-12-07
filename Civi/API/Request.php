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

use Civi\Api4\Utils\CoreUtil;

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
        $className = CoreUtil::getApiClass($entity);
        $callable = [$className, $action];
        if (!$className || !is_callable($callable)) {
          throw new \Civi\API\Exception\NotImplementedException("API ($entity, $action) does not exist (or the extension it belongs to is not enabled).");
        }
        // Extra arguments used e.g. by dynamic entities like Multi-Record custom groups & the ECK extension
        $args = (array) CoreUtil::getInfoItem($entity, 'class_args');
        $apiRequest = call_user_func_array($callable, $args);
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
    return \CRM_Core_DAO_AllCoreTables::convertEntityNameToCamel(\CRM_Utils_String::munge($entity), TRUE);
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
