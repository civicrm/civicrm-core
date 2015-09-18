<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * @copyright CiviCRM LLC (c) 2004-2015
 * $Id$
 *
 */
class CRM_Core_Key {
  static $_key = NULL;

  static $_sessionID = NULL;

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
   * @param $key
   *
   * @return bool
   */
  public static function valid($key) {
    // a valid key is a 32 digit hex number
    // followed by an optional _ and a number between 1 and 10000
    if (strpos('_', $key) !== FALSE) {
      list($hash, $seq) = explode('_', $key);

      // ensure seq is between 1 and 10000
      if (!is_numeric($seq) ||
        $seq < 1 ||
        $seq > 10000
      ) {
        return FALSE;
      }
    }
    else {
      $hash = $key;
    }

    // ensure that hash is a 32 digit hex number
    return preg_match('#[0-9a-f]{32}#i', $hash) ? TRUE : FALSE;
  }

}
