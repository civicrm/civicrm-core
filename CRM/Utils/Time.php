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
   * A function which determines the current time.
   * Only used during testing (with mocked time).
   *
   * The normal value, NULL, indicates the use of real time.
   *
   * @var callable|null
   */
  static private $callback = NULL;

  /**
   * Get the time.
   *
   * @param string $returnFormat
   *   Format in which date is to be retrieved.
   *
   * @return string
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
    return self::$callback === NULL ? time() : call_user_func(self::$callback);
  }

  /**
   * Set the given time.
   *
   * @param string $newDateTime
   *   A date formatted with strtotime.
   * @param string $returnFormat
   *   Format in which date is to be retrieved.
   *
   * Note: The progression of time will be influenced by TIME_FUNC, which may be:
   *   - 'frozen' (time does not move)
   *   - 'natural' (time moves naturally)
   *   - 'linear:XXX' (time moves in increments of XXX milliseconds - with every lookup)
   *   - 'prng:XXX' (time moves by random increments, between 0 and XXX milliseconds)
   * @return string
   */
  public static function setTime($newDateTime, $returnFormat = 'YmdHis') {
    $mode = getenv('TIME_FUNC') ? getenv('TIME_FUNC') : 'natural';

    list ($modeName, $modeNum) = explode(":", "$mode:");

    switch ($modeName) {
      case 'frozen':
        // Every getTime() will produce the same value (ie $newDateTime).
        $now = strtotime($newDateTime);
        self::$callback = function () use ($now) {
          return $now;
        };
        break;

      case 'natural':
        // Time changes to $newDateTime and then proceeds naturally.
        $delta = strtotime($newDateTime) - time();
        self::$callback = function () use ($delta) {
          return time() + $delta;
        };
        break;

      case 'linear':
        // Time changes to $newDateTime and then proceeds in fixed increments ($modeNum milliseconds).
        $incr = ($modeNum / 1000.0);
        $now = (float) strtotime($newDateTime) - $incr;
        self::$callback = function () use (&$now, $incr) {
          $now += $incr;
          return floor($now);
        };
        break;

      case 'prng':
        // Time changes to $newDateTime and then proceeds using deterministic pseudorandom increments (of up to $modeNum milliseconds).
        $seed = md5($newDateTime . chr(0) . $mode, TRUE);
        $now = (float) strtotime($newDateTime);
        self::$callback = function () use (&$seed, &$now, $modeNum) {
          $mod = gmp_strval(gmp_mod(gmp_import($seed), "$modeNum"));
          $seed = md5($seed . $now, TRUE);
          $now = $now + ($mod / 1000.0);
          return floor($now);
        };
        break;

      default:
        throw new \RuntimeException("Unrecognized TIME_FUNC ($mode)");
    }

    return self::getTime($returnFormat);
  }

  /**
   * Remove any time overrides.
   */
  public static function resetTime() {
    self::$callback = NULL;
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
