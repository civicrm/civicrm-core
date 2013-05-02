<?php
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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

/**
 * Helper authentication class for unit tests
 */
class CRM_Utils_System_UnitTests extends CRM_Utils_System_Drupal {
  function __construct() {
    $this->is_drupal = FALSE;
    $this->supports_form_extensions = False;
  }

  function setTitle($title, $pageTitle = NULL) {
    return;
  }

  static function authenticate($name, $password, $loadCMSBootstrap = FALSE, $realPath = NULL) {
    $retVal = array(1, 1, 12345);
    return $retVal;
  }

  function appendBreadCrumb($breadCrumbs) {
    return;
  }

  function resetBreadCrumb() {
    return;
  }

  function addHTMLHead($head) {
    return;
  }

  function mapConfigToSSL() {
    global $base_url;
    $base_url = str_replace('http://', 'https://', $base_url);
  }

  function postURL($action) {
    return;
  }

  function url($path = NULL, $query = NULL, $absolute = FALSE,
    $fragment = NULL, $htmlize = TRUE,
    $frontend = FALSE, $forceBackend = FALSE
  ) {
    $config = CRM_Core_Config::singleton();
    static $script = 'index.php';

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

  function getUserID($user) {
    //FIXME: look here a bit closer when testing UFMatch

    // this puts the appropriate values in the session, so
    // no need to return anything
    CRM_Core_BAO_UFMatch::synchronize($user, TRUE, 'Standalone', 'Individual');
  }

  function getAllowedToLogin($user) {
    return TRUE;
  }

  function setMessage($message) {
    return;
  }

  function permissionDenied() {
    CRM_Core_Error::fatal(ts('You do not have permission to access this page'));
  }

  function logout() {
    session_destroy();
    header("Location:index.php");
  }

  function getUFLocale() {
    return NULL;
  }

  function getModules() {
    return array();
  }

  /**
   * Get user login URL for hosting CMS (method declared in each CMS system class)
   *
   * @param string $destination - if present, add destination to querystring (works for Drupal only)
   *
   * @return string - loginURL for the current CMS
   * @static
   */
  public function getLoginURL($destination = '') {
    throw new Exception("Method not implemented: getLoginURL");
  }
  
}

