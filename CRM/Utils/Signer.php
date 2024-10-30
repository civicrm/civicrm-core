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
 * A utility which signs and verifies a list of key-value pairs
 *
 * FIXME: Add TTL support?
 *
 * ```
 * $signer = new CRM_Utils_Signer('myprivatekey', array('param1','param2'));
 * $params = array(
 *   'param1' => 'hello',
 *   'param2' => 'world',
 * );
 * $token = $signer->sign($params);
 * ...
 * assertTrue($signer->validate($token, $params));
 * ```
 */
class CRM_Utils_Signer {
  /**
   * Expected length of the salt
   *
   * @var int
   */
  const SALT_LEN = 4;

  /**
   * @var string
   */
  private $secret;

  /**
   * @var array
   */
  private $paramNames;

  /**
   * @var string
   */
  public $signDelim;

  /**
   * @var string
   */
  private $defaultSalt;

  /**
   * Instantiate a signature-processor
   *
   * @param string $secret
   *   private.
   * @param array $paramNames
   *   Array, fields which should be part of the signature.
   */
  public function __construct($secret, $paramNames) {
    // ensure consistent serialization of payloads
    sort($paramNames);
    $this->secret = $secret;
    $this->paramNames = $paramNames;
    // chosen to be valid in URLs but not in salt or md5
    $this->signDelim = "_";
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
    $message = [];
    $message['secret'] = $this->secret;
    $message['payload'] = [];
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
