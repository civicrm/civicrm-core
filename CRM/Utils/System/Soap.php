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
  public function url($path = NULL, $query = NULL, $absolute = TRUE, $fragment = NULL, $frontend = FALSE, $forceBackend = FALSE, $htmlize = TRUE) {
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
    /* Before we do any loading, let's start the session and write to it.
     * We typically call authenticate only when we need to bootstrap the CMS
     * directly via Civi and hence bypass the normal CMS auth and bootstrap
     * process typically done in CLI and cron scripts. See: CRM-12648
     */
    $session = CRM_Core_Session::singleton();
    $session->set('civicrmInitSession', TRUE);

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

  /**
   * @inheritdoc
   */
  public function getCiviSourceStorage():array {
    global $civicrm_root;

    if (!defined('CIVICRM_UF_BASEURL')) {
      throw new RuntimeException('Undefined constant: CIVICRM_UF_BASEURL');
    }

    return [
      'url' => CRM_Utils_File::addTrailingSlash('', '/'),
      'path' => CRM_Utils_File::addTrailingSlash($civicrm_root),
    ];
  }

}
