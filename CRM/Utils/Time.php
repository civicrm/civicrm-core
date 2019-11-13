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

/**
 * Date time utilties
 */
class CRM_Utils_Time {

  /**
   * @var int
   *   the seconds offset from the real world time
   */
  static private $_delta = 0;

  /**
   * Get the time.
   *
   * @param string $returnFormat
   *   Format in which date is to be retrieved.
   *
   * @return date
   */
  public static function getTime($returnFormat = 'YmdHis') {
    return date($returnFormat, self::getTimeRaw());
  }

  /**
   * Get the time.
   *
   * @return int
   *   seconds since epoch
   */
  public static function getTimeRaw() {
    return time() + self::$_delta;
  }

  /**
   * Set the given time.
   *
   * @param string $newDateTime
   *   A date formatted with strtotime.
   * @param string $returnFormat
   *   Format in which date is to be retrieved.
   *
   * @return date
   */
  public static function setTime($newDateTime, $returnFormat = 'YmdHis') {
    self::$_delta = strtotime($newDateTime) - time();
    return self::getTime($returnFormat);
  }

  /**
   * Remove any time overrides.
   */
  public static function resetTime() {
    self::$_delta = 0;
  }

  /**
   * Approximate time-comparison. $a and $b are considered equal if they
   * are within $threshold seconds of each other.
   *
   * @param string $a
   *   Time which can be parsed by strtotime.
   * @param string $b
   *   Time which can be parsed by strtotime.
   * @param int $threshold
   *   Maximum allowed difference (in seconds).
   * @return bool
   */
  public static function isEqual($a, $b, $threshold = 0) {
    $diff = strtotime($b) - strtotime($a);
    return (abs($diff) <= $threshold);
  }

}
