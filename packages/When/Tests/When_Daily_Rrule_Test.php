<?php

require_once 'PHPUnit/Framework.php';

require_once './When.php';

class When_Daily_Rrule_Test extends PHPUnit_Framework_TestCase
{
	/**
	 * Daily for 10 occurrences:
	 * DTSTART;TZID=America/New_York:19970902T090000
	 * RRULE:FREQ=DAILY;COUNT=10
	 */
	function testThirtyThree()
	{
		$results[] = new DateTime('1997-09-02 09:00:00');
		$results[] = new DateTime('1997-09-03 09:00:00');
		$results[] = new DateTime('1997-09-04 09:00:00');
		$results[] = new DateTime('1997-09-05 09:00:00');
		$results[] = new DateTime('1997-09-06 09:00:00');
		$results[] = new DateTime('1997-09-07 09:00:00');
		$results[] = new DateTime('1997-09-08 09:00:00');
		$results[] = new DateTime('1997-09-09 09:00:00');
		$results[] = new DateTime('1997-09-10 09:00:00');
		$results[] = new DateTime('1997-09-11 09:00:00');

		$r = new When();
		$r->recur('19970902T090000')->rrule('FREQ=DAILY;COUNT=10');

		foreach($results as $result)
		{
			$this->assertEquals($result, $r->next());
		}
	}

	/**
	 * Daily until December 24, 1997:
 	 * DTSTART;TZID=America/New_York:19970902T090000
 	 * RRULE:FREQ=DAILY;UNTIL=19971224T000000Z
	 */
	function testThirtyFour()
	{
		$results[] = new DateTime('1997-09-02 09:00:00');
		$results[] = new DateTime('1997-09-03 09:00:00');
		$results[] = new DateTime('1997-09-04 09:00:00');
		$results[] = new DateTime('1997-09-05 09:00:00');
		$results[] = new DateTime('1997-09-06 09:00:00');
		$results[] = new DateTime('1997-09-07 09:00:00');
		$results[] = new DateTime('1997-09-08 09:00:00');
		$results[] = new DateTime('1997-09-09 09:00:00');
		$results[] = new DateTime('1997-09-10 09:00:00');
		$results[] = new DateTime('1997-09-11 09:00:00');
		$results[] = new DateTime('1997-09-12 09:00:00');
		$results[] = new DateTime('1997-09-13 09:00:00');
		$results[] = new DateTime('1997-09-14 09:00:00');
		$results[] = new DateTime('1997-09-15 09:00:00');
		$results[] = new DateTime('1997-09-16 09:00:00');
		$results[] = new DateTime('1997-09-17 09:00:00');
		$results[] = new DateTime('1997-09-18 09:00:00');
		$results[] = new DateTime('1997-09-19 09:00:00');
		$results[] = new DateTime('1997-09-20 09:00:00');
		$results[] = new DateTime('1997-09-21 09:00:00');
		$results[] = new DateTime('1997-09-22 09:00:00');
		$results[] = new DateTime('1997-09-23 09:00:00');
		$results[] = new DateTime('1997-09-24 09:00:00');
		$results[] = new DateTime('1997-09-25 09:00:00');
		$results[] = new DateTime('1997-09-26 09:00:00');
		$results[] = new DateTime('1997-09-27 09:00:00');
		$results[] = new DateTime('1997-09-28 09:00:00');
		$results[] = new DateTime('1997-09-29 09:00:00');
		$results[] = new DateTime('1997-09-30 09:00:00');
		$results[] = new DateTime('1997-10-01 09:00:00');
		$results[] = new DateTime('1997-10-02 09:00:00');
		$results[] = new DateTime('1997-10-03 09:00:00');
		$results[] = new DateTime('1997-10-04 09:00:00');
		$results[] = new DateTime('1997-10-05 09:00:00');
		$results[] = new DateTime('1997-10-06 09:00:00');
		$results[] = new DateTime('1997-10-07 09:00:00');
		$results[] = new DateTime('1997-10-08 09:00:00');
		$results[] = new DateTime('1997-10-09 09:00:00');
		$results[] = new DateTime('1997-10-10 09:00:00');
		$results[] = new DateTime('1997-10-11 09:00:00');
		$results[] = new DateTime('1997-10-12 09:00:00');
		$results[] = new DateTime('1997-10-13 09:00:00');
		$results[] = new DateTime('1997-10-14 09:00:00');
		$results[] = new DateTime('1997-10-15 09:00:00');
		$results[] = new DateTime('1997-10-16 09:00:00');
		$results[] = new DateTime('1997-10-17 09:00:00');
		$results[] = new DateTime('1997-10-18 09:00:00');
		$results[] = new DateTime('1997-10-19 09:00:00');
		$results[] = new DateTime('1997-10-20 09:00:00');
		$results[] = new DateTime('1997-10-21 09:00:00');
		$results[] = new DateTime('1997-10-22 09:00:00');
		$results[] = new DateTime('1997-10-23 09:00:00');
		$results[] = new DateTime('1997-10-24 09:00:00');
		$results[] = new DateTime('1997-10-25 09:00:00');
		$results[] = new DateTime('1997-10-26 09:00:00');
		$results[] = new DateTime('1997-10-27 09:00:00');
		$results[] = new DateTime('1997-10-28 09:00:00');
		$results[] = new DateTime('1997-10-29 09:00:00');
		$results[] = new DateTime('1997-10-30 09:00:00');
		$results[] = new DateTime('1997-10-31 09:00:00');
		$results[] = new DateTime('1997-11-01 09:00:00');
		$results[] = new DateTime('1997-11-02 09:00:00');
		$results[] = new DateTime('1997-11-03 09:00:00');
		$results[] = new DateTime('1997-11-04 09:00:00');
		$results[] = new DateTime('1997-11-05 09:00:00');
		$results[] = new DateTime('1997-11-06 09:00:00');
		$results[] = new DateTime('1997-11-07 09:00:00');
		$results[] = new DateTime('1997-11-08 09:00:00');
		$results[] = new DateTime('1997-11-09 09:00:00');
		$results[] = new DateTime('1997-11-10 09:00:00');
		$results[] = new DateTime('1997-11-11 09:00:00');
		$results[] = new DateTime('1997-11-12 09:00:00');
		$results[] = new DateTime('1997-11-13 09:00:00');
		$results[] = new DateTime('1997-11-14 09:00:00');
		$results[] = new DateTime('1997-11-15 09:00:00');
		$results[] = new DateTime('1997-11-16 09:00:00');
		$results[] = new DateTime('1997-11-17 09:00:00');
		$results[] = new DateTime('1997-11-18 09:00:00');
		$results[] = new DateTime('1997-11-19 09:00:00');
		$results[] = new DateTime('1997-11-20 09:00:00');
		$results[] = new DateTime('1997-11-21 09:00:00');
		$results[] = new DateTime('1997-11-22 09:00:00');
		$results[] = new DateTime('1997-11-23 09:00:00');
		$results[] = new DateTime('1997-11-24 09:00:00');
		$results[] = new DateTime('1997-11-25 09:00:00');
		$results[] = new DateTime('1997-11-26 09:00:00');
		$results[] = new DateTime('1997-11-27 09:00:00');
		$results[] = new DateTime('1997-11-28 09:00:00');
		$results[] = new DateTime('1997-11-29 09:00:00');
		$results[] = new DateTime('1997-11-30 09:00:00');
		$results[] = new DateTime('1997-12-01 09:00:00');
		$results[] = new DateTime('1997-12-02 09:00:00');
		$results[] = new DateTime('1997-12-03 09:00:00');
		$results[] = new DateTime('1997-12-04 09:00:00');
		$results[] = new DateTime('1997-12-05 09:00:00');
		$results[] = new DateTime('1997-12-06 09:00:00');
		$results[] = new DateTime('1997-12-07 09:00:00');
		$results[] = new DateTime('1997-12-08 09:00:00');
		$results[] = new DateTime('1997-12-09 09:00:00');
		$results[] = new DateTime('1997-12-10 09:00:00');
		$results[] = new DateTime('1997-12-11 09:00:00');
		$results[] = new DateTime('1997-12-12 09:00:00');
		$results[] = new DateTime('1997-12-13 09:00:00');
		$results[] = new DateTime('1997-12-14 09:00:00');
		$results[] = new DateTime('1997-12-15 09:00:00');
		$results[] = new DateTime('1997-12-16 09:00:00');
		$results[] = new DateTime('1997-12-17 09:00:00');
		$results[] = new DateTime('1997-12-18 09:00:00');
		$results[] = new DateTime('1997-12-19 09:00:00');
		$results[] = new DateTime('1997-12-20 09:00:00');
		$results[] = new DateTime('1997-12-21 09:00:00');
		$results[] = new DateTime('1997-12-22 09:00:00');
		$results[] = new DateTime('1997-12-23 09:00:00');

		$r = new When();
		$r->recur('19970902T090000')->rrule('FREQ=DAILY;UNTIL=19971224T000000Z');

		foreach($results as $result)
		{
			$this->assertEquals($result, $r->next());
		}
	}

	/**
	 * Every other day - forever:
	 * DTSTART;TZID=America/New_York:19970902T090000
	 * RRULE:FREQ=DAILY;INTERVAL=2
	 */
	function testThirtyFive()
	{
		$results[] = new DateTime('1997-09-02 09:00:00');
		$results[] = new DateTime('1997-09-04 09:00:00');
		$results[] = new DateTime('1997-09-06 09:00:00');
		$results[] = new DateTime('1997-09-08 09:00:00');
		$results[] = new DateTime('1997-09-10 09:00:00');
		$results[] = new DateTime('1997-09-12 09:00:00');
		$results[] = new DateTime('1997-09-14 09:00:00');
		$results[] = new DateTime('1997-09-16 09:00:00');
		$results[] = new DateTime('1997-09-18 09:00:00');
		$results[] = new DateTime('1997-09-20 09:00:00');
		$results[] = new DateTime('1997-09-22 09:00:00');
		$results[] = new DateTime('1997-09-24 09:00:00');
		$results[] = new DateTime('1997-09-26 09:00:00');
		$results[] = new DateTime('1997-09-28 09:00:00');
		$results[] = new DateTime('1997-09-30 09:00:00');
		$results[] = new DateTime('1997-10-02 09:00:00');
		$results[] = new DateTime('1997-10-04 09:00:00');
		$results[] = new DateTime('1997-10-06 09:00:00');
		$results[] = new DateTime('1997-10-08 09:00:00');
		$results[] = new DateTime('1997-10-10 09:00:00');
		$results[] = new DateTime('1997-10-12 09:00:00');
		$results[] = new DateTime('1997-10-14 09:00:00');
		$results[] = new DateTime('1997-10-16 09:00:00');
		$results[] = new DateTime('1997-10-18 09:00:00');
		$results[] = new DateTime('1997-10-20 09:00:00');
		$results[] = new DateTime('1997-10-22 09:00:00');
		$results[] = new DateTime('1997-10-24 09:00:00');
		$results[] = new DateTime('1997-10-26 09:00:00');
		$results[] = new DateTime('1997-10-28 09:00:00');
		$results[] = new DateTime('1997-10-30 09:00:00');
		$results[] = new DateTime('1997-11-01 09:00:00');
		$results[] = new DateTime('1997-11-03 09:00:00');
		$results[] = new DateTime('1997-11-05 09:00:00');
		$results[] = new DateTime('1997-11-07 09:00:00');
		$results[] = new DateTime('1997-11-09 09:00:00');
		$results[] = new DateTime('1997-11-11 09:00:00');
		$results[] = new DateTime('1997-11-13 09:00:00');
		$results[] = new DateTime('1997-11-15 09:00:00');
		$results[] = new DateTime('1997-11-17 09:00:00');
		$results[] = new DateTime('1997-11-19 09:00:00');
		$results[] = new DateTime('1997-11-21 09:00:00');
		$results[] = new DateTime('1997-11-23 09:00:00');
		$results[] = new DateTime('1997-11-25 09:00:00');
		$results[] = new DateTime('1997-11-27 09:00:00');
		$results[] = new DateTime('1997-11-29 09:00:00');
		$results[] = new DateTime('1997-12-01 09:00:00');
		$results[] = new DateTime('1997-12-03 09:00:00');

		$r = new When();
		$r->recur('19970902T090000')->count(47)->rrule('FREQ=DAILY;INTERVAL=2');

		foreach($results as $result)
		{
			$this->assertEquals($result, $r->next());
		}
	}

	/**
	 * Every 10 days, 5 occurrences:
	 * DTSTART;TZID=America/New_York:19970902T090000
	 * RRULE:FREQ=DAILY;INTERVAL=10;COUNT=5
	 */
	function testThirtySix()
	{
		$results[] = new DateTime('1997-09-02 09:00:00');
		$results[] = new DateTime('1997-09-12 09:00:00');
		$results[] = new DateTime('1997-09-22 09:00:00');
		$results[] = new DateTime('1997-10-02 09:00:00');
		$results[] = new DateTime('1997-10-12 09:00:00');

		$r = new When();
		$r->recur('19970902T090000')->rrule('FREQ=DAILY;INTERVAL=10;COUNT=5');

		foreach($results as $result)
		{
			$this->assertEquals($result, $r->next());
		}
	}

	/**
	 * Every day in January, for 3 years:
	 * DTSTART;TZID=America/New_York:19980101T090000
	 * RRULE:FREQ=DAILY;UNTIL=20000131T140000Z;BYMONTH=1
	 */
	function testThirtySeven()
	{
		$results[] = new DateTime('1998-01-01 09:00:00');
		$results[] = new DateTime('1998-01-02 09:00:00');
		$results[] = new DateTime('1998-01-03 09:00:00');
		$results[] = new DateTime('1998-01-04 09:00:00');
		$results[] = new DateTime('1998-01-05 09:00:00');
		$results[] = new DateTime('1998-01-06 09:00:00');
		$results[] = new DateTime('1998-01-07 09:00:00');
		$results[] = new DateTime('1998-01-08 09:00:00');
		$results[] = new DateTime('1998-01-09 09:00:00');
		$results[] = new DateTime('1998-01-10 09:00:00');
		$results[] = new DateTime('1998-01-11 09:00:00');
		$results[] = new DateTime('1998-01-12 09:00:00');
		$results[] = new DateTime('1998-01-13 09:00:00');
		$results[] = new DateTime('1998-01-14 09:00:00');
		$results[] = new DateTime('1998-01-15 09:00:00');
		$results[] = new DateTime('1998-01-16 09:00:00');
		$results[] = new DateTime('1998-01-17 09:00:00');
		$results[] = new DateTime('1998-01-18 09:00:00');
		$results[] = new DateTime('1998-01-19 09:00:00');
		$results[] = new DateTime('1998-01-20 09:00:00');
		$results[] = new DateTime('1998-01-21 09:00:00');
		$results[] = new DateTime('1998-01-22 09:00:00');
		$results[] = new DateTime('1998-01-23 09:00:00');
		$results[] = new DateTime('1998-01-24 09:00:00');
		$results[] = new DateTime('1998-01-25 09:00:00');
		$results[] = new DateTime('1998-01-26 09:00:00');
		$results[] = new DateTime('1998-01-27 09:00:00');
		$results[] = new DateTime('1998-01-28 09:00:00');
		$results[] = new DateTime('1998-01-29 09:00:00');
		$results[] = new DateTime('1998-01-30 09:00:00');
		$results[] = new DateTime('1998-01-31 09:00:00');
		$results[] = new DateTime('1999-01-01 09:00:00');
		$results[] = new DateTime('1999-01-02 09:00:00');
		$results[] = new DateTime('1999-01-03 09:00:00');
		$results[] = new DateTime('1999-01-04 09:00:00');
		$results[] = new DateTime('1999-01-05 09:00:00');
		$results[] = new DateTime('1999-01-06 09:00:00');
		$results[] = new DateTime('1999-01-07 09:00:00');
		$results[] = new DateTime('1999-01-08 09:00:00');
		$results[] = new DateTime('1999-01-09 09:00:00');
		$results[] = new DateTime('1999-01-10 09:00:00');
		$results[] = new DateTime('1999-01-11 09:00:00');
		$results[] = new DateTime('1999-01-12 09:00:00');
		$results[] = new DateTime('1999-01-13 09:00:00');
		$results[] = new DateTime('1999-01-14 09:00:00');
		$results[] = new DateTime('1999-01-15 09:00:00');
		$results[] = new DateTime('1999-01-16 09:00:00');
		$results[] = new DateTime('1999-01-17 09:00:00');
		$results[] = new DateTime('1999-01-18 09:00:00');
		$results[] = new DateTime('1999-01-19 09:00:00');
		$results[] = new DateTime('1999-01-20 09:00:00');
		$results[] = new DateTime('1999-01-21 09:00:00');
		$results[] = new DateTime('1999-01-22 09:00:00');
		$results[] = new DateTime('1999-01-23 09:00:00');
		$results[] = new DateTime('1999-01-24 09:00:00');
		$results[] = new DateTime('1999-01-25 09:00:00');
		$results[] = new DateTime('1999-01-26 09:00:00');
		$results[] = new DateTime('1999-01-27 09:00:00');
		$results[] = new DateTime('1999-01-28 09:00:00');
		$results[] = new DateTime('1999-01-29 09:00:00');
		$results[] = new DateTime('1999-01-30 09:00:00');
		$results[] = new DateTime('1999-01-31 09:00:00');
		$results[] = new DateTime('2000-01-01 09:00:00');
		$results[] = new DateTime('2000-01-02 09:00:00');
		$results[] = new DateTime('2000-01-03 09:00:00');
		$results[] = new DateTime('2000-01-04 09:00:00');
		$results[] = new DateTime('2000-01-05 09:00:00');
		$results[] = new DateTime('2000-01-06 09:00:00');
		$results[] = new DateTime('2000-01-07 09:00:00');
		$results[] = new DateTime('2000-01-08 09:00:00');
		$results[] = new DateTime('2000-01-09 09:00:00');
		$results[] = new DateTime('2000-01-10 09:00:00');
		$results[] = new DateTime('2000-01-11 09:00:00');
		$results[] = new DateTime('2000-01-12 09:00:00');
		$results[] = new DateTime('2000-01-13 09:00:00');
		$results[] = new DateTime('2000-01-14 09:00:00');
		$results[] = new DateTime('2000-01-15 09:00:00');
		$results[] = new DateTime('2000-01-16 09:00:00');
		$results[] = new DateTime('2000-01-17 09:00:00');
		$results[] = new DateTime('2000-01-18 09:00:00');
		$results[] = new DateTime('2000-01-19 09:00:00');
		$results[] = new DateTime('2000-01-20 09:00:00');
		$results[] = new DateTime('2000-01-21 09:00:00');
		$results[] = new DateTime('2000-01-22 09:00:00');
		$results[] = new DateTime('2000-01-23 09:00:00');
		$results[] = new DateTime('2000-01-24 09:00:00');
		$results[] = new DateTime('2000-01-25 09:00:00');
		$results[] = new DateTime('2000-01-26 09:00:00');
		$results[] = new DateTime('2000-01-27 09:00:00');
		$results[] = new DateTime('2000-01-28 09:00:00');
		$results[] = new DateTime('2000-01-29 09:00:00');
		$results[] = new DateTime('2000-01-30 09:00:00');
		$results[] = new DateTime('2000-01-31 09:00:00');

		$r = new When();
		$r->recur('19980101T090000')->rrule('FREQ=DAILY;UNTIL=20000131T140000Z;BYMONTH=1');

		foreach($results as $result)
		{
			$this->assertEquals($result, $r->next());
		}
	}

	/**
	 * RRULE:FREQ=DAILY
	 */
	function testThirtyEight()
	{
		$results[] = new DateTime('1997-09-02 09:00:00');
		$results[] = new DateTime('1997-09-03 09:00:00');
		$results[] = new DateTime('1997-09-04 09:00:00');
		$results[] = new DateTime('1997-09-05 09:00:00');
		$results[] = new DateTime('1997-09-06 09:00:00');
		$results[] = new DateTime('1997-09-07 09:00:00');
		$results[] = new DateTime('1997-09-08 09:00:00');
		$results[] = new DateTime('1997-09-09 09:00:00');
		$results[] = new DateTime('1997-09-10 09:00:00');
		$results[] = new DateTime('1997-09-11 09:00:00');
		$results[] = new DateTime('1997-09-12 09:00:00');
		$results[] = new DateTime('1997-09-13 09:00:00');
		$results[] = new DateTime('1997-09-14 09:00:00');
		$results[] = new DateTime('1997-09-15 09:00:00');
		$results[] = new DateTime('1997-09-16 09:00:00');

		$r = new When();
		$r->recur('19970902T090000')->rrule('FREQ=DAILY');

		foreach($results as $result)
		{
			$this->assertEquals($result, $r->next());
		}
	}

		/**
	 * RRULE:FREQ=DAILY
	 */
	function testThirtyNine()
	{
		$results[] = new DateTime('1999-12-25 09:00:00');
		$results[] = new DateTime('1999-12-26 09:00:00');
		$results[] = new DateTime('1999-12-27 09:00:00');
		$results[] = new DateTime('1999-12-28 09:00:00');
		$results[] = new DateTime('1999-12-29 09:00:00');
		$results[] = new DateTime('1999-12-30 09:00:00');
		$results[] = new DateTime('1999-12-31 09:00:00');
		$results[] = new DateTime('2000-01-01 09:00:00');
		$results[] = new DateTime('2000-01-02 09:00:00');
		$results[] = new DateTime('2000-01-03 09:00:00');
		$results[] = new DateTime('2000-01-04 09:00:00');

		$r = new When();
		$r->recur('19991225T090000')->rrule('FREQ=DAILY');

		foreach($results as $result)
		{
			$this->assertEquals($result, $r->next());
		}
	}
}
