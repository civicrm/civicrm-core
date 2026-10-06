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
   * Date input formats.
   *
   * For example a user selecting `DATE_dd_mm_yyyy` in the context of an import is
   * saying that they want the dates they are importing to be converted from dd_mm_yyy format.
   */
  public const DATE_yyyy_mm_dd = 1, DATE_mm_dd_yy = 2, DATE_mm_dd_yyyy = 4, DATE_Month_dd_yyyy = 8, DATE_dd_mon_yy = 16, DATE_dd_mm_yyyy = 32;

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
    if (!empty($date['H']) ||
      !empty($date['h']) ||
      !empty($date['i']) ||
      !empty($date['s'])
    ) {
      // we have time too..
      if (!empty($date['h'])) {
        if (($date['A'] ?? NULL) == 'PM' or ($date['a'] ?? NULL) == 'pm') {
          if ($date['h'] != 12) {
            $date['h'] = $date['h'] + 12;
          }
        }
        if ((($date['A'] ?? NULL) == 'AM' or ($date['a'] ?? NULL) == 'am') &&
          ($date['h'] ?? NULL) == 12
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
  public static function getAbbrWeekdayNames(): array {
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
  public static function getFullWeekdayNames(): array {
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
   * Get the available input formats.
   *
   * These are the formats that this class is able to convert into a standard format
   * provided it knows the input format. These are used when doing an import.
   *
   * @param bool $isShowTime
   *
   * @return array
   */
  public static function getAvailableInputFormats(bool $isShowTime): array {
    if ($isShowTime) {
      $dateText = ts('yyyy-mm-dd OR yyyy-mm-dd HH:mm OR yyyymmdd OR yyyymmdd HH:mm (1998-12-25 OR 1998-12-25 15:33 OR 19981225 OR 19981225 10:30 OR ( 2008-9-1 OR 2008-9-1 15:33 OR 20080901 15:33)');
    }
    else {
      $dateText = ts('yyyy-mm-dd OR yyyymmdd (1998-12-25 OR 19981225) OR (2008-9-1 OR 20080901)');
    }
    return [
      CRM_Utils_Date::DATE_yyyy_mm_dd => $dateText,
      CRM_Utils_Date::DATE_mm_dd_yy => ts('mm/dd/yy OR mm-dd-yy (12/25/98 OR 12-25-98) OR (9/1/08 OR 9-1-08)'),
      CRM_Utils_Date::DATE_mm_dd_yyyy => ts('mm/dd/yyyy OR mm-dd-yyyy (12/25/1998 OR 12-25-1998) OR (9/1/2008 OR 9-1-2008)'),
      CRM_Utils_Date::DATE_Month_dd_yyyy => ts('Month dd, yyyy (December 12, 1998)'),
      CRM_Utils_Date::DATE_dd_mon_yy => ts('dd-mon-yy OR dd/mm/yy (25-Dec-98 OR 25/12/98 OR 1-09-98)'),
      CRM_Utils_Date::DATE_dd_mm_yyyy => ts('dd/mm/yyyy (25/12/1998 OR 1/9/2008 OR 1-Sep-2008 or 25.12.1998 or 25-12-1998)'),
    ];
  }

  /**
   * Return abbreviated month names according to the locale.
   *
   * @param bool|string $month (deprecated)
   *
   * @return array|string
   *   1-based array with abbreviated month names
   */
  public static function getAbbrMonthNames($month = FALSE) {
    $key = 'abbrMonthNames_' . \CRM_Core_I18n::getLocale();
    if (empty(\Civi::$statics[__CLASS__][$key])) {
      // Note: IntlDateFormatter provides even more strings than `strftime()` or `l10n/*/civicrm.mo`.
      // Note: Consistently use UTC for all requests in resolving these names. Avoid edge-cases where TZ support is inconsistent.
      $intlFormatter = IntlDateFormatter::create(CRM_Core_I18n::getLocale(), IntlDateFormatter::MEDIUM, IntlDateFormatter::MEDIUM, 'UTC', IntlDateFormatter::GREGORIAN, 'MMM');
      $monthNums = range(1, 12);
      \Civi::$statics[__CLASS__][$key] = array_combine($monthNums, array_map(
        function(int $monthNum) use ($intlFormatter) {
          return $intlFormatter->format(gmmktime(0, 0, 0, $monthNum, 1));
        },
        $monthNums
      ));
    }
    if ($month) {
      CRM_Core_Error::deprecatedWarning('passing in month is deprecated');
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
  public static function getFullMonthNames(): array {
    $key = 'fullMonthNames_' . \CRM_Core_I18n::getLocale();
    if (empty(\Civi::$statics[__CLASS__][$key])) {
      // Note: IntlDateFormatter provides even more strings than `strftime()` or `l10n/*/civicrm.mo`.
      // Note: Consistently use UTC for all requests in resolving these names. Avoid edge-cases where TZ support is inconsistent.
      $intlFormatter = IntlDateFormatter::create(CRM_Core_I18n::getLocale(), IntlDateFormatter::MEDIUM, IntlDateFormatter::MEDIUM, 'UTC', IntlDateFormatter::GREGORIAN, 'MMMM');
      $monthNums = range(1, 12);
      \Civi::$statics[__CLASS__][$key] = array_combine($monthNums, array_map(
        function(int $monthNum) use ($intlFormatter) {
          return $intlFormatter->format(gmmktime(0, 0, 0, $monthNum, 1));
        },
        $monthNums
      ));
    }

    return \Civi::$statics[__CLASS__][$key];
  }

  /**
   * @param string $string
   *
   * @return int
   */
  public static function unixTime($string) {
    if (!$string) {
      return 0;
    }
    $parsedDate = date_parse($string);
    return mktime($parsedDate['hour'],
       $parsedDate['minute'],
      59,
       $parsedDate['month'],
       $parsedDate['day'],
       $parsedDate['year']
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
   * Validate input date against the input type.
   *
   * @param string $inputValue
   * @param int $dateType
   *
   * @internal Function signature subject to change without notice.
   *
   * @return bool
   */
  protected static function validateDateInput(string $inputValue, int $dateType = self::DATE_yyyy_mm_dd): bool {
    // @todo - return these regex from the same function that returns the values in getAvailableInputFormats()
    // so they are defined together.
    // suppress hh:mm or hh:mm:ss if it exists CRM-7957
    // @todo - fix regex instead.
    $inputValue = preg_replace(self::getTimeRegex(), "", $inputValue);
    switch ($dateType) {
      case self::DATE_yyyy_mm_dd:
        // 4 numbers separated by - followed by 1-2 numbers, separated by -
        // followed by 1-2 numbers with optional time string.
        return preg_match('/^\d\d\d\d-?(\d|\d\d)-?(\d|\d\d)$/', $inputValue);

      case self::DATE_mm_dd_yy:
        return preg_match('/^(\d|\d\d)[-\/.](\d|\d\d)[-\/.]\d\d$/', $inputValue);

      case self::DATE_mm_dd_yyyy:
        return preg_match('/^(\d|\d\d)[-\/.](\d|\d\d)[-\/,]\d\d\d\d$/', $inputValue);

      case self::DATE_Month_dd_yyyy:
        $monthRegex = self::getMonthRegex();
        $regex = '/^' . $monthRegex . ',?\s?(\d|\d\d),?\s]?(\d\d|\d\d\d\d)$/i';
        return preg_match($regex, $inputValue);

      case self::DATE_dd_mon_yy:
        return preg_match('/^(\d|\d\d)-' . self::getMonthRegex() . '-\d\d$/i', $inputValue) || preg_match('/^(\d|\d\d)[-\/](\d|\d\d)[-\/]\d\d$/', $inputValue);

      case self::DATE_dd_mm_yyyy:
        return preg_match('/^(\d|\d\d)[-\/.](\d|\d\d)[-\/.]\d\d\d\d/', $inputValue);
    }
    return FALSE;
  }

  /**
   * Get the regex to extract the time portion.
   *
   * This is accessed when importing date fields from CSV files.
   *
   * The time could be in ISO8601 or just hyphen separated (preceded by a space)
   * - ie
   *
   * T13:15:30-01:00
   * T13:15:30+01:00
   * T13:15:30Z
   *  13:15:30
   *
   *
   * @see https://www.w3.org/TR/NOTE-datetime
   *
   * @internal
   *
   * @return string
   */
  protected static function getTimeRegex(): string {
    return "/([T\s](([01]*\d)|[2][0-3])(:([0-5]\d)){1,2}([+|-]\d{2}:\d{2}|Z)?)$/";
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
   * @param Date $startDate
   *   Start date for the range.
   * @param Date $endDate
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

    if (isset($mysqlStartDate, $mysqlEndDate) && (($mysqlToday >= $mysqlStartDate) && ($mysqlToday <= $mysqlEndDate))) {
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
      $dateRange = CRM_Utils_Hook::relativeDate($relative);
      if (!is_array($dateRange) || (empty($dateRange['from']) && empty($dateRange['to']))) {
        [$term, $unit] = array_pad(explode('.', $relative, 2), 2, NULL);
        $dateRange = self::relativeToAbsolute($term, $unit);
      }
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
   * @param Date $birthDate
   *   Birth Date.
   * @param Date $targetDate
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
   * Historically it was decided that where the view format is 'dd/mm/yy' or
   * 'mm/dd/yy' they should be rendered using a longer date format. This is
   * likely as much to do with the earlier date widget being unable to handle
   * some formats as usablity. However, we continue to respect this.
   *
   * @param $format
   *   Given format ( eg 'M Y', 'Y M' ).
   *
   * @return string|null
   *   Smarty translation of the date format. Null is also valid and is
   *   translated according to the available parts at the smarty layer.
   */
  public static function getDateFieldViewFormat($format) {
    $supportableFormats = [
      'mm/dd' => '%B %E',
      'dd-mm' => '%E %B',
      'yy-mm' => '%Y %B',
      'M yy' => '%b %Y',
      'yy' => '%Y',
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
   * Map date plugin format to the closest CiviCRM dateformat setting name.
   *
   * Mapping is imperfect but better than nothing. Used by SearchKit to format columns for custom Date fields.
   *
   * @param string $format
   *   Date plugin input format (e.g. 'mm/dd/yy', 'yy', 'M yy', 'DD, d MM yy')
   * @param int|null $timeFormat
   *   Time format (1 for 12hr, 2 for 24hr, 0/NULL for none)
   * @return string|null
   *   Setting name (e.g. 'dateformatshortdate', 'dateformatFull', 'dateformatPartial', 'dateformatYear', 'dateformatDatetime')
   */
  public static function datePluginToSetting(string $format, ?int $timeFormat): ?string {
    if (!empty($timeFormat)) {
      return 'dateformatDatetime';
    }
    if (str_starts_with($format, 'dateformat')) {
      return $format;
    }
    $map = [
      'yy' => 'dateformatYear',
      'yy-mm' => 'dateformatPartial',
      'M yy' => 'dateformatPartial',
      'M Y' => 'dateformatPartial',
      'M d' => 'dateformatFull',
      'M d, yy' => 'dateformatFull',
      'd M yy' => 'dateformatFull',
      'MM d, yy' => 'dateformatFull',
      'd MM yy' => 'dateformatFull',
      'DD, d MM yy' => 'dateformatFull',
      'mm/dd/yy' => 'dateformatshortdate',
      'dd/mm/yy' => 'dateformatshortdate',
      'yy-mm-dd' => 'dateformatshortdate',
      'dd-mm-yy' => 'dateformatshortdate',
      'dd.mm.yy' => 'dateformatshortdate',
      'mm/dd' => 'dateformatshortdate',
      'dd-mm' => 'dateformatshortdate',
    ];
    return $map[$format] ?? (str_contains($format, 'm') ? 'dateformatPartial' : 'dateformatFull');
  }

  /**
   * Helper to convert CiviCRM relative date filters into MySQL expressions.
   *
   * @param string $relativeTerm
   *   The relative term, e.g. 'this', 'previous', 'previous_2', 'ending_30', 'starting'.
   * @param string|null $unit
   *   The date unit, e.g. 'day', 'week', 'month', 'quarter', 'year', 'fiscal_year'.
   * @return array|null
   *   ['from' => startExpression, 'to' => endExpression] (elements may be NULL for open-ended ranges).
   */
  public static function relativeToSql(string $relativeTerm, ?string $unit = NULL): ?array {
    if ($unit === NULL && str_contains($relativeTerm, '.')) {
      [$relativeTerm, $unit] = explode('.', $relativeTerm, 2);
    }

    // Allow extensions to customize relative dates
    $hookSql = ['from' => NULL, 'to' => NULL];
    CRM_Utils_Hook::relativeDateSql($relativeTerm, $unit, $hookSql);
    if (array_filter($hookSql)) {
      return $hookSql;
    }

    $unit = strtolower($unit ?? '');
    $intervalUnit = match ($unit) {
      'day' => 'DAY',
      'week' => 'WEEK',
      'month' => 'MONTH',
      'quarter' => 'QUARTER',
      'year', 'fiscal_year' => 'YEAR',
      default => NULL,
    };
    if (!$intervalUnit) {
      return NULL;
    }

    $mysqlWeekStart = (\Civi::settings()->get('weekBegins') ?? 0) + 1;
    $fyStartSetting = \CRM_Core_Config::singleton()->fiscalYearStart;
    $fyMonth = (int) ($fyStartSetting['M'] ?? 1);
    $fyDay = (int) ($fyStartSetting['d'] ?? 1);
    $fyThisYear = "STR_TO_DATE(CONCAT(YEAR(CURDATE()), '-$fyMonth-$fyDay'), '%Y-%m-%d')";

    $startExpr = match ($unit) {
      'day' => 'CURDATE()',
      'week' => "DATE_SUB(CURDATE(), INTERVAL MOD(DAYOFWEEK(CURDATE()) - $mysqlWeekStart + 7, 7) DAY)",
      'month' => "DATE_FORMAT(CURDATE(), '%Y-%m-01')",
      'quarter' => "DATE_ADD(MAKEDATE(YEAR(CURDATE()), 1), INTERVAL (QUARTER(CURDATE()) - 1) * 3 MONTH)",
      'year' => "DATE_FORMAT(CURDATE(), '%Y-01-01')",
      'fiscal_year' => "IF(CURDATE() >= $fyThisYear, $fyThisYear, STR_TO_DATE(CONCAT(YEAR(CURDATE()) - 1, '-$fyMonth-$fyDay'), '%Y-%m-%d'))",
    };

    $action = match ($relativeTerm) {
      'previous_before' => 'previous_before',
      default => preg_replace('/_\d+$/', '', $relativeTerm),
    };
    $count = match ($relativeTerm) {
      'previous_before' => 1,
      default => (int) (explode('_', $relativeTerm)[1] ?? 1),
    };

    $endingFrom = match (TRUE) {
      $unit === 'day' => "DATE_SUB(CURDATE(), INTERVAL " . ($count - 1) . " DAY)",
      $relativeTerm === 'ending' && $unit === 'month' => "DATE_SUB(CURDATE(), INTERVAL 29 DAY)",
      $relativeTerm === 'ending' && $unit === 'quarter' => "DATE_SUB(CURDATE(), INTERVAL 89 DAY)",
      $relativeTerm === 'ending' && $unit === 'week' => "DATE_SUB(CURDATE(), INTERVAL 6 DAY)",
      default => "DATE_SUB(CURDATE() + INTERVAL 1 DAY, INTERVAL $count $intervalUnit)",
    };

    $startingTo = match (TRUE) {
      $unit === 'day' => "CURDATE() + INTERVAL 2 DAY - INTERVAL 1 SECOND",
      $unit === 'week' => "DATE_ADD(CURDATE(), INTERVAL " . ($count * 7) . " DAY) - INTERVAL 1 SECOND",
      $unit === 'month' => "DATE_ADD(CURDATE(), INTERVAL " . ($count * 30) . " DAY) - INTERVAL 1 SECOND",
      $unit === 'quarter' => "DATE_ADD(CURDATE(), INTERVAL " . ($count * 90) . " DAY) - INTERVAL 1 SECOND",
      default => "DATE_ADD(CURDATE(), INTERVAL $count $intervalUnit) - INTERVAL 1 SECOND",
    };

    return match ($action) {
      'this' => [
        'from' => $count > 1 ? "DATE_SUB($startExpr, INTERVAL " . ($count - 1) . " $intervalUnit)" : $startExpr,
        'to' => "DATE_ADD($startExpr, INTERVAL 1 $intervalUnit) - INTERVAL 1 SECOND",
      ],
      'previous' => [
        'from' => "DATE_SUB($startExpr, INTERVAL $count $intervalUnit)",
        'to' => "$startExpr - INTERVAL 1 SECOND",
      ],
      'previous_before' => [
        'from' => "DATE_SUB($startExpr, INTERVAL 2 $intervalUnit)",
        'to' => "DATE_SUB($startExpr, INTERVAL 1 $intervalUnit) - INTERVAL 1 SECOND",
      ],
      'next' => [
        'from' => "DATE_ADD($startExpr, INTERVAL 1 $intervalUnit)",
        'to' => "DATE_ADD($startExpr, INTERVAL " . ($count + 1) . " $intervalUnit) - INTERVAL 1 SECOND",
      ],
      'current' => [
        'from' => $startExpr,
        'to' => "CURDATE() + INTERVAL 1 DAY - INTERVAL 1 SECOND",
      ],
      'earlier' => [
        'from' => NULL,
        'to' => "DATE_SUB($startExpr, INTERVAL 1 SECOND)",
      ],
      'greater' => [
        'from' => $startExpr,
        'to' => NULL,
      ],
      'greater_previous' => [
        'from' => "DATE_SUB($startExpr, INTERVAL 1 DAY)",
        'to' => NULL,
      ],
      'less' => [
        'from' => NULL,
        'to' => "DATE_ADD($startExpr, INTERVAL 1 $intervalUnit) - INTERVAL 1 SECOND",
      ],
      'ending' => [
        'from' => $endingFrom,
        'to' => "CURDATE() + INTERVAL 1 DAY - INTERVAL 1 SECOND",
      ],
      'starting' => [
        'from' => $unit === 'day' ? "CURDATE() + INTERVAL 1 DAY" : "CURDATE()",
        'to' => $startingTo,
      ],
      default => NULL,
    };
  }

  /**
   * @param string $relativeTerm
   *   Relative time frame: this, previous, previous_1.
   * @param string|null $unit
   *   Frequency unit like year, month, week etc.
   *
   * @return array
   *   start date and end date for the relative time frame
   */
  public static function relativeToAbsolute($relativeTerm, ?string $unit = NULL) {
    $sql = self::relativeToSql($relativeTerm, $unit);
    if (!$sql) {
      return [];
    }

    $selectFrom = $sql['from'] ? "DATE_FORMAT({$sql['from']}, '%Y%m%d%H%i%s')" : 'NULL';
    $selectTo = $sql['to'] ? "DATE_FORMAT({$sql['to']}, '%Y%m%d%H%i%s')" : 'NULL';

    $dao = \CRM_Core_DAO::executeQuery("SELECT $selectFrom AS from_date, $selectTo AS to_date");
    $dao->fetch();

    $dateRange = [
      'from' => NULL,
      'to' => $dao->to_date,
    ];
    if ($dao->from_date) {
      if (str_starts_with($relativeTerm, 'ending') || str_starts_with($relativeTerm, 'starting') || ($relativeTerm === 'this_2' && $unit === 'fiscal_year')) {
        $dateRange['from'] = $dao->from_date;
      }
      else {
        $dateRange['from'] = substr($dao->from_date, 0, 8);
      }
    }
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
    $date = date("Y-m-d", CRM_Utils_Time::time());
    $currentYear = date("Y", CRM_Utils_Time::time());

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
      if ($time) {
        $date .= ' ' . $time;
      }
      $mysqlDate = date($format, strtotime($date));
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
   * @throws \CRM_Core_Exception
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
        $field['datepicker']['extra']['time'] = $dateAttributes['time'];
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
    $todayMD = date('-m-d');
    if (isset($field['start_date_years'])) {
      $extra['minDate'] = $field['start_date_years'] . $todayMD;
    }
    if (isset($field['end_date_years'])) {
      $extra['maxDate'] = $field['end_date_years'] . $todayMD;
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
   * @param int $dateType
   *   One of the constants like CRM_Utils_Date::DATE_yyyy_mm_dd.
   *
   * @return null|string
   */
  public static function formatDate($date, int $dateType = self::DATE_yyyy_mm_dd): ?string {
    // This empty check is never hit in practice - hence it is ok to treat it the same as bad data.
    if (empty($date) || !self::validateDateInput($date, $dateType)) {
      return NULL;
    }
    // Test cover for this function is in CRM_Utils_DateTest::testFormatDate().
    // This function does some special handling for month translations and
    // for 2 digit years outside normal strtotime. It might be possible to migrate
    // to other multilingual php date handling - which is why the recent focus has been on adding
    // tests.
    // Note that the replaceShortYear & replaceTextMonth functions would work
    // on other date formats - eg. self::DATE_mm_dd_yy - it is only the
    // validation function that is block ing. There is some question as to how
    // useful it is to require the user to specify the date format to the extent we
    // do - as 2 digit year vs 4 digit can be figured out...
    if ($dateType === self::DATE_mm_dd_yy || $dateType === self::DATE_mm_dd_yyyy) {
      // PHP interprets slashes as American and dots/dashes as European/other.
      // The only thing we support for mm_dd_yy that differs from strtotime is
      // the use of dashes - so here we replace then we can use strtotime.
      $date = str_replace(['-', '.'], '/', $date);
      $date = self::replaceShortYear($date, '/', 3);
    }
    if ($dateType === self::DATE_dd_mon_yy || $dateType === self::DATE_dd_mm_yyyy) {
      // PHP interprets slashes as American and dashes as European/other
      // We swap any slashes to dashes so strtotime will handle.
      $date = str_replace(['/', '.'], '-', $date);
      $date = self::replaceTextMonth($date, '-', 2);
      $date = self::replaceShortYear($date, '-', 3);
    }

    if ($dateType === self::DATE_Month_dd_yyyy) {
      $timeMatches = [];
      preg_match(self::getTimeRegex(), $date, $timeMatches);
      $timePortion = $timeMatches[0] ?? '';
      $datePortion = str_replace($timePortion, '', $date);
      // Replace whitespace in the date portion of the string with '/' for strtotime to handle
      // as US-ordered values. We strip off & re-add the time portion
      // so that part is not changed (ie a space continues to separate the date & time).
      $date = preg_replace('/(,|\s)+/', '/', $datePortion) . $timePortion;
      $date = self::replaceTextMonth($date, '/', 1);
      $date = self::replaceShortYear($date, '/', 3);
    }

    $timestamp = strtotime($date);
    return $timestamp ? date('YmdHis', $timestamp) : NULL;
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
   * @param DateTimeInterface $dateObject
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
      !str_contains($value, ':')
      || str_contains($value, ':') && str_contains($value, '-')
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

  /**
   * Get the 2-digit numeric month from the input variable.
   *
   * Month names & abbreviations are checked in a translation-sensitive manner.
   *
   * @param string $string
   *
   * @return string|bool
   *
   * @internal
   */
  protected static function getNumericMonth(string $string) {
    if (is_numeric($string)) {
      return $string;
    }
    $string = strtolower(trim($string, "., \n\r\t\v\0"));
    foreach (self::getFullMonthNames() as $monthNumeric => $monthName) {
      if ($string === mb_strtolower($monthName)) {
        return str_pad($monthNumeric, 2, 0, STR_PAD_LEFT);
      }
    }
    foreach (self::getAbbrMonthNames() as $monthNumeric => $monthAbbreviation) {
      if ($string === mb_strtolower($monthAbbreviation)) {
        return str_pad($monthNumeric, 2, 0, STR_PAD_LEFT);
      }
    }
    return FALSE;
  }

  /**
   * Get the date element from the passed date string.
   *
   * @param string $date e.g. '20-Oct-2022'
   * @param string $separator e.g '-'
   * @param int $monthPlacement eg. 2 for the second section of the string
   *
   * @internal
   *
   * @return string
   */
  protected static function getDateElement(string $date, string $separator, int $monthPlacement): string {
    $element = explode($separator, $date)[$monthPlacement - 1];
    // This second explosion drops any trailing time string.
    return explode(' ', $element)[0];
  }

  /**
   * @param $date
   * @param string $separator
   * @param int $monthPlacement
   *
   * @internal
   *
   * @return float|int|mixed|string
   */
  protected static function replaceTextMonth($date, string $separator, int $monthPlacement) {
    $month = self::getDateElement($date, $separator, $monthPlacement);
    if (!is_numeric($month)) {
      return str_replace($month, self::getNumericMonth($month), $date);
    }
    return $date;
  }

  /**
   * Replace a year in the short year format e.g 22.
   *
   * Note this differs from standard php strotime as we treat anything less
   * than 5 years in the future as being in the past.
   *
   * The reasons for this are not documented but it is likely that our use cases
   * dictated it - eg. importing birth dates would more sanely default to handling 68
   * as 1968. By contrast importing future data is likely rare.
   *
   * @param string $date
   * @param string $separator
   * @param int $yearPlacement
   *
   * @internal
   *
   * @return string
   */
  protected static function replaceShortYear($date, string $separator, int $yearPlacement): string {
    $year = self::getDateElement($date, $separator, $yearPlacement);
    if (strlen($year) === 4) {
      return $date;
    }
    $parts = explode($separator, $date);
    // Replace the year with the 4-digit-year, re-appending any trailing time string.
    $parts[$yearPlacement - 1] = self::getYear($year) . substr($parts[$yearPlacement - 1], 2);
    return implode($separator, $parts);
  }

  /**
   * Get a 4 digit year from a 2 or 4 digit year.
   *
   * The handling differs from strtotime as a year more than 5 years in the future
   * is deemed to be in the past whereas strtotime uses a 1970 cutoff
   * https://www.w3schools.com/php/func_date_strtotime.asp
   *
   * @param int $year
   *
   * @internal
   *
   * @return int
   */
  protected static function getYear(int $year) {
    $currentYear = date('Y');
    if ($year < 100) {
      $year = ((int) substr($currentYear, 0, 2)) * 100 + $year;
      if ($year > ($currentYear + 5)) {
        $year -= 100;
      }
      elseif ($year <= ($currentYear - 95)) {
        $year += 100;
      }
    }
    return $year;
  }

  /**
   * Get the regex to find a locale-relevant date in the string.
   *
   * Resulting regex looks like this, in English locale
   * /^(January|February|March|April|May|June|July|August|September|October|November|December|Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec),?\s?\d\d,?\s]?\d\d\d\d$/
   *
   *
   * @return string
   * @internal
   *
   */
  protected static function getMonthRegex(): string {
    $months = array_merge(self::getFullMonthNames(), self::getAbbrMonthNames());
    return '(' . implode('|', $months) . ')';
  }

  /**
   * Sql expression to calculate the upcoming anniversary of a given date.
   *
   * The IF() accounts for the possibility that the date has already passed, & so skips to next year.
   * The INTERVAL() functions correctly handle leap years.
   *
   * @param string $dateColumn
   * @return string
   */
  public static function getAnniversarySql(string $dateColumn): string {
    return "IF(
      DATE_ADD($dateColumn, INTERVAL(YEAR(CURDATE()) - YEAR($dateColumn)) YEAR) < CURDATE(),
      DATE_ADD($dateColumn, INTERVAL(1 + YEAR(CURDATE()) - YEAR($dateColumn)) YEAR),
      DATE_ADD($dateColumn, INTERVAL(YEAR(CURDATE()) - YEAR($dateColumn)) YEAR)
    )";
  }

}
