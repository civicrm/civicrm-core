<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2017                                |
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

define('API_V3_EXTENSION_DELIMITER', ',');


/**
 * This provides an api interface for CiviCRM extension management.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Install an extension.
 *
 * @param array $params
 *   Input parameters.
 *    - key: string, eg "com.example.myextension"
 *    - keys: array of string, eg array("com.example.myextension1", "com.example.myextension2")
 *    - path: string, e.g. "/var/www/extensions/*"
 *
 * Using 'keys' should be more performant than making multiple API calls with 'key'
 *
 * @return array
 */
function civicrm_api3_extension_install($params) {
  $keys = _civicrm_api3_getKeys($params);
  if (!$keys) {
    return civicrm_api3_create_success();
  }

  try {
    $manager = CRM_Extension_System::singleton()->getManager();
    $manager->install($manager->findInstallRequirements($keys));
  }
  catch (CRM_Extension_Exception $e) {
    return civicrm_api3_create_error($e->getMessage());
  }

  return civicrm_api3_create_success();
}

/**
 * Spec function for getfields
 * @param $fields
 */
function _civicrm_api3_extension_install_spec(&$fields) {
  $fields['keys'] = array(
    'title' => 'Extension Key(s)',
    'api.aliases' => array('key'),
    'type' => CRM_Utils_Type::T_STRING,
    'description' => 'Fully qualified name of one or more extensions',
  );
  $fields['path'] = array(
    'title' => 'Extension Path',
    'type' => CRM_Utils_Type::T_STRING,
    'description' => 'The path to the extension. May use wildcard ("*").',
  );
}

/**
 * Upgrade an extension - runs upgrade_N hooks and system.flush.
 *
 * @return array
 *   API result
 */
function civicrm_api3_extension_upgrade() {
  CRM_Core_Invoke::rebuildMenuAndCaches(TRUE);
  $queue = CRM_Extension_Upgrades::createQueue();
  $runner = new CRM_Queue_Runner(array(
    'title' => 'Extension Upgrades',
    'queue' => $queue,
    'errorMode' => CRM_Queue_Runner::ERROR_ABORT,
  ));

  try {
    $result = $runner->runAll();
  }
  catch (CRM_Extension_Exception $e) {
    return civicrm_api3_create_error($e->getMessage());
  }

  if ($result === TRUE) {
    return civicrm_api3_create_success();
  }
  else {
    return $result;
  }
}

/**
 * Enable an extension.
 *
 * @param array $params
 *   Input parameters.
 *    - key: string, eg "com.example.myextension"
 *    - keys: array of string, eg array("com.example.myextension1", "com.example.myextension2")
 *    - path: string, e.g. "/var/www/vendor/foo/myext" or "/var/www/vendor/*"
 *
 * Using 'keys' should be more performant than making multiple API calls with 'key'
 *
 * @return array
 */
function civicrm_api3_extension_enable($params) {
  $keys = _civicrm_api3_getKeys($params);
  if (count($keys) == 0) {
    return civicrm_api3_create_success();
  }

  $manager = CRM_Extension_System::singleton()->getManager();
  $manager->enable($manager->findInstallRequirements($keys));
  return civicrm_api3_create_success();
}

/**
 * Spec function for getfields
 * @param $fields
 */
function _civicrm_api3_extension_enable_spec(&$fields) {
  _civicrm_api3_extension_install_spec($fields);
}

/**
 * Disable an extension.
 *
 * @param array $params
 *   Input parameters.
 *    - key: string, eg "com.example.myextension"
 *    - keys: array of string, eg array("com.example.myextension1", "com.example.myextension2")
 *    - path: string, e.g. "/var/www/vendor/foo/myext" or "/var/www/vendor/*"
 *
 * Using 'keys' should be more performant than making multiple API calls with 'key'
 *
 * @return array
 */
function civicrm_api3_extension_disable($params) {
  $keys = _civicrm_api3_getKeys($params);
  if (count($keys) == 0) {
    return civicrm_api3_create_success();
  }

  CRM_Extension_System::singleton()->getManager()->disable($keys);
  return civicrm_api3_create_success();
}

/**
 * Spec function for getfields
 * @param $fields
 */
function _civicrm_api3_extension_disable_spec(&$fields) {
  _civicrm_api3_extension_install_spec($fields);
}

/**
 * Uninstall an extension.
 *
 * @param array $params
 *   Input parameters.
 *    - key: string, eg "com.example.myextension"
 *    - keys: array of string, eg array("com.example.myextension1", "com.example.myextension2")
 *    - path: string, e.g. "/var/www/vendor/foo/myext" or "/var/www/vendor/*"
 *
 * Using 'keys' should be more performant than making multiple API calls with 'key'
 *
 * @todo: removeFiles as optional param
 *
 * @return array
 */
function civicrm_api3_extension_uninstall($params) {
  $keys = _civicrm_api3_getKeys($params);
  if (count($keys) == 0) {
    return civicrm_api3_create_success();
  }

  CRM_Extension_System::singleton()->getManager()->uninstall($keys);
  return civicrm_api3_create_success();
}

/**
 * Spec function for getfields
 * @param $fields
 */
function _civicrm_api3_extension_uninstall_spec(&$fields) {
  _civicrm_api3_extension_install_spec($fields);
  //$fields['removeFiles'] = array(
  //  'title' => 'Remove files',
  //  'description' => 'Whether to remove the source tree. Default FALSE.',
  //  'type' => CRM_Utils_Type::T_BOOLEAN,
  //);
}

/**
 * Download and install an extension.
 *
 * @param array $params
 *   Input parameters.
 *   - key: string, eg "com.example.myextension"
 *   - url: string eg "http://repo.com/myextension-1.0.zip"
 *
 * @throws API_Exception
 * @return array
 *   API result
 */
function civicrm_api3_extension_download($params) {
  if (!array_key_exists('url', $params)) {
    if (!CRM_Extension_System::singleton()->getBrowser()->isEnabled()) {
      throw new API_Exception('Automatic downloading is disabled. Try adding parameter "url"');
    }
    if ($reqs = CRM_Extension_System::singleton()->getBrowser()->checkRequirements()) {
      $first = array_shift($reqs);
      throw new API_Exception($first['message']);
    }
    if ($info = CRM_Extension_System::singleton()->getBrowser()->getExtension($params['key'])) {
      if ($info->downloadUrl) {
        $params['url'] = $info->downloadUrl;
      }
    }
  }

  if (!array_key_exists('url', $params)) {
    throw new API_Exception('Cannot resolve download url for extension. Try adding parameter "url"');
  }

  foreach (CRM_Extension_System::singleton()->getDownloader()->checkRequirements() as $requirement) {
    return civicrm_api3_create_error($requirement['message']);
  }

  if (!CRM_Extension_System::singleton()->getDownloader()->download($params['key'], $params['url'])) {
    return civicrm_api3_create_error('Download failed - ZIP file is unavailable or malformed');
  }
  CRM_Extension_System::singleton()->getCache()->flush();
  CRM_Extension_System::singleton(TRUE);
  if (CRM_Utils_Array::value('install', $params, TRUE)) {
    CRM_Extension_System::singleton()->getManager()->install(array($params['key']));
  }

  return civicrm_api3_create_success();
}

/**
 * Spec function for getfields
 * @param $fields
 */
function _civicrm_api3_extension_download_spec(&$fields) {
  $fields['key'] = array(
    'title' => 'Extension Key',
    'api.required' => 1,
    'type' => CRM_Utils_Type::T_STRING,
    'description' => 'Fully qualified name of the extension',
  );
  $fields['url'] = array(
    'title' => 'Download URL',
    'type' => CRM_Utils_Type::T_STRING,
    'description' => 'Optional as the system can determine the url automatically for public extensions',
  );
  $fields['install'] = array(
    'title' => 'Auto-install',
    'type' => CRM_Utils_Type::T_STRING,
    'description' => 'Automatically install the downloaded extension',
    'api.default' => TRUE,
  );
}

/**
 * Download and install an extension.
 *
 * @param array $params
 *   Input parameters.
 *   - local: bool, whether to rescan local filesystem (default: TRUE)
 *   - remote: bool, whether to rescan remote repository (default: TRUE)
 *
 * @return array
 *   API result
 */
function civicrm_api3_extension_refresh($params) {
  $system = CRM_Extension_System::singleton(TRUE);

  if ($params['local']) {
    $system->getManager()->refresh();
    $system->getManager()->getStatuses(); // force immediate scan
  }

  if ($params['remote']) {
    if ($system->getBrowser()->isEnabled() && empty($system->getBrowser()->checkRequirements)) {
      $system->getBrowser()->refresh();
      $system->getBrowser()->getExtensions(); // force immediate download
    }
  }

  return civicrm_api3_create_success();
}

/**
 * Spec function for getfields
 * @param $fields
 */
function _civicrm_api3_extension_refresh_spec(&$fields) {
  $fields['local'] = array(
    'title' => 'Rescan Local',
    'api.default' => 1,
    'type' => CRM_Utils_Type::T_BOOLEAN,
    'description' => 'Whether to rescan the local filesystem (default TRUE)',
  );
  $fields['remote'] = array(
    'title' => 'Rescan Remote',
    'api.default' => 1,
    'type' => CRM_Utils_Type::T_BOOLEAN,
    'description' => 'Whether to rescan the remote repository (default TRUE)',
  );
}

/**
 * Get a list of available extensions.
 *
 * @param array $params
 *
 * @return array
 *   API result
 */
function civicrm_api3_extension_get($params) {
  $full_names = _civicrm_api3_getKeys($params, 'full_name');
  $keys = _civicrm_api3_getKeys($params, 'key');
  $keys = array_merge($full_names, $keys);
  $statuses = CRM_Extension_System::singleton()->getManager()->getStatuses();
  $mapper = CRM_Extension_System::singleton()->getMapper();
  $result = array();
  $id = 0;
  foreach ($statuses as $key => $status) {
    try {
      $obj = $mapper->keyToInfo($key);
    }
    catch (CRM_Extension_Exception $ex) {
      CRM_Core_Session::setStatus(ts('Failed to read extension (%1). Please refresh the extension list.', array(1 => $key)));
      continue;
    }
    $info = CRM_Extension_System::createExtendedInfo($obj);
    $info['id'] = $id++; // backward compatibility with indexing scheme
    if (!empty($keys)) {
      if (in_array($key, $keys)) {
        $result[] = $info;
      }
    }
    else {
      $result[] = $info;
    }
  }

  // These fields have been filtered already, and they have special semantics.
  unset($params['key']);
  unset($params['keys']);
  unset($params['full_name']);

  $filterableFields = array('id', 'type', 'status', 'path');
  return _civicrm_api3_basic_array_get('Extension', $params, $result, 'id', $filterableFields);
}

/**
 * Get a list of remotely available extensions.
 *
 * @param array $params
 *
 * @return array
 *   API result
 */
function civicrm_api3_extension_getremote($params) {
  $extensions = CRM_Extension_System::singleton()->getBrowser()->getExtensions();
  $result = array();
  $id = 0;
  foreach ($extensions as $key => $obj) {
    $info = array();
    $info['id'] = $id++; // backward compatibility with indexing scheme
    $info = array_merge($info, (array) $obj);
    $result[] = $info;
  }
  return _civicrm_api3_basic_array_get('Extension', $params, $result, 'id', CRM_Utils_Array::value('return', $params, array()));
}

/**
 * Determine the list of extension keys.
 *
 * @param array $params
 * @param string $key
 *   API request params with 'keys' or 'path'.
 *   - keys: A comma-delimited list of extension names
 *   - path: An absolute directory path. May append '*' to match all sub-directories.
 *
 * @return array
 */
function _civicrm_api3_getKeys($params, $key = 'keys') {
  if ($key == 'path') {
    return CRM_Extension_System::singleton()->getMapper()->getKeysByPath($params['path']);
  }
  if (isset($params[$key])) {
    if (is_array($params[$key])) {
      return $params[$key];
    }
    if ($params[$key] == '') {
      return array();
    }
    return explode(API_V3_EXTENSION_DELIMITER, $params[$key]);
  }
  else {
    return array();
  }
}
