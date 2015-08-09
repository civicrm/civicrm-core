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

class Time {

  /**
   * @var int, the seconds offset from the real world time
   */
  static private $_delta = 0;

  /**
   * @return int
   */
  public static function getTime() {
    return time() + self::$_delta;
  }

  /**
   * Set the given time.
   *
   * @param string $newDateTime
   *   A date formatted with strtotime.
   *
   * @return date
   *
   */
  public static function setTime($newDateTime) {
    self::$_delta = strtotime($newDateTime) - time();
    return self::getTime();
  }

  /**
   * Remove any time overrides.
   */
  public static function resetTime() {
    self::$_delta = 0;
  }

}
