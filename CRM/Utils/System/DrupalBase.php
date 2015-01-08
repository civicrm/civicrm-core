<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */

/**
 * Drupal specific stuff goes here
 */
abstract class CRM_Utils_System_DrupalBase extends CRM_Utils_System_Base {

  /**
   * Does this CMS / UF support a CMS specific logging mechanism?
   * @todo - we should think about offering up logging mechanisms in a way that is also extensible by extensions
   * @var bool
   */
  var $supports_UF_Logging = TRUE;
  /**
   *
   */
  function __construct() {
    /**
     * deprecated property to check if this is a drupal install. The correct method is to have functions on the UF classes for all UF specific
     * functions and leave the codebase oblivious to the type of CMS
     * @deprecated
     * @var bool
     */
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

  /**
   * Generate an internal CiviCRM URL (copied from DRUPAL/includes/common.inc#url)
   *
   * @param $path     string   The path being linked to, such as "civicrm/add"
   * @param $query    string   A query string to append to the link.
   * @param $absolute boolean  Whether to force the output to be an absolute link (beginning with http:).
   *                           Useful for links that will be displayed outside the site, such as in an
   *                           RSS feed.
   * @param $fragment string   A fragment identifier (named anchor) to append to the link.
   * @param $htmlize  boolean  whether to convert to html eqivalant
   * @param $frontend boolean  a gross joomla hack
   * @param $forceBackend boolean  a gross joomla hack
   *
   * @return string an HTML string containing a link to the given path.
   * @access public
   *
   */
  function url($path = NULL, $query = NULL, $absolute = FALSE,
    $fragment = NULL, $htmlize = TRUE,
    $frontend = FALSE, $forceBackend = FALSE
  ) {
    $config = CRM_Core_Config::singleton();
    $script = 'index.php';

    $path = CRM_Utils_String::stripPathChars($path);

    if (isset($fragment)) {
      $fragment = '#' . $fragment;
    }

    if (!isset($config->useFrameworkRelativeBase)) {
      $base = parse_url($config->userFrameworkBaseURL);
      $config->useFrameworkRelativeBase = $base['path'];
    }
    $base = $absolute ? $config->userFrameworkBaseURL : $config->useFrameworkRelativeBase;

    $separator = $htmlize ? '&amp;' : '&';

    if (!$config->cleanURL) {
      if (isset($path)) {
        if (isset($query)) {
          return $base . $script . '?q=' . $path . $separator . $query . $fragment;
        }
        else {
          return $base . $script . '?q=' . $path . $fragment;
        }
      }
      else {
        if (isset($query)) {
          return $base . $script . '?' . $query . $fragment;
        }
        else {
          return $base . $fragment;
        }
      }
    }
    else {
      if (isset($path)) {
        if (isset($query)) {
          return $base . $path . '?' . $query . $fragment;
        }
        else {
          return $base . $path . $fragment;
        }
      }
      else {
        if (isset($query)) {
          return $base . $script . '?' . $query . $fragment;
        }
        else {
          return $base . $fragment;
        }
      }
    }
  }

  /**
   * Get User ID from UserFramework system (Drupal)
   * @param object $user object as described by the CMS
   * @return mixed <NULL, number>
   */
  function getUserIDFromUserObject($user) {
    return !empty($user->uid) ? $user->uid : NULL;
  }

  /**
   * Get Unique Identifier from UserFramework system (CMS)
   * @param object $user object as described by the User Framework
   * @return mixed $uniqueIdentifer Unique identifier from the user Framework system
   *
   */
  function getUniqueIdentifierFromUserObject($user) {
    return empty($user->mail) ? NULL : $user->mail;
  }

  /**
   * Get currently logged in user unique identifier - this tends to be the email address or user name.
   *
   * @return string $userID logged in user unique identifier
   */
  function getLoggedInUniqueIdentifier() {
    global $user;
    return $this->getUniqueIdentifierFromUserObject($user);
  }

  /**
   * Action to take when access is not permitted
   */
  function permissionDenied() {
    drupal_access_denied();
  }

  /**
   * Get Url to view user record
   * @param integer $contactID Contact ID
   *
   * @return string
   */
  function getUserRecordUrl($contactID) {
    $uid = CRM_Core_BAO_UFMatch::getUFId($contactID);
    if (CRM_Core_Session::singleton()->get('userID') == $contactID || CRM_Core_Permission::checkAnyPerm(array('cms:administer users', 'cms:view user account'))) {
      return CRM_Utils_System::url('user/' . $uid);
    };
  }

  /**
   * Is the current user permitted to add a user
   * @return bool
   */
  function checkPermissionAddUser() {
    if (CRM_Core_Permission::check('administer users')) {
      return TRUE;
    }
  }


  /**
   * Log error to CMS
   */
  function logger($message) {
    if (CRM_Core_Config::singleton()->userFrameworkLogging) {
      watchdog('civicrm', $message, NULL, WATCHDOG_DEBUG);
    }
  }

  /**
   * Flush css/js caches
   */
  function clearResourceCache() {
    _drupal_flush_css_js();
  }

  /**
   * Append to coreResourcesList
   */
  function appendCoreResources(&$list) {
    $list[] = 'js/crm.drupal.js';
  }
}
