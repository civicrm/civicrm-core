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

namespace Civi\Crypto;

use Civi\Crypto\Exception\CryptoException;

/**
 * The "Crypto Token" service supports a token format suitable for
 * storing specific values in the database with encryption. Characteristics:
 *
 * - Primarily designed to defend confidentiality in case of data-leaks
 *   (SQL injections, lost backups, etc).
 * - NOT appropriate for securing data-transmission. Data-transmission
 *   requires more protections (eg mandatory TTLs + signatures). If you need
 *   that, consider adding a JWT/JWS/JWE implementation.
 * - Data-format allows phase-in/phase-out. If you have a datum that was written
 *   with an old key or with no key, it will still be readable.
 *
 * USAGE: The "encrypt()" and "decrypt()" methods are the primary interfaces.
 *
 *   $encrypted = Civi::service('crypto.token')->encrypt('my-mail-password, 'KEY_ID_OR_TAG');
 *   $decrypted = Civi::service('crypto.token')->decrypt($encrypted, '*');
 *
 * FORMAT: An encoded token may be in either of these formats:
 *
 *   - Plain text: Any string which does not begin with chr(2)
 *   - Encrypted text: A string in the format:
 *        TOKEN := DLM + VERSION + DLM + KEY_ID + DLM + CIPHERTEXT
 *        DLM := ASCII CHAR #2
 *        VERSION := String, 4-digit, alphanumeric (as in "CTK0")
 *        KEY_ID := String, alphanumeric and symbols "_-.,:;=+/\"
 *
 * @package Civi\Crypto
 */
class CryptoToken {

  const VERSION_1 = 'CTK0';

  protected $delim;

  /**
   * CryptoToken constructor.
   */
  public function __construct() {
    $this->delim = chr(2);
  }

  /**
   * Determine if a string looks like plain-text.
   *
   * @param string $plainText
   * @return bool
   */
  public function isPlainText($plainText) {
    return is_string($plainText) && ($plainText === '' || $plainText{0} !== $this->delim);
  }

  /**
   * Create an encrypted token (given the plaintext).
   *
   * @param string $plainText
   *   The secret value to encode (e.g. plain-text password).
   * @param string|string[] $keyIdOrTag
   *   List of key IDs or key tags to check. First available match wins.
   * @return string
   *   A token
   * @throws \Civi\Crypto\Exception\CryptoException
   */
  public function encrypt($plainText, $keyIdOrTag) {
    /** @var CryptoRegistry $registry */
    $registry = \Civi::service('crypto.registry');

    $key = $registry->findKey($keyIdOrTag);
    if ($key['suite'] === 'plain') {
      if (!$this->isPlainText($plainText)) {
        throw new CryptoException("Cannot use plaintext encoding for data with reserved delimiter.");
      }
      return $plainText;
    }

    /** @var \Civi\Crypto\CipherSuiteInterface $cipherSuite */
    $cipherSuite = $registry->findSuite($key['suite']);
    $cipherText = $cipherSuite->encrypt($plainText, $key);
    return $this->delim . self::VERSION_1 . $this->delim . $key['id'] . $this->delim . base64_encode($cipherText);
  }

  /**
   * Get the plaintext (given an encrypted token).
   *
   * @param string $token
   * @param string|string[] $keyIdOrTag
   *   Whitelist of acceptable keys. Wildcard '*' will allow it to use
   *   any/all available means to decode the token.
   * @return string
   * @throws \Civi\Crypto\Exception\CryptoException
   */
  public function decrypt($token, $keyIdOrTag = '*') {
    $keyIdOrTag = (array) $keyIdOrTag;

    if ($this->isPlainText($token)) {
      if (in_array('*', $keyIdOrTag) || in_array('plain', $keyIdOrTag)) {
        return $token;
      }
      else {
        throw new CryptoException("Cannot decrypt token. Unexpected key: plain");
      }
    }

    /** @var CryptoRegistry $registry */
    $registry = \Civi::service('crypto.registry');

    $parts = explode($this->delim, $token, 4);
    if (count($parts) !== 4 || $parts[1] !== self::VERSION_1) {
      throw new CryptoException("Cannot decrypt token. Invalid format.");
    }
    $keyId = $parts[2];
    $cipherText = base64_decode($parts[3]);

    $key = $registry->findKey($keyId);
    if (!in_array('*', $keyIdOrTag) && !in_array($keyId, $keyIdOrTag) && empty(array_intersect($keyIdOrTag, $key['tags']))) {
      throw new CryptoException("Cannot decrypt token. Unexpected key: $keyId");
    }

    /** @var \Civi\Crypto\CipherSuiteInterface $cipherSuite */
    $cipherSuite = $registry->findSuite($key['suite']);
    $plainText = $cipherSuite->decrypt($cipherText, $key);
    return $plainText;
  }

}
