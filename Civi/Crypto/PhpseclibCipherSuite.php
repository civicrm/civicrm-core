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
 * This is an implementation of CipherSuiteInterface based on phpseclib 1.x/2.x.
 *
 * It supports multiple ciphers:
 *
 * - aes-cbc: AES-256 w/CBC, no authentication
 * - aes-ctr: AES-256 w/CTR, no authentication
 * - aes-cbc-hs: AES-256 w/CBC, HMAC-SHA256 authentication. Enc+auth use derived keys.
 * - aes-ctr-hs: AES-256 w/CTR, HMAC-SHA256 authentication. Enc+auth use derived keys.
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class PhpseclibCipherSuite implements CipherSuiteInterface {

  /**
   * List of phpseclib "Cipher" objects. These are template objects
   * which may be cloned for use with specific keys.
   *
   * @var array|null
   */
  protected $ciphers = NULL;

  public function __construct() {
    $this->ciphers = [];
    if (class_exists('\phpseclib\Crypt\AES')) {
      // phpseclib v2
      $this->ciphers['aes-cbc'] = new \phpseclib\Crypt\AES(\phpseclib\Crypt\AES::MODE_CBC);
      $this->ciphers['aes-cbc']->setKeyLength(256);
      $this->ciphers['aes-ctr'] = new \phpseclib\Crypt\AES(\phpseclib\Crypt\AES::MODE_CTR);
      $this->ciphers['aes-ctr']->setKeyLength(256);
    }
    elseif (class_exists('Crypt_AES')) {
      // phpseclib v1
      $this->ciphers['aes-cbc'] = new \Crypt_AES(CRYPT_MODE_CBC);
      $this->ciphers['aes-cbc']->setKeyLength(256);
      $this->ciphers['aes-ctr'] = new \Crypt_AES(CRYPT_MODE_CBC);
      $this->ciphers['aes-ctr']->setKeyLength(256);
    }
    else {
      throw new CryptoException("Failed to find phpseclib");
    }
  }

  /**
   * @inheritdoc
   */
  public function getSuites(): array {
    return ['aes-cbc', 'aes-ctr', 'aes-cbc-hs', 'aes-ctr-hs'];
  }

  /**
   * @inheritdoc
   */
  public function encrypt(string $plainText, array $key): string {
    switch ($key['suite']) {
      case 'aes-cbc-hs':
      case 'aes-ctr-hs':
        return $this->encryptThenSign($plainText, substr($key['suite'], 0, -3), 'sha256', $key['key']);

      case 'aes-cbc':
      case 'aes-ctr':
        return $this->encryptOnly($plainText, $key['suite'], $key['key']);
    }
  }

  /**
   * @inheritdoc
   */
  public function decrypt(string $cipherText, array $key): string {
    switch ($key['suite']) {
      case 'aes-cbc-hs':
      case 'aes-ctr-hs':
        return $this->authenticateThenDecrypt($cipherText, substr($key['suite'], 0, -3), 'sha256', $key['key']);

      case 'aes-cbc':
      case 'aes-ctr':
        return $this->decryptOnly($cipherText, $key['suite'], $key['key']);
    }
  }

  /**
   * Given an master key, derive a pair of encryption+authentication keys.
   *
   * @param string $masterKey
   * @return array
   */
  protected function createEncAuthKeys($masterKey) {
    return [
      hash_hmac('sha256', 'enc', $masterKey, TRUE),
      hash_hmac('sha256', 'auth', $masterKey, TRUE),
    ];
  }

  protected function encryptOnly($plainText, $suite, $key) {
    $cipher = $this->createCipher($suite, $key);
    $blockBytes = $cipher->getBlockLength() >> 3;
    $iv = random_bytes($blockBytes);
    $cipher->setIV($iv);
    return $iv . $cipher->encrypt($plainText);
  }

  protected function decryptOnly(string $cipherText, $suite, $key) {
    $cipher = $this->createCipher($suite, $key);
    $blockBytes = $cipher->getBlockLength() >> 3;
    $iv = substr($cipherText, 0, $blockBytes);
    $cipher->setIV($iv);
    return $cipher->decrypt(substr($cipherText, $blockBytes));
  }

  /**
   * @param string $plainText
   * @param string $suite
   *   The encryption algorithms
   *   Ex: aes-cbc, aes-ctr
   * @param string $digest
   *   The authentication algorithm
   *   Ex: sha256
   * @param string $masterKey
   *   Binary representation of the key
   *
   * @return string
   *   The concatenation of IV, ciphertext, signature
   */
  protected function encryptThenSign($plainText, $suite, $digest, $masterKey) {
    list ($encKey, $authKey) = $this->createEncAuthKeys($masterKey);
    $cipher = $this->createCipher($suite, $encKey);
    $blockBytes = $cipher->getBlockLength() >> 3;
    $iv = random_bytes($blockBytes);
    $cipher->setIV($iv);
    $ivText = $iv . $cipher->encrypt($plainText);
    $sig = hash_hmac($digest, $ivText, $authKey, TRUE);
    $this->assertLen($this->getDigestBytes($digest), $sig);
    return $ivText . $sig;
  }

  /**
   * @param string $cipherText
   *   Combined ciphertext (IV + encrypted text + signature)
   * @param string $suite
   *   The encryption algorithms
   *   Ex: aes-cbc, aes-ctr
   * @param string $digest
   *   The authentication algorithm
   *   Ex: sha256
   * @param string $masterKey
   *   Binary representation of the key
   *
   * @return string
   *   Decrypted text
   * @throws CryptoException
   *   Throws an exception if authentication fails.
   */
  protected function authenticateThenDecrypt($cipherText, $suite, $digest, $masterKey) {
    list ($encKey, $authKey) = $this->createEncAuthKeys($masterKey);
    $cipher = $this->createCipher($suite, $encKey);
    $blockBytes = $cipher->getBlockLength() >> 3;
    $digestBytes = $this->getDigestBytes($digest);
    $sigExpect = substr($cipherText, -1 * $digestBytes);
    $sigActual = hash_hmac($digest, substr($cipherText, 0, -1 * $digestBytes), $authKey, TRUE);
    if (!hash_equals($sigActual, $sigExpect)) {
      throw new CryptoException("Failed to decrypt token. Invalid digest.");
    }
    $cipher->setIV(substr($cipherText, 0, $blockBytes));
    return $cipher->decrypt(substr($cipherText, $blockBytes, -1 * $digestBytes));
  }

  /**
   * @param $suite
   * @param $key
   * @return \phpseclib\Crypt\Base|\Crypt_Base
   */
  protected function createCipher($suite, $key) {
    if (!isset($this->ciphers[$suite])) {
      throw new \RuntimeException("Cipher suite does not support " . $suite);
    }

    $cipher = clone $this->ciphers[$suite];
    $this->assertLen($cipher->getKeyLength() >> 3, $key);
    $cipher->setKey($key);
    return $cipher;
  }

  protected function getDigestBytes($digest) {
    if ($digest === 'sha256') {
      return 32;
    }
    throw new \RuntimeException('Unrecognized digest');
  }

  private function assertLen($bytes, $value) {
    if ($bytes != strlen($value)) {
      throw new \InvalidArgumentException("Malformed AES key");
    }
  }

}
