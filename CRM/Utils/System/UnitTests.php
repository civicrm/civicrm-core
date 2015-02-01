<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.6                                                |
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
 * Helper authentication class for unit tests
 */
class CRM_Utils_System_UnitTests extends CRM_Utils_System_Drupal {
  /**
   */
  public function __construct() {
    $this->is_drupal = FALSE;
    $this->supports_form_extensions = FALSE;
  }

  /**
   * Sets the title of the page
   *
   * @param string $title
   * @param null $pageTitle
   *
   * @paqram string $pageTitle
   *
   * @return void
   */
  /**
   * @param string $title
   * @param null $pageTitle
   */
  public function setTitle($title, $pageTitle = NULL) {
    return NULL;
  }

  /**
   * Authenticate the user against the drupal db
   *
   * @param string $name
   *   The user name.
   * @param string $password
   *   The password for the above user name.
   * @param bool $loadCMSBootstrap
   *   Load cms bootstrap?.
   * @param NULL|string $realPath filename of script
   *
   * @return array|bool
   *   [contactID, ufID, uniqueString] if success else false if no auth
   */
  /**
   * @param string $name
   * @param string $password
   * @param bool $loadCMSBootstrap
   * @param null|string $realPath
   *
   * @return mixed
   */
  public function authenticate($name, $password, $loadCMSBootstrap = FALSE, $realPath = NULL) {
    $retVal = array(1, 1, 12345);
    return $retVal;
  }

  /**
   * Append an additional breadcrumb tag to the existing breadcrumb
   *
   * @param $breadCrumbs
   *
   * @internal param string $title
   * @internal param string $url
   *
   * @return void
   */
  /**
   * @param $breadCrumbs
   */
  public function appendBreadCrumb($breadCrumbs) {
    return NULL;
  }

  public function resetBreadCrumb() {
    return NULL;
  }

  /**
   * Append a string to the head of the html file
   *
   * @param string $header
   *   The new string to be appended.
   *
   * @return void
   */
  /**
   * @param string $head
   */
  public function addHTMLHead($head) {
    return NULL;
  }

  public function mapConfigToSSL() {
    global $base_url;
    $base_url = str_replace('http://', 'https://', $base_url);
  }

  /**
   * Figure out the post url for the form
   *
   * @param mix $action
   *   The default action if one is pre-specified.
   *
   * @return string
   *   the url to post the form
   */
  /**
   * @param mix $action
   *
   * @return void
   */
  public function postURL($action) {
    return NULL;
  }

  /**
   * Generate an internal CiviCRM URL (copied from DRUPAL/includes/common.inc#url)
   *
   * @param string $path
   *   The path being linked to, such as "civicrm/add".
   * @param string $query
   *   A query string to append to the link.
   * @param bool $absolute
   *   Whether to force the output to be an absolute link (beginning with http:).
   *                           Useful for links that will be displayed outside the site, such as in an
   *                           RSS feed.
   * @param string $fragment
   *   A fragment identifier (named anchor) to append to the link.
   * @param bool $htmlize
   *   whether to convert to html eqivalant.
   * @param bool $frontend
   *   a gross joomla hack.
   * @param bool $forceBackend
   *   a gross joomla hack.
   *
   * @return string
   *   an HTML string containing a link to the given path.
   */
  /**
   * @param null|string $path
   * @param null|string $query
   * @param bool $absolute
   * @param null|string $fragment
   * @param bool $htmlize
   * @param bool $frontend
   * @param bool $forceBackend
   *
   * @return string
   */
  public function url(
    $path = NULL, $query = NULL, $absolute = FALSE,
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

  /**
   * @param $user
   */
  public function getUserID($user) {
    //FIXME: look here a bit closer when testing UFMatch

    // this puts the appropriate values in the session, so
    // no need to return anything
    CRM_Core_BAO_UFMatch::synchronize($user, TRUE, 'Standalone', 'Individual');
  }

  /**
   * @param $user
   *
   * @return bool
   */
  public function getAllowedToLogin($user) {
    return TRUE;
  }

  /**
   * Set a message in the UF to display to a user
   *
   * @param string $message
   *   The message to set.
   */
  /**
   * @param string $message
   */
  public function setMessage($message) {
    return NULL;
  }

  public function permissionDenied() {
    CRM_Core_Error::fatal(ts('You do not have permission to access this page.'));
  }

  public function logout() {
    session_destroy();
    header("Location:index.php");
  }

  /**
   * Get the locale set in the hosting CMS
   *
   * @return string
   *   with the locale or null for none
   */
  /**
   * @return void
   */
  public function getUFLocale() {
    return NULL;
  }

  /**
   * Get a list of all installed modules, including enabled and disabled ones
   *
   * @return array
   *   CRM_Core_Module
   */
  /**
   * @return array
   */
  public function getModules() {
    return array();
  }

  /**
   * Get user login URL for hosting CMS (method declared in each CMS system class)
   *
   * @param string $destination
   *   If present, add destination to querystring (works for Drupal only).
   *
   * @Throws Exception
   *   loginURL for the current CMS
   */
  public function getLoginURL($destination = '') {
    throw new Exception("Method not implemented: getLoginURL");
  }

  /**
   * Over-ridable function to get timezone as a string eg.
   * @return string
   *   Timezone e.g. 'America/Los_Angeles'
   */
  public function getTimeZoneString() {
    // This class extends Drupal, but we don't want Drupal's behavior; reproduce CRM_Utils_System_Base::getTimeZoneString
    return date_default_timezone_get();
  }

  public function clearResourceCache() {
    // UGH. Obscure Drupal-specific implementation. Why does UnitTests extend Drupal?
    // You should delete this function if the base-classes are properly rearranged.
  }

}
