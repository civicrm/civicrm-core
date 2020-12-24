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

/**
 * @package Civi\Crypt
 */
interface CipherSuiteInterface {

  /**
   * Get a list of supported cipher suites.
   *
   * @return array
   *   Ex: ['aes-cbc', 'aes-bbc', 'aes-pbs']
   */
  public function getSuites(): array;

  /**
   * Encrypt a string
   *
   * @param string $plainText
   * @param array $key
   *
   * @return string
   *   Encrypted content as a binary string.
   *   Depending on the suite, this may include related values (eg HMAC + IV).
   */
  public function encrypt(string $plainText, array $key): string;

  /**
   * Decrypt a string
   *
   * @param string $cipherText
   *   Encrypted content as a binary string.
   *   Depending on the suite, this may include related values (eg HMAC + IV).
   * @param array $key
   *
   * @return string
   *   Decrypted string
   */
  public function decrypt(string $cipherText, array $key): string;

}
