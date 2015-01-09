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
 * Soap specific stuff goes here
 */
class CRM_Utils_System_Soap extends CRM_Utils_System_Base {

  /**
   * UF container variables
   */
  static $uf = NULL;
  static $ufClass = NULL;

  /**
   * Sets the title of the page
   *
   * @param string $title
   *   Title for page.
   * @param $pageTitle
   *
   * @paqram string $pageTitle
   *
   * @return void
   */
  public function setTitle($title, $pageTitle) {
    return;
  }

  /**
   * Given a permission string, check for access requirements
   *
   * @param string $str
   *   The permission to check.
   *
   * @return boolean
   *   true if yes, else false
   * @static
   */
  public function checkPermission($str) {
    return TRUE;
  }

  /**
   * Append an additional breadcrumb tag to the existing breadcrumb
   *
   * @param string $title
   * @param string $url
   *
   * @return void
   */
  public function appendBreadCrumb($title, $url) {
    return;
  }

  /**
   * Append a string to the head of the html file
   *
   * @param string $head
   *   The new string to be appended.
   *
   * @return void
   */
  public function addHTMLHead($head) {
    return;
  }

  /**
   * Generate an internal CiviCRM URL
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
   *
   * @return string
   *   an HTML string containing a link to the given path.
   *
   */
  public function url($path = NULL, $query = NULL, $absolute = TRUE, $fragment = NULL) {
    if (isset(self::$ufClass)) {
      $className = self::$ufClass;
      $url = $className::url($path, $query, $absolute, $fragment);
      return $url;
    }
    else {
      return NULL;
    }
  }

  /**
   * Figure out the post url for the form
   *
   * @param the default action if one is pre-specified
   *
   * @return string
   *   the url to post the form
   */
  public function postURL($action) {
    return NULL;
  }

  /**
   * Set the email address of the user
   *
   * @param object $user
   *   Handle to the user object.
   *
   * @return void
   */
  public function setEmail(&$user) {
  }

  /**
   * Authenticate a user against the real UF
   *
   * @param string $name
   *   Login name.
   * @param string $pass
   *   Login password.
   *
   * @return array
   *   Result array
   */
  public function &authenticate($name, $pass) {
    if (isset(self::$ufClass)) {
      $className = self::$ufClass;
      $result =& $className::authenticate($name, $pass);
      return $result;
    }
    else {
      return NULL;
    }
  }

  /**
   * Swap the current UF for soap
   *
   */
  public function swapUF() {
    $config = CRM_Core_Config::singleton();

    self::$uf = $config->userFramework;
    $config->userFramework = 'Soap';

    self::$ufClass = $config->userFrameworkClass;
    $config->userFrameworkClass = 'CRM_Utils_System_Soap';
  }

  /**
   * Get the locale set in the hosting CMS
   *
   * @return null
   *   as the language is set elsewhere
   */
  public function getUFLocale() {
    return NULL;
  }

  /**
   * Get user login URL for hosting CMS (method declared in each CMS system class)
   *
   * @param string $destination
   *   If present, add destination to querystring (works for Drupal only).
   *
   * @throws Exception
   * @return string
   *   loginURL for the current CMS
   * @static
   */
  public function getLoginURL($destination = '') {
    throw new Exception("Method not implemented: getLoginURL");
  }
}
