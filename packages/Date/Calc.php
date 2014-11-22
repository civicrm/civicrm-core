<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker: */

// {{{ Header

/**
 * Calculates, manipulates and retrieves dates
 *
 * It does not rely on 32-bit system time stamps, so it works dates
 * before 1970 and after 2038.
 *
 * PHP versions 4 and 5
 *
 * LICENSE:
 *
 * Copyright (c) 1999-2006 Monte Ohrt, Pierre-Alain Joye, Daniel Convissor
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted under the terms of the BSD License.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @category   Date and Time
 * @package    Date
 * @author     Monte Ohrt <monte@ispi.net>
 * @author     Pierre-Alain Joye <pajoye@php.net>
 * @author     Daniel Convissor <danielc@php.net>
 * @copyright  1999-2006 Monte Ohrt, Pierre-Alain Joye, Daniel Convissor
 * @license    http://www.opensource.org/licenses/bsd-license.php
 *             BSD License
 * @version    CVS: $Id: Calc.php,v 1.35 2006/11/21 23:01:13 firman Exp $
 * @link       http://pear.php.net/package/Date
 * @since      File available since Release 1.2
 */

// }}}

if (!defined('DATE_CALC_BEGIN_WEEKDAY')) {
    /**
     * Defines what day starts the week
     *
     * Monday (1) is the international standard.
     * Redefine this to 0 if you want weeks to begin on Sunday.
     */
    define('DATE_CALC_BEGIN_WEEKDAY', 1);
}

if (!defined('DATE_CALC_FORMAT')) {
    /**
     * The default value for each method's $format parameter
     *
     * The default is '%Y%m%d'.  To override this default, define
     * this constant before including Calc.php.
     *
     * @since Constant available since Release 1.4.4
     */
    define('DATE_CALC_FORMAT', '%Y%m%d');
}

// {{{ Class: Date_Calc

/**
 * Calculates, manipulates and retrieves dates
 *
 * It does not rely on 32-bit system time stamps, so it works dates
 * before 1970 and after 2038.
 *
 * @author     Monte Ohrt <monte@ispi.net>
 * @author     Daniel Convissor <danielc@php.net>
 * @copyright  1999-2006 Monte Ohrt, Pierre-Alain Joye, Daniel Convissor
 * @license    http://www.opensource.org/licenses/bsd-license.php
 *             BSD License
 * @version    Release: 1.4.7
 * @link       http://pear.php.net/package/Date
 * @since      Class available since Release 1.2
 */
class Date_Calc
{
    // {{{ dateFormat()

    /**
     * Formats the date in the given format, much like strfmt()
     *
     * This function is used to alleviate the problem with 32-bit numbers for
     * dates pre 1970 or post 2038, as strfmt() has on most systems.
     * Most of the formatting options are compatible.
     *
     * Formatting options:
     * <pre>
     * %a   abbreviated weekday name (Sun, Mon, Tue)
     * %A   full weekday name (Sunday, Monday, Tuesday)
     * %b   abbreviated month name (Jan, Feb, Mar)
     * %B   full month name (January, February, March)
     * %d   day of month (range 00 to 31)
     * %e   day of month, single digit (range 0 to 31)
     * %E   number of days since unspecified epoch (integer)
     *        (%E is useful for passing a date in a URL as
     *        an integer value. Then simply use
     *        daysToDate() to convert back to a date.)
     * %j   day of year (range 001 to 366)
     * %m   month as decimal number (range 1 to 12)
     * %n   newline character (\n)
     * %t   tab character (\t)
     * %w   weekday as decimal (0 = Sunday)
     * %U   week number of current year, first sunday as first week
     * %y   year as decimal (range 00 to 99)
     * %Y   year as decimal including century (range 0000 to 9999)
     * %%   literal '%'
     * </pre>
     *
     * @param int    $day     the day of the month
     * @param int    $month   the month
     * @param int    $year    the year.  Use the complete year instead of the
     *                         abbreviated version.  E.g. use 2005, not 05.
     *                         Do not add leading 0's for years prior to 1000.
     * @param string $format  the format string
     *
     * @return string  the date in the desired format
     *
     * @access public
     * @static
     */
    function dateFormat($day, $month, $year, $format)
    {
        if (!Date_Calc::isValidDate($day, $month, $year)) {
            $year  = Date_Calc::dateNow('%Y');
            $month = Date_Calc::dateNow('%m');
            $day   = Date_Calc::dateNow('%d');
        }

        $output = '';

        for ($strpos = 0; $strpos < strlen($format); $strpos++) {
            $char = substr($format, $strpos, 1);
            if ($char == '%') {
                $nextchar = substr($format, $strpos + 1, 1);
                switch($nextchar) {
                    case 'a':
                        $output .= Date_Calc::getWeekdayAbbrname($day, $month, $year);
                        break;
                    case 'A':
                        $output .= Date_Calc::getWeekdayFullname($day, $month, $year);
                        break;
                    case 'b':
                        $output .= Date_Calc::getMonthAbbrname($month);
                        break;
                    case 'B':
                        $output .= Date_Calc::getMonthFullname($month);
                        break;
                    case 'd':
                        $output .= sprintf('%02d', $day);
                        break;
                    case 'e':
                        $output .= $day;
                        break;
                    case 'E':
                        $output .= Date_Calc::dateToDays($day, $month, $year);
                        break;
                    case 'j':
                        $output .= Date_Calc::julianDate($day, $month, $year);
                        break;
                    case 'm':
                        $output .= sprintf('%02d', $month);
                        break;
                    case 'n':
                        $output .= "\n";
                        break;
                    case 't':
                        $output .= "\t";
                        break;
                    case 'w':
                        $output .= Date_Calc::dayOfWeek($day, $month, $year);
                        break;
                    case 'U':
                        $output .= Date_Calc::weekOfYear($day, $month, $year);
                        break;
                    case 'y':
                        $output .= substr($year, 2, 2);
                        break;
                    case 'Y':
                        $output .= $year;
                        break;
                    case '%':
                        $output .= '%';
                        break;
                    default:
                        $output .= $char.$nextchar;
                }
                $strpos++;
            } else {
                $output .= $char;
            }
        }
        return $output;
    }

    // }}}
    // {{{ defaultCentury()

    /**
     * Turns a two digit year into a four digit year
     *
     * From '51 to '99 is in the 1900's, otherwise it's in the 2000's.
     *
     * @param int    $year    the 2 digit year
     *
     * @return string  the 4 digit year
     *
     * @access public
     * @static
     */
    function defaultCentury($year)
    {
        if (strlen($year) == 1) {
            $year = '0' . $year;
        }
        if ($year > 50) {
            return '19' . $year;
        } else {
            return '20' . $year;
        }
    }

    // }}}
    // {{{ dateToDays()

    /**
     * Converts a date to number of days since a distant unspecified epoch
     *
     * @param int    $day     the day of the month
     * @param int    $month   the month
     * @param int    $year    the year.  Use the complete year instead of the
     *                         abbreviated version.  E.g. use 2005, not 05.
     *                         Do not add leading 0's for years prior to 1000.
     *
     * @return integer  the number of days since the Date_Calc epoch
     *
     * @access public
     * @static
     */
    function dateToDays($day, $month, $year)
    {
        $century = (int)substr($year, 0, 2);
        $year = (int)substr($year, 2, 2);
        if ($month > 2) {
            $month -= 3;
        } else {
            $month += 9;
            if ($year) {
                $year--;
            } else {
                $year = 99;
                $century --;
            }
        }

        return (floor((146097 * $century) / 4 ) +
                floor((1461 * $year) / 4 ) +
                floor((153 * $month + 2) / 5 ) +
                $day + 1721119);
    }

    // }}}
    // {{{ daysToDate()

    /**
     * Converts number of days to a distant unspecified epoch
     *
     * @param int    $days    the number of days since the Date_Calc epoch
     * @param string $format  the string indicating how to format the output
     *
     * @return string  the date in the desired format
     *
     * @access public
     * @static
     */
    function daysToDate($days, $format = DATE_CALC_FORMAT)
    {
        $days   -= 1721119;
        $century = floor((4 * $days - 1) / 146097);
        $days    = floor(4 * $days - 1 - 146097 * $century);
        $day     = floor($days / 4);

        $year    = floor((4 * $day +  3) / 1461);
        $day     = floor(4 * $day +  3 - 1461 * $year);
        $day     = floor(($day +  4) / 4);

        $month   = floor((5 * $day - 3) / 153);
        $day     = floor(5 * $day - 3 - 153 * $month);
        $day     = floor(($day +  5) /  5);

        if ($month < 10) {
            $month +=3;
        } else {
            $month -=9;
            if ($year++ == 99) {
                $year = 0;
                $century++;
            }
        }

        $century = sprintf('%02d', $century);
        $year    = sprintf('%02d', $year);
        return Date_Calc::dateFormat($day, $month, $century . $year, $format);
    }

    // }}}
    // {{{ gregorianToISO()

    /**
     * Converts from Gregorian Year-Month-Day to ISO Year-WeekNumber-WeekDay
     *
     * Uses ISO 8601 definitions.  Algorithm by Rick McCarty, 1999 at
     * http://personal.ecu.edu/mccartyr/ISOwdALG.txt .
     * Transcribed to PHP by Jesus M. Castagnetto.
     *
     * @param int    $day     the day of the month
     * @param int    $month   the month
     * @param int    $year    the year.  Use the complete year instead of the
     *                         abbreviated version.  E.g. use 2005, not 05.
     *                         Do not add leading 0's for years prior to 1000.
     *
     * @return string  the date in ISO Year-WeekNumber-WeekDay format
     *
     * @access public
     * @static
     */
    function gregorianToISO($day, $month, $year)
    {
        $mnth = array (0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334);
        $y_isleap = Date_Calc::isLeapYear($year);
        $y_1_isleap = Date_Calc::isLeapYear($year - 1);
        $day_of_year_number = $day + $mnth[$month - 1];
        if ($y_isleap && $month > 2) {
            $day_of_year_number++;
        }
        // find Jan 1 weekday (monday = 1, sunday = 7)
        $yy = ($year - 1) % 100;
        $c = ($year - 1) - $yy;
        $g = $yy + intval($yy / 4);
        $jan1_weekday = 1 + intval((((($c / 100) % 4) * 5) + $g) % 7);
        // weekday for year-month-day
        $h = $day_of_year_number + ($jan1_weekday - 1);
        $weekday = 1 + intval(($h - 1) % 7);
        // find if Y M D falls in YearNumber Y-1, WeekNumber 52 or
        if ($day_of_year_number <= (8 - $jan1_weekday) && $jan1_weekday > 4){
            $yearnumber = $year - 1;
            if ($jan1_weekday == 5 || ($jan1_weekday == 6 && $y_1_isleap)) {
                $weeknumber = 53;
            } else {
                $weeknumber = 52;
            }
        } else {
            $yearnumber = $year;
        }
        // find if Y M D falls in YearNumber Y+1, WeekNumber 1
        if ($yearnumber == $year) {
            if ($y_isleap) {
                $i = 366;
            } else {
                $i = 365;
            }
            if (($i - $day_of_year_number) < (4 - $weekday)) {
                $yearnumber++;
                $weeknumber = 1;
            }
        }
        // find if Y M D falls in YearNumber Y, WeekNumber 1 through 53
        if ($yearnumber == $year) {
            $j = $day_of_year_number + (7 - $weekday) + ($jan1_weekday - 1);
            $weeknumber = intval($j / 7);
            if ($jan1_weekday > 4) {
                $weeknumber--;
            }
        }
        // put it all together
        if ($weeknumber < 10) {
            $weeknumber = '0'.$weeknumber;
        }
        return $yearnumber . '-' . $weeknumber . '-' . $weekday;
    }

    // }}}
    // {{{ dateSeason()

    /**
     * Determines julian date of the given season
     *
     * Adapted from previous work in Java by James Mark Hamilton.
     *
     * @param string $season  the season to get the date for: VERNALEQUINOX,
     *                         SUMMERSOLSTICE, AUTUMNALEQUINOX,
     *                         or WINTERSOLSTICE
     * @param string $year    the year in four digit format.  Must be between
     *                         -1000BC and 3000AD.
     *
     * @return float  the julian date the season starts on
     *
     * @author James Mark Hamilton <mhamilton@qwest.net>
     * @author Robert Butler <rob@maxwellcreek.org>
     * @access public
     * @static
     */
    function dateSeason($season, $year = 0)
    {
        if ($year == '') {
            $year = Date_Calc::dateNow('%Y');
        }
        if (($year >= -1000) && ($year <= 1000)) {
            $y = $year / 1000.0;
            switch ($season) {
                case 'VERNALEQUINOX':
                    $juliandate = (((((((-0.00071 * $y) - 0.00111) * $y) + 0.06134) * $y) + 365242.1374) * $y) + 1721139.29189;
                    break;
                case 'SUMMERSOLSTICE':
                    $juliandate = (((((((0.00025 * $y) + 0.00907) * $y) - 0.05323) * $y) + 365241.72562) * $y) + 1721233.25401;
                    break;
                case 'AUTUMNALEQUINOX':
                    $juliandate = (((((((0.00074 * $y) - 0.00297) * $y) - 0.11677) * $y) + 365242.49558) * $y) + 1721325.70455;
                    break;
                case 'WINTERSOLSTICE':
                default:
                    $juliandate = (((((((-0.00006 * $y) - 0.00933) * $y) - 0.00769) * $y) + 365242.88257) * $y) + 1721414.39987;
            }
        } elseif (($year > 1000) && ($year <= 3000)) {
            $y = ($year - 2000) / 1000;
            switch ($season) {
                case 'VERNALEQUINOX':
                    $juliandate = (((((((-0.00057 * $y) - 0.00411) * $y) + 0.05169) * $y) + 365242.37404) * $y) + 2451623.80984;
                    break;
                case 'SUMMERSOLSTICE':
                    $juliandate = (((((((-0.0003 * $y) + 0.00888) * $y) + 0.00325) * $y) + 365241.62603) * $y) + 2451716.56767;
                    break;
                case 'AUTUMNALEQUINOX':
                    $juliandate = (((((((0.00078 * $y) + 0.00337) * $y) - 0.11575) * $y) + 365242.01767) * $y) + 2451810.21715;
                    break;
                case 'WINTERSOLSTICE':
                default:
                    $juliandate = (((((((0.00032 * $y) - 0.00823) * $y) - 0.06223) * $y) + 365242.74049) * $y) + 2451900.05952;
            }
        }
        return $juliandate;
    }

    // }}}
    // {{{ dateNow()

    /**
     * Returns the current local date
     *
     * NOTE: This function retrieves the local date using strftime(),
     * which may or may not be 32-bit safe on your system.
     *
     * @param string $format  the string indicating how to format the output
     *
     * @return string  the current date in the specified format
     *
     * @access public
     * @static
     */
    function dateNow($format = DATE_CALC_FORMAT)
    {
        return strftime($format, time());
    }

    // }}}
    // {{{ getYear()

    /**
     * Returns the current local year in format CCYY
     *
     * @return string  the current year in four digit format
     *
     * @access public
     * @static
     */
    function getYear()
    {
        return Date_Calc::dateNow('%Y');
    }

    // }}}
    // {{{ getMonth()

    /**
     * Returns the current local month in format MM
     *
     * @return string  the current month in two digit format
     *
     * @access public
     * @static
     */
    function getMonth()
    {
        return Date_Calc::dateNow('%m');
    }

    // }}}
    // {{{ getDay()

    /**
     * Returns the current local day in format DD
     *
     * @return string  the current day of the month in two digit format
     *
     * @access public
     * @static
     */
    function getDay()
    {
        return Date_Calc::dateNow('%d');
    }

    // }}}
    // {{{ julianDate()

    /**
     * Returns number of days since 31 December of year before given date
     *
     * @param int    $day     the day of the month, default is current local day
     * @param int    $month   the month, default is current local month
     * @param int    $year    the year in four digit format, default is current local year
     *
     * @return int  the julian date for the date
     *
     * @access public
     * @static
     */
    function julianDate($day = 0, $month = 0, $year = 0)
    {
        if (empty($year)) {
            $year = Date_Calc::dateNow('%Y');
        }
        if (empty($month)) {
            $month = Date_Calc::dateNow('%m');
        }
        if (empty($day)) {
            $day = Date_Calc::dateNow('%d');
        }
        $days = array(0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334);
        $julian = ($days[$month - 1] + $day);
        if ($month > 2 && Date_Calc::isLeapYear($year)) {
            $julian++;
        }
        return $julian;
    }

    // }}}
    // {{{ getWeekdayFullname()

    /**
     * Returns the full weekday name for the given date
     *
     * @param int    $day     the day of the month, default is current local day
     * @param int    $month   the month, default is current local month
     * @param int    $year    the year in four digit format, default is current local year
     *
     * @return string  the full name of the day of the week
     *
     * @access public
     * @static
     */
    function getWeekdayFullname($day = 0, $month = 0, $year = 0)
    {
        if (empty($year)) {
            $year = Date_Calc::dateNow('%Y');
        }
        if (empty($month)) {
            $month = Date_Calc::dateNow('%m');
        }
        if (empty($day)) {
            $day = Date_Calc::dateNow('%d');
        }
        $weekday_names = Date_Calc::getWeekDays();
        $weekday = Date_Calc::dayOfWeek($day, $month, $year);
        return $weekday_names[$weekday];
    }

    // }}}
    // {{{ getWeekdayAbbrname()

    /**
     * Returns the abbreviated weekday name for the given date
     *
     * @param int    $day     the day of the month, default is current local day
     * @param int    $month   the month, default is current local month
     * @param int    $year    the year in four digit format, default is current local year
     * @param int    $length  the length of abbreviation
     *
     * @return string  the abbreviated name of the day of the week
     *
     * @access public
     * @static
     * @see Date_Calc::getWeekdayFullname()
     */
    function getWeekdayAbbrname($day = 0, $month = 0, $year = 0, $length = 3)
    {
        if (empty($year)) {
            $year = Date_Calc::dateNow('%Y');
        }
        if (empty($month)) {
            $month = Date_Calc::dateNow('%m');
        }
        if (empty($day)) {
            $day = Date_Calc::dateNow('%d');
        }
        return substr(Date_Calc::getWeekdayFullname($day, $month, $year),
                      0, $length);
    }

    // }}}
    // {{{ getMonthFullname()

    /**
     * Returns the full month name for the given month
     *
     * @param int    $month   the month
     *
     * @return string  the full name of the month
     *
     * @access public
     * @static
     */
    function getMonthFullname($month)
    {
        $month = (int)$month;
        if (empty($month)) {
            $month = (int)Date_Calc::dateNow('%m');
        }
        $month_names = Date_Calc::getMonthNames();
        return $month_names[$month];
    }

    // }}}
    // {{{ getMonthAbbrname()

    /**
     * Returns the abbreviated month name for the given month
     *
     * @param int    $month   the month
     * @param int    $length  the length of abbreviation
     *
     * @return string  the abbreviated name of the month
     *
     * @access public
     * @static
     * @see Date_Calc::getMonthFullname
     */
    function getMonthAbbrname($month, $length = 3)
    {
        $month = (int)$month;
        if (empty($month)) {
            $month = Date_Calc::dateNow('%m');
        }
        return substr(Date_Calc::getMonthFullname($month), 0, $length);
    }

    // }}}
    // {{{ getMonthFromFullname()

    /**
     * Returns the numeric month from the month name or an abreviation
     *
     * Both August and Aug would return 8.
     *
     * @param string $month  the name of the month to examine.
     *                        Case insensitive.
     *
     * @return integer  the month's number
     *
     * @access public
     * @static
     */
    function getMonthFromFullName($month)
    {
        $month = strtolower($month);
        $months = Date_Calc::getMonthNames();
        while(list($id, $name) = each($months)) {
            if (ereg($month, strtolower($name))) {
                return $id;
            }
        }
        return 0;
    }

    // }}}
    // {{{ getMonthNames()

    /**
     * Returns an array of month names
     *
     * Used to take advantage of the setlocale function to return
     * language specific month names.
     *
     * TODO: cache values to some global array to avoid preformace
     * hits when called more than once.
     *
     * @returns array  an array of month names
     *
     * @access public
     * @static
     */
    function getMonthNames()
    {
        $months = array();
        for ($i = 1; $i < 13; $i++) {
            $months[$i] = strftime('%B', mktime(0, 0, 0, $i, 1, 2001));
        }
        return $months;
    }

    // }}}
    // {{{ getWeekDays()

    /**
     * Returns an array of week days
     *
     * Used to take advantage of the setlocale function to
     * return language specific week days.
     *
     * TODO: cache values to some global array to avoid preformace
     * hits when called more than once.
     *
     * @returns array  an array of week day names
     *
     * @access public
     * @static
     */
    function getWeekDays()
    {
        $weekdays = array();
        for ($i = 0; $i < 7; $i++) {
            $weekdays[$i] = strftime('%A', mktime(0, 0, 0, 1, $i, 2001));
        }
        return $weekdays;
    }

    // }}}
    // {{{ dayOfWeek()

    /**
     * Returns day of week for given date (0 = Sunday)
     *
     * @param int    $day     the day of the month, default is current local day
     * @param int    $month   the month, default is current local month
     * @param int    $year    the year in four digit format, default is current local year
     *
     * @return int  the number of the day in the week
     *
     * @access public
     * @static
     */
    function dayOfWeek($day = 0, $month = 0, $year = 0)
    {
        if (empty($year)) {
            $year = Date_Calc::dateNow('%Y');
        }
        if (empty($month)) {
            $month = Date_Calc::dateNow('%m');
        }
        if (empty($day)) {
            $day = Date_Calc::dateNow('%d');
        }
        if ($month > 2) {
            $month -= 2;
        } else {
            $month += 10;
            $year--;
        }

        $day = (floor((13 * $month - 1) / 5) +
                $day + ($year % 100) +
                floor(($year % 100) / 4) +
                floor(($year / 100) / 4) - 2 *
                floor($year / 100) + 77);

        $weekday_number = $day - 7 * floor($day / 7);
        return $weekday_number;
    }

    // }}}
    // {{{ weekOfYear()

    /**
     * Returns week of the year, first Sunday is first day of first week
     *
     * @param int    $day     the day of the month, default is current local day
     * @param int    $month   the month, default is current local month
     * @param int    $year    the year in four digit format, default is current local year
     *
     * @return int  the number of the week in the year
     *
     * @access public
     * @static
     */
    function weekOfYear($day = 0, $month = 0, $year = 0)
    {
        if (empty($year)) {
            $year = Date_Calc::dateNow('%Y');
        }
        if (empty($month)) {
            $month = Date_Calc::dateNow('%m');
        }
        if (empty($day)) {
            $day = Date_Calc::dateNow('%d');
        }
        $iso    = Date_Calc::gregorianToISO($day, $month, $year);
        $parts  = explode('-', $iso);
        $week_number = intval($parts[1]);
        return $week_number;
    }

    // }}}
    // {{{ quarterOfYear()

    /**
     * Returns quarter of the year for given date
     *
     * @param int    $day     the day of the month, default is current local day
     * @param int    $month   the month, default is current local month
     * @param int    $year    the year in four digit format, default is current local year
     *
     * @return int  the number of the quarter in the year
     *
     * @access public
     * @static
     */
    function quarterOfYear($day = 0, $month = 0, $year = 0)
    {
        if (empty($year)) {
            $year = Date_Calc::dateNow('%Y');
        }
        if (empty($month)) {
            $month = Date_Calc::dateNow('%m');
        }
        if (empty($day)) {
            $day = Date_Calc::dateNow('%d');
        }
        $year_quarter = intval(($month - 1) / 3 + 1);
        return $year_quarter;
    }

    // }}}
    // {{{ daysInMonth()

    /**
     * Find the number of days in the given month
     *
     * @param int    $month   the month, default is current local month
     * @param int    $year    the year in four digit format, default is current local year
     *
     * @return int  the number of days the month has
     *
     * @access public
     * @static
     */
    function daysInMonth($month = 0, $year = 0)
    {
        if (empty($year)) {
            $year = Date_Calc::dateNow('%Y');
        }
        if (empty($month)) {
            $month = Date_Calc::dateNow('%m');
        }

        if ($year == 1582 && $month == 10) {
            return 21;  // October 1582 only had 1st-4th and 15th-31st
        }

        if ($month == 2) {
            if (Date_Calc::isLeapYear($year)) {
                return 29;
             } else {
                return 28;
            }
        } elseif ($month == 4 or $month == 6 or $month == 9 or $month == 11) {
            return 30;
        } else {
            return 31;
        }
    }

    // }}}
    // {{{ weeksInMonth()

    /**
     * Returns the number of rows on a calendar month
     *
     * Useful for determining the number of rows when displaying a typical
     * month calendar.
     *
     * @param int    $month   the month, default is current local month
     * @param int    $year    the year in four digit format, default is current local year
     *
     * @return int  the number of weeks the month has
     *
     * @access public
     * @static
     */
    function weeksInMonth($month = 0, $year = 0)
    {
        if (empty($year)) {
            $year = Date_Calc::dateNow('%Y');
        }
        if (empty($month)) {
            $month = Date_Calc::dateNow('%m');
        }
        $FDOM = Date_Calc::firstOfMonthWeekday($month, $year);
        if (DATE_CALC_BEGIN_WEEKDAY==1 && $FDOM==0) {
            $first_week_days = 7 - $FDOM + DATE_CALC_BEGIN_WEEKDAY;
            $weeks = 1;
        } elseif (DATE_CALC_BEGIN_WEEKDAY==0 && $FDOM == 6) {
            $first_week_days = 7 - $FDOM + DATE_CALC_BEGIN_WEEKDAY;
            $weeks = 1;
        } else {
            $first_week_days = DATE_CALC_BEGIN_WEEKDAY - $FDOM;
            $weeks = 0;
        }
        $first_week_days %= 7;
        return ceil((Date_Calc::daysInMonth($month, $year)
                     - $first_week_days) / 7) + $weeks;
    }

    // }}}
    // {{{ getCalendarWeek()

    /**
     * Return an array with days in week
     *
     * @param int    $day     the day of the month, default is current local day
     * @param int    $month   the month, default is current local month
     * @param int    $year    the year in four digit format, default is current local year
     * @param string $format  the string indicating how to format the output
     *
     * @return array $week[$weekday]
     *
     * @access public
     * @static
     */
    function getCalendarWeek($day = 0, $month = 0, $year = 0,
                             $format = DATE_CALC_FORMAT)
    {
        if (empty($year)) {
            $year = Date_Calc::dateNow('%Y');
        }
        if (empty($month)) {
            $month = Date_Calc::dateNow('%m');
        }
        if (empty($day)) {
            $day = Date_Calc::dateNow('%d');
        }

        $week_array = array();

        // date for the column of week

        $curr_day = Date_Calc::beginOfWeek($day, $month, $year,'%E');

        for ($counter = 0; $counter <= 6; $counter++) {
            $week_array[$counter] = Date_Calc::daysToDate($curr_day, $format);
            $curr_day++;
        }
        return $week_array;
    }

    // }}}
    // {{{ getCalendarMonth()

    /**
     * Return a set of arrays to construct a calendar month for the given date
     *
     * @param int    $month   the month, default is current local month
     * @param int    $year    the year in four digit format, default is current local year
     * @param string $format  the string indicating how to format the output
     *
     * @return array $month[$row][$col]
     *
     * @access public
     * @static
     */
    function getCalendarMonth($month = 0, $year = 0,
                              $format = DATE_CALC_FORMAT)
    {
        if (empty($year)) {
            $year = Date_Calc::dateNow('%Y');
        }
        if (empty($month)) {
            $month = Date_Calc::dateNow('%m');
        }

        $month_array = array();

        // date for the first row, first column of calendar month
        if (DATE_CALC_BEGIN_WEEKDAY == 1) {
            if (Date_Calc::firstOfMonthWeekday($month, $year) == 0) {
                $curr_day = Date_Calc::dateToDays('01', $month, $year) - 6;
            } else {
                $curr_day = Date_Calc::dateToDays('01', $month, $year)
                    - Date_Calc::firstOfMonthWeekday($month, $year) + 1;
            }
        } else {
            $curr_day = (Date_Calc::dateToDays('01', $month, $year)
                - Date_Calc::firstOfMonthWeekday($month, $year));
        }

        // number of days in this month
        $daysInMonth = Date_Calc::daysInMonth($month, $year);

        $weeksInMonth = Date_Calc::weeksInMonth($month, $year);
        for ($row_counter = 0; $row_counter < $weeksInMonth; $row_counter++) {
            for ($column_counter = 0; $column_counter <= 6; $column_counter++) {
                $month_array[$row_counter][$column_counter] =
                        Date_Calc::daysToDate($curr_day , $format);
                $curr_day++;
            }
        }

        return $month_array;
    }

    // }}}
    // {{{ getCalendarYear()

    /**
     * Return a set of arrays to construct a calendar year for the given date
     *
     * @param int    $year    the year in four digit format, default current local year
     * @param string $format  the string indicating how to format the output
     *
     * @return array $year[$month][$row][$col]
     *
     * @access public
     * @static
     */
    function getCalendarYear($year = 0, $format = DATE_CALC_FORMAT)
    {
        if (empty($year)) {
            $year = Date_Calc::dateNow('%Y');
        }

        $year_array = array();

        for ($curr_month = 0; $curr_month <= 11; $curr_month++) {
            $year_array[$curr_month] =
                    Date_Calc::getCalendarMonth($curr_month + 1,
                                                $year, $format);
        }

        return $year_array;
    }

    // }}}
    // {{{ prevDay()

    /**
     * Returns date of day before given date
     *
     * @param int    $day     the day of the month, default is current local day
     * @param int    $month   the month, default is current local month
     * @param int    $year    the year in four digit format, default is current local year
     * @param string $format  the string indicating how to format the output
     *
     * @return string  the date in the desired format
     *
     * @access public
     * @static
     */
    function prevDay($day = 0, $month = 0, $year = 0,
                     $format = DATE_CALC_FORMAT)
    {
        if (empty($year)) {
            $year = Date_Calc::dateNow('%Y');
        }
        if (empty($month)) {
            $month = Date_Calc::dateNow('%m');
        }
        if (empty($day)) {
            $day = Date_Calc::dateNow('%d');
        }
        $days = Date_Calc::dateToDays($day, $month, $year);
        return Date_Calc::daysToDate($days - 1, $format);
    }

    // }}}
    // {{{ nextDay()

    /**
     * Returns date of day after given date
     *
     * @param int    $day     the day of the month, default is current local day
     * @param int    $month   the month, default is current local month
     * @param int    $year    the year in four digit format, default is current local year
     * @param string $format  the string indicating how to format the output
     *
     * @return string  the date in the desired format
     *
     * @access public
     * @static
     */
    function nextDay($day = 0, $month = 0, $year = 0,
                     $format = DATE_CALC_FORMAT)
    {
        if (empty($year)) {
            $year = Date_Calc::dateNow('%Y');
        }
        if (empty($month)) {
            $month = Date_Calc::dateNow('%m');
        }
        if (empty($day)) {
            $day = Date_Calc::dateNow('%d');
        }
        $days = Date_Calc::dateToDays($day, $month, $year);
        return Date_Calc::daysToDate($days + 1, $format);
    }

    // }}}
    // {{{ prevWeekday()

    /**
     * Returns date of the previous weekday, skipping from Monday to Friday
     *
     * @param int    $day     the day of the month, default is current local day
     * @param int    $month   the month, default is current local month
     * @param int    $year    the year in four digit format, default is current local year
     * @param string $format  the string indicating how to format the output
     *
     * @return string  the date in the desired format
     *
     * @access public
     * @static
     */
    function prevWeekday($day = 0, $month = 0, $year = 0,
                         $format = DATE_CALC_FORMAT)
    {
        if (empty($year)) {
            $year = Date_Calc::dateNow('%Y');
        }
        if (empty($month)) {
            $month = Date_Calc::dateNow('%m');
        }
        if (empty($day)) {
            $day = Date_Calc::dateNow('%d');
        }
        $days = Date_Calc::dateToDays($day, $month, $year);
        if (Date_Calc::dayOfWeek($day, $month, $year) == 1) {
            $days -= 3;
        } elseif (Date_Calc::dayOfWeek($day, $month, $year) == 0) {
            $days -= 2;
        } else {
            $days -= 1;
        }
        return Date_Calc::daysToDate($days, $format);
    }

    // }}}
    // {{{ nextWeekday()

    /**
     * Returns date of the next weekday of given date, skipping from
     * Friday to Monday
     *
     * @param int    $day     the day of the month, default is current local day
     * @param int    $month   the month, default is current local month
     * @param int    $year    the year in four digit format, default is current local year
     * @param string $format  the string indicating how to format the output
     *
     * @return string  the date in the desired format
     *
     * @access public
     * @static
     */
    function nextWeekday($day = 0, $month = 0, $year = 0,
                         $format = DATE_CALC_FORMAT)
    {
        if (empty($year)) {
            $year = Date_Calc::dateNow('%Y');
        }
        if (empty($month)) {
            $month = Date_Calc::dateNow('%m');
        }
        if (empty($day)) {
            $day = Date_Calc::dateNow('%d');
        }
        $days = Date_Calc::dateToDays($day, $month, $year);
        if (Date_Calc::dayOfWeek($day, $month, $year) == 5) {
            $days += 3;
        } elseif (Date_Calc::dayOfWeek($day, $month, $year) == 6) {
            $days += 2;
        } else {
            $days += 1;
        }
        return Date_Calc::daysToDate($days, $format);
    }

    // }}}
    // {{{ prevDayOfWeek()

    /**
     * Returns date of the previous specific day of the week
     * from the given date
     *
     * @param int day of week, 0=Sunday
     * @param int    $day     the day of the month, default is current local day
     * @param int    $month   the month, default is current local month
     * @param int    $year    the year in four digit format, default is current local year
     * @param bool   $onOrBefore  if true and days are same, returns current day
     * @param string $format  the string indicating how to format the output
     *
     * @return string  the date in the desired format
     *
     * @access public
     * @static
     */
    function prevDayOfWeek($dow, $day = 0, $month = 0, $year = 0,
                           $format = DATE_CALC_FORMAT, $onOrBefore = false)
    {
        if (empty($year)) {
            $year = Date_Calc::dateNow('%Y');
        }
        if (empty($month)) {
            $month = Date_Calc::dateNow('%m');
        }
        if (empty($day)) {
            $day = Date_Calc::dateNow('%d');
        }
        $days = Date_Calc::dateToDays($day, $month, $year);
        $curr_weekday = Date_Calc::dayOfWeek($day, $month, $year);
        if ($curr_weekday == $dow) {
            if (!$onOrBefore) {
                $days -= 7;
            }
        } elseif ($curr_weekday < $dow) {
            $days -= 7 - ($dow - $curr_weekday);
        } else {
            $days -= $curr_weekday - $dow;
        }
        return Date_Calc::daysToDate($days, $format);
    }

    // }}}
    // {{{ nextDayOfWeek()

    /**
     * Returns date of the next specific day of the week
     * from the given date
     *
     * @param int    $dow     the day of the week (0 = Sunday)
     * @param int    $day     the day of the month, default is current local day
     * @param int    $month   the month, default is current local month
     * @param int    $year    the year in four digit format, default is current local year
     * @param bool   $onOrAfter  if true and days are same, returns current day
     * @param string $format  the string indicating how to format the output
     *
     * @return string  the date in the desired format
     *
     * @access public
     * @static
     */
    function nextDayOfWeek($dow, $day = 0, $month = 0, $year = 0,
                           $format = DATE_CALC_FORMAT, $onOrAfter = false)
    {
        if (empty($year)) {
            $year = Date_Calc::dateNow('%Y');
        }
        if (empty($month)) {
            $month = Date_Calc::dateNow('%m');
        }
        if (empty($day)) {
            $day = Date_Calc::dateNow('%d');
        }

        $days = Date_Calc::dateToDays($day, $month, $year);
        $curr_weekday = Date_Calc::dayOfWeek($day, $month, $year);

        if ($curr_weekday == $dow) {
            if (!$onOrAfter) {
                $days += 7;
            }
        } elseif ($curr_weekday > $dow) {
            $days += 7 - ($curr_weekday - $dow);
        } else {
            $days += $dow - $curr_weekday;
        }

        return Date_Calc::daysToDate($days, $format);
    }

    // }}}
    // {{{ prevDayOfWeekOnOrBefore()

    /**
     * Returns date of the previous specific day of the week
     * on or before the given date
     *
     * @param int    $dow     the day of the week (0 = Sunday)
     * @param int    $day     the day of the month, default is current local day
     * @param int    $month   the month, default is current local month
     * @param int    $year    the year in four digit format, default is current local year
     * @param string $format  the string indicating how to format the output
     *
     * @return string  the date in the desired format
     *
     * @access public
     * @static
     */
    function prevDayOfWeekOnOrBefore($dow, $day = 0, $month = 0, $year = 0,
                                     $format = DATE_CALC_FORMAT)
    {
        return Date_Calc::prevDayOfWeek($dow, $day, $month, $year, $format,
                                        true);
    }

    // }}}
    // {{{ nextDayOfWeekOnOrAfter()

    /**
     * Returns date of the next specific day of the week
     * on or after the given date
     *
     * @param int    $dow     the day of the week (0 = Sunday)
     * @param int    $day     the day of the month, default is current local day
     * @param int    $month   the month, default is current local month
     * @param int    $year    the year in four digit format, default is current local year
     * @param string $format  the string indicating how to format the output
     *
     * @return string  the date in the desired format
     *
     * @access public
     * @static
     */
    function nextDayOfWeekOnOrAfter($dow, $day = 0, $month = 0, $year = 0,
                                    $format = DATE_CALC_FORMAT)
    {
        return Date_Calc::nextDayOfWeek($dow, $day, $month, $year, $format,
                                        true);
    }

    // }}}
    // {{{ beginOfWeek()

    /**
     * Find the month day of the beginning of week for given date,
     * using DATE_CALC_BEGIN_WEEKDAY
     *
     * Can return weekday of prev month.
     *
     * @param int    $day     the day of the month, default is current local day
     * @param int    $month   the month, default is current local month
     * @param int    $year    the year in four digit format, default is current local year
     * @param string $format  the string indicating how to format the output
     *
     * @return string  the date in the desired format
     *
     * @access public
     * @static
     */
    function beginOfWeek($day = 0, $month = 0, $year = 0,
                         $format = DATE_CALC_FORMAT)
    {
        if (empty($year)) {
            $year = Date_Calc::dateNow('%Y');
        }
        if (empty($month)) {
            $month = Date_Calc::dateNow('%m');
        }
        if (empty($day)) {
            $day = Date_Calc::dateNow('%d');
        }
        $this_weekday = Date_Calc::dayOfWeek($day, $month, $year);
        $interval = (7 - DATE_CALC_BEGIN_WEEKDAY + $this_weekday) % 7;
        return Date_Calc::daysToDate(Date_Calc::dateToDays($day, $month, $year)
                                     - $interval, $format);
    }

    // }}}
    // {{{ endOfWeek()

    /**
     * Find the month day of the end of week for given date,
     * using DATE_CALC_BEGIN_WEEKDAY
     *
     * Can return weekday of following month.
     *
     * @param int    $day     the day of the month, default is current local day
     * @param int    $month   the month, default is current local month
     * @param int    $year    the year in four digit format, default is current local year
     * @param string $format  the string indicating how to format the output
     *
     * @return string  the date in the desired format
     *
     * @access public
     * @static
     */
    function endOfWeek($day = 0, $month = 0, $year = 0,
                       $format = DATE_CALC_FORMAT)
    {
        if (empty($year)) {
            $year = Date_Calc::dateNow('%Y');
        }
        if (empty($month)) {
            $month = Date_Calc::dateNow('%m');
        }
        if (empty($day)) {
            $day = Date_Calc::dateNow('%d');
        }
        $this_weekday = Date_Calc::dayOfWeek($day, $month, $year);
        $interval = (6 + DATE_CALC_BEGIN_WEEKDAY - $this_weekday) % 7;
        return Date_Calc::daysToDate(Date_Calc::dateToDays($day, $month, $year)
                                     + $interval, $format);
    }

    // }}}
    // {{{ beginOfPrevWeek()

    /**
     * Find the month day of the beginning of week before given date,
     * using DATE_CALC_BEGIN_WEEKDAY
     *
     * Can return weekday of prev month.
     *
     * @param int    $day     the day of the month, default is current local day
     * @param int    $month   the month, default is current local month
     * @param int    $year    the year in four digit format, default is current local year
     * @param string $format  the string indicating how to format the output
     *
     * @return string  the date in the desired format
     *
     * @access public
     * @static
     */
    function beginOfPrevWeek($day = 0, $month = 0, $year = 0,
                             $format = DATE_CALC_FORMAT)
    {
        if (empty($year)) {
            $year = Date_Calc::dateNow('%Y');
        }
        if (empty($month)) {
            $month = Date_Calc::dateNow('%m');
        }
        if (empty($day)) {
            $day = Date_Calc::dateNow('%d');
        }

        $date = Date_Calc::daysToDate(Date_Calc::dateToDays($day-7,
                                                            $month,
                                                            $year),
                                      '%Y%m%d');

        $prev_week_year  = substr($date, 0, 4);
        $prev_week_month = substr($date, 4, 2);
        $prev_week_day   = substr($date, 6, 2);

        return Date_Calc::beginOfWeek($prev_week_day, $prev_week_month,
                                      $prev_week_year, $format);
    }

    // }}}
    // {{{ beginOfNextWeek()

    /**
     * Find the month day of the beginning of week after given date,
     * using DATE_CALC_BEGIN_WEEKDAY
     *
     * Can return weekday of prev month.
     *
     * @param int    $day     the day of the month, default is current local day
     * @param int    $month   the month, default is current local month
     * @param int    $year    the year in four digit format, default is current local year
     * @param string $format  the string indicating how to format the output
     *
     * @return string  the date in the desired format
     *
     * @access public
     * @static
     */
    function beginOfNextWeek($day = 0, $month = 0, $year = 0,
                             $format = DATE_CALC_FORMAT)
    {
        if (empty($year)) {
            $year = Date_Calc::dateNow('%Y');
        }
        if (empty($month)) {
            $month = Date_Calc::dateNow('%m');
        }
        if (empty($day)) {
            $day = Date_Calc::dateNow('%d');
        }

        $date = Date_Calc::daysToDate(Date_Calc::dateToDays($day + 7,
                                                            $month,
                                                            $year),
                                      '%Y%m%d');

        $next_week_year  = substr($date, 0, 4);
        $next_week_month = substr($date, 4, 2);
        $next_week_day   = substr($date, 6, 2);

        return Date_Calc::beginOfWeek($next_week_day, $next_week_month,
                                      $next_week_year, $format);
    }

    // }}}
    // {{{ beginOfMonth()

    /**
     * Return date of first day of month of given date
     *
     * @param int    $month   the month, default is current local month
     * @param int    $year    the year in four digit format, default is current local year
     * @param string $format  the string indicating how to format the output
     *
     * @return string  the date in the desired format
     *
     * @access public
     * @static
     * @see Date_Calc::beginOfMonthBySpan()
     * @deprecated Method deprecated in Release 1.4.4
     */
    function beginOfMonth($month = 0, $year = 0, $format = DATE_CALC_FORMAT)
    {
        if (empty($year)) {
            $year = Date_Calc::dateNow('%Y');
        }
        if (empty($month)) {
            $month = Date_Calc::dateNow('%m');
        }
        return Date_Calc::dateFormat('01', $month, $year, $format);
    }

    // }}}
    // {{{ beginOfPrevMonth()

    /**
     * Returns date of the first day of previous month of given date
     *
     * @param int    $day     the day of the month, default is current local day
     * @param int    $month   the month, default is current local month
     * @param int    $year    the year in four digit format, default is current local year
     * @param string $format  the string indicating how to format the output
     *
     * @return string  the date in the desired format
     *
     * @access public
     * @static
     * @see Date_Calc::beginOfMonthBySpan()
     * @deprecated Method deprecated in Release 1.4.4
     */
    function beginOfPrevMonth($day = 0, $month = 0, $year = 0,
                              $format = DATE_CALC_FORMAT)
    {
        if (empty($year)) {
            $year = Date_Calc::dateNow('%Y');
        }
        if (empty($month)) {
            $month = Date_Calc::dateNow('%m');
        }
        if (empty($day)) {
            $day = Date_Calc::dateNow('%d');
        }
        if ($month > 1) {
            $month--;
            $day = 1;
        } else {
            $year--;
            $month = 12;
            $day   = 1;
        }
        return Date_Calc::dateFormat($day, $month, $year, $format);
    }

    // }}}
    // {{{ endOfPrevMonth()

    /**
     * Returns date of the last day of previous month for given date
     *
     * @param int    $day     the day of the month, default is current local day
     * @param int    $month   the month, default is current local month
     * @param int    $year    the year in four digit format, default is current local year
     * @param string $format  the string indicating how to format the output
     *
     * @return string  the date in the desired format
     *
     * @access public
     * @static
     * @see Date_Calc::endOfMonthBySpan()
     * @deprecated Method deprecated in Release 1.4.4
     */
    function endOfPrevMonth($day = 0, $month = 0, $year = 0,
                            $format = DATE_CALC_FORMAT)
    {
        if (empty($year)) {
            $year = Date_Calc::dateNow('%Y');
        }
        if (empty($month)) {
            $month = Date_Calc::dateNow('%m');
        }
        if (empty($day)) {
            $day = Date_Calc::dateNow('%d');
        }
        if ($month > 1) {
            $month--;
        } else {
            $year--;
            $month = 12;
        }
        $day = Date_Calc::daysInMonth($month, $year);
        return Date_Calc::dateFormat($day, $month, $year, $format);
    }

    // }}}
    // {{{ beginOfNextMonth()

    /**
     * Returns date of begin of next month of given date
     *
     * @param int    $day     the day of the month, default is current local day
     * @param int    $month   the month, default is current local month
     * @param int    $year    the year in four digit format, default is current local year
     * @param string $format  the string indicating how to format the output
     *
     * @return string  the date in the desired format
     *
     * @access public
     * @static
     * @see Date_Calc::beginOfMonthBySpan()
     * @deprecated Method deprecated in Release 1.4.4
     */
    function beginOfNextMonth($day = 0, $month = 0, $year = 0,
                              $format = DATE_CALC_FORMAT)
    {
        if (empty($year)) {
            $year = Date_Calc::dateNow('%Y');
        }
        if (empty($month)) {
            $month = Date_Calc::dateNow('%m');
        }
        if (empty($day)) {
            $day = Date_Calc::dateNow('%d');
        }
        if ($month < 12) {
            $month++;
            $day = 1;
        } else {
            $year++;
            $month = 1;
            $day = 1;
        }
        return Date_Calc::dateFormat($day, $month, $year, $format);
    }

    // }}}
    // {{{ endOfNextMonth()

    /**
     * Returns date of the last day of next month of given date
     *
     * @param int    $day     the day of the month, default is current local day
     * @param int    $month   the month, default is current local month
     * @param int    $year    the year in four digit format, default is current local year
     * @param string $format  the string indicating how to format the output
     *
     * @return string  the date in the desired format
     *
     * @access public
     * @static
     * @see Date_Calc::endOfMonthBySpan()
     * @deprecated Method deprecated in Release 1.4.4
     */
    function endOfNextMonth($day = 0, $month = 0, $year = 0,
                            $format = DATE_CALC_FORMAT)
    {
        if (empty($year)) {
            $year = Date_Calc::dateNow('%Y');
        }
        if (empty($month)) {
            $month = Date_Calc::dateNow('%m');
        }
        if (empty($day)) {
            $day = Date_Calc::dateNow('%d');
        }
        if ($month < 12) {
            $month++;
        } else {
            $year++;
            $month = 1;
        }
        $day = Date_Calc::daysInMonth($month, $year);
        return Date_Calc::dateFormat($day, $month, $year, $format);
    }

    // }}}
    // {{{ beginOfMonthBySpan()

    /**
     * Returns date of the first day of the month in the number of months
     * from the given date
     *
     * @param int    $months  the number of months from the date provided.
     *                         Positive numbers go into the future.
     *                         Negative numbers go into the past.
     *                         0 is the month presented in $month.
     * @param string $month   the month, default is current local month
     * @param string $year    the year in four digit format, default is the
     *                         current local year
     * @param string $format  the string indicating how to format the output
     *
     * @return string  the date in the desired format
     *
     * @access public
     * @static
     * @since  Method available since Release 1.4.4
     */
    function beginOfMonthBySpan($months = 0, $month = 0, $year = 0,
                                $format = DATE_CALC_FORMAT)
    {
        if (empty($year)) {
            $year = Date_Calc::dateNow('%Y');
        }
        if (empty($month)) {
            $month = Date_Calc::dateNow('%m');
        }
        if ($months > 0) {
            // future month
            $tmp_mo = $month + $months;
            $month  = $tmp_mo % 12;
            if ($month == 0) {
                $month = 12;
                $year = $year + floor(($tmp_mo - 1) / 12);
            } else {
                $year = $year + floor($tmp_mo / 12);
            }
        } else {
            // past or present month
            $tmp_mo = $month + $months;
            if ($tmp_mo > 0) {
                // same year
                $month = $tmp_mo;
            } elseif ($tmp_mo == 0) {
                // prior dec
                $month = 12;
                $year--;
            } else {
                // some time in a prior year
                $month = 12 + ($tmp_mo % 12);
                $year  = $year + floor($tmp_mo / 12);
            }
        }
        return Date_Calc::dateFormat(1, $month, $year, $format);
    }

    // }}}
    // {{{ endOfMonthBySpan()

    /**
     * Returns date of the last day of the month in the number of months
     * from the given date
     *
     * @param int    $months  the number of months from the date provided.
     *                         Positive numbers go into the future.
     *                         Negative numbers go into the past.
     *                         0 is the month presented in $month.
     * @param string $month   the month, default is current local month
     * @param string $year    the year in four digit format, default is the
     *                         current local year
     * @param string $format  the string indicating how to format the output
     *
     * @return string  the date in the desired format
     *
     * @access public
     * @static
     * @since  Method available since Release 1.4.4
     */
    function endOfMonthBySpan($months = 0, $month = 0, $year = 0,
                              $format = DATE_CALC_FORMAT)
    {
        if (empty($year)) {
            $year = Date_Calc::dateNow('%Y');
        }
        if (empty($month)) {
            $month = Date_Calc::dateNow('%m');
        }
        if ($months > 0) {
            // future month
            $tmp_mo = $month + $months;
            $month  = $tmp_mo % 12;
            if ($month == 0) {
                $month = 12;
                $year = $year + floor(($tmp_mo - 1) / 12);
            } else {
                $year = $year + floor($tmp_mo / 12);
            }
        } else {
            // past or present month
            $tmp_mo = $month + $months;
            if ($tmp_mo > 0) {
                // same year
                $month = $tmp_mo;
            } elseif ($tmp_mo == 0) {
                // prior dec
                $month = 12;
                $year--;
            } else {
                // some time in a prior year
                $month = 12 + ($tmp_mo % 12);
                $year  = $year + floor($tmp_mo / 12);
            }
        }
        return Date_Calc::dateFormat(Date_Calc::daysInMonth($month, $year),
                                     $month, $year, $format);
    }

    // }}}
    // {{{ firstOfMonthWeekday()

    /**
     * Find the day of the week for the first of the month of given date
     *
     * @param int    $month   the month, default is current local month
     * @param int    $year    the year in four digit format, default is current local year
     *
     * @return int number of weekday for the first day, 0=Sunday
     *
     * @access public
     * @static
     */
    function firstOfMonthWeekday($month = 0, $year = 0)
    {
        if (empty($year)) {
            $year = Date_Calc::dateNow('%Y');
        }
        if (empty($month)) {
            $month = Date_Calc::dateNow('%m');
        }
        return Date_Calc::dayOfWeek('01', $month, $year);
    }

    // }}}
    // {{{ NWeekdayOfMonth()

    /**
     * Calculates the date of the Nth weekday of the month,
     * such as the second Saturday of January 2000
     *
     * @param int    $week    the number of the week to get
     *                         (1 = first, etc.  Also can be 'last'.)
     * @param int    $dow     the day of the week (0 = Sunday)
     * @param int    $month   the month
     * @param int    $year    the year.  Use the complete year instead of the
     *                         abbreviated version.  E.g. use 2005, not 05.
     *                         Do not add leading 0's for years prior to 1000.
     * @param string $format  the string indicating how to format the output
     *
     * @return string  the date in the desired format
     *
     * @access public
     * @static
     */
    function NWeekdayOfMonth($week, $dow, $month, $year,
                             $format = DATE_CALC_FORMAT)
    {
        if (is_numeric($week)) {
            $DOW1day = ($week - 1) * 7 + 1;
            $DOW1    = Date_Calc::dayOfWeek($DOW1day, $month, $year);
            $wdate   = ($week - 1) * 7 + 1 + (7 + $dow - $DOW1) % 7;
            if ($wdate > Date_Calc::daysInMonth($month, $year)) {
                return -1;
            } else {
                return Date_Calc::dateFormat($wdate, $month, $year, $format);
            }
        } elseif ($week == 'last' && $dow < 7) {
            $lastday = Date_Calc::daysInMonth($month, $year);
            $lastdow = Date_Calc::dayOfWeek($lastday, $month, $year);
            $diff    = $dow - $lastdow;
            if ($diff > 0) {
                return Date_Calc::dateFormat($lastday - (7 - $diff), $month,
                                             $year, $format);
            } else {
                return Date_Calc::dateFormat($lastday + $diff, $month,
                                             $year, $format);
            }
        } else {
            return -1;
        }
    }

    // }}}
    // {{{ isValidDate()

    /**
     * Returns true for valid date, false for invalid date
     *
     * @param int    $day     the day of the month
     * @param int    $month   the month
     * @param int    $year    the year.  Use the complete year instead of the
     *                         abbreviated version.  E.g. use 2005, not 05.
     *                         Do not add leading 0's for years prior to 1000.
     *
     * @return boolean
     *
     * @access public
     * @static
     */
    function isValidDate($day, $month, $year)
    {
        if ($year < 0 || $year > 9999) {
            return false;
        }
        if (!checkdate($month, $day, $year)) {
            return false;
        }
        return true;
    }

    // }}}
    // {{{ isLeapYear()

    /**
     * Returns true for a leap year, else false
     *
     * @param int    $year    the year.  Use the complete year instead of the
     *                         abbreviated version.  E.g. use 2005, not 05.
     *                         Do not add leading 0's for years prior to 1000.
     *
     * @return boolean
     *
     * @access public
     * @static
     */
    function isLeapYear($year = 0)
    {
        if (empty($year)) {
            $year = Date_Calc::dateNow('%Y');
        }
        if (preg_match('/\D/', $year)) {
            return false;
        }
        if ($year < 1000) {
            return false;
        }
        if ($year < 1582) {
            // pre Gregorio XIII - 1582
            return ($year % 4 == 0);
        } else {
            // post Gregorio XIII - 1582
            return (($year % 4 == 0) && ($year % 100 != 0)) || ($year % 400 == 0);
        }
    }

    // }}}
    // {{{ isFutureDate()

    /**
     * Determines if given date is a future date from now
     *
     * @param int    $day     the day of the month
     * @param int    $month   the month
     * @param int    $year    the year.  Use the complete year instead of the
     *                         abbreviated version.  E.g. use 2005, not 05.
     *                         Do not add leading 0's for years prior to 1000.
     *
     * @return boolean
     *
     * @access public
     * @static
     */
    function isFutureDate($day, $month, $year)
    {
        $this_year  = Date_Calc::dateNow('%Y');
        $this_month = Date_Calc::dateNow('%m');
        $this_day   = Date_Calc::dateNow('%d');

        if ($year > $this_year) {
            return true;
        } elseif ($year == $this_year) {
            if ($month > $this_month) {
                return true;
            } elseif ($month == $this_month) {
                if ($day > $this_day) {
                    return true;
                }
            }
        }
        return false;
    }

    // }}}
    // {{{ isPastDate()

    /**
     * Determines if given date is a past date from now
     *
     * @param int    $day     the day of the month
     * @param int    $month   the month
     * @param int    $year    the year.  Use the complete year instead of the
     *                         abbreviated version.  E.g. use 2005, not 05.
     *                         Do not add leading 0's for years prior to 1000.
     *
     * @return boolean
     *
     * @access public
     * @static
     */
    function isPastDate($day, $month, $year)
    {
        $this_year  = Date_Calc::dateNow('%Y');
        $this_month = Date_Calc::dateNow('%m');
        $this_day   = Date_Calc::dateNow('%d');

        if ($year < $this_year) {
            return true;
        } elseif ($year == $this_year) {
            if ($month < $this_month) {
                return true;
            } elseif ($month == $this_month) {
                if ($day < $this_day) {
                    return true;
                }
            }
        }
        return false;
    }

    // }}}
    // {{{ dateDiff()

    /**
     * Returns number of days between two given dates
     *
     * @param int    $day1    the day of the month
     * @param int    $month1  the month
     * @param int    $year1   the year.  Use the complete year instead of the
     *                         abbreviated version.  E.g. use 2005, not 05.
     *                         Do not add leading 0's for years prior to 1000.
     * @param int    $day2    the day of the month
     * @param int    $month2  the month
     * @param int    $year2   the year.  Use the complete year instead of the
     *                         abbreviated version.  E.g. use 2005, not 05.
     *                         Do not add leading 0's for years prior to 1000.
     *
     * @return int  the absolute number of days between the two dates.
     *               If an error occurs, -1 is returned.
     *
     * @access public
     * @static
     */
    function dateDiff($day1, $month1, $year1, $day2, $month2, $year2)
    {
        if (!Date_Calc::isValidDate($day1, $month1, $year1)) {
            return -1;
        }
        if (!Date_Calc::isValidDate($day2, $month2, $year2)) {
            return -1;
        }
        return abs(Date_Calc::dateToDays($day1, $month1, $year1)
                   - Date_Calc::dateToDays($day2, $month2, $year2));
    }

    // }}}
    // {{{ compareDates()

    /**
     * Compares two dates
     *
     * @param int    $day1    the day of the month
     * @param int    $month1  the month
     * @param int    $year1   the year.  Use the complete year instead of the
     *                         abbreviated version.  E.g. use 2005, not 05.
     *                         Do not add leading 0's for years prior to 1000.
     * @param int    $day2    the day of the month
     * @param int    $month2  the month
     * @param int    $year2   the year.  Use the complete year instead of the
     *                         abbreviated version.  E.g. use 2005, not 05.
     *                         Do not add leading 0's for years prior to 1000.
     *
     * @return int  0 if the dates are equal. 1 if date 1 is later, -1 if
     *               date 1 is earlier.
     *
     * @access public
     * @static
     */
    function compareDates($day1, $month1, $year1, $day2, $month2, $year2)
    {
        $ndays1 = Date_Calc::dateToDays($day1, $month1, $year1);
        $ndays2 = Date_Calc::dateToDays($day2, $month2, $year2);
        if ($ndays1 == $ndays2) {
            return 0;
        }
        return ($ndays1 > $ndays2) ? 1 : -1;
    }

    // }}}
}

// }}}

/*
 * Local variables:
 * mode: php
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */
?>