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
 * Class to handle encoding and decoding Variable Enveleope Return Path (VERP)
 * headers.
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2015
 */
class CRM_Utils_Verp {
  /* Mapping of reserved characters to hex codes */

  static $encodeMap = array(
    '+' => '2B',
    '@' => '40',
    ':' => '3A',
    '%' => '25',
    '!' => '21',
    '-' => '2D',
    '[' => '5B',
    ']' => '5D',
  );

  /* Mapping of hex codes to reserved characters */

  static $decodeMap = array(
    '40' => '@',
    '3A' => ':',
    '25' => '%',
    '21' => '!',
    '2D' => '-',
    '5B' => '[',
    '5D' => ']',
    '2B' => '+',
  );

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
    $rlocal = CRM_Utils_Array::value(1, $match);
    $rdomain = CRM_Utils_Array::value(2, $match);

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

    return array("$slocal@$sdomain", "$rlocal@$rdomain");
  }

}
