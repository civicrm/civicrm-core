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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2015
 */

/**
 * @file
 * API for event export in iCalendar format
 * as outlined in Internet Calendaring and
 * Scheduling Core Object Specification
 */
class CRM_Utils_ICalendar {

  /**
   * Escape text elements for safe ICalendar use.
   *
   * @param $text
   *   Text to escape.
   *
   * @return string
   *   Escaped text
   */
  public static function formatText($text) {
    $text = strip_tags($text);
    $text = str_replace("\"", "DQUOTE", $text);
    $text = str_replace("\\", "\\\\", $text);
    $text = str_replace(",", "\,", $text);
    $text = str_replace(";", "\;", $text);
    $text = str_replace(array("\r\n", "\n", "\r"), "\\n ", $text);
    $text = implode("\n ", str_split($text, 50));
    return $text;
  }

  /**
   * Escape date elements for safe ICalendar use.
   *
   * @param $date
   *   Date to escape.
   *
   * @param bool $gdata
   *
   * @return string
   *   Escaped date
   */
  public static function formatDate($date, $gdata = FALSE) {

    if ($gdata) {
      return date("Y-m-d\TH:i:s.000",
        strtotime($date)
      );
    }
    else {
      return date("Ymd\THis",
        strtotime($date)
      );
    }
  }

  /**
   * Send the ICalendar to the browser with the specified content type
   * - 'text/calendar' : used for downloaded ics file
   * - 'text/plain'    : used for iCal formatted feed
   * - 'text/xml'      : used for gData or rss formatted feeds
   *
   *
   * @param string $calendar
   *   The calendar data to be published.
   * @param string $content_type
   * @param string $charset
   *   The character set to use, defaults to 'us-ascii'.
   * @param string $fileName
   *   The file name (for downloads).
   * @param string $disposition
   *   How the file should be sent ('attachment' for downloads).
   */
  public static function send($calendar, $content_type = 'text/calendar', $charset = 'us-ascii', $fileName = NULL, $disposition = NULL) {
    $config = CRM_Core_Config::singleton();
    $lang = $config->lcMessages;
    CRM_Utils_System::setHttpHeader("Content-Language", $lang);
    CRM_Utils_System::setHttpHeader("Content-Type", "$content_type; charset=$charset");

    if ($content_type == 'text/calendar') {
      CRM_Utils_System::setHttpHeader('Content-Length', strlen($calendar));
      CRM_Utils_System::setHttpHeader("Content-Disposition", "$disposition; filename=\"$fileName\"");
      CRM_Utils_System::setHttpHeader("Pragma", "no-cache");
      CRM_Utils_System::setHttpHeader("Expires", "0");
      CRM_Utils_System::setHttpHeader("Cache-Control", "no-cache, must-revalidate");
    }

    echo $calendar;
  }

}
