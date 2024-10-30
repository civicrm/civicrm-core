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

use Civi\Core\Service\AutoService;
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
 *        TOKEN := DLM + FMT + QUERY
 *        DLM := ASCII char #2
 *        FMT := String, 4-digit, alphanumeric (as in "CTK?")
 *        QUERY := String, URL-encoded key-value pairs,
 *           "k", the key ID (alphanumeric and symbols "_-.,:;=+/\")
 *           "t", the text (base64-encoded ciphertext)
 *
 * @package Civi\Crypto
 * @service crypto.token
 */
class CryptoToken extends AutoService {

  /**
   * Format identification code
   */
  const FMT_QUERY = 'CTK?';

  /**
   * @var string
   */
  protected $delim;

  /**
   * @var \Civi\Crypto\CryptoRegistry|null
   */
  private $registry;

  /**
   * CryptoToken constructor.
   *
   * @param CryptoRegistry $registry
   */
  public function __construct($registry = NULL) {
    $this->delim = chr(2);
    $this->registry = $registry;
  }

  /**
   * Determine if a string looks like plain-text.
   *
   * @param string $plainText
   * @return bool
   */
  public function isPlainText($plainText) {
    return is_string($plainText) && ($plainText === '' || $plainText[0] !== $this->delim);
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
    $registry = $this->getRegistry();

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

    return $this->delim . self::FMT_QUERY . \http_build_query([
      'k' => $key['id'],
      't' => \CRM_Utils_String::base64UrlEncode($cipherText),
    ]);
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
    $registry = $this->getRegistry();

    $tokenData = $this->parse($token);

    $key = $registry->findKey($tokenData['k']);
    if (!in_array('*', $keyIdOrTag) && !in_array($tokenData['k'], $keyIdOrTag) && empty(array_intersect($keyIdOrTag, $key['tags']))) {
      throw new CryptoException("Cannot decrypt token. Unexpected key: {$tokenData['k']}");
    }

    /** @var \Civi\Crypto\CipherSuiteInterface $cipherSuite */
    $cipherSuite = $registry->findSuite($key['suite']);
    $plainText = $cipherSuite->decrypt($tokenData['t'], $key);
    return $plainText;
  }

  /**
   * Re-encrypt an existing token with a newer version of the key.
   *
   * @param string $oldToken
   * @param string $keyTag
   *   Ex: 'CRED'
   *
   * @return string|null
   *   A re-encrypted version of $oldToken, or NULL if there should be no change.
   * @throws \Civi\Crypto\Exception\CryptoException
   */
  public function rekey($oldToken, $keyTag) {
    /** @var \Civi\Crypto\CryptoRegistry $registry */
    $registry = $this->getRegistry();

    $sourceKeys = $registry->findKeysByTag($keyTag);
    $targetKey = array_shift($sourceKeys);

    if ($this->isPlainText($oldToken)) {
      if ($targetKey['suite'] === 'plain') {
        return NULL;
      }
    }
    else {
      $tokenData = $this->parse($oldToken);
      if ($tokenData['k'] === $targetKey['id'] || !isset($sourceKeys[$tokenData['k']])) {
        return NULL;
      }
    }

    $decrypted = $this->decrypt($oldToken);
    return $this->encrypt($decrypted, $targetKey['id']);
  }

  /**
   * Parse the content of a token (without decrypting it).
   *
   * @param string $token
   *
   * @return array
   * @throws \Civi\Crypto\Exception\CryptoException
   */
  public function parse($token): array {
    $fmt = substr($token, 1, 4);
    switch ($fmt) {
      case self::FMT_QUERY:
        $tokenData = [];
        parse_str(substr($token, 5), $tokenData);
        $tokenData['t'] = \CRM_Utils_String::base64UrlDecode($tokenData['t']);
        break;

      default:
        throw new CryptoException("Cannot decrypt token. Invalid format.");
    }
    return $tokenData;
  }

  /**
   * @return CryptoRegistry
   */
  protected function getRegistry(): CryptoRegistry {
    if ($this->registry === NULL) {
      $this->registry = \Civi::service('crypto.registry');
    }
    return $this->registry;
  }

}
