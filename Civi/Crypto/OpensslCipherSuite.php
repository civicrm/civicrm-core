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
 * This is an implementation of CipherSuiteInterface based on OpenSSL.
 *
 * It supports multiple ciphers:
 *
 * - aes-cbc: AES-256 w/CBC, no authentication
 * - aes-ctr: AES-256 w/CTR, no authentication
 * - aes-cbc-hs: AES-256 w/CBC, HMAC-SHA256 authentication. Enc+auth use derived keys.
 * - aes-ctr-hs: AES-256 w/CTR, HMAC-SHA256 authentication. Enc+auth use derived keys.
 *
 * @package CRM
 */
class OpensslCipherSuite implements CipherSuiteInterface {

  private const KEY_BYTES = 32;

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
    return match ($key['suite']) {
      'aes-cbc-hs', 'aes-ctr-hs' => $this->encryptThenSign($plainText, substr($key['suite'], 0, -3), 'sha256', $key['key']),
      'aes-cbc', 'aes-ctr' => $this->encryptOnly($plainText, $key['suite'], $key['key']),
      default => throw new \RuntimeException('Cipher suite does not support ' . $key['suite']),
    };
  }

  /**
   * @inheritdoc
   */
  public function decrypt(string $cipherText, array $key): string {
    return match ($key['suite']) {
      'aes-cbc-hs', 'aes-ctr-hs' => $this->authenticateThenDecrypt($cipherText, substr($key['suite'], 0, -3), 'sha256', $key['key']),
      'aes-cbc', 'aes-ctr' => $this->decryptOnly($cipherText, $key['suite'], $key['key']),
      default => throw new \RuntimeException('Cipher suite does not support ' . $key['suite']),
    };
  }

  /**
   * Given a master key, derive a pair of encryption+authentication keys.
   *
   * @param string $masterKey
   * @return array
   */
  protected function createEncAuthKeys($masterKey): array {
    return [
      hash_hmac('sha256', 'enc', $masterKey, TRUE),
      hash_hmac('sha256', 'auth', $masterKey, TRUE),
    ];
  }

  protected function encryptOnly(string $plainText, string $suite, string $key): string {
    $method = $this->getCipherMethod($suite);
    $this->assertLen(self::KEY_BYTES, $key);
    $iv = random_bytes(openssl_cipher_iv_length($method));
    $this->clearOpenSslErrors();
    $encrypted = openssl_encrypt($plainText, $method, $key, OPENSSL_RAW_DATA, $iv);
    if ($encrypted === FALSE) {
      throw new CryptoException('OpenSSL encryption failed' . (($err = openssl_error_string()) ? ": $err" : ''));
    }
    return $iv . $encrypted;
  }

  protected function decryptOnly(string $cipherText, string $suite, string $key): string {
    $method = $this->getCipherMethod($suite);
    $this->assertLen(self::KEY_BYTES, $key);
    $blockBytes = openssl_cipher_iv_length($method);
    $iv = substr($cipherText, 0, $blockBytes);
    $this->clearOpenSslErrors();
    $decrypted = openssl_decrypt(substr($cipherText, $blockBytes), $method, $key, OPENSSL_RAW_DATA, $iv);
    if ($decrypted === FALSE) {
      throw new CryptoException('OpenSSL decryption failed' . (($err = openssl_error_string()) ? ": $err" : ''));
    }
    return $decrypted;
  }

  /**
   * Drain any prior unrelated errors from the OpenSSL error queue.
   */
  private function clearOpenSslErrors(): void {
    while (openssl_error_string() !== FALSE) {
    }
  }

  /**
   * @param string $plainText
   * @param string $suite
   *   The encryption algorithm (Ex: aes-cbc, aes-ctr)
   * @param string $digest
   *   The authentication algorithm (Ex: sha256)
   * @param string $masterKey
   *   Binary representation of the key
   *
   * @return string
   *   The concatenation of IV, ciphertext, signature
   */
  protected function encryptThenSign(string $plainText, string $suite, string $digest, string $masterKey): string {
    [$encKey, $authKey] = $this->createEncAuthKeys($masterKey);
    $ivText = $this->encryptOnly($plainText, $suite, $encKey);
    $sig = hash_hmac($digest, $ivText, $authKey, TRUE);
    $this->assertLen($this->getDigestBytes($digest), $sig);
    return $ivText . $sig;
  }

  /**
   * @param string $cipherText
   *   Combined ciphertext (IV + encrypted text + signature)
   * @param string $suite
   *   The encryption algorithm (Ex: aes-cbc, aes-ctr)
   * @param string $digest
   *   The authentication algorithm (Ex: sha256)
   * @param string $masterKey
   *   Binary representation of the key
   *
   * @return string
   *   Decrypted text
   * @throws CryptoException
   *   Throws an exception if authentication fails.
   */
  protected function authenticateThenDecrypt(string $cipherText, string $suite, string $digest, string $masterKey): string {
    [$encKey, $authKey] = $this->createEncAuthKeys($masterKey);
    $digestBytes = $this->getDigestBytes($digest);
    $sigExpect = substr($cipherText, -1 * $digestBytes);
    $sigActual = hash_hmac($digest, substr($cipherText, 0, -1 * $digestBytes), $authKey, TRUE);
    if (!hash_equals($sigActual, $sigExpect)) {
      throw new CryptoException('Failed to decrypt token. Invalid digest.');
    }
    return $this->decryptOnly(substr($cipherText, 0, -1 * $digestBytes), $suite, $encKey);
  }

  private function getCipherMethod(string $suite): string {
    return match ($suite) {
      'aes-cbc' => 'aes-256-cbc',
      'aes-ctr' => 'aes-256-ctr',
      default => throw new \RuntimeException('Cipher suite does not support ' . $suite),
    };
  }

  protected function getDigestBytes(string $digest): int {
    if ($digest === 'sha256') {
      return 32;
    }
    throw new \RuntimeException('Unrecognized digest');
  }

  private function assertLen(int $bytes, string $value): void {
    if ($bytes !== strlen($value)) {
      throw new \InvalidArgumentException('Malformed AES key');
    }
  }

}
