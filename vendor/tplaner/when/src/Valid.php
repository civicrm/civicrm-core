<?php

namespace When;

class Valid
{
    public static $frequencies = array(
                                    'secondly', 'minutely', 'hourly',
                                    'daily', 'weekly', 'monthly', 'yearly'
                                );

    public static  $weekDays = array('su', 'mo', 'tu', 'we', 'th', 'fr', 'sa');

    /**
     * Test if array of days is valid
     *
     * @param  array    $days
     * @return bool
     */
    public static function daysList($days)
    {
        foreach($days as $day)
        {
            // if it isn't negative, it's positive
            $day = ltrim($day, "+");
            $day = trim($day);

            $ordwk = 1;
            $weekday = false;

            if (strlen($day) === 2)
            {
                $weekday = $day;
            }
            else
            {
                list($ordwk, $weekday) = sscanf($day, "%d%s");
            }

            if (!self::weekDay($weekday) || !self::ordWk(abs($ordwk)))
            {
                return false;
            }
        }

        return true;
    }

    /**
     * Test for valid itemsList
     *
     * @param  array    $items
     * @param  string   $validator  Validator to use agains the list (second, minute, hour)
     * @return bool
     */
    public static function itemsList($items, $validator)
    {
        foreach ($items as $item)
        {
            if (!self::$validator($item))
            {
                return false;
            }
        }

        return true;
    }

    public static function byFreqValid($freq, $byweeknos, $byyeardays, $bymonthdays)
    {
        if (isset($byweeknos) && $freq !== "yearly")
        {
            throw new InvalidCombination();
        }

        if (isset($byyeardays) && !in_array($freq, array("daily", "weekly", "monthly")))
        {
            throw new InvalidCombination();
        }

        if (isset($bymonthdays) && $freq === "weekly")
        {
            throw new InvalidCombination();
        }

        return true;
    }

    public static function yearDayNum($day)
    {
        return self::ordYrDay(abs($day));
    }

    public static function ordYrDay($ordyrday)
    {
        return ($ordyrday >= 1 && $ordyrday <= 366);
    }

    public static function monthDayNum($day)
    {
        return self::ordMoDay(abs($day));
    }

    public static function monthNum($month)
    {
        return ($month >= 1 && $month <= 12);
    }

    public static function setPosDay($day)
    {
        return self::yearDayNum($day);
    }

    /**
     * Tests for valid ordMoDay
     *
     * @param  integer $ordmoday
     * @return bool
     */
    public static function ordMoDay($ordmoday)
    {
        return ($ordmoday >= 1 && $ordmoday <= 31);
    }

    /**
     * Test for a valid weekNum
     *
     * @param  integer $week
     * @return bool
     */
    public static function weekNum($week)
    {
        return self::ordWk(abs($week));
    }

    /**
     * Test for valid ordWk
     *
     * TODO: ensure this doesn't suffer from Y2K bug since there can be 54 weeks in a year
     *
     * @param  integer $ordwk
     * @return bool
     */
    public static function ordWk($ordwk)
    {
        return ($ordwk >= 1 && $ordwk <= 53);
    }

    /**
     * Test for valid hour
     *
     * @param  integer $hour
     * @return bool
     */
    public static function hour($hour)
    {
        return ($hour >= 0 && $hour <= 23);
    }

    /**
     * Test for valid minute
     *
     * @param  integer $minute
     * @return bool
     */
    public static function minute($minute)
    {
        return ($minute >= 0 && $minute <= 59);
    }

    /**
     * Test for valid second
     *
     * @param  integer $second
     * @return bool
     */
    public static function second($second)
    {
        return ($second >= 0 && $second <= 60);
    }

    /**
     * Test for valid weekDay
     *
     * @param  string $weekDay
     * @return bool
     */
    public static function weekDay($weekDay)
    {
        return in_array(strtolower($weekDay), self::$weekDays);
    }

    /**
     * Test for valid frequency
     *
     * @param  string $frequency
     * @return bool
     */
    public static function freq($frequency)
    {
        return in_array(strtolower($frequency), self::$frequencies);
    }

    /**
     * Test for valid DateTime object
     *
     * @param  DateTime $dateTime
     * @return bool
     */
    public static function dateTimeObject($dateTime)
    {
        return (is_object($dateTime) && $dateTime instanceof \DateTime);
    }
    
    /**
     * Test for a list of valid DateTime objects
     *
     * @param  aray $dateTimes
     * @return bool
     */
    public static function dateTimeList($dateTimes)
    {
        return is_array($dateTimes) && array_filter($dateTimes, [__CLASS__, 'dateTimeObject']);
    }
}
