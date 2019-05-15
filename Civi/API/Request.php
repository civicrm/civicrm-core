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
   * @param mixed $extra
   *   Who knows? ...
   *
   * @throws \API_Exception
   * @return array
   *   the request descriptor; keys:
   *   - version: int
   *   - entity: string
   *   - action: string
   *   - params: array (string $key => mixed $value) [deprecated in v4]
   *   - extra: unspecified
   *   - fields: NULL|array (string $key => array $fieldSpec)
   *   - options: \CRM_Utils_OptionBag derived from params [v4-only]
   *   - data: \CRM_Utils_OptionBag derived from params [v4-only]
   *   - chains: unspecified derived from params [v4-only]
   */
  public static function create($entity, $action, $params, $extra = NULL) {
    $version = \CRM_Utils_Array::value('version', $params);
    switch ($version) {
      default:
        $apiRequest = [];
        $apiRequest['id'] = self::$nextId++;
        $apiRequest['version'] = (int) $version;
        $apiRequest['params'] = $params;
        $apiRequest['extra'] = $extra;
        $apiRequest['fields'] = NULL;
        $apiRequest['entity'] = self::normalizeEntityName($entity, $apiRequest['version']);
        $apiRequest['action'] = self::normalizeActionName($action, $apiRequest['version']);
        return $apiRequest;

      case 4:
        $callable = ["Civi\\Api4\\$entity", $action];
        if (!is_callable($callable)) {
          throw new Exception\NotImplementedException("API ($entity, $action) does not exist (join the API team and implement it!)");
        }
        $apiCall = call_user_func($callable);
        $apiRequest['id'] = self::$nextId++;
        unset($params['version']);
        foreach ($params as $name => $param) {
          $setter = 'set' . ucfirst($name);
          $apiCall->$setter($param);
        }
        return $apiCall;
    }

  }

  /**
   * Normalize entity to be CamelCase.
   *
   * APIv1-v3 munges entity/action names, and accepts any mixture of case and underscores.
   *
   * @param string $entity
   * @param int $version
   * @return string
   */
  public static function normalizeEntityName($entity, $version) {
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
  public static function normalizeActionName($action, $version) {
    return strtolower(\CRM_Utils_String::munge($action));
  }

}
