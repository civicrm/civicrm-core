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
  public static function create($entity, $action, $params, $extra) {
    $apiRequest = array(); // new \Civi\API\Request();
    $apiRequest['id'] = self::$nextId++;
    $apiRequest['version'] = self::parseVersion($params);
    $apiRequest['params'] = $params;
    $apiRequest['extra'] = $extra;
    $apiRequest['fields'] = NULL;

    $apiRequest['entity'] = $entity = self::normalizeEntityName($entity, $apiRequest['version']);
    $apiRequest['action'] = $action = self::normalizeActionName($action, $apiRequest['version']);

    // APIv1-v3 mix data+options in $params which means that each API callback is responsible
    // for splitting the two. In APIv4, the split is done systematically so that we don't
    // so much parsing logic spread around.
    if ($apiRequest['version'] >= 4) {
      $options = array();
      $data = array();
      $chains = array();
      foreach ($params as $key => $value) {
        if ($key == 'options') {
          $options = array_merge($options, $value);
        }
        elseif ($key == 'return') {
          if (!isset($options['return'])) {
            $options['return'] = array();
          }
          $options['return'] = array_merge($options['return'], $value);
        }
        elseif (preg_match('/^option\.(.*)$/', $key, $matches)) {
          $options[$matches[1]] = $value;
        }
        elseif (preg_match('/^return\.(.*)$/', $key, $matches)) {
          if ($value) {
            if (!isset($options['return'])) {
              $options['return'] = array();
            }
            $options['return'][] = $matches[1];
          }
        }
        elseif (preg_match('/^format\.(.*)$/', $key, $matches)) {
          if ($value) {
            if (!isset($options['format'])) {
              $options['format'] = $matches[1];
            }
            else {
              throw new \API_Exception("Too many API formats specified");
            }
          }
        }
        elseif (preg_match('/^api\./', $key)) {
          // FIXME: represent subrequests as instances of "Request"
          $chains[$key] = $value;
        }
        elseif ($key == 'debug') {
          $options['debug'] = $value;
        }
        elseif ($key == 'version') {
          // ignore
        }
        else {
          $data[$key] = $value;

        }
      }
      $apiRequest['options'] = new \CRM_Utils_OptionBag($options);
      $apiRequest['data'] = new \CRM_Utils_OptionBag($data);
      $apiRequest['chains'] = $chains;
    }

    return $apiRequest;
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
      // APIv4 requires exact spelling & capitalization of entity/action name; deviations should cause errors
      if (!preg_match('/^[a-zA-Z][a-zA-Z0-9]*$/', $entity)) {
        throw new \API_Exception("Malformed entity");
      }
      return $entity;
    }
  }

  public static function normalizeActionName($action, $version) {
    if ($version <= 3) {
      // APIv1-v3 munges entity/action names, and accepts any mixture of case and underscores.
      // We normalize action to be lowercase.
      return strtolower(\CRM_Utils_String::munge($action));
    }
    else {
      // APIv4 requires exact spelling & capitalization of entity/action name; deviations should cause errors
      if (!preg_match('/^[a-zA-Z][a-zA-Z0-9]*$/', $action)) {
        throw new \API_Exception("Malformed action");
      }
      // TODO: Not sure about camelCase actions - in v3 they are all lowercase.
      return strtolower($action{0}) . substr($action, 1);
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
