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
class BinHex {

  private static $emulate = NULL;

  public static function hex2bin($str) {
    // http://php.net/hex2bin -- PHP 5.4+
    if (self::$emulate === NULL) {
      self::$emulate = !function_exists('hex2bin');
    }

    if (self::$emulate) {
      $sbin = "";
      $len = strlen($str);
      for ($i = 0; $i < $len; $i += 2) {
        $sbin .= pack("H*", substr($str, $i, 2));
      }
      return $sbin;
    }
    else {
      return hex2bin($str);
    }
  }

  public static function bin2hex($str) {
    // http://php.net/bin2hex -- PHP 4+
    return bin2hex($str);
  }

}
