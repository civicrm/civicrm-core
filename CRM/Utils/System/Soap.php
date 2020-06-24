<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
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
