<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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

namespace Civi\API\Provider;
use Civi\API\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * This class manages the loading of API's using strict file+function naming
 * conventions.
 */
class MagicFunctionProvider implements EventSubscriberInterface, ProviderInterface {
  /**
   * @return array
   */
  public static function getSubscribedEvents() {
    return array(
      Events::RESOLVE => array(
        array('onApiResolve', Events::W_MIDDLE),
      ),
    );
  }

  /**
   * @var array (string $cachekey => array('function' => string, 'is_generic' => bool))
   */
  private $cache;

  /**
   *
   */
  function __construct() {
    $this->cache = array();
  }

  /**
   * @param \Civi\API\Event\ResolveEvent $event
   */
  public function onApiResolve(\Civi\API\Event\ResolveEvent $event) {
    $apiRequest = $event->getApiRequest();
    $resolved = $this->resolve($apiRequest);
    if ($resolved['function']) {
      $apiRequest += $resolved;
      $event->setApiRequest($apiRequest);
      $event->setApiProvider($this);
      $event->stopPropagation();
    }
  }

  /**
   * {inheritdoc}
   */
  public function invoke($apiRequest) {
    $function = $apiRequest['function'];
    if ($apiRequest['function'] && $apiRequest['is_generic']) {
      // Unlike normal API implementations, generic implementations require explicit
      // knowledge of the entity and action (as well as $params). Bundle up these bits
      // into a convenient data structure.
      $result = $function($apiRequest);
    }
    elseif ($apiRequest['function'] && !$apiRequest['is_generic']) {
      $result = isset($extra) ? $function($apiRequest['params'], $extra) : $function($apiRequest['params']);
    }
    return $result;
  }

  /**
   * {inheritdoc}
   */
  function getEntityNames($version) {
    $entities = array();
    $include_dirs = array_unique(explode(PATH_SEPARATOR, get_include_path()));
    #$include_dirs = array(dirname(__FILE__). '/../../');
    foreach ($include_dirs as $include_dir) {
      $api_dir = implode(DIRECTORY_SEPARATOR, array($include_dir, 'api', 'v' . $version));
      if (! is_dir($api_dir)) {
        continue;
      }
      $iterator = new \DirectoryIterator($api_dir);
      foreach ($iterator as $fileinfo) {
        $file = $fileinfo->getFilename();

        // Check for entities with a master file ("api/v3/MyEntity.php")
        $parts = explode(".", $file);
        if (end($parts) == "php" && $file != "utils.php" && !preg_match('/Tests?.php$/', $file) ) {
          // without the ".php"
          $entities[] = substr($file, 0, -4);
        }

        // Check for entities with standalone action files ("api/v3/MyEntity/MyAction.php")
        $action_dir = $api_dir . DIRECTORY_SEPARATOR . $file;
        if (preg_match('/^[A-Z][A-Za-z0-9]*$/', $file) && is_dir($action_dir)) {
          if (count(glob("$action_dir/[A-Z]*.php")) > 0) {
            $entities[] = $file;
          }
        }
      }
    }
    $entities = array_diff($entities, array('Generic'));
    $entities = array_unique($entities);
    sort($entities);

    return $entities;
  }

  /**
   * {inheritdoc}
   */
  public function getActionNames($version, $entity) {
    $entity = _civicrm_api_get_camel_name($entity);
    $entities = $this->getEntityNames($version);
    if (!in_array($entity, $entities)) {
      return array();
    }
    $this->loadEntity($entity, $version);

    $functions = get_defined_functions();
    $actions = array();
    $prefix = 'civicrm_api' . $version . '_' . _civicrm_api_get_entity_name_from_camel($entity) . '_';
    $prefixGeneric = 'civicrm_api' . $version . '_generic_';
    foreach ($functions['user'] as $fct) {
      if (strpos($fct, $prefix) === 0) {
        $actions[] = substr($fct, strlen($prefix));
      }
      elseif (strpos($fct, $prefixGeneric) === 0) {
        $actions[] = substr($fct, strlen($prefixGeneric));
      }
    }
    return $actions;
  }

  /**
   * Look up the implementation for a given API request
   *
   * @param $apiRequest array with keys:
   *  - entity: string, required
   *  - action: string, required
   *  - params: array
   *  - version: scalar, required
   *
   * @return array with keys
   *  - function: callback (mixed)
   *  - is_generic: boolean
   */
  protected function resolve($apiRequest) {
    $cachekey = strtolower($apiRequest['entity']) . ':' . strtolower($apiRequest['action']) . ':' . $apiRequest['version'];
    if (isset($this->cache[$cachekey])) {
      return $this->cache[$cachekey];
    }

    $camelName = _civicrm_api_get_camel_name($apiRequest['entity'], $apiRequest['version']);
    $actionCamelName = _civicrm_api_get_camel_name($apiRequest['action']);

    // Determine if there is an entity-specific implementation of the action
    $stdFunction = $this->getFunctionName($apiRequest['entity'], $apiRequest['action'], $apiRequest['version']);
    if (function_exists($stdFunction)) {
      // someone already loaded the appropriate file
      // FIXME: This has the affect of masking bugs in load order; this is included to provide bug-compatibility
      $this->cache[$cachekey] = array('function' => $stdFunction, 'is_generic' => FALSE);
      return $this->cache[$cachekey];
    }

    $stdFiles = array(
      // By convention, the $camelName.php is more likely to contain the function, so test it first
      'api/v' . $apiRequest['version'] . '/' . $camelName . '.php',
      'api/v' . $apiRequest['version'] . '/' . $camelName . '/' . $actionCamelName . '.php',
    );
    foreach ($stdFiles as $stdFile) {
      if (\CRM_Utils_File::isIncludable($stdFile)) {
        require_once $stdFile;
        if (function_exists($stdFunction)) {
          $this->cache[$cachekey] = array('function' => $stdFunction, 'is_generic' => FALSE);
          return $this->cache[$cachekey];
        }
      }
    }

    // Determine if there is a generic implementation of the action
    require_once 'api/v3/Generic.php';
    # $genericFunction = 'civicrm_api3_generic_' . $apiRequest['action'];
    $genericFunction = $this->getFunctionName('generic', $apiRequest['action'], $apiRequest['version']);
    $genericFiles = array(
      // By convention, the Generic.php is more likely to contain the function, so test it first
      'api/v' . $apiRequest['version'] . '/Generic.php',
      'api/v' . $apiRequest['version'] . '/Generic/' . $actionCamelName . '.php',
    );
    foreach ($genericFiles as $genericFile) {
      if (\CRM_Utils_File::isIncludable($genericFile)) {
        require_once $genericFile;
        if (function_exists($genericFunction)) {
          $this->cache[$cachekey] = array('function' => $genericFunction, 'is_generic' => TRUE);
          return $this->cache[$cachekey];
        }
      }
    }

    $this->cache[$cachekey] = array('function' => FALSE, 'is_generic' => FALSE);
    return $this->cache[$cachekey];
  }

  /**
   * @param string $entity
   * @param string $action
   * @param $version
   *
   * @return string
   */
  protected function getFunctionName($entity, $action, $version) {
    $entity = _civicrm_api_get_entity_name_from_camel($entity);
    return 'civicrm_api' . $version . '_' . $entity . '_' . $action;
  }

  /**
   * Load/require all files related to an entity.
   *
   * This should not normally be called because it's does a file-system scan; it's
   * only appropriate when introspection is really required (eg for "getActions").
   *
   * @param string $entity
   * @param int $version
   *
   * @return void
   */
  protected function loadEntity($entity, $version) {
    $camelName = _civicrm_api_get_camel_name($entity, $version);

    // Check for master entity file; to match _civicrm_api_resolve(), only load the first one
    $stdFile = 'api/v' . $version . '/' . $camelName . '.php';
    if (\CRM_Utils_File::isIncludable($stdFile)) {
      require_once $stdFile;
    }

    // Check for standalone action files; to match _civicrm_api_resolve(), only load the first one
    $loaded_files = array(); // array($relativeFilePath => TRUE)
    $include_dirs = array_unique(explode(PATH_SEPARATOR, get_include_path()));
    foreach ($include_dirs as $include_dir) {
      foreach (array($camelName, 'Generic') as $name) {
        $action_dir = implode(DIRECTORY_SEPARATOR, array($include_dir, 'api', "v${version}", $name));
        if (!is_dir($action_dir)) {
          continue;
        }

        $iterator = new \DirectoryIterator($action_dir);
        foreach ($iterator as $fileinfo) {
          $file = $fileinfo->getFilename();
          if (array_key_exists($file, $loaded_files)) {
            continue; // action provided by an earlier item on include_path
          }

          $parts = explode(".", $file);
          if (end($parts) == "php" && !preg_match('/Tests?\.php$/', $file)) {
            require_once $action_dir . DIRECTORY_SEPARATOR . $file;
            $loaded_files[$file] = TRUE;
          }
        }
      }
    }
  }

}
