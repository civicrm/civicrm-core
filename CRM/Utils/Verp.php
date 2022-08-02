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
 * Class to handle encoding and decoding Variable Enveleope Return Path (VERP)
 * headers.
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Utils_Verp {
  /**
   * Mapping of reserved characters to hex codes
   * @var array
   */
  public static $encodeMap = [
    '+' => '2B',
    '@' => '40',
    ':' => '3A',
    '%' => '25',
    '!' => '21',
    '-' => '2D',
    '[' => '5B',
    ']' => '5D',
  ];

  /**
   * Mapping of hex codes to reserved characters
   * @var array
   */
  public static $decodeMap = [
    '40' => '@',
    '3A' => ':',
    '25' => '%',
    '21' => '!',
    '2D' => '-',
    '5B' => '[',
    '5D' => ']',
    '2B' => '+',
  ];

  /**
   * Encode the sender's address with the VERPed recipient.
   *
   * @param string $sender
   *   The address of the sender.
   * @param string $recipient
   *   The address of the recipient.
   *
   * @return string
   *   The VERP encoded address
   */
  public static function encode($sender, $recipient) {
    preg_match('/(.+)\@([^\@]+)$/', $sender, $match);
    $slocal = $match[1];
    $sdomain = $match[2];

    preg_match('/(.+)\@([^\@]+)$/', $recipient, $match);
    $rlocal = $match[1] ?? '';
    $rdomain = $match[2] ?? '';

    foreach (self::$encodeMap as $char => $code) {
      $rlocal = preg_replace('/' . preg_quote($char) . '/i', "+$code", $rlocal);
      $rdomain = preg_replace('/' . preg_quote($char) . '/i', "+$code", $rdomain);
    }

    return "$slocal-$rlocal=$rdomain@$sdomain";
  }

  /**
   * Decode the address and return the sender and recipient as an array.
   *
   * @param string $address
   *   The address to be decoded.
   *
   * @return array
   *   The tuple ($sender, $recipient)
   */
  public static function &verpdecode($address) {
    preg_match('/^(.+)-([^=]+)=([^\@]+)\@(.+)/', $address, $match);

    $slocal = $match[1];
    $rlocal = $match[2];
    $rdomain = $match[3];
    $sdomain = $match[4];

    foreach (self::$decodeMap as $code => $char) {
      $rlocal = preg_replace("/+$code/i", $char, $rlocal);
      $rdomain = preg_replace("/+$code/i", $char, $rdomain);
    }

    return ["$slocal@$sdomain", "$rlocal@$rdomain"];
  }

}
