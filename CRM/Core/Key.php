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
class CRM_Core_Key {
  public static $_key = NULL;

  public static $_sessionID = NULL;

  /**
   * Generate a private key per session and store in session.
   *
   * @return string
   *   private key for this session
   */
  public static function privateKey() {
    if (!self::$_key) {
      $session = CRM_Core_Session::singleton();
      self::$_key = $session->get('qfPrivateKey');
      if (!self::$_key) {
        self::$_key = md5(uniqid(mt_rand(), TRUE)) . md5(uniqid(mt_rand(), TRUE));
        $session->set('qfPrivateKey', self::$_key);
      }
    }
    return self::$_key;
  }

  /**
   * @return mixed|null|string
   */
  public static function sessionID() {
    if (!self::$_sessionID) {
      $session = CRM_Core_Session::singleton();
      self::$_sessionID = $session->get('qfSessionID');
      if (!self::$_sessionID) {
        self::$_sessionID = session_id();
        $session->set('qfSessionID', self::$_sessionID);
      }
    }
    return self::$_sessionID;
  }

  /**
   * Generate a form key based on form name, the current user session
   * and a private key. Modelled after drupal's form API
   *
   * @param string $name
   * @param bool $addSequence
   *   Should we add a unique sequence number to the end of the key.
   *
   * @return string
   *   valid formID
   */
  public static function get($name, $addSequence = FALSE) {
    $privateKey = self::privateKey();
    $sessionID = self::sessionID();
    $key = md5($sessionID . $name . $privateKey);

    if ($addSequence) {
      // now generate a random number between 1 and 100K and add it to the key
      // so that we can have forms in mutiple tabs etc
      $key = $key . '_' . mt_rand(1, 10000);
    }
    return $key;
  }

  /**
   * Validate a form key based on the form name.
   *
   * @param string $key
   * @param string $name
   * @param bool $addSequence
   *
   * @return string
   *   if valid, else null
   */
  public static function validate($key, $name, $addSequence = FALSE) {
    if (!is_string($key)) {
      return NULL;
    }

    if ($addSequence) {
      list($k, $t) = explode('_', $key);
      if ($t < 1 || $t > 10000) {
        return NULL;
      }
    }
    else {
      $k = $key;
    }

    $privateKey = self::privateKey();
    $sessionID = self::sessionID();
    if ($k != md5($sessionID . $name . $privateKey)) {
      return NULL;
    }
    return $key;
  }

  /**
   * The original version of this function, added circa 2010 and untouched
   * since then, seemed intended to check for a 32-digit hex string followed
   * optionally by an underscore and 4-digit number. But it had a bug where
   * the optional part was never checked ever. So have decided to remove that
   * second check to keep it simple since it seems like pseudo-security.
   *
   * @param string $key
   *
   * @return bool
   */
  public static function valid($key) {
    // ensure that key contains a 32 digit hex string
    return (bool) preg_match('#[0-9a-f]{32}#i', $key);
  }

}
