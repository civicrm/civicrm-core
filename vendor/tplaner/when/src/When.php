<?php

namespace When;

class When extends \DateTime
{
    const EXCEPTION = 0;
    const NOTICE = 1;
    const IGNORE = 2;

    public $RFC5545_COMPLIANT = self::EXCEPTION;
    public $startDate;
    public $freq;
    public $until;
    public $count;
    public $interval;
    public $exclusions = array();

    public $byseconds;
    public $byminutes;
    public $byhours;
    public $bydays;
    public $bymonthdays;
    public $byyeardays;
    public $byweeknos;
    public $bymonths;
    public $bysetpos;
    public $wkst;

    public $occurrences = array();
    public $rangeLimit  = 200;

    public function __construct($time = "now", $timezone = NULL)
    {
        parent::__construct($time, $timezone);
        $this->startDate = new \DateTime($time, $timezone);
    }

    public function startDate($startDate)
    {
        if (Valid::dateTimeObject($startDate))
        {
            $this->startDate = clone $startDate;

            return $this;
        }

	    throw new \InvalidArgumentException("startDate: Accepts valid DateTime objects");
    }

    public function freq($frequency)
    {
        if (Valid::freq($frequency))
        {
            $this->freq = strtolower($frequency);

            return $this;
        }

        throw new \InvalidArgumentException("freq: Accepts " . rtrim(implode(Valid::$frequencies, ", "), ","));
    }

    public function until($endDate)
    {
        if (Valid::dateTimeObject($endDate))
        {
            $this->until = clone $endDate;
            return $this;
        }

        throw new \InvalidArgumentException("until: Accepts valid DateTime objects");
    }

    public function count($count)
    {
        if (is_numeric($count))
        {
            $this->count = (int)$count;

            return $this;
        }

        throw new \InvalidArgumentException("count: Accepts numeric values");
    }

    public function interval($interval)
    {
        if (is_numeric($interval))
        {
            $this->interval = (int)$interval;

            return $this;
        }

        throw new \InvalidArgumentException("interval: Accepts numeric values");
    }

    public function bysecond($seconds, $delimiter = ",")
    {
        if ($this->byseconds = self::prepareItemsList($seconds, $delimiter, 'second'))
        {
            return $this;
        }

        throw new \InvalidArgumentException("bysecond: Accepts numeric values between 0 and 60");
    }

    public function byminute($minutes, $delimiter = ",")
    {
        if ($this->byminutes = self::prepareItemsList($minutes, $delimiter, 'minute'))
        {
            return $this;
        }

        throw new \InvalidArgumentException("byminute: Accepts numeric values between 0 and 59");
    }

    public function byhour($hours, $delimiter = ",")
    {
        if ($this->byhours = self::prepareItemsList($hours, $delimiter, 'hour'))
        {
            return $this;
        }

        throw new \InvalidArgumentException("byhour: Accepts numeric values between 0 and 23");
    }

    public function byday($bywdaylist, $delimiter = ",")
    {
        if (is_string($bywdaylist) && strpos($bywdaylist, $delimiter) !== false)
        {
            // remove any accidental delimiters
            $bywdaylist = trim($bywdaylist, $delimiter);

            $bywdaylist = explode($delimiter, $bywdaylist);
        }
        else if(is_string($bywdaylist))
        {
            // remove any accidental delimiters
            $bywdaylist = trim($bywdaylist, $delimiter);

            $bywdaylist = array($bywdaylist);
        }

        if (is_array($bywdaylist) && Valid::daysList($bywdaylist))
        {
            $this->bydays = self::createDaysList($bywdaylist);

            return $this;
        }

        throw new \InvalidArgumentException("bydays: Accepts (optional) positive and negative values between 1 and 53 followed by a valid week day");
    }
    
    public function exclusions ($exclusionList, $delimiter = ",") {
        
        if (is_string($exclusionList)) {
            if (strpos($exclusionList, $delimiter) !== false)
            {
                // remove any accidental delimiters
                $exclusionList = trim($exclusionList, $delimiter);
    
                $exclusionList = explode($delimiter, $exclusionList);
            }
            else
            {
                // remove any accidental delimiters
                $exclusionList = trim($exclusionList, $delimiter);
    
                $exclusionList = array($exclusionList);
            }
            
            $exclusionList = array_map('date_create',$exclusionList);
        }
        if (is_array($exclusionList)  && Valid::dateTimeList($exclusionList))
        {
            $this->exclusions = array_filter($exclusionList,['When\Valid','dateTimeObject']);
            return $this;
        }
        throw new \InvalidArgumentException("exclusions: Accepts (optional) a array of date time objects or a string seperated by a specified delimiter.");
    }

    public function bymonthday($bymodaylist, $delimiter = ",")
    {
        if($this->bymonthdays = self::prepareItemsList($bymodaylist, $delimiter, 'monthDayNum'))
        {
            return $this;
        }

        throw new \InvalidArgumentException("bymonthday: Accepts positive and negative values between 1 and 31");
    }

    public function byyearday($byyrdaylist, $delimiter = ",")
    {
        if($this->byyeardays = self::prepareItemsList($byyrdaylist, $delimiter, 'yearDayNum'))
        {
            return $this;
        }

        throw new \InvalidArgumentException("byyearday: Accepts positive and negative values between 1 and 366");
    }

    public function byweekno($bywknolist, $delimiter = ",")
    {
        if($this->byweeknos = self::prepareItemsList($bywknolist, $delimiter, 'weekNum'))
        {
            return $this;
        }

        throw new \InvalidArgumentException("byweekno: Accepts positive and negative values between 1 and 53");
    }

    public function bymonth($bymolist, $delimiter = ",")
    {
        if($this->bymonths = self::prepareItemsList($bymolist, $delimiter, 'monthNum'))
        {
            return $this;
        }

        throw new \InvalidArgumentException("bymonth: Accepts values between 1 and 12");
    }

    public function bysetpos($bysplist, $delimiter = ",")
    {
        if ($this->bysetpos = self::prepareItemsList($bysplist, $delimiter, 'setPosDay'))
        {
            return $this;
        }

        throw new \InvalidArgumentException("bysetpos: Accepts positive and negative values between 1 and 366");
    }

    public function wkst($weekDay)
    {
        if (Valid::weekDay($weekDay))
        {
            $this->wkst = strtolower($weekDay);

            return $this;
        }

	    throw new \InvalidArgumentException("wkst: Accepts " . rtrim(implode(Valid::$weekDays, ", "), ","));
    }

    public function rrule($rrule)
    {
        // strip off a trailing semi-colon
        $rrule = trim($rrule, ";");

        $parts = explode(";", $rrule);

        foreach($parts as $part)
        {
            list($rule, $param) = explode("=", $part);

            $rule = strtoupper($rule);
            $param = strtoupper($param);

            switch($rule)
            {
                case "DTSTART":
                    $this->startDate(new \DateTime($param));
                    break;
                case "UNTIL":
                    $this->until(new \DateTime($param));
                    break;
                case "FREQ":
                case "COUNT":
                case "INTERVAL":
                case "WKST":
                    $this->{$rule}($param);
                    break;
                case "BYDAY":
                case "BYMONTHDAY":
                case "BYYEARDAY":
                case "BYWEEKNO":
                case "BYMONTH":
                case "BYSETPOS":
                case "BYHOUR":
                case "BYMINUTE":
                case "BYSECOND":
                    $params = explode(",", $param);
                    $this->{$rule}($params);
                    break;
            }
        }

        return $this;
    }

    public function occursOn($date)
    {
        if (!Valid::dateTimeObject($date))
        {
            throw new \InvalidArgumentException("occursOn: Accepts valid DateTime objects");
        }

        // breakdown the date
        $year = $date->format('Y');
        $month = $date->format('n');
        $day = $date->format('j');
        $dayFromEndOfMonth = -((int)$date->format('t') + 1 - (int)$day);

        $leapYear = (int)$date->format('L');

        $yearDay = $date->format('z') + 1;
        $yearDayNeg = -366 + (int)$yearDay;
        if ($leapYear)
        {
            $yearDayNeg = -367 + (int)$yearDay;
        }

        // this is the nth occurrence of the date
        $occur = ceil($day / 7);
        $occurNeg = -1 * ceil(abs($dayFromEndOfMonth) / 7);

        // starting on a monday
        $week = $date->format('W');
        $weekDay = strtolower($date->format('D'));

        $dayOfWeek = $date->format('l');
        $dayOfWeekAbr = strtolower(substr($dayOfWeek, 0, 2));

        // the date has to be greater then the start date
        if ($date < $this->startDate)
        {
            return false;
        }

        // if the there is an end date, make sure date is under
        if (isset($this->until))
        {
            if ($date > $this->until)
            {
                return false;
            }
        }

        if (isset($this->bymonths))
        {
            if (!in_array($month, $this->bymonths))
            {
                return false;
            }
        }

        if (isset($this->bydays))
        {
            if (!in_array(0 . $dayOfWeekAbr, $this->bydays) &&
                !in_array($occur . $dayOfWeekAbr, $this->bydays) &&
                !in_array($occurNeg . $dayOfWeekAbr, $this->bydays))
            {
                return false;
            }
        }

        if (isset($this->byweeknos))
        {
            if (!in_array($week, $this->byweeknos))
            {
                return false;
            }
        }

        if (isset($this->bymonthdays))
        {
            if (!in_array($day, $this->bymonthdays) &&
                !in_array($dayFromEndOfMonth, $this->bymonthdays))
            {
                return false;
            }
        }

        if (isset($this->byyeardays))
        {
            if (!in_array($yearDay, $this->byyeardays) &&
                !in_array($yearDayNeg, $this->byyeardays))
            {
                return false;
            }
        }

        // If there is an interval != 1, check whether this is an nth period.
        if ($this->interval > 1) {
            switch ($this->freq) {
            case 'yearly':
                $start = new \DateTime($this->startDate->format("Y-1-1\TH:i:sP"));
                $sinceStart = $date->diff($start);
                $numPeriods = $sinceStart->y;
                break;
            case 'monthly':
                // Normalize to the first of the month, so patterns that land on nth weekday
                // aren't affected by the shift of the nth weekday back and forth by day of month.
                // Use UTC so timezone offset shifts don't cause fencepost errors.
                $utc = new \DateTimezone("UTC");
                $start = new \DateTime($this->startDate->format("Y-m-1\TH:i:s"), $utc);
                $dateMonthStart = new \DateTime($date->format("Y-m-1\TH:i:s"), $utc);
                $sinceStart = $dateMonthStart->diff($start);
                $numYears = $sinceStart->y;
                $numMonths = $sinceStart->m;
                $numPeriods = ($numYears * 12) + $numMonths;
                break;
            case 'weekly':
                if (isset($this->bydays)) {
                    $weekStartDate = self::getFirstWeekStartDate($this->startDate, $this->wkst);
                }
                else {
                    $weekStartDate = $this->startDate;
                }
                $sinceStart = $date->diff($weekStartDate);
                $numPeriods = floor($sinceStart->days / 7);
                break;
            case 'daily':
                $sinceStart = $date->diff($this->startDate); // Note we "expanded" startDate already.
                $numPeriods = $sinceStart->days;
                break;
            case 'hourly':
                $sinceStart = $date->diff($this->startDate); // Note we "expanded" startDate already.
                $numDays = $sinceStart->days;
                $numHours = $sinceStart->h;
                $numPeriods = (24 * $numDays) + $numHours;
                break;
            case 'minutely':
                $sinceStart = $date->diff($this->startDate); // Note we "expanded" startDate already.
                $numDays = $sinceStart->days;
                $numHours = $sinceStart->h;
                $numMinutes = $sinceStart->i;
                $numPeriods = (60 * ((24 * $numDays) + $numHours)) + $numMinutes;
                break;
            case 'secondly':
                $sinceStart = $date->diff($this->startDate); // Note we "expanded" startDate already.
                $numDays = $sinceStart->days;
                $numHours = $sinceStart->h;
                $numMinutes = $sinceStart->i;
                $numSeconds = $sinceStart->s;
                $numPeriods = (60 * (60 * ((24 * $numDays) + $numHours)) + $numMinutes) + $numSeconds;
                break;
            }
            if (($numPeriods % $this->interval) == 0) {
                return true;
            }
            else {
                return false;
            }
        }

        return true;
    }

    public function occursAt($date)
    {
        $hour = (int)$date->format('G');
        $minute = (int)$date->format('i');
        $second = (int)$date->format('s');

        if (isset($this->byhours))
        {
            if (!in_array($hour, $this->byhours))
            {
                return false;
            }
        }

        if (isset($this->byminutes))
        {
            if (!in_array($minute, $this->byminutes))
            {
                return false;
            }
        }

        if (isset($this->byseconds))
        {
            if (!in_array($second, $this->byseconds))
            {
                return false;
            }
        }

        return true;
    }

    // Get occurrences between two DateTimes, exclusive. Does not modify $this.
    public function getOccurrencesBetween($startDate, $endDate, $limit=NULL) {

    	$thisClone = clone $this;

        // Enforce consistent time zones. Date comparisons don't require them, but +P1D loop does.
        if ($tz = $thisClone->getTimeZone()) {
            $startDate->setTimeZone($tz);
            $endDate->setTimeZone($tz);
        }

        $occurrences = array();

        if ($endDate <= $startDate) {
            return $occurrences;
        }

        // if existing UNTIL < startDate - we have nothing
        if (isset($thisClone->until) && $thisClone->until < $startDate) {
            return $occurrences;
        }
        // prevent unnecessary leg-work - our endDate is our new UNTIL
        elseif (!isset($thisClone->until)) {
            $thisClone->until = $endDate;
        }

        $thisClone->generateOccurrences();
        $all_occurrences = $thisClone->occurrences;

        // nothing found in $thisClone->generateOccurrences();
        if (empty($all_occurrences)) {
            return $occurrences;
        }

        $last_occurrence = end($all_occurrences);

        // if we've hit the rangeLimit, restart looking but start at this last_occurrence
        if ($thisClone->rangeLimit == count($all_occurrences)
        	&& $thisClone->startDate != $last_occurrence)
        {
            $thisClone->startDate = clone $last_occurrence;

            if (isset($thisClone->limit)) {
                $thisClone->limit = $thisClone->limit - 200;
            }

            // clear all occurrences before our start date
            foreach ($thisClone->occurrences as $key => $occurrence) {
            	if ( $occurrence < $startDate )
            	{
            		unset($thisClone->occurrences[$key]);
            	}
            }

            return $thisClone->getOccurrencesBetween($startDate, $endDate, $limit);
        }

        // if our last occurrence is is before our startDate, we have nothing
        if ($last_occurrence < $startDate) {
            return $occurrences;
        }

        // we have something to report, so reset our array pointer
        reset($all_occurrences);

        $count = 0;

        foreach($all_occurrences as $occurrence) {
            // fastforward our pointer to where it's >= startDate
            if ($occurrence < $startDate) {
                continue;
            }
            // if current occurence is past our endDate - we're done
            if ($occurrence > $endDate) {
                break;
            }
            // if we reach getOccurrencesBetween()'s limit - we're done
            if (NULL != $limit && ++$count > $limit) {
                break;
            }

            $occurrences[] = $occurrence;
        }

        return $occurrences;
    }

    private function findDateRangeOverlap($startDate, $endDate) {
        // Trim to the defined range of this When:
        if ($this->startDate > $startDate) {
            $startDate = clone $this->startDate;
        }
        if ($this->until && ($this->until < $endDate)) {
            $endDate = clone $this->until;
        }
        return array($startDate, $endDate);
    }

    private function countOccurrencesBefore($date) {
        return count($this->getOccurrencesBetween($this->startDate, $date));
    }

    private static function abbrevToDayName($abbrev) {
        $daynames = array('su' => 'Sunday',
                          'mo' => 'Monday',
                          'tu' => 'Tuesday',
                          'we' => 'Wednesday',
                          'th' => 'Thursday',
                          'fr' => 'Friday',
                          'sa' => 'Saturyday',
        );
        return $daynames[strtolower($abbrev)];
    }

    /**
     * "The WKST rule part specifies the day on which the workweek starts. [...]
     * This is significant when a WEEKLY "RRULE" has an interval greater than 1,
     * and a BYDAY rule part is specified." -- RFC 5545
     * See http://stackoverflow.com/questions/5750586/determining-occurrences-from-icalendar-rrule-that-expands
     */
    public static function getFirstWeekStartDate($startDate, $wkst) {
        $wkst = self::abbrevToDayName($wkst);
        $startWeekDay = clone $startDate;

        // Get first $wkst before or equal to $startDate
        $startWeekDay->modify("next " . $wkst);
        $startWeekDay->modify("last " . $wkst);

        return $startWeekDay;
    }

    public function getNextOccurrence($occurDate, $strictly_after=true) {

        self::prepareDateElements(false);

        if (! $strictly_after) {
            if ($this->occursOn($occurDate) && $this->occursAt($occurDate)) {
                return $occurDate;
            }
        }

        // Set an arbitrary end date, taking the 400Y advice from elsewhere in this module.
        // TODO: do this in smaller chunks so we don't get a bunch of unneeded occurrences
        $endDate = clone $occurDate;
        $endDate->add(new \DateInterval('P400Y'));
        $candidates = $this->getOccurrencesBetween($occurDate, $endDate, 2);
        foreach ($candidates as $candidate) {
            if (! $strictly_after) {
                return $candidate;
            }
            elseif ($candidate > $occurDate) {
                return $candidate;
            }
        }
        return false;
    }

    public function getPrevOccurrence($occurDate) {

        self::prepareDateElements(false);

        $startDate = $this->startDate;
        $candidates = $this->getOccurrencesBetween($startDate, $occurDate);
        if (count($candidates)) {
            $lastDate = array_pop($candidates);
            if ( $lastDate == $occurDate )
            {
                $lastDate = array_pop($candidates);
            }
            return $lastDate;
        }
        return false;
    }

    public function generateOccurrences()
    {
        self::prepareDateElements();

        $count = 0;

        $dateLooper = clone $this->startDate;

        // add the start date to the list of occurrences
        if ($this->occursOn($dateLooper))
        {
            $this->addOccurrence($this->generateTimeOccurrences($dateLooper));
        }
        else
        {
            switch ($this->RFC5545_COMPLIANT) {
                case self::NOTICE:
                    trigger_error('InvalidStartDate: startDate is outside the bounds of the occurrence parameters.');
                    break;
                case self::IGNORE:
                    break;
                case self::EXCEPTION:
                default:
                    throw new InvalidStartDate();
                    break;
            }
        }

        while ($dateLooper < $this->until && count($this->occurrences) < $this->count)
        {
            $occurrences = array();

            if ($this->freq === "yearly")
            {
                if (isset($this->bymonths))
                {
                    foreach ($this->bymonths as $month)
                    {
                        if (isset($this->bydays))
                        {
                            $dateLooper->setDate($dateLooper->format("Y"), $month, 1);

                            // get the number of days
                            $totalDays = $dateLooper->format("t");
                            $today = 0;

                            while ($today < $totalDays)
                            {
                                if ($this->occursOn($dateLooper))
                                {
                                    $occurrences = array_merge($occurrences, $this->generateTimeOccurrences($dateLooper));
                                }

                                $dateLooper->add(new \DateInterval('P1D'));
                                $today++;
                            }
                        }
                        else
                        {
                            $dateLooper->setDate($dateLooper->format("Y"), $month, $dateLooper->format("j"));

                            if ($this->occursOn($dateLooper))
                            {
                                $occurrences = array_merge($occurrences, $this->generateTimeOccurrences($dateLooper));
                            }
                        }
                    }
                }
                else
                {
                    $dateLooper->setDate($dateLooper->format("Y"), 1, 1);

                    $leapYear = (int)$dateLooper->format("L");
                    if ($leapYear)
                    {
                        $days = 366;
                    }
                    else
                    {
                        $days = 365;
                    }

                    $day = 0;
                    while ($day < $days)
                    {
                        if ($this->occursOn($dateLooper))
                        {
                            $occurrences = array_merge($occurrences, $this->generateTimeOccurrences($dateLooper));
                        }
                        $dateLooper->add(new \DateInterval('P1D'));
                        $day++;
                    }
                }

                $occurrences = $this->prepareOccurrences($occurrences, $count);
                $this->addOccurrence($occurrences);

                $dateLooper = clone $this->startDate;
                $dateLooper->add(new \DateInterval('P' . ($this->interval * ++$count) . 'Y'));
            }
            else if ($this->freq === "monthly")
            {
                $days = (int)$dateLooper->format("t");

                $day = (int)$dateLooper->format("j");

                while ($day <= $days)
                {
                    if ($this->occursOn($dateLooper))
                    {
                        $occurrences = array_merge($occurrences, $this->generateTimeOccurrences($dateLooper));
                    }

                    $dateLooper->add(new \DateInterval('P1D'));
                    $day++;
                }

                $occurrences = $this->prepareOccurrences($occurrences, $count);
                $this->addOccurrence($occurrences);

                $dateLooper = clone $this->startDate;
                $dateLooper->setDate($dateLooper->format("Y"), $dateLooper->format("n"), 1);
                $dateLooper->add(new \DateInterval('P' . ($this->interval * ++$count) . 'M'));
            }
            else if ($this->freq === "weekly")
            {
                $dateLooper->setDate($dateLooper->format("Y"), $dateLooper->format("n"), $dateLooper->format("j"));

                $wkst = self::abbrevToDayName($this->wkst);

                $daysLeft = 7;

                // not very happy with this
                if ($count === 0)
                {
                    $startWeekDay = clone $this->startDate;
                    $startWeekDay->modify("next " . $wkst);
                    $startWeekDay->setTime($dateLooper->format('H'), $dateLooper->format('i'), $dateLooper->format('s'));

                    $daysLeft = (int) $dateLooper->diff($startWeekDay)->format("%a");

                    $startWeekDay->modify("last " . $wkst);
                }

                while ($daysLeft > 0)
                {
                    if ($this->occursOn($dateLooper))
                    {
                        $occurrences = array_merge($occurrences, $this->generateTimeOccurrences($dateLooper));
                    }

                    $dateLooper->add(new \DateInterval('P1D'));
                    $daysLeft--;
                }

                $occurrences = $this->prepareOccurrences($occurrences, $count);
                $this->addOccurrence($occurrences);

                $dateLooper = clone $this->startDate;
                $dateLooper->setDate($startWeekDay->format("Y"), $startWeekDay->format("n"), $startWeekDay->format('j'));
                $dateLooper->add(new \DateInterval('P' . ($this->interval * (++$count * 7)) . 'D'));
            }
            else if ($this->freq === "daily")
            {
                if ($this->occursOn($dateLooper))
                {
                    $this->addOccurrence($this->generateTimeOccurrences($dateLooper));
                }

                $dateLooper = clone $this->startDate;
                $dateLooper->setDate($dateLooper->format("Y"), $dateLooper->format("n"), $dateLooper->format('j'));
                $dateLooper->add(new \DateInterval('P' . ($this->interval * ++$count) . 'D'));
            }
            else if ($this->freq === "hourly")
            {
                $occurrence = array();
                if ($this->occursOn($dateLooper))
                {
                    $occurrence[] = $dateLooper;
                    $this->addOccurrence($occurrence);
                }

                $dateLooper = clone $this->startDate;
                $dateLooper->add(new \DateInterval('PT' . ($this->interval * ++$count) . 'H'));
            }
            else if ($this->freq === "minutely")
            {
                $occurrence = array();
                if ($this->occursOn($dateLooper))
                {
                    $occurrence[] = $dateLooper;
                    $this->addOccurrence($occurrence);
                }

                $dateLooper = clone $this->startDate;
                $dateLooper->add(new \DateInterval('PT' . ($this->interval * ++$count) . 'M'));
            }
            else if ($this->freq === "secondly")
            {
                $occurrence = array();
                if ($this->occursOn($dateLooper))
                {
                    $occurrence[] = $dateLooper;
                    $this->addOccurrence($occurrence);
                }

                $dateLooper = clone $this->startDate;
                $dateLooper->add(new \DateInterval('PT' . ($this->interval * ++$count) . 'S'));

            }
        }
        // generateTimeOccurrences can overshoot $this->count, so trim:
        if ($this->count && (count($this->occurrences) >= $this->count)) {
            $this->occurrences = array_slice($this->occurrences, 0, $this->count);
        }
    }

    protected function prepareOccurrences($occurrences, $count = 0)
    {
        if (isset($this->bysetpos))
        {
            $filtered_occurrences = array();

            if ($count > 0)
            {
                $occurrenceCount = count($occurrences);

                foreach ($this->bysetpos as $setpos)
                {
                    if ($setpos > 0 && isset($occurrences[$setpos - 1]))
                    {
                        $filtered_occurrences[] = $occurrences[$setpos - 1];
                    }
                    elseif(isset($occurrences[$occurrenceCount + $setpos]))
                    {
                        $filtered_occurrences[] = $occurrences[$occurrenceCount + $setpos];
                    }
                }
            }

            $occurrences = $filtered_occurrences;
        }

        return $occurrences;
    }

    protected function addOccurrence($occurrences)
    {
        foreach ($occurrences as $occurrence)
        {
            // make sure that this occurrence isn't already in the list
            if (!in_array($occurrence, $this->occurrences) && (!count($this->exclusions) || !in_array($occurrence,$this->exclusions)))
            {
                $this->occurrences[] = $occurrence;
            }
        }
    }

    // not happy with this.
    protected function generateTimeOccurrences($dateLooper)
    {
        $occurrences = array();

        foreach ($this->byhours as $hour)
        {
            foreach ($this->byminutes as $minute)
            {
                foreach ($this->byseconds as $second)
                {
                    $occurrence = clone $dateLooper;
                    $occurrence->setTime($hour, $minute, $second);
                    $occurrences[] = $occurrence;
                }
            }
        }

        return $occurrences;
    }

    // If $limitRange is true, $this->count and $this->until will be set if not already set.
    protected function prepareDateElements($limitRange=true)
    {
        // if the interval isn't set, set it.
        if (!isset($this->interval))
        {
            $this->interval = 1;
        }

        // must have a frequency
        if (!isset($this->freq) && Valid::byFreqValid($this->freq, $this->byweeknos, $this->byyeardays, $this->bymonthdays))
        {
            throw new FrequencyRequired();
        }

        if ($limitRange && !isset($this->count))
        {
            $this->count = $this->rangeLimit;
        }

        // "Similarly, if the BYMINUTE, BYHOUR, BYDAY,
        // BYMONTHDAY, or BYMONTH rule part were missing, the appropriate
        // minute, hour, day, or month would have been retrieved from the
        // "DTSTART" property."

        // if there is no startDate, make it now
        if (!$this->startDate)
        {
            $this->startDate = new \DateTime();
        }

        // the calendar repeats itself every 400 years, so if a date
        // doesn't exist for 400 years, I don't think it will ever
        // occur
        if ($limitRange && !isset($this->until))
        {
            $this->until = new \DateTime();
            $this->until->add(new \DateInterval('P400Y'));
        }

        if (!isset($this->byminutes))
        {
            $this->byminutes = array((int)$this->startDate->format('i'));
        }

        if (!isset($this->byhours))
        {
            $this->byhours = array((int)$this->startDate->format('G'));
        }

        if (!isset($this->byseconds))
        {
            $this->byseconds = array((int)$this->startDate->format('s'));
        }

        if (!isset($this->wkst))
        {
            $this->wkst = "mo";
        }

        /*if (!isset($this->bydays))
        {
            $dayOfWeek = $this->startDate->format('l');
            $dayOfWeekAbr = strtolower(substr($dayOfWeek, 0, 2));
            $this->bydays = array($dayOfWeekAbr);
        }*/

        if ($this->freq === "monthly")
        {
            if (!isset($this->bymonthdays) && !isset($this->bydays))
            {
                $this->bymonthdays = array((int)$this->startDate->format('j'));
            }
        }

        if ($this->freq === "weekly")
        {
            if (!isset($this->bymonthdays) && !isset($this->bydays))
            {
                $dayOfWeek = $this->startDate->format('l');
                $dayOfWeekAbr = strtolower(substr($dayOfWeek, 0, 2));
                $this->bydays = array("0" . $dayOfWeekAbr);
            }
        }

        if ($this->freq === "yearly")
        {
            if (!isset($this->bydays) &&
                !isset($this->bymonths) &&
                !isset($this->bymonthdays) &&
                !isset($this->byyeardays) &&
                !isset($this->byweeknos) &&
                !isset($this->bysetpos))
            {
                $this->bymonth($this->startDate->format('n'));
            }
        }

    }

    protected static function createItemsList($list, $delimiter)
    {
        $items = explode($delimiter, $list);

        return array_map('intval', $items);
    }

    protected static function prepareItemsList($items, $delimiter = ",", $validator=null)
    {
        $_items = false;

        if (is_numeric($items))
        {
            $_items = array(intval($items));
        }

        if (is_string($items) && $_items === false)
        {
            // remove any accidental delimiters
            $items = trim($items, $delimiter);

            $_items = self::createItemsList($items, $delimiter);
        }

        if (is_array($items))
        {
            $_items = $items;
        }

        if (is_array($_items) && Valid::itemsList($_items, $validator))
        {
            return $_items;
        }

	    return false;
    }

    protected static function createDaysList($days)
    {
        $_days = array();

        foreach($days as $day)
        {
            $day = ltrim($day, "+");
            $day = trim($day);

            $ordwk = 0;
            $weekday = false;

            if (strlen($day) === 2)
            {
                $weekday = $day;
            }
            else
            {
                list($ordwk, $weekday) = sscanf($day, "%d%s");
            }

            $_days[] = $ordwk . strtolower($weekday);
        }

        return $_days;
    }
}

class InvalidCombination extends \Exception
{
    public function __construct($message = "Invalid combination.", $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

class FrequencyRequired extends \Exception
{
    public function __construct($message = "You are required to set a frequency.", $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

class InvalidStartDate extends \Exception
{
    public function __construct($message = "The start date must be the first occurrence.", $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
