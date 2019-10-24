<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
 */

/**
 * Simple static helpers for network operations
 */
class CRM_Utils_Network {

  /**
   * Try connecting to a TCP service; if it fails, retry. Repeat until serverStartupTimeOut elapses.
   *
   * @param $host
   * @param $port
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
