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
 */

/**
 * A utility which signs and verifies a list of key-value pairs
 *
 * FIXME: Add TTL support?
 *
 * @code
 * $signer = new CRM_Utils_Signer('myprivatekey', array('param1','param2'));
 * $params = array(
 *   'param1' => 'hello',
 *   'param2' => 'world',
 * );
 * $token = $signer->sign($params);
 * ...
 * assertTrue($signer->validate($token, $params));
 * @endcode
 */
class CRM_Utils_Signer {
  /**
   * Expected length of the salt
   *
   * @var int
   */
  const SALT_LEN = 4;

  /**
   * Instantiate a signature-processor
   *
   * @param string $secret
   *   private.
   * @param array $paramNames
   *   Array, fields which should be part of the signature.
   */
  public function __construct($secret, $paramNames) {
    sort($paramNames); // ensure consistent serialization of payloads
    $this->secret = $secret;
    $this->paramNames = $paramNames;
    $this->signDelim = "_"; // chosen to be valid in URLs but not in salt or md5
    $this->defaultSalt = CRM_Utils_String::createRandom(self::SALT_LEN, CRM_Utils_String::ALPHANUMERIC);
  }

  /**
   * Generate a signature for a set of key-value pairs
   *
   * @param array $params
   *   Array, key-value pairs.
   * @param string $salt
   *   the salt (if known) or NULL (for auto-generated).
   * @return string, the full public token representing the signature
   */
  public function sign($params, $salt = NULL) {
    $message = array();
    $message['secret'] = $this->secret;
    $message['payload'] = array();
    if (empty($salt)) {
      $message['salt'] = $this->createSalt();
    }
    else {
      $message['salt'] = $salt;
    }
    // recall: paramNames is pre-sorted for stability
    foreach ($this->paramNames as $paramName) {
      if (isset($params[$paramName])) {
        if (is_numeric($params[$paramName])) {
          $params[$paramName] = (string) $params[$paramName];
        }
      }
      else {
        // $paramName is not included or ===NULL
        $params[$paramName] = '';
      }
      $message['payload'][$paramName] = $params[$paramName];
    }
    $token = $message['salt'] . $this->signDelim . md5(serialize($message));
    return $token;
  }

  /**
   * Determine whether a token represents a proper signature for $params
   *
   * @param string $token
   *   the full public token representing the signature.
   * @param array $params
   *   Array, key-value pairs.
   *
   * @throws Exception
   * @return bool, TRUE iff all $paramNames for the submitted validate($params) and the original sign($params)
   */
  public function validate($token, $params) {
    list ($salt, $signature) = explode($this->signDelim, $token);
    if (strlen($salt) != self::SALT_LEN) {
      throw new Exception("Token contains invalid salt [" . urlencode($token) . "]");
    }
    $newToken = $this->sign($params, $salt);
    return ($token == $newToken);
  }

  /**
   * @return string
   */
  public function createSalt() {
    // It would be more secure to generate a new value but liable to run this
    // many times on certain admin pages; so instead we'll re-use the hash.
    return $this->defaultSalt;
  }

}
