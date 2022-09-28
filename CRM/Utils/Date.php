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
 * Date utilties
 */
class CRM_Utils_Date {

  /**
   * Format a date by padding it with leading '0'.
   *
   * @param array $date
   *   ('Y', 'M', 'd').
   * @param string $separator
   *   The separator to use when formatting the date.
   * @param int|string $invalidDate what to return if the date is invalid
   *
   * @return string
   *   formatted string for date
   */
  public static function format($date, $separator = '', $invalidDate = 0) {
    if (is_numeric($date) &&
      ((strlen($date) == 8) || (strlen($date) == 14))
    ) {
      return $date;
    }

    if (!is_array($date) ||
      CRM_Utils_System::isNull($date) ||
      empty($date['Y'])
    ) {
      return $invalidDate;
    }

    $date['Y'] = (int ) $date['Y'];
    if ($date['Y'] < 1000 || $date['Y'] > 2999) {
      return $invalidDate;
    }

    if (array_key_exists('m', $date)) {
      $date['M'] = $date['m'];
    }
    elseif (array_key_exists('F', $date)) {
      $date['M'] = $date['F'];
    }

    if (!empty($date['M'])) {
      $date['M'] = (int ) $date['M'];
      if ($date['M'] < 1 || $date['M'] > 12) {
        return $invalidDate;
      }
    }
    else {
      $date['M'] = 1;
    }

    if (!empty($date['d'])) {
      $date['d'] = (int ) $date['d'];
    }
    else {
      $date['d'] = 1;
    }

    if (!checkdate($date['M'], $date['d'], $date['Y'])) {
      return $invalidDate;
    }

    $date['M'] = sprintf('%02d', $date['M']);
    $date['d'] = sprintf('%02d', $date['d']);

    $time = '';
    if (CRM_Utils_Array::value('H', $date) != NULL ||
      CRM_Utils_Array::value('h', $date) != NULL ||
      CRM_Utils_Array::value('i', $date) != NULL ||
      CRM_Utils_Array::value('s', $date) != NULL
    ) {
      // we have time too..
      if (!empty($date['h'])) {
        if (CRM_Utils_Array::value('A', $date) == 'PM' or CRM_Utils_Array::value('a', $date) == 'pm') {
          if ($date['h'] != 12) {
            $date['h'] = $date['h'] + 12;
          }
        }
        if ((CRM_Utils_Array::value('A', $date) == 'AM' or CRM_Utils_Array::value('a', $date) == 'am') &&
          CRM_Utils_Array::value('h', $date) == 12
        ) {
          $date['h'] = '00';
        }

        $date['h'] = (int ) $date['h'];
      }
      else {
        $date['h'] = 0;
      }

      // in 24-hour format the hour is under the 'H' key
      if (!empty($date['H'])) {
        $date['H'] = (int) $date['H'];
      }
      else {
        $date['H'] = 0;
      }

      if (!empty($date['i'])) {
        $date['i'] = (int ) $date['i'];
      }
      else {
        $date['i'] = 0;
      }

      if ($date['h'] == 0 && $date['H'] != 0) {
        $date['h'] = $date['H'];
      }

      if (!empty($date['s'])) {
        $date['s'] = (int ) $date['s'];
      }
      else {
        $date['s'] = 0;
      }

      $date['h'] = sprintf('%02d', $date['h']);
      $date['i'] = sprintf('%02d', $date['i']);
      $date['s'] = sprintf('%02d', $date['s']);

      if ($separator) {
        $time = '&nbsp;';
      }
      $time .= $date['h'] . $separator . $date['i'] . $separator . $date['s'];
    }

    return $date['Y'] . $separator . $date['M'] . $separator . $date['d'] . $time;
  }

  /**
   * Return abbreviated weekday names according to the locale.
   *
   * Array will be in localized order according to 'weekBegins' setting,
   * but array keys will always match to:
   * 0 => Sun
   * 1 => Mon
   * etc.
   *
   * @return array
   *   0-based array with abbreviated weekday names
   *
   */
  public static function getAbbrWeekdayNames() {
    $key = 'abbrDays_' . \CRM_Core_I18n::getLocale();
    if (empty(\Civi::$statics[__CLASS__][$key])) {
      $intl_formatter = IntlDateFormatter::create(CRM_Core_I18n::getLocale(), IntlDateFormatter::MEDIUM, IntlDateFormatter::MEDIUM, NULL, IntlDateFormatter::GREGORIAN, 'E');
      $days = [
        0 => $intl_formatter->format(strtotime('Sunday')),
        1 => $intl_formatter->format(strtotime('Monday')),
        2 => $intl_formatter->format(strtotime('Tuesday')),
        3 => $intl_formatter->format(strtotime('Wednesday')),
        4 => $intl_formatter->format(strtotime('Thursday')),
        5 => $intl_formatter->format(strtotime('Friday')),
        6 => $intl_formatter->format(strtotime('Saturday')),
      ];
      // First day of the week
      $firstDay = Civi::settings()->get('weekBegins');

      \Civi::$statics[__CLASS__][$key] = [];
      for ($i = $firstDay; count(\Civi::$statics[__CLASS__][$key]) < 7; $i = $i > 5 ? 0 : $i + 1) {
        \Civi::$statics[__CLASS__][$key][$i] = $days[$i];
      }
    }
    return \Civi::$statics[__CLASS__][$key];
  }

  /**
   * Return full weekday names according to the locale.
   *
   * Array will be in localized order according to 'weekBegins' setting,
   * but array keys will always match to:
   * 0 => Sunday
   * 1 => Monday
   * etc.
   *
   * @return array
   *   0-based array with full weekday names
   *
   */
  public static function getFullWeekdayNames() {
    $key = 'fullDays_' . \CRM_Core_I18n::getLocale();
    if (empty(\Civi::$statics[__CLASS__][$key])) {
      $intl_formatter = IntlDateFormatter::create(CRM_Core_I18n::getLocale(), IntlDateFormatter::MEDIUM, IntlDateFormatter::MEDIUM, NULL, IntlDateFormatter::GREGORIAN, 'EEEE');
      $days = [
        0 => $intl_formatter->format(strtotime('Sunday')),
        1 => $intl_formatter->format(strtotime('Monday')),
        2 => $intl_formatter->format(strtotime('Tuesday')),
        3 => $intl_formatter->format(strtotime('Wednesday')),
        4 => $intl_formatter->format(strtotime('Thursday')),
        5 => $intl_formatter->format(strtotime('Friday')),
        6 => $intl_formatter->format(strtotime('Saturday')),
      ];
      // First day of the week
      $firstDay = Civi::settings()->get('weekBegins');

      \Civi::$statics[__CLASS__][$key] = [];
      for ($i = $firstDay; count(\Civi::$statics[__CLASS__][$key]) < 7; $i = $i > 5 ? 0 : $i + 1) {
        \Civi::$statics[__CLASS__][$key][$i] = $days[$i];
      }
    }
    return \Civi::$statics[__CLASS__][$key];
  }

  /**
   * Return abbreviated month names according to the locale.
   *
   * @param bool $month
   *
   * @return array
   *   1-based array with abbreviated month names
   *
   */
  public static function &getAbbrMonthNames($month = FALSE) {
    $key = 'abbrMonthNames_' . \CRM_Core_I18n::getLocale();
    if (empty(\Civi::$statics[__CLASS__][$key])) {
      $intl_formatter = IntlDateFormatter::create(CRM_Core_I18n::getLocale(), IntlDateFormatter::MEDIUM, IntlDateFormatter::MEDIUM, NULL, IntlDateFormatter::GREGORIAN, 'MMM');
      \Civi::$statics[__CLASS__][$key] = [
        1 => $intl_formatter->format(strtotime('1 January')),
        2 => $intl_formatter->format(strtotime('1 February')),
        3 => $intl_formatter->format(strtotime('1 March')),
        4 => $intl_formatter->format(strtotime('1 April')),
        5 => $intl_formatter->format(strtotime('1 May')),
        6 => $intl_formatter->format(strtotime('1 June')),
        7 => $intl_formatter->format(strtotime('1 July')),
        8 => $intl_formatter->format(strtotime('1 August')),
        9 => $intl_formatter->format(strtotime('1 September')),
        10 => $intl_formatter->format(strtotime('1 October')),
        11 => $intl_formatter->format(strtotime('1 November')),
        12 => $intl_formatter->format(strtotime('1 December')),
      ];
    }
    if ($month) {
      return \Civi::$statics[__CLASS__][$key][$month];
    }
    return \Civi::$statics[__CLASS__][$key];
  }

  /**
   * Return full month names according to the locale.
   *
   * @return array
   *   1-based array with full month names
   *
   */
  public static function &getFullMonthNames() {
    $key = 'fullMonthNames_' . \CRM_Core_I18n::getLocale();
    if (empty(\Civi::$statics[__CLASS__][$key])) {
      // Not relying on strftime because it depends on the operating system
      // and most people will not have a non-US locale configured out of the box
      // Ignoring other date names for now, since less visible by default
      \Civi::$statics[__CLASS__][$key] = [
        1 => ts('January'),
        2 => ts('February'),
        3 => ts('March'),
        4 => ts('April'),
        5 => ts('May'),
        6 => ts('June'),
        7 => ts('July'),
        8 => ts('August'),
        9 => ts('September'),
        10 => ts('October'),
        11 => ts('November'),
        12 => ts('December'),
      ];
    }

    return \Civi::$statics[__CLASS__][$key];
  }

  /**
   * @param string $string
   *
   * @return int
   */
  public static function unixTime($string) {
    if (empty($string)) {
      return 0;
    }
    $parsedDate = date_parse($string);
    return mktime(CRM_Utils_Array::value('hour', $parsedDate),
      CRM_Utils_Array::value('minute', $parsedDate),
      59,
      CRM_Utils_Array::value('month', $parsedDate),
      CRM_Utils_Array::value('day', $parsedDate),
      CRM_Utils_Array::value('year', $parsedDate)
    );
  }

  /**
   * Create a date and time string in a provided format.
   * %A - Full day name ('Saturday'..'Sunday')
   * %a - abbreviated day name ('Sat'..'Sun')
   * %b - abbreviated month name ('Jan'..'Dec')
   * %B - full month name ('January'..'December')
   * %d - day of the month as a decimal number, 0-padded ('01'..'31')
   * %e - day of the month as a decimal number, blank-padded (' 1'..'31')
   * %E - day of the month as a decimal number ('1'..'31')
   * %f - English ordinal suffix for the day of the month ('st', 'nd', 'rd', 'th')
   * %H - hour in 24-hour format, 0-padded ('00'..'23')
   * %I - hour in 12-hour format, 0-padded ('01'..'12')
   * %k - hour in 24-hour format, blank-padded (' 0'..'23')
   * %l - hour in 12-hour format, blank-padded (' 1'..'12')
   * %m - month as a decimal number, 0-padded ('01'..'12')
   * %M - minute, 0-padded ('00'..'60')
   * %p - lowercase ante/post meridiem ('am', 'pm')
   * %P - uppercase ante/post meridiem ('AM', 'PM')
   * %Y - year as a decimal number including the century ('2005')
   *
   * @param string $dateString
   *   Date and time in 'YYYY-MM-DD hh:mm:ss' format.
   * @param string $format
   *   The output format.
   * @param array $dateParts
   *   An array with the desired date parts.
   *
   * @return string
   *   the $format-formatted $date
   */
  public static function customFormat($dateString, $format = NULL, $dateParts = NULL) {
    // 1-based (January) month names arrays
    $abbrMonths = self::getAbbrMonthNames();
    $fullMonths = self::getFullMonthNames();
    $fullWeekdayNames = self::getFullWeekdayNames();
    $abbrWeekdayNames = self::getAbbrWeekdayNames();

    // backwards compatibility with %D being the equivalent of %m/%d/%y
    $format = str_replace('%D', '%m/%d/%y', ($format ?? ''));

    if (!$format) {
      $config = CRM_Core_Config::singleton();

      if ($dateParts) {
        if (array_intersect(['h', 'H'], $dateParts)) {
          $format = $config->dateformatDatetime;
        }
        elseif (array_intersect(['d', 'j'], $dateParts)) {
          $format = $config->dateformatFull;
        }
        elseif (array_intersect(['m', 'M'], $dateParts)) {
          $format = $config->dateformatPartial;
        }
        else {
          $format = $config->dateformatYear;
        }
      }
      else {
        if (strpos(($dateString ?? ''), '-')) {
          $month = (int) substr($dateString, 5, 2);
          $day = (int) substr($dateString, 8, 2);
        }
        else {
          $month = (int) substr(($dateString ?? ''), 4, 2);
          $day = (int) substr(($dateString ?? ''), 6, 2);
        }

        if (strlen(($dateString ?? '')) > 10) {
          $format = $config->dateformatDatetime;
        }
        elseif ($day > 0) {
          $format = $config->dateformatFull;
        }
        elseif ($month > 0) {
          $format = $config->dateformatPartial;
        }
        else {
          $format = $config->dateformatYear;
        }
      }
    }

    if (!CRM_Utils_System::isNull($dateString)) {
      if (strpos($dateString, '-')) {
        $year = (int) substr($dateString, 0, 4);
        $month = (int) substr($dateString, 5, 2);
        $day = (int) substr($dateString, 8, 2);

        $hour24 = (int) substr($dateString, 11, 2);
        $minute = (int) substr($dateString, 14, 2);
        $second = (int) substr($dateString, 17, 2);
      }
      else {
        $year = (int) substr($dateString, 0, 4);
        $month = (int) substr($dateString, 4, 2);
        $day = (int) substr($dateString, 6, 2);

        $hour24 = (int) substr($dateString, 8, 2);
        $minute = (int) substr($dateString, 10, 2);
        $second = (int) substr($dateString, 12, 2);
      }

      $dayInt = date('w', strtotime($dateString));

      if ($day % 10 == 1 and $day != 11) {
        $suffix = 'st';
      }
      elseif ($day % 10 == 2 and $day != 12) {
        $suffix = 'nd';
      }
      elseif ($day % 10 == 3 and $day != 13) {
        $suffix = 'rd';
      }
      else {
        $suffix = 'th';
      }

      if ($hour24 < 12) {
        if ($hour24 == 00) {
          $hour12 = 12;
        }
        else {
          $hour12 = $hour24;
        }
        $type = 'AM';
      }
      else {
        if ($hour24 == 12) {
          $hour12 = 12;
        }
        else {
          $hour12 = $hour24 - 12;
        }
        $type = 'PM';
      }

      $date = [
        '%A' => $fullWeekdayNames[$dayInt] ?? NULL,
        '%a' => $abbrWeekdayNames[$dayInt] ?? NULL,
        '%b' => $abbrMonths[$month] ?? NULL,
        '%B' => $fullMonths[$month] ?? NULL,
        '%d' => $day > 9 ? $day : '0' . $day,
        '%e' => $day > 9 ? $day : ' ' . $day,
        '%E' => $day,
        '%f' => $suffix,
        '%H' => $hour24 > 9 ? $hour24 : '0' . $hour24,
        '%h' => $hour12 > 9 ? $hour12 : '0' . $hour12,
        '%I' => $hour12 > 9 ? $hour12 : '0' . $hour12,
        '%k' => $hour24 > 9 ? $hour24 : ' ' . $hour24,
        '%l' => $hour12 > 9 ? $hour12 : ' ' . $hour12,
        '%m' => $month > 9 ? $month : '0' . $month,
        '%M' => $minute > 9 ? $minute : '0' . $minute,
        '%i' => $minute > 9 ? $minute : '0' . $minute,
        '%p' => strtolower($type),
        '%P' => $type,
        '%Y' => $year,
        '%y' => substr($year, 2),
        '%s' => str_pad($second, 2, 0, STR_PAD_LEFT),
        '%S' => str_pad($second, 2, 0, STR_PAD_LEFT),
        '%Z' => date('T', strtotime($dateString)),
      ];

      return strtr($format, $date);
    }
    return '';
  }

  /**
   * Format the field according to the site's preferred date format.
   *
   * This is likely to look something like December 31st, 2020.
   *
   * @param string $date
   *
   * @return string
   */
  public static function formatDateOnlyLong(string $date):string {
    return CRM_Utils_Date::customFormat($date, Civi::settings()->get('dateformatFull'));
  }

  /**
   * Wrapper for customFormat that takes a timestamp
   *
   * @param int $timestamp
   *   Date and time in timestamp format.
   * @param string $format
   *   The output format.
   * @param array $dateParts
   *   An array with the desired date parts.
   *
   * @return string
   *   the $format-formatted $date
   */
  public static function customFormatTs($timestamp, $format = NULL, $dateParts = NULL) {
    return CRM_Utils_Date::customFormat(date("Y-m-d H:i:s", $timestamp), $format, $dateParts);
  }

  /**
   * Converts the date/datetime from MySQL format to ISO format
   *
   * @param string $mysql
   *   Date/datetime in MySQL format.
   *
   * @return string
   *   date/datetime in ISO format
   */
  public static function mysqlToIso($mysql) {
    $year = substr(($mysql ?? ''), 0, 4);
    $month = substr(($mysql ?? ''), 4, 2);
    $day = substr(($mysql ?? ''), 6, 2);
    $hour = substr(($mysql ?? ''), 8, 2);
    $minute = substr(($mysql ?? ''), 10, 2);
    $second = substr(($mysql ?? ''), 12, 2);

    $iso = '';
    if ($year) {
      $iso .= "$year";
    }
    if ($month) {
      $iso .= "-$month";
      if ($day) {
        $iso .= "-$day";
      }
    }

    if ($hour) {
      $iso .= " $hour";
      if ($minute) {
        $iso .= ":$minute";
        if ($second) {
          $iso .= ":$second";
        }
      }
    }
    return $iso;
  }

  /**
   * Converts the date/datetime from ISO format to MySQL format
   * Note that until CRM-14986/ 4.4.7 this was required whenever the pattern $dao->find(TRUE): $dao->save(); was
   * used to update an object with a date field was used. The DAO now checks for a '-' in date field strings
   * & runs this function if the - appears - meaning it is likely redundant in the form & BAO layers
   *
   * @param string $iso
   *   Date/datetime in ISO format.
   *
   * @return string
   *   date/datetime in MySQL format
   */
  public static function isoToMysql($iso) {
    $dropArray = ['-' => '', ':' => '', ' ' => ''];
    return strtr(($iso ?? ''), $dropArray);
  }

  /**
   * Converts the any given date to default date format.
   *
   * @param array $params
   *   Has given date-format.
   * @param int $dateType
   *   Type of date.
   * @param string $dateParam
   *   Index of params.
   *
   * @return bool
   */
  public static function convertToDefaultDate(&$params, $dateType, $dateParam) {
    $now = getdate();

    $value = '';
    if (!empty($params[$dateParam])) {
      // suppress hh:mm or hh:mm:ss if it exists CRM-7957
      $value = preg_replace("/(\s(([01]\d)|[2][0-3])(:([0-5]\d)){1,2})$/", "", $params[$dateParam]);
    }

    switch ($dateType) {
      case 1:
        if (!preg_match('/^\d\d\d\d-?(\d|\d\d)-?(\d|\d\d)$/', $value)) {
          return FALSE;
        }
        break;

      case 2:
        if (!preg_match('/^(\d|\d\d)[-\/](\d|\d\d)[-\/]\d\d$/', $value)) {
          return FALSE;
        }
        break;

      case 4:
        if (!preg_match('/^(\d|\d\d)[-\/](\d|\d\d)[-\/]\d\d\d\d$/', $value)) {
          return FALSE;
        }
        break;

      case 8:
        if (!preg_match('/^[A-Za-z]*.[ \t]?\d\d\,[ \t]?\d\d\d\d$/', $value)) {
          return FALSE;
        }
        break;

      case 16:
        if (!preg_match('/^\d\d-[A-Za-z]{3}.*-\d\d$/', $value) && !preg_match('/^\d\d[-\/]\d\d[-\/]\d\d$/', $value)) {
          return FALSE;
        }
        break;

      case 32:
        if (!preg_match('/^(\d|\d\d)[-\/](\d|\d\d)[-\/]\d\d\d\d/', $value)) {
          return FALSE;
        }
        break;
    }

    if ($dateType == 1) {
      $formattedDate = explode("-", $value);
      if (count($formattedDate) == 3) {
        $year = (int) $formattedDate[0];
        $month = (int) $formattedDate[1];
        $day = (int) $formattedDate[2];
      }
      elseif (count($formattedDate) == 1 && (strlen($value) == 8)) {
        return TRUE;
      }
      else {
        return FALSE;
      }
    }

    if ($dateType == 2 || $dateType == 4) {
      $formattedDate = explode("/", $value);
      if (count($formattedDate) != 3) {
        $formattedDate = explode("-", $value);
      }
      if (count($formattedDate) == 3) {
        $year = (int) $formattedDate[2];
        $month = (int) $formattedDate[0];
        $day = (int) $formattedDate[1];
      }
      else {
        return FALSE;
      }
    }
    if ($dateType == 8) {
      $dateArray = explode(' ', $value);
      // ignore comma(,)
      $dateArray[1] = (int) substr($dateArray[1], 0, 2);

      $monthInt = 0;
      $fullMonths = self::getFullMonthNames();
      foreach ($fullMonths as $key => $val) {
        if (strtolower($dateArray[0]) == strtolower($val)) {
          $monthInt = $key;
          break;
        }
      }
      if (!$monthInt) {
        $abbrMonths = self::getAbbrMonthNames();
        foreach ($abbrMonths as $key => $val) {
          if (strtolower(trim($dateArray[0], ".")) == strtolower($val)) {
            $monthInt = $key;
            break;
          }
        }
      }
      $year = (int) $dateArray[2];
      $day = (int) $dateArray[1];
      $month = (int) $monthInt;
    }
    if ($dateType == 16) {
      $dateArray = explode('-', $value);
      if (count($dateArray) != 3) {
        $dateArray = explode('/', $value);
      }

      if (count($dateArray) == 3) {
        $monthInt = 0;
        $fullMonths = self::getFullMonthNames();
        foreach ($fullMonths as $key => $val) {
          if (strtolower($dateArray[1]) == strtolower($val)) {
            $monthInt = $key;
            break;
          }
        }
        if (!$monthInt) {
          $abbrMonths = self::getAbbrMonthNames();
          foreach ($abbrMonths as $key => $val) {
            if (strtolower(trim($dateArray[1], ".")) == strtolower($val)) {
              $monthInt = $key;
              break;
            }
          }
        }
        if (!$monthInt) {
          $monthInt = $dateArray[1];
        }

        $year = (int) $dateArray[2];
        $day = (int) $dateArray[0];
        $month = (int) $monthInt;
      }
      else {
        return FALSE;
      }
    }
    if ($dateType == 32) {
      $formattedDate = explode("/", $value);
      if (count($formattedDate) == 3) {
        $year = (int) $formattedDate[2];
        $month = (int) $formattedDate[1];
        $day = (int) $formattedDate[0];
      }
      else {
        return FALSE;
      }
    }

    $month = ($month < 10) ? "0" . "$month" : $month;
    $day = ($day < 10) ? "0" . "$day" : $day;

    $year = (int) $year;
    if ($year < 100) {
      $year = substr($now['year'], 0, 2) * 100 + $year;
      if ($year > ($now['year'] + 5)) {
        $year = $year - 100;
      }
      elseif ($year <= ($now['year'] - 95)) {
        $year = $year + 100;
      }
    }

    if ($params[$dateParam]) {
      $params[$dateParam] = "$year$month$day";
    }
    // if month is invalid return as error
    if ($month !== '00' && $month <= 12) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Translate a TTL to a concrete expiration time.
   *
   * @param null|int|DateInterval $ttl
   * @param int $default
   *   The value to use if $ttl is not specified (NULL).
   * @return int
   *   Timestamp (seconds since epoch).
   * @throws \CRM_Utils_Cache_InvalidArgumentException
   */
  public static function convertCacheTtlToExpires($ttl, $default) {
    if ($ttl === NULL) {
      $ttl = $default;
    }

    if (is_int($ttl)) {
      return time() + $ttl;
    }
    elseif ($ttl instanceof DateInterval) {
      return date_add(new DateTime(), $ttl)->getTimestamp();
    }
    else {
      throw new CRM_Utils_Cache_InvalidArgumentException("Invalid cache TTL");
    }
  }

  /**
   * Normalize a TTL.
   *
   * @param null|int|DateInterval $ttl
   * @param int $default
   *   The value to use if $ttl is not specified (NULL).
   * @return int
   *   Seconds until expiration.
   * @throws \CRM_Utils_Cache_InvalidArgumentException
   */
  public static function convertCacheTtl($ttl, $default) {
    if ($ttl === NULL) {
      return $default;
    }
    elseif (is_int($ttl)) {
      return $ttl;
    }
    elseif ($ttl instanceof DateInterval) {
      return date_add(new DateTime(), $ttl)->getTimestamp() - time();
    }
    else {
      throw new CRM_Utils_Cache_InvalidArgumentException("Invalid cache TTL");
    }
  }

  /**
   * @param int|false|null $timeStamp
   *
   * @return bool|string
   */
  public static function currentDBDate($timeStamp = NULL) {
    return $timeStamp ? date('YmdHis', $timeStamp) : date('YmdHis');
  }

  /**
   * @param $date
   * @param null $now
   *
   * @return bool
   */
  public static function overdue($date, $now = NULL) {
    $mysqlDate = self::isoToMysql($date);
    if (!$now) {
      $now = self::currentDBDate();
    }
    else {
      $now = self::isoToMysql($now);
    }

    return !(strtotime($mysqlDate) >= strtotime($now));
  }

  /**
   * Get customized today.
   *
   * This function is used for getting customized today. To get
   * actuall today pass 'dayParams' as null. or else pass the day,
   * month, year values as array values
   * Example: $dayParams = array(
   * 'day' => '25', 'month' => '10',
   *                              'year' => '2007' );
   *
   * @param array $dayParams of the day, month, year.
   *   Array of the day, month, year.
   *                             values.
   * @param string $format
   *   Expected date format( default.
   *                             format is 2007-12-21 )
   *
   * @return string
   *   Return the customized today's date (Y-m-d)
   */
  public static function getToday($dayParams = NULL, $format = "Y-m-d") {
    if (is_null($dayParams) || empty($dayParams)) {
      $today = date($format);
    }
    else {
      $today = date($format, mktime(0, 0, 0,
        $dayParams['month'],
        $dayParams['day'],
        $dayParams['year']
      ));
    }

    return $today;
  }

  /**
   * Find whether today's date lies in
   * the given range
   *
   * @param date $startDate
   *   Start date for the range.
   * @param date $endDate
   *   End date for the range.
   *
   * @return bool
   *   true if today's date is in the given date range
   */
  public static function getRange($startDate, $endDate) {
    $today = date("Y-m-d");
    $mysqlStartDate = self::isoToMysql($startDate);
    $mysqlEndDate = self::isoToMysql($endDate);
    $mysqlToday = self::isoToMysql($today);

    if ((isset($mysqlStartDate) && isset($mysqlEndDate)) && (($mysqlToday >= $mysqlStartDate) && ($mysqlToday <= $mysqlEndDate))) {
      return TRUE;
    }
    elseif ((isset($mysqlStartDate) && !isset($mysqlEndDate)) && (($mysqlToday >= $mysqlStartDate))) {
      return TRUE;
    }
    elseif ((!isset($mysqlStartDate) && isset($mysqlEndDate)) && (($mysqlToday <= $mysqlEndDate))) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Get start date and end from
   * the given relative term and unit
   *
   * @param string $relative Relative format in the format term.unit.
   *   Eg: previous.day
   *
   * @param string $from
   * @param string $to
   * @param string $fromTime
   * @param string $toTime
   *
   * @return array
   *   start date, end date
   */
  public static function getFromTo($relative, $from = NULL, $to = NULL, $fromTime = NULL, $toTime = '235959') {
    if ($relative) {
      list($term, $unit) = explode('.', $relative, 2);
      $dateRange = self::relativeToAbsolute($term, $unit);
      $from = substr(($dateRange['from'] ?? ''), 0, 8);
      $to = substr(($dateRange['to'] ?? ''), 0, 8);
      // @todo fix relativeToAbsolute & add tests
      // relativeToAbsolute returns 8 char date strings
      // or 14 char date + time strings.
      // We should use those. However, it turns out to be unreliable.
      // e.g. this.week does NOT return 235959 for 'from'
      // so our defaults are more reliable.
      // Currently relativeToAbsolute only supports 'whole' days so that is ok
    }

    $from = self::processDate($from, $fromTime);
    $to = self::processDate($to, $toTime);

    return [$from, $to];
  }

  /**
   * Calculate Age in Years if greater than one year else in months.
   *
   * @param date $birthDate
   *   Birth Date.
   * @param date $targetDate
   *   Target Date. (show age on specific date)
   *
   * @return array
   *   array $results contains years or months
   */
  public static function calculateAge($birthDate, $targetDate = NULL) {
    $results = [];
    $formatedBirthDate = CRM_Utils_Date::customFormat($birthDate, '%Y-%m-%d');

    $bDate = explode('-', $formatedBirthDate);
    $birthYear = $bDate[0];
    $birthMonth = $bDate[1];
    $birthDay = $bDate[2];
    $targetDate = strtotime($targetDate ?? date('Y-m-d'));

    $year_diff = date("Y", $targetDate) - $birthYear;

    // don't calculate age CRM-3143
    if ($birthYear == '1902') {
      return $results;
    }
    switch ($year_diff) {
      case 1:
        $month = (12 - $birthMonth) + date("m", $targetDate);
        if ($month < 12) {
          if (date("d", $targetDate) < $birthDay) {
            $month--;
          }
          $results['months'] = $month;
        }
        elseif ($month == 12 && (date("d", $targetDate) < $birthDay)) {
          $results['months'] = $month - 1;
        }
        else {
          $results['years'] = $year_diff;
        }
        break;

      case 0:
        $month = date("m", $targetDate) - $birthMonth;
        $results['months'] = $month;
        break;

      default:
        $results['years'] = $year_diff;
        if ((date("m", $targetDate) < $birthMonth) || (date("m", $targetDate) == $birthMonth) && (date("d", $targetDate) < $birthDay)) {
          $results['years']--;
        }
    }

    return $results;
  }

  /**
   * Calculate next payment date according to provided  unit & interval
   *
   * @param string $unit
   *   Frequency unit like year,month, week etc.
   *
   * @param int $interval
   *   Frequency interval.
   *
   * @param array $date
   *   Start date of pledge.
   *
   * @param bool $dontCareTime
   *
   * @return array
   *   contains new date with added interval
   */
  public static function intervalAdd($unit, $interval, $date, $dontCareTime = FALSE) {
    if (is_array($date)) {
      $hour = $date['H'] ?? '00';
      $minute = $date['i'] ?? '00';
      $second = $date['s'] ?? '00';
      $month = $date['M'] ?? NULL;
      $day = $date['d'] ?? NULL;
      $year = $date['Y'] ?? NULL;
    }
    else {
      extract(date_parse($date));
    }
    $date = mktime($hour, $minute, $second, $month, $day, $year);
    switch ($unit) {
      case 'year':
        $date = mktime($hour, $minute, $second, $month, $day, $year + $interval);
        break;

      case 'month':
        $date = mktime($hour, $minute, $second, $month + $interval, $day, $year);
        break;

      case 'week':
        $interval = $interval * 7;
        $date = mktime($hour, $minute, $second, $month, $day + $interval, $year);
        break;

      case 'day':
        $date = mktime($hour, $minute, $second, $month, $day + $interval, $year);
        break;

      case 'second':
        $date = mktime($hour, $minute, $second + $interval, $month, $day, $year);
        break;
    }

    $scheduleDate = explode("-", date("n-j-Y-H-i-s", $date));

    $date = [];
    $date['M'] = $scheduleDate[0];
    $date['d'] = $scheduleDate[1];
    $date['Y'] = $scheduleDate[2];
    if ($dontCareTime == FALSE) {
      $date['H'] = $scheduleDate[3];
      $date['i'] = $scheduleDate[4];
      $date['s'] = $scheduleDate[5];
    }
    return $date;
  }

  /**
   * Get the smarty view presentation mapping for the given format.
   *
   * Historically it was decided that where the view format is 'dd/mm/yy' or 'mm/dd/yy'
   * they should be rendered using a longer date format. This is likely as much to
   * do with the earlier date widget being unable to handle some formats as usablity.
   * However, we continue to respect this.
   *
   * @param $format
   *   Given format ( eg 'M Y', 'Y M' ).
   *
   * @return string|null
   *   Smarty translation of the date format. Null is also valid and is translated
   *   according to the available parts at the smarty layer.
   */
  public static function getDateFieldViewFormat($format) {
    $supportableFormats = [
      'mm/dd' => '%B %E%f',
      'dd-mm' => '%E%f %B',
      'yy-mm' => '%Y %B',
      'M yy' => '%b %Y',
      'yy' => '%Y',
      'dd/mm/yy' => '%E%f %B %Y',
    ];

    return array_key_exists($format, $supportableFormats) ? $supportableFormats[$format] : self::pickBestSmartyFormat($format);
  }

  /**
   * Pick the smarty format from settings that best matches the time string we have.
   *
   * For view purposes we historically use the setting that most closely matches the data
   * in the format from our settings, as opposed to the setting configured for the field.
   *
   * @param $format
   * @return mixed
   */
  public static function pickBestSmartyFormat($format) {
    if (stristr($format, 'h')) {
      return Civi::settings()->get('dateformatDatetime');
    }
    if (stristr($format, 'd') || stristr($format, 'j')) {
      return Civi::settings()->get('dateformatFull');
    }
    if (stristr($format, 'm')) {
      return Civi::settings()->get('dateformatPartial');
    }
    return Civi::settings()->get('dateformatYear');
  }

  /**
   * Map date plugin and actual format that is used by PHP.
   *
   * @return array
   */
  public static function datePluginToPHPFormats() {
    $dateInputFormats = [
      "mm/dd/yy" => 'm/d/Y',
      "dd/mm/yy" => 'd/m/Y',
      "yy-mm-dd" => 'Y-m-d',
      "dd-mm-yy" => 'd-m-Y',
      "dd.mm.yy" => 'd.m.Y',
      "M d" => 'M j',
      "M d, yy" => 'M j, Y',
      "d M yy" => 'j M Y',
      "MM d, yy" => 'F j, Y',
      "d MM yy" => 'j F Y',
      "DD, d MM yy" => 'l, j F Y',
      "mm/dd" => 'm/d',
      "dd-mm" => 'd-m',
      "yy-mm" => 'Y-m',
      "M yy" => 'M Y',
      "M Y" => 'M Y',
      "yy" => 'Y',
    ];
    return $dateInputFormats;
  }

  /**
   * Resolves the given relative time interval into finite time limits.
   *
   * @param string $relativeTerm
   *   Relative time frame: this, previous, previous_1.
   * @param int $unit
   *   Frequency unit like year, month, week etc.
   *
   * @return array
   *   start date and end date for the relative time frame
   */
  public static function relativeToAbsolute($relativeTerm, $unit) {
    $now = getdate();
    $from = $to = $dateRange = [];
    $from['H'] = $from['i'] = $from['s'] = 0;
    $relativeTermParts = explode('_', $relativeTerm);
    $relativeTermPrefix = $relativeTermParts[0];
    $relativeTermSuffix = $relativeTermParts[1] ?? '';

    switch ($unit) {
      case 'year':
        switch ($relativeTerm) {
          case 'previous':
            $from['M'] = $from['d'] = 1;
            $to['d'] = 31;
            $to['M'] = 12;
            $to['Y'] = $from['Y'] = $now['year'] - 1;
            break;

          case 'previous_before':
            $from['M'] = $from['d'] = 1;
            $to['d'] = 31;
            $to['M'] = 12;
            $to['Y'] = $from['Y'] = $now['year'] - 2;
            break;

          case 'previous_2':
            $from['M'] = $from['d'] = 1;
            $to['d'] = 31;
            $to['M'] = 12;
            $from['Y'] = $now['year'] - 2;
            $to['Y'] = $now['year'] - 1;
            break;

          case 'earlier':
            $to['d'] = 31;
            $to['M'] = 12;
            $to['Y'] = $now['year'] - 1;
            unset($from);
            break;

          case 'greater':
            $from['M'] = $from['d'] = 1;
            $from['Y'] = $now['year'];
            unset($to);
            break;

          case 'greater_previous':
            $from['d'] = 31;
            $from['M'] = 12;
            $from['Y'] = $now['year'] - 1;
            unset($to);
            break;

          case 'ending':
            $to['d'] = $now['mday'];
            $to['M'] = $now['mon'];
            $to['Y'] = $now['year'];
            $to['H'] = 23;
            $to['i'] = $to['s'] = 59;
            $from = self::intervalAdd('year', -1, $to);
            $from = self::intervalAdd('second', 1, $from);
            break;

          case 'current':
            $from['M'] = $from['d'] = 1;
            $from['Y'] = $now['year'];
            $to['H'] = 23;
            $to['i'] = $to['s'] = 59;
            $to['d'] = $now['mday'];
            $to['M'] = $now['mon'];
            $to['Y'] = $now['year'];
            break;

          case 'ending_2':
            $to['d'] = $now['mday'];
            $to['M'] = $now['mon'];
            $to['Y'] = $now['year'];
            $to['H'] = 23;
            $to['i'] = $to['s'] = 59;
            $from = self::intervalAdd('year', -2, $to);
            $from = self::intervalAdd('second', 1, $from);
            break;

          case 'ending_3':
            $to['d'] = $now['mday'];
            $to['M'] = $now['mon'];
            $to['Y'] = $now['year'];
            $to['H'] = 23;
            $to['i'] = $to['s'] = 59;
            $from = self::intervalAdd('year', -3, $to);
            $from = self::intervalAdd('second', 1, $from);
            break;

          case 'less':
            $to['d'] = 31;
            $to['M'] = 12;
            $to['Y'] = $now['year'];
            unset($from);
            break;

          case 'next':
            $from['M'] = $from['d'] = 1;
            $to['d'] = 31;
            $to['M'] = 12;
            $to['Y'] = $from['Y'] = $now['year'] + 1;
            break;

          case 'starting':
            $from['d'] = $now['mday'];
            $from['M'] = $now['mon'];
            $from['Y'] = $now['year'];
            $to['d'] = $now['mday'] - 1;
            $to['M'] = $now['mon'];
            $to['Y'] = $now['year'] + 1;
            break;

          default:
            switch ($relativeTermPrefix) {

              case 'ending':
                $to['d'] = $now['mday'];
                $to['M'] = $now['mon'];
                $to['Y'] = $now['year'];
                $to['H'] = 23;
                $to['i'] = $to['s'] = 59;
                $from = self::intervalAdd('year', -$relativeTermSuffix, $to);
                $from = self::intervalAdd('second', 1, $from);
                break;

              case 'this':
                $from['d'] = $from['M'] = 1;
                $to['d'] = 31;
                $to['M'] = 12;
                $to['Y'] = $from['Y'] = $now['year'];
                if (is_numeric($relativeTermSuffix)) {
                  $from['Y'] -= ($relativeTermSuffix - 1);
                }
                break;
            }
            break;
        }
        break;

      case 'fiscal_year':
        $config = CRM_Core_Config::singleton();
        $from['d'] = $config->fiscalYearStart['d'];
        $from['M'] = $config->fiscalYearStart['M'];
        $fYear = self::calculateFiscalYear($from['d'], $from['M']);
        switch ($relativeTermPrefix) {
          case 'this':
            $from['Y'] = $fYear;
            $fiscalYear = mktime(0, 0, 0, $from['M'], $from['d'] - 1, $from['Y'] + 1);
            $fiscalEnd = explode('-', date("Y-m-d", $fiscalYear));
            $to['d'] = $fiscalEnd['2'];
            $to['M'] = $fiscalEnd['1'];
            $to['Y'] = $fiscalEnd['0'];
            $to['H'] = 23;
            $to['i'] = $to['s'] = 59;
            if (is_numeric($relativeTermSuffix)) {
              $from = self::intervalAdd('year', (-$relativeTermSuffix), $to);
              $from = self::intervalAdd('second', 1, $from);
            }
            break;

          case 'previous':
            if (!is_numeric($relativeTermSuffix)) {
              $from['Y'] = ($relativeTermSuffix === 'before') ? $fYear - 2 : $fYear - 1;
              $fiscalYear = mktime(0, 0, 0, $from['M'], $from['d'] - 1, $from['Y'] + 1);
              $fiscalEnd = explode('-', date("Y-m-d", $fiscalYear));
              $to['d'] = $fiscalEnd['2'];
              $to['M'] = $fiscalEnd['1'];
              $to['Y'] = $fiscalEnd['0'];
              $to['H'] = 23;
              $to['i'] = $to['s'] = 59;
            }
            else {
              $from['Y'] = $fYear - $relativeTermSuffix;
              $fiscalYear = mktime(0, 0, 0, $from['M'], $from['d'] - 1, $from['Y'] + 1);
              $fiscalEnd = explode('-', date("Y-m-d", $fiscalYear));
              $to['d'] = $fiscalEnd['2'];
              $to['M'] = $fiscalEnd['1'];
              $to['Y'] = $fYear;
              $to['H'] = 23;
              $to['i'] = $to['s'] = 59;
            }
            break;

          case 'next':
            $from['Y'] = $fYear + 1;
            $fiscalYear = mktime(0, 0, 0, $from['M'], $from['d'] - 1, $from['Y'] + 1);
            $fiscalEnd = explode('-', date("Y-m-d", $fiscalYear));
            $to['d'] = $fiscalEnd['2'];
            $to['M'] = $fiscalEnd['1'];
            $to['Y'] = $fiscalEnd['0'];
            break;
        }
        break;

      case 'quarter':
        switch ($relativeTerm) {
          case 'this':

            $quarter = ceil($now['mon'] / 3);
            $from['d'] = 1;
            $from['M'] = (3 * $quarter) - 2;
            $to['M'] = 3 * $quarter;
            $to['Y'] = $from['Y'] = $now['year'];
            $to['d'] = date('t', mktime(0, 0, 0, $to['M'], 1, $now['year']));
            break;

          case 'previous':
            $difference = 1;
            $quarter = ceil($now['mon'] / 3);
            $quarter = $quarter - $difference;
            $subtractYear = 0;
            if ($quarter <= 0) {
              $subtractYear = 1;
              $quarter += 4;
            }
            $from['d'] = 1;
            $from['M'] = (3 * $quarter) - 2;
            $to['M'] = 3 * $quarter;
            $to['Y'] = $from['Y'] = $now['year'] - $subtractYear;
            $to['d'] = date('t', mktime(0, 0, 0, $to['M'], 1, $to['Y']));
            break;

          case 'previous_before':
            $difference = 2;
            $quarter = ceil($now['mon'] / 3);
            $quarter = $quarter - $difference;
            $subtractYear = 0;
            if ($quarter <= 0) {
              $subtractYear = 1;
              $quarter += 4;
            }
            $from['d'] = 1;
            $from['M'] = (3 * $quarter) - 2;
            $to['M'] = 3 * $quarter;
            $to['Y'] = $from['Y'] = $now['year'] - $subtractYear;
            $to['d'] = date('t', mktime(0, 0, 0, $to['M'], 1, $to['Y']));
            break;

          case 'previous_2':
            $difference = 2;
            $quarter = ceil($now['mon'] / 3);
            $current_quarter = $quarter;
            $quarter = $quarter - $difference;
            $subtractYear = 0;
            if ($quarter <= 0) {
              $subtractYear = 1;
              $quarter += 4;
            }
            $from['d'] = 1;
            $from['M'] = (3 * $quarter) - 2;
            switch ($current_quarter) {
              case 1:
                $to['M'] = (4 * $quarter);
                break;

              case 2:
                $to['M'] = (4 * $quarter) + 3;
                break;

              case 3:
                $to['M'] = (4 * $quarter) + 2;
                break;

              case 4:
                $to['M'] = (4 * $quarter) + 1;
                break;
            }
            $to['Y'] = $from['Y'] = $now['year'] - $subtractYear;
            if ($to['M'] > 12) {
              $to['M'] = 3 * ($quarter - 3);
              $to['Y'] = $now['year'];
            }
            $to['d'] = date('t', mktime(0, 0, 0, $to['M'], 1, $to['Y']));
            break;

          case 'earlier':
            $quarter = ceil($now['mon'] / 3) - 1;
            $subtractYear = 0;
            if ($quarter <= 0) {
              $subtractYear = 1;
              $quarter += 4;
            }
            $to['M'] = 3 * $quarter;
            $to['Y'] = $from['Y'] = $now['year'] - $subtractYear;
            $to['d'] = date('t', mktime(0, 0, 0, $to['M'], 1, $to['Y']));
            unset($from);
            break;

          case 'greater':
            $quarter = ceil($now['mon'] / 3);
            $from['d'] = 1;
            $from['M'] = (3 * $quarter) - 2;
            $from['Y'] = $now['year'];
            unset($to);
            break;

          case 'greater_previous':
            $quarter = ceil($now['mon'] / 3) - 1;
            $subtractYear = 0;
            if ($quarter <= 0) {
              $subtractYear = 1;
              $quarter += 4;
            }
            $from['M'] = 3 * $quarter;
            $from['Y'] = $from['Y'] = $now['year'] - $subtractYear;
            $from['d'] = date('t', mktime(0, 0, 0, $from['M'], 1, $from['Y']));
            unset($to);
            break;

          case 'ending':
            $to['d'] = $now['mday'];
            $to['M'] = $now['mon'];
            $to['Y'] = $now['year'];
            $to['H'] = 23;
            $to['i'] = $to['s'] = 59;
            $from = self::intervalAdd('day', -90, $to);
            $from = self::intervalAdd('second', 1, $from);
            break;

          case 'current':
            $quarter = ceil($now['mon'] / 3);
            $from['d'] = 1;
            $from['M'] = (3 * $quarter) - 2;
            $from['Y'] = $now['year'];
            $to['d'] = $now['mday'];
            $to['M'] = $now['mon'];
            $to['Y'] = $now['year'];
            $to['H'] = 23;
            $to['i'] = $to['s'] = 59;
            break;

          case 'less':
            $quarter = ceil($now['mon'] / 3);
            $to['M'] = 3 * $quarter;
            $to['Y'] = $now['year'];
            $to['d'] = date('t', mktime(0, 0, 0, $to['M'], 1, $now['year']));
            unset($from);
            break;

          case 'next':
            $difference = -1;
            $subtractYear = 0;
            $quarter = ceil($now['mon'] / 3);
            $quarter = $quarter - $difference;
            // CRM-14550 QA Fix
            if ($quarter > 4) {
              $now['year'] = $now['year'] + 1;
              $quarter = 1;
            }
            if ($quarter <= 0) {
              $subtractYear = 1;
              $quarter += 4;
            }
            $from['d'] = 1;
            $from['M'] = (3 * $quarter) - 2;
            $to['M'] = 3 * $quarter;
            $to['Y'] = $from['Y'] = $now['year'] - $subtractYear;
            $to['d'] = date('t', mktime(0, 0, 0, $to['M'], 1, $to['Y']));
            break;

          case 'starting':
            $from['d'] = $now['mday'];
            $from['M'] = $now['mon'];
            $from['Y'] = $now['year'];
            $from['H'] = 00;
            $from['i'] = $to['s'] = 00;
            $to = self::intervalAdd('day', 90, $from);
            $to = self::intervalAdd('second', -1, $to);
            break;

          default:
            if ($relativeTermPrefix === 'ending') {
              $to['d'] = $now['mday'];
              $to['M'] = $now['mon'];
              $to['Y'] = $now['year'];
              $to['H'] = 23;
              $to['i'] = $to['s'] = 59;
              $from = self::intervalAdd('month', -($relativeTermSuffix * 3), $to);
              $from = self::intervalAdd('second', 1, $from);
            }
        }
        break;

      case 'month':
        switch ($relativeTerm) {
          case 'this':
            $from['d'] = 1;
            $to['d'] = date('t', mktime(0, 0, 0, $now['mon'], 1, $now['year']));
            $from['M'] = $to['M'] = $now['mon'];
            $from['Y'] = $to['Y'] = $now['year'];
            break;

          case 'previous':
            $from['d'] = 1;
            if ($now['mon'] == 1) {
              $from['M'] = $to['M'] = 12;
              $from['Y'] = $to['Y'] = $now['year'] - 1;
            }
            else {
              $from['M'] = $to['M'] = $now['mon'] - 1;
              $from['Y'] = $to['Y'] = $now['year'];
            }
            $to['d'] = date('t', mktime(0, 0, 0, $to['M'], 1, $to['Y']));
            break;

          case 'previous_before':
            $from['d'] = 1;
            if ($now['mon'] < 3) {
              $from['M'] = $to['M'] = 10 + $now['mon'];
              $from['Y'] = $to['Y'] = $now['year'] - 1;
            }
            else {
              $from['M'] = $to['M'] = $now['mon'] - 2;
              $from['Y'] = $to['Y'] = $now['year'];
            }
            $to['d'] = date('t', mktime(0, 0, 0, $to['M'], 1, $to['Y']));
            break;

          case 'previous_2':
            $from['d'] = 1;
            if ($now['mon'] < 3) {
              $from['M'] = 10 + $now['mon'];
              $from['Y'] = $now['year'] - 1;
            }
            else {
              $from['M'] = $now['mon'] - 2;
              $from['Y'] = $now['year'];
            }

            if ($now['mon'] == 1) {
              $to['M'] = 12;
              $to['Y'] = $now['year'] - 1;
            }
            else {
              $to['M'] = $now['mon'] - 1;
              $to['Y'] = $now['year'];
            }

            $to['d'] = date('t', mktime(0, 0, 0, $to['M'], 1, $to['Y']));
            break;

          case 'earlier':
            // before end of past month
            if ($now['mon'] == 1) {
              $to['M'] = 12;
              $to['Y'] = $now['year'] - 1;
            }
            else {
              $to['M'] = $now['mon'] - 1;
              $to['Y'] = $now['year'];
            }

            $to['d'] = date('t', mktime(0, 0, 0, $to['M'], 1, $to['Y']));
            unset($from);
            break;

          case 'greater':
            $from['d'] = 1;
            $from['M'] = $now['mon'];
            $from['Y'] = $now['year'];
            unset($to);
            break;

          case 'greater_previous':
            // from end of past month
            if ($now['mon'] == 1) {
              $from['M'] = 12;
              $from['Y'] = $now['year'] - 1;
            }
            else {
              $from['M'] = $now['mon'] - 1;
              $from['Y'] = $now['year'];
            }

            $from['d'] = date('t', mktime(0, 0, 0, $from['M'], 1, $from['Y']));
            unset($to);
            break;

          case 'ending_2':
            $to['d'] = $now['mday'];
            $to['M'] = $now['mon'];
            $to['Y'] = $now['year'];
            $to['H'] = 23;
            $to['i'] = $to['s'] = 59;
            $from = self::intervalAdd('day', -60, $to);
            $from = self::intervalAdd('second', 1, $from);
            break;

          case 'ending':
            $to['d'] = $now['mday'];
            $to['M'] = $now['mon'];
            $to['Y'] = $now['year'];
            $to['H'] = 23;
            $to['i'] = $to['s'] = 59;
            $from = self::intervalAdd('day', -30, $to);
            $from = self::intervalAdd('second', 1, $from);
            break;

          case 'current':
            $from['d'] = 1;
            $from['M'] = $now['mon'];
            $from['Y'] = $now['year'];
            $to['d'] = $now['mday'];
            $to['M'] = $now['mon'];
            $to['Y'] = $now['year'];
            $to['H'] = 23;
            $to['i'] = $to['s'] = 59;
            break;

          case 'less':
            // CRM-14550 QA Fix
            $to['Y'] = $now['year'];
            $to['M'] = $now['mon'];
            $to['d'] = date('t', mktime(0, 0, 0, $now['mon'], 1, $now['year']));
            unset($from);
            break;

          case 'next':
            $from['d'] = 1;
            if ($now['mon'] == 12) {
              $from['M'] = $to['M'] = 1;
              $from['Y'] = $to['Y'] = $now['year'] + 1;
            }
            else {
              $from['M'] = $to['M'] = $now['mon'] + 1;
              $from['Y'] = $to['Y'] = $now['year'];
            }
            $to['d'] = date('t', mktime(0, 0, 0, $to['M'], 1, $to['Y']));
            break;

          case 'starting':
            $from['d'] = $now['mday'];
            $from['M'] = $now['mon'];
            $from['Y'] = $now['year'];
            $from['H'] = 00;
            $from['i'] = $to['s'] = 00;
            $to = self::intervalAdd('day', 30, $from);
            $to = self::intervalAdd('second', -1, $to);
            break;

          case 'starting_2':
            $from['d'] = $now['mday'];
            $from['M'] = $now['mon'];
            $from['Y'] = $now['year'];
            $from['H'] = 00;
            $from['i'] = $to['s'] = 00;
            $to = self::intervalAdd('day', 60, $from);
            $to = self::intervalAdd('second', -1, $to);
            break;

          default:
            if ($relativeTermPrefix === 'ending') {
              $to['d'] = $now['mday'];
              $to['M'] = $now['mon'];
              $to['Y'] = $now['year'];
              $to['H'] = 23;
              $to['i'] = $to['s'] = 59;
              $from = self::intervalAdd($unit, -$relativeTermSuffix, $to);
              $from = self::intervalAdd('second', 1, $from);
            }
        }
        break;

      case 'week':
        $weekFirst = Civi::settings()->get('weekBegins');
        $thisDay = $now['wday'];
        if ($weekFirst > $thisDay) {
          $diffDay = $thisDay - $weekFirst + 7;
        }
        else {
          $diffDay = $thisDay - $weekFirst;
        }
        switch ($relativeTerm) {
          case 'this':
            $from['d'] = $now['mday'];
            $from['M'] = $now['mon'];
            $from['Y'] = $now['year'];
            $from = self::intervalAdd('day', -1 * ($diffDay), $from);
            $to = self::intervalAdd('day', 6, $from);
            break;

          case 'previous':
            $from['d'] = $now['mday'];
            $from['M'] = $now['mon'];
            $from['Y'] = $now['year'];
            $from = self::intervalAdd('day', -1 * ($diffDay) - 7, $from);
            $to = self::intervalAdd('day', 6, $from);
            break;

          case 'previous_before':
            $from['d'] = $now['mday'];
            $from['M'] = $now['mon'];
            $from['Y'] = $now['year'];
            $from = self::intervalAdd('day', -1 * ($diffDay) - 14, $from);
            $to = self::intervalAdd('day', 6, $from);
            break;

          case 'previous_2':
            $from['d'] = $now['mday'];
            $from['M'] = $now['mon'];
            $from['Y'] = $now['year'];
            $from = self::intervalAdd('day', -1 * ($diffDay) - 14, $from);
            $to = self::intervalAdd('day', 13, $from);
            break;

          case 'earlier':
            $to['d'] = $now['mday'];
            $to['M'] = $now['mon'];
            $to['Y'] = $now['year'];
            $to = self::intervalAdd('day', -1 * ($diffDay) - 1, $to);
            unset($from);
            break;

          case 'greater':
            $from['d'] = $now['mday'];
            $from['M'] = $now['mon'];
            $from['Y'] = $now['year'];
            $from = self::intervalAdd('day', -1 * ($diffDay), $from);
            unset($to);
            break;

          case 'greater_previous':
            $from['d'] = $now['mday'];
            $from['M'] = $now['mon'];
            $from['Y'] = $now['year'];
            $from = self::intervalAdd('day', -1 * ($diffDay) - 1, $from);
            unset($to);
            break;

          case 'ending':
            $to['d'] = $now['mday'];
            $to['M'] = $now['mon'];
            $to['Y'] = $now['year'];
            $to['H'] = 23;
            $to['i'] = $to['s'] = 59;
            $from = self::intervalAdd('day', -7, $to);
            $from = self::intervalAdd('second', 1, $from);
            break;

          case 'current':
            $from['d'] = $now['mday'];
            $from['M'] = $now['mon'];
            $from['Y'] = $now['year'];
            $from = self::intervalAdd('day', -1 * ($diffDay), $from);
            $to['d'] = $now['mday'];
            $to['M'] = $now['mon'];
            $to['Y'] = $now['year'];
            $to['H'] = 23;
            $to['i'] = $to['s'] = 59;
            break;

          case 'less':
            $to['d'] = $now['mday'];
            $to['M'] = $now['mon'];
            $to['Y'] = $now['year'];
            // CRM-14550 QA Fix
            $to = self::intervalAdd('day', -1 * ($diffDay) + 6, $to);
            unset($from);
            break;

          case 'next':
            $from['d'] = $now['mday'];
            $from['M'] = $now['mon'];
            $from['Y'] = $now['year'];
            $from = self::intervalAdd('day', -1 * ($diffDay) + 7, $from);
            $to = self::intervalAdd('day', 6, $from);
            break;

          case 'starting':
            $from['d'] = $now['mday'];
            $from['M'] = $now['mon'];
            $from['Y'] = $now['year'];
            $from['H'] = 00;
            $from['i'] = $to['s'] = 00;
            $to = self::intervalAdd('day', 7, $from);
            $to = self::intervalAdd('second', -1, $to);
            break;

          default:
            if ($relativeTermPrefix === 'ending') {
              $to['d'] = $now['mday'];
              $to['M'] = $now['mon'];
              $to['Y'] = $now['year'];
              $to['H'] = 23;
              $to['i'] = $to['s'] = 59;
              $from = self::intervalAdd($unit, -$relativeTermSuffix, $to);
              $from = self::intervalAdd('second', 1, $from);
            }
        }
        break;

      case 'day':
        switch ($relativeTerm) {
          case 'this':
            $from['d'] = $to['d'] = $now['mday'];
            $from['M'] = $to['M'] = $now['mon'];
            $from['Y'] = $to['Y'] = $now['year'];
            break;

          case 'previous':
            $from['d'] = $now['mday'];
            $from['M'] = $now['mon'];
            $from['Y'] = $now['year'];
            $from = self::intervalAdd('day', -1, $from);
            $to['d'] = $from['d'];
            $to['M'] = $from['M'];
            $to['Y'] = $from['Y'];
            break;

          case 'previous_before':
            $from['d'] = $now['mday'];
            $from['M'] = $now['mon'];
            $from['Y'] = $now['year'];
            $from = self::intervalAdd('day', -2, $from);
            $to['d'] = $from['d'];
            $to['M'] = $from['M'];
            $to['Y'] = $from['Y'];
            break;

          case 'previous_2':
            $from['d'] = $to['d'] = $now['mday'];
            $from['M'] = $to['M'] = $now['mon'];
            $from['Y'] = $to['Y'] = $now['year'];
            $from = self::intervalAdd('day', -2, $from);
            $to = self::intervalAdd('day', -1, $to);
            break;

          case 'earlier':
            $to['d'] = $now['mday'];
            $to['M'] = $now['mon'];
            $to['Y'] = $now['year'];
            $to = self::intervalAdd('day', -1, $to);
            unset($from);
            break;

          case 'greater':
            $from['d'] = $now['mday'];
            $from['M'] = $now['mon'];
            $from['Y'] = $now['year'];
            unset($to);
            break;

          case 'starting':
            $to['d'] = $now['mday'];
            $to['M'] = $now['mon'];
            $to['Y'] = $now['year'];
            $to = self::intervalAdd('day', 1, $to);
            $from['d'] = $to['d'];
            $from['M'] = $to['M'];
            $from['Y'] = $to['Y'];
            break;

          default:
            if ($relativeTermPrefix === 'ending') {
              $to['d'] = $now['mday'];
              $to['M'] = $now['mon'];
              $to['Y'] = $now['year'];
              $to['H'] = 23;
              $to['i'] = $to['s'] = 59;
              $from = self::intervalAdd($unit, -$relativeTermSuffix, $to);
              $from = self::intervalAdd('second', 1, $from);
            }
        }
        break;
    }

    $dateRange['from'] = empty($from) ? NULL : self::format($from);
    $dateRange['to'] = empty($to) ? NULL : self::format($to);
    return $dateRange;
  }

  /**
   * Calculate current fiscal year based on the fiscal month and day.
   *
   * @param int $fyDate
   *   Fiscal start date.
   *
   * @param int $fyMonth
   *   Fiscal Start Month.
   *
   * @return int
   *   $fy       Current Fiscal Year
   */
  public static function calculateFiscalYear($fyDate, $fyMonth) {
    $date = date("Y-m-d");
    $currentYear = date("Y");

    // recalculate the date because month 4::04 make the difference
    $fiscalYear = explode('-', date("Y-m-d", mktime(0, 0, 0, $fyMonth, $fyDate, $currentYear)));
    $fyDate = $fiscalYear[2];
    $fyMonth = $fiscalYear[1];
    $fyStartDate = date("Y-m-d", mktime(0, 0, 0, $fyMonth, $fyDate, $currentYear));

    if ($fyStartDate > $date) {
      $fy = intval(intval($currentYear) - 1);
    }
    else {
      $fy = intval($currentYear);
    }
    return $fy;
  }

  /**
   * Function to process date, convert to mysql format
   *
   * @param string $date
   *   Date string.
   * @param string $time
   *   Time string.
   * @param bool|string $returnNullString 'null' needs to be returned
   *                so that db oject will set null in db
   * @param string $format
   *   Expected return date format.( default is mysql ).
   *
   * @return string
   *   date format that is excepted by mysql
   */
  public static function processDate($date, $time = NULL, $returnNullString = FALSE, $format = 'YmdHis') {
    $mysqlDate = NULL;

    if ($returnNullString) {
      $mysqlDate = 'null';
    }

    if (trim($date ?? '')) {
      $mysqlDate = date($format, strtotime($date . ' ' . $time));
    }

    return $mysqlDate;
  }

  /**
   * Add the metadata about a date field to the field.
   *
   * This metadata will work with the call $form->add('datepicker', ...
   *
   * @param array $fieldMetaData
   * @param array $field
   *
   * @return array
   */
  public static function addDateMetadataToField($fieldMetaData, $field) {
    if (isset($fieldMetaData['html'])) {
      $field['html_type'] = $fieldMetaData['html']['type'];
      if ($field['html_type'] === 'Select Date') {
        if (!isset($field['date_format'])) {
          $dateAttributes = CRM_Core_SelectValues::date($fieldMetaData['html']['formatType'], NULL, NULL, NULL, 'Input');
          $field['start_date_years'] = $dateAttributes['minYear'];
          $field['end_date_years'] = $dateAttributes['maxYear'];
          $field['date_format'] = $dateAttributes['format'];
          $field['is_datetime_field'] = TRUE;
          $field['time_format'] = $dateAttributes['time'];
          $field['smarty_view_format'] = $dateAttributes['smarty_view_format'];
        }
        $field['datepicker']['extra'] = self::getDatePickerExtra($field);
        $field['datepicker']['attributes'] = self::getDatePickerAttributes($field);
      }
    }
    return $field;
  }

  /**
   * Get the fields required for the 'extra' parameter when adding a datepicker.
   *
   * @param array $field
   *
   * @return array
   */
  public static function getDatePickerExtra($field) {
    $extra = [];
    if (isset($field['date_format'])) {
      $extra['date'] = $field['date_format'];
      $extra['time'] = $field['time_format'];
    }
    $thisYear = date('Y');
    if (isset($field['start_date_years'])) {
      $extra['minDate'] = date('Y-m-d', strtotime('-' . ($thisYear - $field['start_date_years']) . ' years'));
    }
    if (isset($field['end_date_years'])) {
      $extra['maxDate'] = date('Y-m-d', strtotime('-' . ($thisYear - $field['end_date_years']) . ' years'));
    }
    return $extra;
  }

  /**
   * Get the attributes parameters required for datepicker.
   *
   * @param array $field
   *   Field metadata
   *
   * @return array
   *   Array ready to pass to $this->addForm('datepicker' as attributes.
   */
  public static function getDatePickerAttributes(&$field) {
    $attributes = [];
    $dateAttributes = [
      'start_date_years' => 'minYear',
      'end_date_years' => 'maxYear',
      'date_format' => 'format',
    ];
    foreach ($dateAttributes as $dateAttribute => $mapTo) {
      if (isset($field[$dateAttribute])) {
        $attributes[$mapTo] = $field[$dateAttribute];
      }
    }
    return $attributes;
  }

  /**
   * Function to convert mysql to date plugin format.
   *
   * @param string $mysqlDate
   *   Date string.
   *
   * @param null $formatType
   * @param null $format
   * @param null $timeFormat
   *
   * @return array
   *   and time
   */
  public static function setDateDefaults($mysqlDate = NULL, $formatType = NULL, $format = NULL, $timeFormat = NULL) {
    // if date is not passed assume it as today
    if (!$mysqlDate) {
      $mysqlDate = date('Y-m-d G:i:s');
    }

    $config = CRM_Core_Config::singleton();
    if ($formatType) {
      // get actual format
      $params = ['name' => $formatType];
      $values = [];
      CRM_Core_DAO::commonRetrieve('CRM_Core_DAO_PreferencesDate', $params, $values);

      if ($values['date_format']) {
        $format = $values['date_format'];
      }

      if (isset($values['time_format'])) {
        $timeFormat = $values['time_format'];
      }
    }

    $dateFormat = 'm/d/Y';
    $date = date($dateFormat, strtotime($mysqlDate));

    if (!$timeFormat) {
      $timeFormat = $config->timeInputFormat;
    }

    $actualTimeFormat = "g:iA";
    $appendZeroLength = 7;
    if ($timeFormat > 1) {
      $actualTimeFormat = "G:i";
      $appendZeroLength = 5;
    }

    $time = date($actualTimeFormat, strtotime($mysqlDate));

    // need to append zero for hours < 10
    if (strlen($time) < $appendZeroLength) {
      $time = '0' . $time;
    }

    return [$date, $time];
  }

  /**
   * Function get date format.
   *
   * @param string $formatType
   *   Date name e.g. birth.
   *
   * @return string
   */
  public static function getDateFormat($formatType = NULL) {
    $format = NULL;
    if ($formatType) {
      $format = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_PreferencesDate',
        $formatType, 'date_format', 'name'
      );
    }

    if (!$format) {
      $config = CRM_Core_Config::singleton();
      $format = $config->dateInputFormat;
    }
    return $format;
  }

  /**
   * Date formatting for imports where date format is specified.
   *
   * Note this is used for imports (only) because the importer can
   * specify the format.
   *
   * Tests are in CRM_Utils_DateTest::testFormatDate
   *
   * @param $date
   *   Date string as entered.
   * @param $dateType
   *   One of the constants like CRM_Core_Form_Date::DATE_yyyy_mm_dd.
   *
   * @return null|string
   */
  public static function formatDate($date, $dateType) {
    if (empty($date)) {
      return NULL;
    }

    // 1. first convert date to default format.
    // 2. append time to default formatted date (might be removed during format)
    // 3. validate date / date time.
    // 4. If date and time then convert to default date time format.

    $dateKey = 'date';
    $dateParams = [$dateKey => $date];

    if (CRM_Utils_Date::convertToDefaultDate($dateParams, $dateType, $dateKey)) {
      $dateVal = $dateParams[$dateKey];
      if ($dateType == 1) {
        $matches = [];
        // The seconds part of this regex is not quite right - but it does succeed
        // in clarifying whether there is a time component or not - which is all it is meant
        // to do.
        if (preg_match('/(\s(([01]\d)|[2][0-3]):([0-5]\d):?[0-5]?\d?)$/', $date, $matches)) {
          if (strpos($date, '-') !== FALSE) {
            $dateVal .= array_shift($matches);
          }
          if (!CRM_Utils_Rule::dateTime($dateVal)) {
            return NULL;
          }
          $dateVal = CRM_Utils_Date::customFormat(preg_replace("/(:|\s)?/", '', $dateVal), '%Y%m%d%H%i%s');
          return $dateVal;
        }
      }

      // validate date.
      return CRM_Utils_Rule::date($dateVal) ? $dateVal : NULL;
    }

    return NULL;
  }

  /**
   * Function to return days of the month.
   *
   * @return array
   */
  public static function getCalendarDayOfMonth() {
    $month = [];
    for ($i = 1; $i <= 31; $i++) {
      $month[$i] = $i;
      if ($i == 31) {
        $month[$i] = $i . ' / Last day of month';
      }
    }
    return $month;
  }

  /**
   * Convert a relative date format to an api field.
   *
   * @param array $params
   * @param string $dateField
   * @param bool $isDatePicker
   *   Non datepicker fields are deprecated. Exterminate Exterminate.
   *   (but for now handle them).
   */
  public static function convertFormDateToApiFormat(&$params, $dateField, $isDatePicker = TRUE) {
    if (!empty($params[$dateField . '_relative'])) {
      $dates = CRM_Utils_Date::getFromTo($params[$dateField . '_relative'], NULL, NULL);
      unset($params[$dateField . '_relative']);
    }
    if (!empty($params[$dateField . '_low'])) {
      $dates[0] = $isDatePicker ? $params[$dateField . '_low'] : date('Y-m-d H:i:s', strtotime($params[$dateField . '_low']));
      unset($params[$dateField . '_low']);
    }
    if (!empty($params[$dateField . '_high'])) {
      $dates[1] = $isDatePicker ? $params[$dateField . '_high'] : date('Y-m-d 23:59:59', strtotime($params[$dateField . '_high']));
      unset($params[$dateField . '_high']);
    }
    if (empty($dates)) {
      return;
    }
    if (empty($dates[0])) {
      $params[$dateField] = ['<=' => $dates[1]];
    }
    elseif (empty($dates[1])) {
      $params[$dateField] = ['>=' => $dates[0]];
    }
    else {
      $params[$dateField] = ['BETWEEN' => $dates];
    }
  }

  /**
   * Print out a date object in specified format in local timezone
   *
   * @param DateTimeObject $dateObject
   * @param string $format
   * @return string
   */
  public static function convertDateToLocalTime($dateObject, $format = 'YmdHis') {
    $systemTimeZone = new DateTimeZone(CRM_Core_Config::singleton()->userSystem->getTimeZoneString());
    $dateObject->setTimezone($systemTimeZone);
    return $dateObject->format($format);
  }

  /**
   * Check if the value returned by a date picker has a date section (ie: includes
   * a '-' character) if it includes a time section (ie: includes a ':').
   *
   * @param string $value
   *   A date/time string input from a datepicker value.
   *
   * @return bool
   *   TRUE if valid, FALSE if there is a time without a date.
   */
  public static function datePickerValueWithTimeHasDate($value) {
    // If there's no : (time) or a : and a - (date) then return true
    return (
      strpos($value, ':') === FALSE
      || strpos($value, ':') !== FALSE && strpos($value, '-') !== FALSE
    );
  }

  /**
   * Validate start and end dates entered on a form to make sure they are
   * logical. Expects the form keys to be start_date and end_date.
   *
   * @param string $startFormKey
   *   The form element key of the 'start date'
   * @param string $startValue
   *   The value of the 'start date'
   * @param string $endFormKey
   *   The form element key of the 'end date'
   * @param string $endValue
   * The value of the 'end date'
   *
   * @return array|bool
   *   TRUE if valid, an array of the erroneous form key, and error message to
   *   use otherwise.
   */
  public static function validateStartEndDatepickerInputs($startFormKey, $startValue, $endFormKey, $endValue) {

    // Check date as well as time is set
    if (!empty($startValue) && !self::datePickerValueWithTimeHasDate($startValue)) {
      return ['key' => $startFormKey, 'message' => ts('Please enter a date as well as a time.')];
    }
    if (!empty($endValue) && !self::datePickerValueWithTimeHasDate($endValue)) {
      return ['key' => $endFormKey, 'message' => ts('Please enter a date as well as a time.')];
    }

    // Check end date is after start date
    if (!empty($startValue) && !empty($endValue) && $endValue < $startValue) {
      return ['key' => $endFormKey, 'message' => ts('The end date should be after the start date.')];
    }

    return TRUE;
  }

}
