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
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * Simple static helpers for network operations
 */
class CRM_Utils_Network {

  /**
   * Try connecting to a TCP service; if it fails, retry. Repeat until serverStartupTimeOut elapses.
   *
   * @param string $host
   * @param string $port
   * @param int $serverStartupTimeOut
   *   Seconds.
   * @param float $interval
   *   Seconds to wait in between pollings.
   *
   * @return bool
   *   TRUE if service is online
   */
  public static function waitForServiceStartup($host, $port, $serverStartupTimeOut, $interval = 0.333) {
    $start = time();
    $end = $start + $serverStartupTimeOut;
    $found = FALSE;
    $interval_usec = (int) 1000000 * $interval;

    while (!$found && $end >= time()) {
      $found = self::checkService($host, $port, $end - time());
      if ($found) {
        return TRUE;
      }
      usleep($interval_usec);
    }
    return FALSE;
  }

  /**
   * Check whether a TCP service is available on $host and $port.
   *
   * @param string $host
   * @param string $port
   * @param string $serverConnectionTimeOut
   *
   * @return bool
   */
  public static function checkService($host, $port, $serverConnectionTimeOut) {
    $old_error_reporting = error_reporting();
    error_reporting($old_error_reporting & ~E_WARNING);
    try {
      $fh = fsockopen($host, $port, $errno, $errstr, $serverConnectionTimeOut);
      if ($fh) {
        fclose($fh);
        error_reporting($old_error_reporting);
        return TRUE;
      }
    }
    catch (Exception $e) {
    }
    error_reporting($old_error_reporting);
    return FALSE;
  }

}
