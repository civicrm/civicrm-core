<?php
// $Id$

define('API_V3_EXTENSION_DELIMITER', ',');

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
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

/**
 * File for the CiviCRM APIv3 extension functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Extension
 *
 * @copyright CiviCRM LLC (c) 2004-2013
 * @version $Id$
 *
 */

/**
 * Install an extension
 *
 * @param  array       $params input parameters
 *                          - key: string, eg "com.example.myextension"
 *                          - keys: mixed; array of string, eg array("com.example.myextension1", "com.example.myextension2") or string with comma-delimited list
 *                            using 'keys' should be more performant than making multiple API calls with 'key'
 *
 * @return array API result
 * @static void
 * @access public
 * @example ExtensionInstall.php
 *
 */
function civicrm_api3_extension_install($params) {
  $keys = _civicrm_api3_getKeys($params);
  if (count($keys) == 0) {
    return civicrm_api3_create_success();
  }

  try {
    CRM_Extension_System::singleton()->getManager()->install($keys);
  } catch (CRM_Extension_Exception $e) {
    return civicrm_api3_create_error($e->getMessage());
  }

  return civicrm_api3_create_success();
}

/**
 * Enable an extension
 *
 * @param  array       $params input parameters
 *                          - key: string, eg "com.example.myextension"
 *                          - keys: mixed; array of string, eg array("com.example.myextension1", "com.example.myextension2") or string with comma-delimited list
 *                            using 'keys' should be more performant than making multiple API calls with 'key'
 *
 * @return array API result
 * @static void
 * @access public
 * @example ExtensionEnable.php
 *
 */
function civicrm_api3_extension_enable($params) {
  $keys = _civicrm_api3_getKeys($params);
  if (count($keys) == 0) {
    return civicrm_api3_create_success();
  }

  CRM_Extension_System::singleton()->getManager()->enable($keys);
  return civicrm_api3_create_success();
}

/**
 * Disable an extension
 *
 * @param  array       $params input parameters
 *                          - key: string, eg "com.example.myextension"
 *                          - keys: mixed; array of string, eg array("com.example.myextension1", "com.example.myextension2") or string with comma-delimited list
 *                            using 'keys' should be more performant than making multiple API calls with 'key'
 *
 * @return array API result
 * @static void
 * @access public
 * @example ExtensionDisable.php
 *
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
 * Uninstall an extension
 *
 * @param  array       $params input parameters
 *                          - key: string, eg "com.example.myextension"
 *                          - keys: array of string, eg array("com.example.myextension1", "com.example.myextension2")
 *                            using 'keys' should be more performant than making multiple API calls with 'key'
 *                          - removeFiles: bool, whether to remove source tree; default: FALSE
 *
 * @return array API result
 * @static void
 * @access public
 * @example ExtensionUninstall.php
 *
 */
function civicrm_api3_extension_uninstall($params) {
  $keys = _civicrm_api3_getKeys($params);
  if (count($keys) == 0) {
    return civicrm_api3_create_success();
  }

  // TODO // $removeFiles = CRM_Utils_Array::value('removeFiles', $params, FALSE);
  CRM_Extension_System::singleton()->getManager()->uninstall($keys);
  return civicrm_api3_create_success();
}

/**
 * Download and install an extension
 *
 * @param  array       $params input parameters
 *                          - key: string, eg "com.example.myextension"
 *                          - url: string eg "http://repo.com/myextension-1.0.zip"
 *
 * @return array API result
 * @static void
 * @access public
 * @example ExtensionDownload.php
 *
 */
function civicrm_api3_extension_download($params) {
  if (! array_key_exists('key', $params)) {
    throw new API_Exception('Missing required parameter: key');
  }

  if (! array_key_exists('url', $params)) {
    if (! CRM_Extension_System::singleton()->getBrowser()->isEnabled()) {
      throw new API_Exception('Automatic downloading is diabled. Try adding parameter "url"');
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

  if (! array_key_exists('url', $params)) {
    throw new API_Exception('Cannot resolve download url for extension. Try adding parameter "url"');
  }

  foreach (CRM_Extension_System::singleton()->getDownloader()->checkRequirements() as $requirement) {
    return civicrm_api3_create_error($requirement['message']);
  }

  if (! CRM_Extension_System::singleton()->getDownloader()->download($params['key'], $params['url'])) {
    return civicrm_api3_create_error('Download failed - ZIP file is unavailable or malformed');
  }
  CRM_Extension_System::singleton()->getCache()->flush();
  CRM_Extension_System::singleton(TRUE);
  CRM_Extension_System::singleton()->getManager()->install(array($params['key']));

  return civicrm_api3_create_success();
}

/**
 * Download and install an extension
 *
 * @param  array       $params input parameters
 *                          - local: bool, whether to rescan local filesystem (default: TRUE)
 *                          - remote: bool, whether to rescan remote repository (default: TRUE)
 *
 * @return array API result
 * @static void
 * @access public
 * @example ExtensionRefresh.php
 *
 */
function civicrm_api3_extension_refresh($params) {
  $defaults = array('local' => TRUE, 'remote' => TRUE);
  $params = array_merge($defaults, $params);

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
 * Get a list of available extensions
 *
 * @return array API result
 * @static void
 * @access public
 * @example ExtensionGet.php
 *
 */
function civicrm_api3_extension_get($params) {
  $statuses = CRM_Extension_System::singleton()->getManager()->getStatuses();
  $mapper = CRM_Extension_System::singleton()->getMapper();
  $result = array();
  foreach ($statuses as $key => $status) {
    //try {
    //  $info = (array) $mapper->keyToInfo($key);
    //} catch (CRM_Extension_Exception $e) {
      $info = array();
      $info['key'] = $key;
    //}
    $info['status'] = $status;
    $result[] = $info;
  }
  return civicrm_api3_create_success($result);
}

/**
 * Determine the list of extension keys
 *
 * @param array $params API request params with 'key' or 'keys'
 * @return array of extension keys
 * @throws API_Exception
 */
function _civicrm_api3_getKeys($params) {
  if (array_key_exists('keys', $params) && is_array($params['keys'])) {
    return $params['keys'];
  } elseif (array_key_exists('keys', $params) && is_string($params['keys'])) {
    if ($params['keys'] == '') {
      return array();
    } else {
      return explode(API_V3_EXTENSION_DELIMITER, $params['keys']);
    }
  } elseif (array_key_exists('key', $params)) {
    return array($params['key']);
  } else {
    throw new API_Exception('Missing required parameter: key or keys');
  }
}
