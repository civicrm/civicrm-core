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

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

/**
 * Drupal specific stuff goes here
 */
abstract class CRM_Utils_System_DrupalBase extends CRM_Utils_System_Base {
  function __construct() {
    $this->is_drupal = TRUE;
    $this->supports_form_extensions = TRUE;
  }

  /**
   * @param string dir base civicrm directory
   * Return default Site Settings
   * @return array array
   * - $url, (Joomla - non admin url)
   * - $siteName,
   * - $siteRoot
   */
  function getDefaultSiteSettings($dir){
    $config = CRM_Core_Config::singleton();
    $siteName = $siteRoot = NULL;
    $matches = array();
    if (preg_match(
      '|/sites/([\w\.\-\_]+)/|',
      $config->templateCompileDir,
      $matches
    )) {
      $siteName = $matches[1];
      if ($siteName) {
        $siteName = "/sites/$siteName/";
        $siteNamePos = strpos($dir, $siteName);
        if ($siteNamePos !== FALSE) {
          $siteRoot = substr($dir, 0, $siteNamePos);
        }
      }
    }
    $url = $config->userFrameworkBaseURL;
    return array($url, $siteName, $siteRoot);
  }

  /**
   * Check if a resource url is within the drupal directory and format appropriately
   *
   * @param url (reference)
   *
   * @return bool: TRUE for internal paths, FALSE for external. The drupal_add_js fn is able to add js more
   * efficiently if it is known to be in the drupal site
   */
  function formatResourceUrl(&$url) {
    $internal = FALSE;
    $base = CRM_Core_Config::singleton()->resourceBase;
    global $base_url;
    // Handle absolute urls
    // compares $url (which is some unknown/untrusted value from a third-party dev) to the CMS's base url (which is independent of civi's url)
    // to see if the url is within our drupal dir, if it is we are able to treated it as an internal url
    if (strpos($url, $base_url) === 0) {
      $internal = TRUE;
      $url = trim(str_replace($base_url, '', $url), '/');
    }
    // Handle relative urls that are within the CiviCRM module directory
    elseif (strpos($url, $base) === 0) {
      $internal = TRUE;
      $url = $this->appendCoreDirectoryToResourceBase(substr(drupal_get_path('module', 'civicrm'), 0, -6)) . trim(substr($url, strlen($base)), '/');
    }
    // Strip query string
    $q = strpos($url, '?');
    if ($q && $internal) {
      $url = substr($url, 0, $q);
    }
    return $internal;
  }

  /**
   * In instance where civicrm folder has a drupal folder & a civicrm core folder @ the same level append the
   * civicrm folder name to the url
   * See CRM-13737 for discussion of how this allows implementers to alter the folder structure
   * @todo - this only provides a limited amount of flexiblity - it still expects a 'civicrm' folder with a 'drupal' folder
   * and is only flexible as to the name of the civicrm folder.
   *
   * @param string $url potential resource url based on standard folder assumptions
   * @return string $url with civicrm-core directory appended if not standard civi dir
   */
  function appendCoreDirectoryToResourceBase($url) {
    global $civicrm_root;
    $lastDirectory = basename($civicrm_root);
    if($lastDirectory != 'civicrm') {
      return $url .= $lastDirectory . '/';
    }
    return $url;
  }
}
