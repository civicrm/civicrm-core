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
 * $Id$
 *
 */

/**
 * Helper authentication class for unit tests
 */
class CRM_Utils_System_UnitTests extends CRM_Utils_System_Base {

  /**
   */
  public function __construct() {
    $this->is_drupal = FALSE;
    $this->supports_form_extensions = FALSE;
  }

  /**
   * @param string $name
   * @param string $value
   */
  public function setHttpHeader($name, $value) {
    Civi::$statics[__CLASS__]['header'][] = ("$name: $value");
  }

  /**
   * @inheritDoc
   */
  public function authenticate($name, $password, $loadCMSBootstrap = FALSE, $realPath = NULL) {
    $retVal = [1, 1, 12345];
    return $retVal;
  }

  /**
   * Bootstrap the phony CMS.
   */
  public function loadBootStrap($params = [], $loadUser = TRUE, $throwError = TRUE, $realPath = NULL) {
    return TRUE;
  }

  /**
   * @inheritDoc
   */
  public function mapConfigToSSL() {
    global $base_url;
    $base_url = str_replace('http://', 'https://', $base_url);
  }

  /**
   * @inheritDoc
   */
  public function postURL($action) {
    return NULL;
  }

  /**
   * @inheritDoc
   */
  public function url(
    $path = NULL,
    $query = NULL,
    $absolute = FALSE,
    $fragment = NULL,
    $htmlize = TRUE,
    $frontend = FALSE,
    $forceBackend = FALSE
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
   * @inheritDoc
   */
  public function logout() {
    session_destroy();
    CRM_Utils_System::setHttpHeader("Location", "index.php");
  }

  /**
   * @inheritDoc
   */
  public function getLoginURL($destination = '') {
    throw new Exception("Method not implemented: getLoginURL");
  }

}
