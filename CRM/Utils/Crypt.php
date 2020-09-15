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
class CRM_Utils_Crypt {

  /**
   * Encrypts a string using AES256 in ECB mode, if encryption is enabled.
   *
   * After encrypting the string, it is base64 encoded.
   *
   * If encryption is not enabled, either due to CIVICRM_SITE_KEY being
   * undefined or due to unavailability of the mcrypt module, the string is
   * merely base64 encoded and is not encrypted at all.
   *
   * @param string $string
   *   Plaintext to be encrypted.
   * @return string
   *   Base64-encoded ciphertext, or base64-encoded plaintext if encryption is
   *   disabled or unavailable.
   */
  public static function encrypt($string) {
    if (empty($string)) {
      return $string;
    }

    if (function_exists('mcrypt_module_open') &&
      defined('CIVICRM_SITE_KEY')
    ) {
      // phpcs:disable
      $td = mcrypt_module_open(MCRYPT_RIJNDAEL_256, '', MCRYPT_MODE_ECB, '');
      // ECB mode - iv not needed - CRM-8198
      $iv = '00000000000000000000000000000000';
      $ks = mcrypt_enc_get_key_size($td);
      $key = substr(sha1(CIVICRM_SITE_KEY), 0, $ks);

      mcrypt_generic_init($td, $key, $iv);
      $string = mcrypt_generic($td, $string);
      mcrypt_generic_deinit($td);
      mcrypt_module_close($td);
    }
    // phpcs:enable
    return base64_encode($string);
  }

  /**
   * Decrypts ciphertext encrypted with AES256 in ECB mode, if possible.
   *
   * If the mcrypt module is not available or if CIVICRM_SITE_KEY is not set,
   * the provided ciphertext is only base64-decoded, not decrypted.
   *
   * @param string $string
   *   Ciphertext to be decrypted.
   * @return string
   *   Plaintext, or base64-decoded ciphertext if encryption is disabled or
   *   unavailable.
   */
  public static function decrypt($string) {
    if (empty($string)) {
      return $string;
    }

    $string = base64_decode($string);
    if (empty($string)) {
      return $string;
    }

    if (function_exists('mcrypt_module_open') &&
      defined('CIVICRM_SITE_KEY')
    ) {
      // phpcs:disable
      $td = mcrypt_module_open(MCRYPT_RIJNDAEL_256, '', MCRYPT_MODE_ECB, '');
      // ECB mode - iv not needed - CRM-8198
      $iv = '00000000000000000000000000000000';
      $ks = mcrypt_enc_get_key_size($td);
      $key = substr(sha1(CIVICRM_SITE_KEY), 0, $ks);

      mcrypt_generic_init($td, $key, $iv);
      $string = rtrim(mdecrypt_generic($td, $string));
      mcrypt_generic_deinit($td);
      mcrypt_module_close($td);
      // phpcs:enable
    }

    return $string;
  }

}
