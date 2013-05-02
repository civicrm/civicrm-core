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
 * Soap specific stuff goes here
 */
class CRM_Utils_System_Soap extends CRM_Utils_System_Base {

  /**
   * UF container variables
   */
  static $uf = NULL;
  static $ufClass = NULL;

  /**
   * sets the title of the page
   *
   * @param string $title title  for page
   * @paqram string $pageTitle
   *
   * @return void
   * @access public
   */
  function setTitle($title, $pageTitle) {
    return;
  }

  /**
   * given a permission string, check for access requirements
   *
   * @param string $str the permission to check
   *
   * @return boolean true if yes, else false
   * @static
   * @access public
   */
  function checkPermission($str) {
    return TRUE;
  }

  /**
   * Append an additional breadcrumb tag to the existing breadcrumb
   *
   * @param string $title
   * @param string $url
   *
   * @return void
   * @access public
   */
  function appendBreadCrumb($title, $url) {
    return;
  }

  /**
   * Append a string to the head of the html file
   *
   * @param string $head the new string to be appended
   *
   * @return void
   * @access public
   */
  function addHTMLHead($head) {
    return;
  }

  /**
   * Generate an internal CiviCRM URL
   *
   * @param $path     string   The path being linked to, such as "civicrm/add"
   * @param $query    string   A query string to append to the link.
   * @param $absolute boolean  Whether to force the output to be an absolute link (beginning with http:).
   *                           Useful for links that will be displayed outside the site, such as in an
   *                           RSS feed.
   * @param $fragment string   A fragment identifier (named anchor) to append to the link.
   *
   * @return string            an HTML string containing a link to the given path.
   * @access public
   *
   */
  function url($path = NULL, $query = NULL, $absolute = TRUE, $fragment = NULL) {
    if (isset(self::$ufClass)) {
      eval('$url = ' . self::$ufClass . '::url($path, $query, $absolute, $fragment);');
      return $url;
    }
    else {
      return NULL;
    }
  }

  /**
   * figure out the post url for the form
   *
   * @param the default action if one is pre-specified
   *
   * @return string the url to post the form
   * @access public
   */
  function postURL($action) {
    return NULL;
  }

  /**
   * Function to set the email address of the user
   *
   * @param object $user handle to the user object
   *
   * @return void
   * @access public
   */
  function setEmail(&$user) {}

  /**
   * Authenticate a user against the real UF
   *
   * @param string $name      Login name
   * @param string $pass      Login password
   *
   * @return array            Result array
   * @access public
   */
  function &authenticate($name, $pass) {
    if (isset(self::$ufClass)) {
      eval('$result =& ' . self::$ufClass . '::authenticate($name, $pass);');
      return $result;
    }
    else {
      return NULL;
    }
  }

  /**
   * Swap the current UF for soap
   *
   * @access public
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
   * @return null  as the language is set elsewhere
   */
  function getUFLocale() {
    return NULL;
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

