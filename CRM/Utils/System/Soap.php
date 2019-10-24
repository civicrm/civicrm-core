<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * @copyright CiviCRM LLC (c) 2004-2019
 */

/**
 * Soap specific stuff goes here.
 */
class CRM_Utils_System_Soap extends CRM_Utils_System_Base {

  /**
   * UF container variables.
   * @var string
   */
  public static $uf = NULL;
  public static $ufClass = NULL;

  /**
   * Given a permission string, check for access requirements
   *
   * @param string $str
   *   The permission to check.
   *
   * @return bool
   *   true if yes, else false
   */
  public function checkPermission($str) {
    return TRUE;
  }

  /**
   * @inheritDoc
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
   * FIXME: Can this override be removed in favor of the parent?
   * @inheritDoc
   */
  public function postURL($action) {
    return NULL;
  }

  /**
   * Set the email address of the user.
   *
   * @param object $user
   *   Handle to the user object.
   */
  public function setEmail(&$user) {
  }

  /**
   * @inheritDoc
   */
  public function authenticate($name, $pass) {
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
   * Swap the current UF for soap.
   */
  public function swapUF() {
    $config = CRM_Core_Config::singleton();

    self::$uf = $config->userFramework;
    $config->userFramework = 'Soap';

    self::$ufClass = $config->userFrameworkClass;
    $config->userFrameworkClass = 'CRM_Utils_System_Soap';
  }

  /**
   * Get user login URL for hosting CMS (method declared in each CMS system class)
   *
   * @param string $destination
   *
   * @throws Exception
   */
  public function getLoginURL($destination = '') {
    throw new Exception("Method not implemented: getLoginURL");
  }

  public function loadBootStrap($params = [], $loadUser = TRUE, $throwError = TRUE, $realPath = NULL) {
    // It makes zero sense for this class to extend CRM_Utils_System_Base.
    throw new \RuntimeException("Not implemented");
  }

}
