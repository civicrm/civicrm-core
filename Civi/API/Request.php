<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
    $version = self::parseVersion($params);

    switch ($version) {
      case 2:
      case 3:
        $apiRequest = array(); // new \Civi\API\Request();
        $apiRequest['id'] = self::$nextId++;
        $apiRequest['version'] = $version;
        $apiRequest['params'] = $params;
        $apiRequest['extra'] = $extra;
        $apiRequest['fields'] = NULL;

        $apiRequest['entity'] = $entity = self::normalizeEntityName($entity, $apiRequest['version']);
        $apiRequest['action'] = $action = self::normalizeActionName($action, $apiRequest['version']);

        return $apiRequest;

      case 4:
        $apiCall = call_user_func(array("Civi\\Api4\\$entity", $action));
        unset($params['version']);
        foreach ($params as $name => $param) {
          $setter = 'set' . ucfirst($name);
          $apiCall->$setter($param);
        }
        return $apiCall;

      default:
    }

  }

  /**
   * Normalize/validate entity and action names
   *
   * @param string $entity
   * @param int $version
   * @return string
   * @throws \API_Exception
   */
  public static function normalizeEntityName($entity, $version) {
    if ($version <= 3) {
      // APIv1-v3 munges entity/action names, and accepts any mixture of case and underscores.
      // We normalize entity to be CamelCase.
      return \CRM_Utils_String::convertStringToCamel(\CRM_Utils_String::munge($entity));
    }
    else {
      throw new \API_Exception("Unknown api version");
    }
  }

  public static function normalizeActionName($action, $version) {
    if ($version <= 3) {
      // APIv1-v3 munges entity/action names, and accepts any mixture of case and underscores.
      // We normalize action to be lowercase.
      return strtolower(\CRM_Utils_String::munge($action));
    }
    else {
      throw new \API_Exception("Unknown api version");
    }
  }

  /**
   * We must be sure that every request uses only one version of the API.
   *
   * @param array $params
   *   API parameters.
   * @return int
   */
  protected static function parseVersion($params) {
    $desired_version = empty($params['version']) ? NULL : (int) $params['version'];
    if (isset($desired_version) && is_int($desired_version)) {
      return $desired_version;
    }
    else {
      // we will set the default to version 3 as soon as we find that it works.
      return 3;
    }
  }

}
