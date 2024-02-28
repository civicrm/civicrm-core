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
 * @file
 * API for event export in iCalendar format
 * as outlined in Internet Calendaring and
 * Scheduling Core Object Specification
 */
class CRM_Utils_ICalendar {

  /**
   * Escape text elements for safe ICalendar use.
   *
   * @param string $text
   *   Text to escape.
   * @param bool $keep_html
   *   Flag to retain HTML formatting
   * @param int $position
   *   Column number of the start of the string in the ICal output - used to
   *   determine allowable length of the first line
   *
   * @return string
   */
  public static function formatText($text, $keep_html = FALSE, int $position = 0) {
    if (!$keep_html) {
      $text = preg_replace(
        '{ <a [^>]+ \\b href=(?: "( [^"]+ )" | \'( [^\']+ )\' ) [^>]* > ( [^<]* ) </a> }xi',
        '$3 ($1$2)',
        $text
      );
      $text = preg_replace(
        '{ < / [^>]+ > \s* }',
        "\$0 ",
        $text
      );
      $text = preg_replace(
        '{ <(br|/tr|/div|/h[1-6]) (\s [^>]*)? > (\s* \n)? }xi',
        "\$0\n",
        $text
      );
      $text = preg_replace(
        '{ </p> (\s* \n)? }xi',
        "\$0\n\n",
        $text
      );
      $text = strip_tags($text);
      $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML401, 'UTF-8');
    }

    $text = str_replace("\\", "\\\\", $text);
    $text = str_replace(',', '\,', $text);
    $text = str_replace(';', '\;', $text);
    $text = str_replace(["\r\n", "\n", "\r"], "\\n ", $text);

    // Remove this check after PHP 7.4 becomes a minimum requirement
    $str_split = function_exists('mb_str_split') ? 'mb_str_split' : 'str_split';

    if ($keep_html) {
      $text = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 3.2//EN"><html><body>' . $text . '</body></html>';
    }
    $prefix = '';
    if ($position) {
      $prefixlen = max(50 - $position, 0);
      $prefix = mb_substr($text, 0, $prefixlen) . "\n ";
      $text = mb_substr($text, $prefixlen);
    }
    $text = $prefix . implode("\n ", $str_split($text, 50));
    return $text;
  }

  /**
   * Restore iCal formatted text to normal.
   *
   * @param string $text
   *   Text to unescape.
   *
   * @return string
   */
  public static function unformatText($text) {
    $text = str_replace("\n ", "", $text);
    $text = str_replace('\n ', "\n", $text);
    $text = str_replace('\;', ';', $text);
    $text = str_replace('\,', ',', $text);
    $text = str_replace("\\\\", "\\", $text);
    $text = str_replace("DQUOTE", "\"", $text);
    return $text;
  }

  /**
   * Escape date elements for safe ICalendar use.
   *
   * @param string $date
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
   * - 'text/calendar' : used for iCal formatted feed
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

    if ($fileName) {
      CRM_Utils_System::setHttpHeader('Content-Length', strlen($calendar));
      CRM_Utils_System::setHttpHeader("Content-Disposition", "$disposition; filename=\"$fileName\"");
      CRM_Utils_System::setHttpHeader("Pragma", "no-cache");
      CRM_Utils_System::setHttpHeader("Expires", "0");
      CRM_Utils_System::setHttpHeader("Cache-Control", "no-cache, must-revalidate");
    }

    echo $calendar;
  }

  /**
   * @param array $timezones - Timezone strings
   * @param $date_min
   * @param $date_max
   *
   * @return array
   */
  public static function generate_timezones(array $timezones, $date_min, $date_max) {
    if (empty($timezones)) {
      return [];
    }

    $tz_items = [];

    foreach ($timezones as $tzstr) {
      $utcTimezone = new DateTimeZone('UTC');
      $eventTimezone = new DateTimeZone($tzstr);

      $transitions = $eventTimezone->getTransitions($date_min, $date_max);

      if (count($transitions) === 1) {
        $transitions[] = array_values($transitions)[0];
      }

      $item = [
        'id' => $eventTimezone->getName(),
        'transitions' => [],
      ];

      $last_transition = array_shift($transitions);

      foreach ($transitions as $transition) {
        $item['transitions'][] = [
          'type' => $transition['isdst'] ? 'DAYLIGHT' : 'STANDARD',
          'offset_from' => self::format_tz_offset($last_transition['offset']),
          'offset_to' => self::format_tz_offset($transition['offset']),
          'abbr' => $transition['abbr'],
          'dtstart' => date_create($transition['time'], $utcTimezone)->setTimezone($eventTimezone)->format("Ymd\THis"),
        ];
        $last_transition = $transition;
      }

      $tz_items[] = $item;
    }

    return $tz_items;
  }

  protected static function format_tz_offset($offset) {
    $offset /= 60;
    $hours = intval($offset / 60);
    $minutes = abs(intval($offset % 60));

    return sprintf('%+03d%02d', $hours, $minutes);
  }

  /**
   * @param array|NULL $info
   *   Information of the events to create an iCal file for, as returned by
   *   CRM_Event_BAO_Event::getCompleteInfo().
   *
   * @return string
   *   The rendered contents of the iCal file.
   */
  public static function createCalendarFile($info) {
    $template = \CRM_Core_Smarty::singleton();

    // Decode HTML entities in relevant fields.
    foreach (['title', 'description', 'event_type', 'location', 'contact_email'] as $field) {
      if (isset($info[0][$field])) {
        $info[0][$field] = html_entity_decode($info[0][$field], ENT_QUOTES | ENT_HTML401, 'UTF-8');
      }
    }

    // Calculate timezones.
    if (count($info) > 0) {
      $date_min = min(
        array_map(function ($event) {
          return strtotime($event['start_date']);
        }, $info)
      );
      $date_max = max(
        array_map(function ($event) {
          return strtotime($event['end_date'] ?? $event['start_date']);
        }, $info)
      );
      $template->assign('timezones', CRM_Utils_ICalendar::generate_timezones([date_default_timezone_get()], $date_min, $date_max));
    }
    else {
      $template->assign('timezones', NULL);
    }
    $template->assign('timezone', @date_default_timezone_get());

    $template->assign('events', $info);
    $ical_data = $template->fetch('CRM/Core/Calendar/ICal.tpl');
    $ical_data = preg_replace('/(?<!\r)\n/', "\r\n", $ical_data);

    return $ical_data;
  }

  /**
   * @param int|NULL $event_id
   *   The CiviCRM Event ID of the event to render an iCal file for.
   *
   * @return string
   */
  public static function createCalendarFileForEvent($event_id) {
    $info = \CRM_Event_BAO_Event::getCompleteInfo(NULL, NULL, $event_id, NULL, FALSE);
    return self::createCalendarFile($info);
  }

}
