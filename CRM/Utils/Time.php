<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

/**
 * Date time utilties
 */
class CRM_Utils_Time {

  /**
   * @var int, the seconds offset from the real world time
   */
  static private $_delta = 0;

  /**
   * get the time
   *
   * @param string $returnFormat format in which date is to be retrieved
   *
   * @return date
   *
   * @static
   */
  static function getTime($returnFormat = 'YmdHis') {
    return date($returnFormat, self::getTimeRaw());
  }

  /**
   * Get the time
   *
   * @return int, seconds since epoch
   */
  static function getTimeRaw() {
    return time() + self::$_delta;
  }

  /**
   * set the given time
   *
   * @param string $newDateTime  a date formatted with strtotime
   * @param string $returnFormat format in which date is to be retrieved
   *
   * @return date
   *
   * @static
   */
  static function setTime($newDateTime, $returnFormat = 'YmdHis') {
    self::$_delta = strtotime($newDateTime) - time();
    return self::getTime($returnFormat);
  }

  /**
   * Remove any time overrides
   */
  static function resetTime() {
    self::$_delta = 0;
  }
}

