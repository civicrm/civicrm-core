<?php

/*
 * This file is part of the civicrm-cxn-rpc package.
 *
 * Copyright (c) CiviCRM LLC <info@civicrm.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this package.
 */

namespace Civi\Cxn\Rpc;

use Civi\Cxn\Rpc\Exception\InvalidMessageException;

class AesHelper {
  /**
   * @return string
   *   A secret, expressed in a series of printable ASCII characters.
   */
  public static function createSecret() {
    return base64_encode(crypt_random_string(Constants::AES_BYTES));
  }

  /**
   * @param $secret
   *   A secret, expressed in a series of printable ASCII characters.
   * @return array
   *   - enc: string, raw encryption key
   *   - auth: string, raw authentication key
   */
  public static function deriveAesKeys($secret) {
    $rawSecret = base64_decode($secret);
    if (Constants::AES_BYTES != strlen($rawSecret)) {
      throw new InvalidMessageException("Failed to derive keys from secret.");
    }

    $result = array(
      'enc' => BinHex::hex2bin(hash_hmac('sha256', 'dearbrutus', $rawSecret)),
      'auth' => BinHex::hex2bin(hash_hmac('sha256', 'thefaultisinourselves', $rawSecret)),
    );
    if (Constants::AES_BYTES != strlen($result['enc']) || Constants::AES_BYTES != strlen($result['auth'])) {
      throw new InvalidMessageException("Failed to derive keys from secret.");
    }
    return $result;
  }

  /**
   * Encrypt $plaintext with $secret, then date and sign the message.
   *
   * @param string $secret
   * @param string $plaintext
   * @return array
   *   Array(string $body, string $signature).
   *   Note that $body begins with an unencrypted envelope (ttl, iv).
   * @throws InvalidMessageException
   */
  public static function encryptThenSign($secret, $plaintext) {
    $iv = crypt_random_string(Constants::AES_BYTES);

    $keys = AesHelper::deriveAesKeys($secret);

    $cipher = new \Crypt_AES(CRYPT_AES_MODE_CBC);
    $cipher->setKeyLength(Constants::AES_BYTES);
    $cipher->setKey($keys['enc']);
    $cipher->setIV($iv);

    // JSON string; this will be signed but not encrypted
    $jsonEnvelope = json_encode(array(
      'ttl' => Time::getTime() + Constants::REQUEST_TTL,
      'iv' => BinHex::bin2hex($iv),
    ));
    // JSON string; this will be signed and encrypted
    $jsonEncrypted = $cipher->encrypt($plaintext);
    $body = $jsonEnvelope . Constants::PROTOCOL_DELIM . $jsonEncrypted;
    $signature = hash_hmac('sha256', $body, $keys['auth']);
    return array($body, $signature);
  }

  /**
   * Validate the signature and date of the message, then
   * decrypt it.
   *
   * @param string $secret
   * @param string $body
   * @param string $signature
   * @return string
   *   Plain text.
   * @throws InvalidMessageException
   */
  public static function authenticateThenDecrypt($secret, $body, $signature) {
    $keys = self::deriveAesKeys($secret);

    $localHmac = hash_hmac('sha256', $body, $keys['auth']);
    if (!self::hash_compare($signature, $localHmac)) {
      throw new InvalidMessageException("Incorrect hash");
    }

    list ($jsonEnvelope, $jsonEncrypted) = explode(Constants::PROTOCOL_DELIM, $body, 2);
    if (strlen($jsonEnvelope) > Constants::MAX_ENVELOPE_BYTES) {
      throw new InvalidMessageException("Oversized envelope");
    }

    $envelope = json_decode($jsonEnvelope, TRUE);
    if (!$envelope) {
      throw new InvalidMessageException("Malformed envelope");
    }

    if (!is_numeric($envelope['ttl']) || Time::getTime() > $envelope['ttl']) {
      throw new InvalidMessageException("Invalid TTL");
    }

    if (!is_string($envelope['iv']) || strlen($envelope['iv']) !== Constants::AES_BYTES * 2 || !preg_match('/^[a-f0-9]+$/', $envelope['iv'])) {
      // AES_BYTES (32) ==> bin2hex ==> 2 hex digits (4-bit) per byte (8-bit)
      throw new InvalidMessageException("Malformed initialization vector");
    }

    $jsonPlaintext = UserError::adapt('Civi\Cxn\Rpc\Exception\InvalidMessageException', function () use ($jsonEncrypted, $envelope, $keys) {
      $cipher = new \Crypt_AES(CRYPT_AES_MODE_CBC);
      $cipher->setKeyLength(Constants::AES_BYTES);
      $cipher->setKey($keys['enc']);
      $cipher->setIV(BinHex::hex2bin($envelope['iv']));
      return $cipher->decrypt($jsonEncrypted);
    });
    return $jsonPlaintext;
  }

  /**
   * Comparison function which resists timing attacks.
   *
   * @param string $a
   * @param string $b
   * @return bool
   */
  private static function hash_compare($a, $b) {
    if (!is_string($a) || !is_string($b)) {
      return FALSE;
    }

    $len = strlen($a);
    if ($len !== strlen($b)) {
      return FALSE;
    }

    $status = 0;
    for ($i = 0; $i < $len; $i++) {
      $status |= ord($a[$i]) ^ ord($b[$i]);
    }
    return $status === 0;
  }

}
