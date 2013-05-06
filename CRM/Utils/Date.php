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
 * Date utilties
 */
class CRM_Utils_Date {

  /**
   * format a date by padding it with leading '0'.
   *
   * @param array  $date ('Y', 'M', 'd')
   * @param string $separator   the seperator to use when formatting the date
   * @param string $invalidDate what to return if the date is invalid
   *
   * @return string - formatted string for date
   *
   * @static
   */
  static function format($date, $separator = '', $invalidDate = 0) {
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

    if (CRM_Utils_Array::value('M', $date)) {
      $date['M'] = (int ) $date['M'];
      if ($date['M'] < 1 || $date['M'] > 12) {
        return $invalidDate;
      }
    }
    else {
      $date['M'] = 1;
    }

    if (CRM_Utils_Array::value('d', $date)) {
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
      if (CRM_Utils_Array::value('h', $date)) {
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
      if (CRM_Utils_Array::value('H', $date)) {
        $date['H'] = (int) $date['H'];
      }
      else {
        $date['H'] = 0;
      }

      if (CRM_Utils_Array::value('i', $date)) {
        $date['i'] = (int ) $date['i'];
      }
      else {
        $date['i'] = 0;
      }

      if ($date['h'] == 0 && $date['H'] != 0) {
        $date['h'] = $date['H'];
      }

      if (CRM_Utils_Array::value('s', $date)) {
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
   * return abbreviated weekday names according to the locale
   *
   * @return array  0-based array with abbreviated weekday names
   *
   * @static
   */
  static function &getAbbrWeekdayNames() {
    static $abbrWeekdayNames;
    if (!isset($abbrWeekdayNames)) {

      // set LC_TIME and build the arrays from locale-provided names
      // June 1st, 1970 was a Monday
      CRM_Core_I18n::setLcTime();
      for ($i = 0; $i < 7; $i++) {
        $abbrWeekdayNames[$i] = strftime('%a', mktime(0, 0, 0, 6, $i, 1970));
      }
    }
    return $abbrWeekdayNames;
  }

  /**
   * return full weekday names according to the locale
   *
   * @return array  0-based array with full weekday names
   *
   * @static
   */
  static function &getFullWeekdayNames() {
    static $fullWeekdayNames;
    if (!isset($fullWeekdayNames)) {

      // set LC_TIME and build the arrays from locale-provided names
      // June 1st, 1970 was a Monday
      CRM_Core_I18n::setLcTime();
      for ($i = 0; $i < 7; $i++) {
        $fullWeekdayNames[$i] = strftime('%A', mktime(0, 0, 0, 6, $i, 1970));
      }
    }
    return $fullWeekdayNames;
  }

  /**
   * return abbreviated month names according to the locale
   *
   * @return array  1-based array with abbreviated month names
   *
   * @static
   */
  static function &getAbbrMonthNames($month = FALSE) {
    static $abbrMonthNames;
    if (!isset($abbrMonthNames)) {

      // set LC_TIME and build the arrays from locale-provided names
      CRM_Core_I18n::setLcTime();
      for ($i = 1; $i <= 12; $i++) {
        $abbrMonthNames[$i] = strftime('%b', mktime(0, 0, 0, $i, 10, 1970));
      }
    }
    if ($month) {
      return $abbrMonthNames[$month];
    }
    return $abbrMonthNames;
  }

  /**
   * return full month names according to the locale
   *
   * @return array  1-based array with full month names
   *
   * @static
   */
  static function &getFullMonthNames() {
    static $fullMonthNames;
    if (!isset($fullMonthNames)) {

      // set LC_TIME and build the arrays from locale-provided names
      CRM_Core_I18n::setLcTime();
      for ($i = 1; $i <= 12; $i++) {
        $fullMonthNames[$i] = strftime('%B', mktime(0, 0, 0, $i, 10, 1970));
      }
    }
    return $fullMonthNames;
  }

  static function unixTime($string) {
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
   * create a date and time string in a provided format
   *
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
   * @param string $date    date and time in 'YYYY-MM-DD hh:mm:ss' format
   * @param string $format  the output format
   * @param array  $dateParts  an array with the desired date parts
   *
   * @return string  the $format-formatted $date
   *
   * @static
   */
  static function customFormat($dateString, $format = NULL, $dateParts = NULL) {
    // 1-based (January) month names arrays
    $abbrMonths = self::getAbbrMonthNames();
    $fullMonths = self::getFullMonthNames();

    if (!$format) {
      $config = CRM_Core_Config::singleton();

      if ($dateParts) {
        if (array_intersect(array('h', 'H'), $dateParts)) {
          $format = $config->dateformatDatetime;
        }
        elseif (array_intersect(array('d', 'j'), $dateParts)) {
          $format = $config->dateformatFull;
        }
        elseif (array_intersect(array('m', 'M'), $dateParts)) {
          $format = $config->dateformatPartial;
        }
        else {
          $format = $config->dateformatYear;
        }
      }
      else {
        if (strpos($dateString, '-')) {
          $month = (int) substr($dateString, 5, 2);
          $day = (int) substr($dateString, 8, 2);
        }
        else {
          $month = (int) substr($dateString, 4, 2);
          $day = (int) substr($dateString, 6, 2);
        }

        if (strlen($dateString) > 10) {
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

    if ($dateString) {
      if (strpos($dateString, '-')) {
        $year  = (int) substr($dateString, 0, 4);
        $month = (int) substr($dateString, 5, 2);
        $day   = (int) substr($dateString, 8, 2);

        $hour24 = (int) substr($dateString, 11, 2);
        $minute = (int) substr($dateString, 14, 2);
      }
      else {
        $year  = (int) substr($dateString, 0, 4);
        $month = (int) substr($dateString, 4, 2);
        $day   = (int) substr($dateString, 6, 2);

        $hour24 = (int) substr($dateString, 8, 2);
        $minute = (int) substr($dateString, 10, 2);
      }

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

      $date = array(
        '%b' => CRM_Utils_Array::value($month, $abbrMonths),
        '%B' => CRM_Utils_Array::value($month, $fullMonths),
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
        '%A' => $type,
        '%Y' => $year,
      );

      return strtr($format, $date);
    }
    else {
      return '';
    }
  }

  /**
   * converts the date/datetime from MySQL format to ISO format
   *
   * @param string $mysql  date/datetime in MySQL format
   *
   * @return string        date/datetime in ISO format
   * @static
   */
  static function mysqlToIso($mysql) {
    $year   = substr($mysql, 0, 4);
    $month  = substr($mysql, 4, 2);
    $day    = substr($mysql, 6, 2);
    $hour   = substr($mysql, 8, 2);
    $minute = substr($mysql, 10, 2);
    $second = substr($mysql, 12, 2);

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
   * converts the date/datetime from ISO format to MySQL format
   *
   * @param string $iso  date/datetime in ISO format
   *
   * @return string      date/datetime in MySQL format
   * @static
   */
  static function isoToMysql($iso) {
    $dropArray = array('-' => '', ':' => '', ' ' => '');
    return strtr($iso, $dropArray);
  }

  /**
   * converts the any given date to default date format.
   *
   * @param array  $params     has given date-format
   * @param int    $dateType   type of date
   * @param string $dateParam  index of params
   * @static
   */
  static function convertToDefaultDate(&$params, $dateType, $dateParam) {
    $now     = getDate();
    $cen     = substr($now['year'], 0, 2);
    $prevCen = $cen - 1;

    $value = NULL;
    if (CRM_Utils_Array::value($dateParam, $params)) {
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
        $year  = (int) $formattedDate[0];
        $month = (int) $formattedDate[1];
        $day   = (int) $formattedDate[2];
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
        $year  = (int) $formattedDate[2];
        $month = (int) $formattedDate[0];
        $day   = (int) $formattedDate[1];
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
      $year  = (int) $dateArray[2];
      $day   = (int) $dateArray[1];
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

        $year  = (int) $dateArray[2];
        $day   = (int) $dateArray[0];
        $month = (int) $monthInt;
      }
      else {
        return FALSE;
      }
    }
    if ($dateType == 32) {
      $formattedDate = explode("/", $value);
      if (count($formattedDate) == 3) {
        $year  = (int) $formattedDate[2];
        $month = (int) $formattedDate[1];
        $day   = (int) $formattedDate[0];
      }
      else {
        return FALSE;
      }
    }

    $month = ($month < 10) ? "0" . "$month" : $month;
    $day = ($day < 10) ? "0" . "$day" : $day;

    $year = (int ) $year;
    // simple heuristic to determine what century to use
    // 00 - 20 is always 2000 - 2020
    // 21 - 99 is always 1921 - 1999
    if ($year < 21) {
      $year = (strlen($year) == 1) ? $cen . '0' . $year : $cen . $year;
    }
    elseif ($year < 100) {
      $year = $prevCen . $year;
    }

    if ($params[$dateParam]) {
      $params[$dateParam] = "$year$month$day";
    }
    //if month is invalid return as error
    if ($month !== '00' && $month <= 12) {
      return TRUE;
    }
    return FALSE;
  }

  static function isDate(&$date) {
    if (CRM_Utils_System::isNull($date)) {
      return FALSE;
    }
    return TRUE;
  }

  static function currentDBDate($timeStamp = NULL) {
    return $timeStamp ? date('YmdHis', $timeStamp) : date('YmdHis');
  }

  static function overdue($date, $now = NULL) {
    $mysqlDate = self::isoToMysql($date);
    if (!$now) {
      $now = self::currentDBDate();
    }
    else {
      $now = self::isoToMysql($now);
    }

    return ($mysqlDate >= $now) ? FALSE : TRUE;
  }

  /**
   * Function to get customized today
   *
   * This function is used for getting customized today. To get
   * actuall today pass 'dayParams' as null. or else pass the day,
   * month, year values as array values
   * Example: $dayParams = array(
   'day' => '25', 'month' => '10',
   *                              'year' => '2007' );
   *
   * @param  Array  $dayParams   Array of the day, month, year
   *                             values.
   * @param  string $format      expected date format( default
   *                             format is 2007-12-21 )
   *
   * @return string  Return the customized todays date (Y-m-d)
   * @static
   */
  static function getToday($dayParams = NULL, $format = "Y-m-d") {
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
   * Function to find whether today's date lies in
   * the given range
   *
   * @param  date  $startDate  start date for the range
   * @param  date  $endDate    end date for the range
   *
   * @return true              todays date is in the given date range
   * @static
   */
  static function getRange($startDate, $endDate) {
    $today          = date("Y-m-d");
    $mysqlStartDate = self::isoToMysql($startDate);
    $mysqlEndDate   = self::isoToMysql($endDate);
    $mysqlToday     = self::isoToMysql($today);

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
   * Function to get start date and end from
   * the given relative term and unit
   *
   * @param  date  $relative  eg: term.unit
   *
   * @return array start date, end date
   * @static
   */
  static function getFromTo($relative, $from, $to) {
    if ($relative) {
      list($term, $unit) = explode('.', $relative);
      $dateRange = self::relativeToAbsolute($term, $unit);
      $from = $dateRange['from'];
      //Take only Date Part, Sometime Time part is also present in 'to'
      $to = substr($dateRange['to'], 0, 8);
    }

    $from = self::processDate($from);
    $to = self::processDate($to, '235959');

    return array($from, $to);
  }

  /**
   * Function to calculate Age in Years if greater than one year else in months
   *
   * @param date $birthDate Birth Date
   *
   * @return int array $results contains years or months
   * @access public
   * @static
   */
  static public function calculateAge($birthDate) {
    $results = array();
    $formatedBirthDate = CRM_Utils_Date::customFormat($birthDate, '%Y-%m-%d');

    $bDate      = explode('-', $formatedBirthDate);
    $birthYear  = $bDate[0];
    $birthMonth = $bDate[1];
    $birthDay   = $bDate[2];
    $year_diff  = date("Y") - $birthYear;

    // don't calculate age CRM-3143
    if ($birthYear == '1902') {
      return $results;
    }

    switch ($year_diff) {
      case 1:
        $month = (12 - $birthMonth) + date("m");
        if ($month < 12) {
          if (date("d") < $birthDay) {
            $month--;
          }
          $results['months'] = $month;
        }
        elseif ($month == 12 && (date("d") < $birthDay)) {
          $results['months'] = $month - 1;
        }
        else {
          $results['years'] = $year_diff;
        }
        break;

      case 0:
        $month = date("m") - $birthMonth;
        $results['months'] = $month;
        break;

      default:
        $results['years'] = $year_diff;
        if ((date("m") < $birthMonth) || (date("m") == $birthMonth) && (date("d") < $birthDay)) {
          $results['years']--;
        }
    }

    return $results;
  }

  /**
   * Function to calculate next payment date according to provided  unit & interval
   *
   * @param string $unit     frequency unit like year,month, week etc..
   *
   * @param int    $interval frequency interval.
   *
   * @param array  $date     start date of pledge.
   *
   * @return array $result contains new date with added interval
   * @access public
   */
  static function intervalAdd($unit, $interval, $date, $dontCareTime = FALSE) {
    if (is_array($date)) {
      $hour   = CRM_Utils_Array::value('H', $date);
      $minute = CRM_Utils_Array::value('i', $date);
      $second = CRM_Utils_Array::value('s', $date);
      $month  = CRM_Utils_Array::value('M', $date);
      $day    = CRM_Utils_Array::value('d', $date);
      $year   = CRM_Utils_Array::value('Y', $date);
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

    $date      = array();
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
   * function to check given format is valid for bith date.
   * and retrun supportable birth date format w/ qf mapping.
   *
   * @param $format given format ( eg 'M Y', 'Y M' )
   * return array of qfMapping and date parts for date format.
   */
  static function &checkBirthDateFormat($format = NULL) {
    $birthDateFormat = NULL;
    if (!$format) {
      $birthDateFormat = self::getDateFormat('birth');
    }

    $supportableFormats = array(
      'mm/dd' => '%B %E%f',
      'dd-mm' => '%E%f %B',
      'yy-mm' => '%Y %B',
      'M yy' => '%b %Y',
      'yy' => '%Y',
      'dd/mm/yy' => '%E%f %B %Y',
    );

    if (array_key_exists($birthDateFormat, $supportableFormats)) {
      $birthDateFormat = array('qfMapping' => $supportableFormats[$birthDateFormat]);
    }

    return $birthDateFormat;
  }

  /**
   * resolves the given relative time interval into finite time limits
   *
   * @param  array $relativeTerm relative time frame like this, previous, etc
   * @param  int   $unit         frequency unit like year, month, week etc..
   *
   * @return array $dateRange    start date and end date for the relative time frame
   * @static
   */
  static function relativeToAbsolute($relativeTerm, $unit) {
    $now       = getDate();
    $from      = $to = $dateRange = array();
    $from['H'] = $from['i'] = $from['s'] = 0;

    switch ($unit) {
      case 'year':
        switch ($relativeTerm) {
          case 'this':
            $from['d'] = $from['M'] = 1;
            $to['d']   = 31;
            $to['M']   = 12;
            $to['Y']   = $from['Y'] = $now['year'];
            break;

          case 'previous':
            $from['M'] = $from['d'] = 1;
            $to['d']   = 31;
            $to['M']   = 12;
            $to['Y']   = $from['Y'] = $now['year'] - 1;
            break;

          case 'previous_before':
            $from['M'] = $from['d'] = 1;
            $to['d']   = 31;
            $to['M']   = 12;
            $to['Y']   = $from['Y'] = $now['year'] - 2;
            break;

          case 'previous_2':
            $from['M'] = $from['d'] = 1;
            $to['d']   = 31;
            $to['M']   = 12;
            $from['Y'] = $now['year'] - 2;
            $to['Y']   = $now['year'] - 1;
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

          case 'ending':
            $to['d'] = $now['mday'];
            $to['M'] = $now['mon'];
            $to['Y'] = $now['year'];
            $to['H'] = 23;
            $to['i'] = $to['s'] = 59;
            $from    = self::intervalAdd('year', -1, $to);
            $from    = self::intervalAdd('second', 1, $from);
            break;
        }
        break;

      case 'fiscal_year':
        $config    = CRM_Core_Config::singleton();
        $from['d'] = $config->fiscalYearStart['d'];
        $from['M'] = $config->fiscalYearStart['M'];
        $fYear     = self::calculateFiscalYear($from['d'], $from['M']);
        switch ($relativeTerm) {
          case 'this':
            $from['Y']  = $fYear;
            $fiscalYear = mktime(0, 0, 0, $from['M'], $form['d'], $from['Y'] + 1);
            $fiscalEnd  = explode('-', date("Y-m-d", $fiscalYear));

            $to['d'] = $fiscalEnd['2'];
            $to['M'] = $fiscalEnd['1'];
            $to['Y'] = $fiscalEnd['0'];
            break;

          case 'previous':
            $from['Y']  = $fYear - 1;
            $fiscalYear = mktime(0, 0, 0, $from['M'], $form['d'], $from['Y'] + 1);
            $fiscalEnd  = explode('-', date("Y-m-d", $fiscalYear));

            $to['d'] = $fiscalEnd['2'];
            $to['M'] = $fiscalEnd['1'];
            $to['Y'] = $fiscalEnd['0'];
            break;
        }
        break;

      case 'quarter':
        switch ($relativeTerm) {
          case 'this':

            $quarter   = ceil($now['mon'] / 3);
            $from['d'] = 1;
            $from['M'] = (3 * $quarter) - 2;
            $to['M']   = 3 * $quarter;
            $to['Y']   = $from['Y'] = $now['year'];
            $to['d']   = date('t', mktime(0, 0, 0, $to['M'], 1, $now['year']));
            break;

          case 'previous':
            $difference   = 1;
            $quarter      = ceil($now['mon'] / 3);
            $quarter      = $quarter - $difference;
            $subtractYear = 0;
            if ($quarter <= 0) {
              $subtractYear = 1;
              $quarter += 4;
            }
            $from['d'] = 1;
            $from['M'] = (3 * $quarter) - 2;
            $to['M']   = 3 * $quarter;
            $to['Y']   = $from['Y'] = $now['year'] - $subtractYear;
            $to['d']   = date('t', mktime(0, 0, 0, $to['M'], 1, $to['Y']));
            break;

          case 'previous_before':
            $difference = 2;
            $quarter    = ceil($now['mon'] / 3);
            $quarter    = $quarter - $difference;
            if ($quarter <= 0) {
              $subtractYear = 1;
              $quarter += 4;
            }
            $from['d'] = 1;
            $from['M'] = (3 * $quarter) - 2;
            $to['M']   = 3 * $quarter;
            $to['Y']   = $from['Y'] = $now['year'] - $subtractYear;
            $to['d']   = date('t', mktime(0, 0, 0, $to['M'], 1, $to['Y']));
            break;

          case 'previous_2':
            $difference      = 2;
            $quarter         = ceil($now['mon'] / 3);
            $current_quarter = $quarter;
            $quarter         = $quarter - $difference;
            $subtractYear    = 0;
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
            $quarter   = ceil($now['mon'] / 3);
            $from['d'] = 1;
            $from['M'] = (3 * $quarter) - 2;
            $from['Y'] = $now['year'];
            unset($to);
            break;

          case 'ending':
            $to['d'] = $now['mday'];
            $to['M'] = $now['mon'];
            $to['Y'] = $now['year'];
            $to['H'] = 23;
            $to['i'] = $to['s'] = 59;
            $from    = self::intervalAdd('month', -3, $to);
            $from    = self::intervalAdd('second', 1, $from);
            break;
        }
        break;

      case 'month':
        switch ($relativeTerm) {
          case 'this':
            $from['d'] = 1;
            $to['d']   = date('t', mktime(0, 0, 0, $now['mon'], 1, $now['year']));
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
            //before end of past month
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
            $from['M'] = $now['mon'];;
            $from['Y'] = $now['year'];
            unset($to);
            break;

          case 'ending':
            $to['d'] = $now['mday'];
            $to['M'] = $now['mon'];
            $to['Y'] = $now['year'];
            $to['H'] = 23;
            $to['i'] = $to['s'] = 59;
            $from    = self::intervalAdd('month', -1, $to);
            $from    = self::intervalAdd('second', 1, $from);
            break;
        }
        break;

      case 'week':
        switch ($relativeTerm) {
          case 'this':
            $from['d'] = $now['mday'];
            $from['M'] = $now['mon'];
            $from['Y'] = $now['year'];
            $from      = self::intervalAdd('day', -1 * ($now['wday']), $from);
            $to        = self::intervalAdd('day', 6, $from);
            break;

          case 'previous':
            $from['d'] = $now['mday'];
            $from['M'] = $now['mon'];
            $from['Y'] = $now['year'];
            $from      = self::intervalAdd('day', -1 * ($now['wday']) - 7, $from);
            $to        = self::intervalAdd('day', 6, $from);
            break;

          case 'previous_before':
            $from['d'] = $now['mday'];
            $from['M'] = $now['mon'];
            $from['Y'] = $now['year'];
            $from      = self::intervalAdd('day', -1 * ($now['wday']) - 14, $from);
            $to        = self::intervalAdd('day', 6, $from);
            break;

          case 'previous_2':
            $from['d'] = $now['mday'];
            $from['M'] = $now['mon'];
            $from['Y'] = $now['year'];
            $from      = self::intervalAdd('day', -1 * ($now['wday']) - 14, $from);
            $to        = self::intervalAdd('day', 13, $from);
            break;

          case 'earlier':
            $to['d'] = $now['mday'];
            $to['M'] = $now['mon'];
            $to['Y'] = $now['year'];
            $to      = self::intervalAdd('day', -1 * ($now['wday']) - 1, $to);
            unset($from);
            break;

          case 'greater':
            $from['d'] = $now['mday'];
            $from['M'] = $now['mon'];
            $from['Y'] = $now['year'];
            $from      = self::intervalAdd('day', -1 * ($now['wday']), $from);
            unset($to);
            break;

          case 'ending':
            $to['d'] = $now['mday'];
            $to['M'] = $now['mon'];
            $to['Y'] = $now['year'];
            $to['H'] = 23;
            $to['i'] = $to['s'] = 59;
            $from    = self::intervalAdd('day', -7, $to);
            $from    = self::intervalAdd('second', 1, $from);
            break;
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
            $from      = self::intervalAdd('day', -1, $from);
            $to['d']   = $from['d'];
            $to['M']   = $from['M'];
            $to['Y']   = $from['Y'];
            break;

          case 'previous_before':
            $from['d'] = $now['mday'];
            $from['M'] = $now['mon'];
            $from['Y'] = $now['year'];
            $from      = self::intervalAdd('day', -2, $from);
            $to['d']   = $from['d'];
            $to['M']   = $from['M'];
            $to['Y']   = $from['Y'];
            break;

          case 'previous_2':
            $from['d'] = $to['d'] = $now['mday'];
            $from['M'] = $to['M'] = $now['mon'];
            $from['Y'] = $to['Y'] = $now['year'];
            $from      = self::intervalAdd('day', -2, $from);
            $to        = self::intervalAdd('day', -1, $to);
            break;

          case 'earlier':
            $to['d'] = $now['mday'];
            $to['M'] = $now['mon'];
            $to['Y'] = $now['year'];
            unset($from);
            break;

          case 'greater':
            $from['d'] = $now['mday'];
            $from['M'] = $now['mon'];;
            $from['Y'] = $now['year'];
            unset($to);
            break;
        }
        break;
    }

    foreach (array(
        'from', 'to') as $item) {
      if (!empty($$item)) {
        $dateRange[$item] = self::format($$item);
      }
      else {
        $dateRange[$item] = NULL;
      }
    }
    return $dateRange;
  }

  /**
   * Function to calculate current fiscal year based on the fiscal month and day
   *
   * @param  int $fyDate    Fiscal start date
   *
   * @param  int $fyMonth   Fiscal Start Month
   *
   * @return int $fy       Current Fiscl Year
   * @access public
   * @static
   */
  static function calculateFiscalYear($fyDate, $fyMonth) {
    $date = date("Y-m-d");
    $currentYear = date("Y");

    //recalculate the date because month 4::04 make the difference
    $fiscalYear  = explode('-', date("Y-m-d", mktime(0, 0, 0, $fyMonth, $fyDate, $currentYear)));
    $fyDate      = $fiscalYear[2];
    $fyMonth     = $fiscalYear[1];
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
   *  Function to process date, convert to mysql format
   *
   *  @param string $date date string
   *  @param string $time time string
   *  @param string $returnNullString  'null' needs to be returned
   *                so that db oject will set null in db
   *  @param string $format expected return date format.( default is  mysql )
   *
   *  @return string $mysqlDate date format that is excepted by mysql
   */
  static function processDate($date, $time = NULL, $returnNullString = FALSE, $format = 'YmdHis') {
    $mysqlDate = NULL;

    if ($returnNullString) {
      $mysqlDate = 'null';
    }

    if (trim($date)) {
      $mysqlDate = date($format, strtotime($date . ' ' . $time));
    }

    return $mysqlDate;
  }

  /**
   *  Function to convert mysql to date plugin format
   *
   *  @param string $mysqlDate date string
   *
   *  @return array $date and time
   */
  static function setDateDefaults($mysqlDate = NULL, $formatType = NULL, $format = NULL, $timeFormat = NULL) {
    // if date is not passed assume it as today
    if (!$mysqlDate) {
      $mysqlDate = date('Y-m-d G:i:s');
    }

    $config = CRM_Core_Config::singleton();
    if ($formatType) {
      // get actual format
      $params = array('name' => $formatType);
      $values = array();
      CRM_Core_DAO::commonRetrieve('CRM_Core_DAO_PreferencesDate', $params, $values);

      if ($values['date_format']) {
        $format = $values['date_format'];
      }

      if (isset($values['time_format'])) {
        $timeFormat = $values['time_format'];
      }
    }

    // now we set display date using js, hence we should always setdefault
    // 'm/d/Y' format. So that submitted value is alwats mm/dd/YY format
    // note that for date display we dynamically create text field
    /*
      if ( !$format ) {
      $format = $config->dateInputFormat;
      }

      // get actual format
      $actualPHPFormats = CRM_Core_SelectValues::datePluginToPHPFormats( );
      $dateFormat       = CRM_Utils_Array::value( $format, $actualPHPFormats );
    */


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

    return array($date, $time);
  }

  /**
   * Function get date format
   *
   * @param  string $formatType Date name e.g. birth
   *
   * @return string $format
   */
  static function getDateFormat($formatType = NULL) {
    $format = NULL;
    if ($formatType) {
      $format = CRM_Core_Dao::getFieldValue('CRM_Core_DAO_PreferencesDate',
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
   * Get the time in UTC for the current time. You can optionally send an offset from the current time if needed
   *
   * @param $offset int the offset from the current time in seconds
   *
   * @return the time in UTC
   * @static
   * @public
   */
  static function getUTCTime($offset = 0) {
    $originalTimezone = date_default_timezone_get();
    date_default_timezone_set('UTC');
    $time = time() + $offset;
    $now = date('YmdHis', $time);
    date_default_timezone_set($originalTimezone);
    return $now;
  }


  static function formatDate($date, $dateType) {
    $formattedDate = NULL;
    if (empty($date)) {
      return $formattedDate;
    }

    //1. first convert date to default format.
    //2. append time to default formatted date (might be removed during format)
    //3. validate date / date time.
    //4. If date and time then convert to default date time format.

    $dateKey = 'date';
    $dateParams = array($dateKey => $date);

    if (CRM_Utils_Date::convertToDefaultDate($dateParams, $dateType, $dateKey)) {
      $dateVal = $dateParams[$dateKey];
      $ruleName = 'date';
      if ($dateType == 1) {
        $matches = array();
        if (preg_match("/(\s(([01]\d)|[2][0-3]):([0-5]\d))$/", $date, $matches)) {
          $ruleName = 'dateTime';
          if (strpos($date, '-') !== FALSE) {
            $dateVal .= array_shift($matches);
          }
        }
      }

      // validate date.
      eval('$valid = CRM_Utils_Rule::' . $ruleName . '( $dateVal );');

      if ($valid) {
        //format date and time to default.
        if ($ruleName == 'dateTime') {
          $dateVal = CRM_Utils_Date::customFormat(preg_replace("/(:|\s)?/", "", $dateVal), '%Y%m%d%H%i');
          //hack to add seconds
          $dateVal .= '00';
        }
        $formattedDate = $dateVal;
      }
    }

    return $formattedDate;
  }

}

