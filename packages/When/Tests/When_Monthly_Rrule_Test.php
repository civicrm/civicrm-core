<?php

require_once 'PHPUnit/Framework.php';

require_once './When.php';

class When_Monthly_Rrule_Test extends PHPUnit_Framework_TestCase
{
	/**
	 * Monthly on the 1st Friday for ten occurrences:
	 * DTSTART;TZID=US-Eastern:19970905T090000
	 * RRULE:FREQ=MONTHLY;COUNT=10;BYDAY=1FR
	 */
	function testNine()
	{
		$results[] = new DateTime('1997-09-05 09:00:00');
		$results[] = new DateTime('1997-10-03 09:00:00');
		$results[] = new DateTime('1997-11-07 09:00:00');
		$results[] = new DateTime('1997-12-05 09:00:00');
		$results[] = new DateTime('1998-01-02 09:00:00');
		$results[] = new DateTime('1998-02-06 09:00:00');
		$results[] = new DateTime('1998-03-06 09:00:00');
		$results[] = new DateTime('1998-04-03 09:00:00');
		$results[] = new DateTime('1998-05-01 09:00:00');
		$results[] = new DateTime('1998-06-05 09:00:00');

		$r = new When();
		$r->recur('19970905T090000')->rrule('FREQ=MONTHLY;COUNT=10;BYDAY=1FR');

		foreach($results as $result)
		{
			$this->assertEquals($result, $r->next());
		}
	}

	/**
	 * Monthly on the 1st Friday until December 24, 1997:
	 * DTSTART;TZID=US-Eastern:19970905T090000
	 * RRULE:FREQ=MONTHLY;UNTIL=19971224T000000Z;BYDAY=1FR
     */
	function testTen()
	{
		$results[] = new DateTime('1997-09-05 09:00:00');
		$results[] = new DateTime('1997-10-03 09:00:00');
		$results[] = new DateTime('1997-11-07 09:00:00');
		$results[] = new DateTime('1997-12-05 09:00:00');

		$r = new When();
		$r->recur('19970905T090000')->rrule('FREQ=MONTHLY;UNTIL=19971224T000000Z;BYDAY=1FR');

		foreach($results as $result)
		{
			$this->assertEquals($result, $r->next());
		}
	}

	/**
	 * Every other month on the 1st and last Sunday of the month for 10 occurrences:
	 * DTSTART;TZID=US-Eastern:19970907T090000
	 * RRULE:FREQ=MONTHLY;INTERVAL=2;COUNT=10;BYDAY=1SU,-1SU
	 */
	function testEleven()
	{
		$results[] = new DateTime('1997-09-07 09:00:00');
		$results[] = new DateTime('1997-09-28 09:00:00');
		$results[] = new DateTime('1997-11-02 09:00:00');
		$results[] = new DateTime('1997-11-30 09:00:00');
		$results[] = new DateTime('1998-01-04 09:00:00');
		$results[] = new DateTime('1998-01-25 09:00:00');
		$results[] = new DateTime('1998-03-01 09:00:00');
		$results[] = new DateTime('1998-03-29 09:00:00');
		$results[] = new DateTime('1998-05-03 09:00:00');
		$results[] = new DateTime('1998-05-31 09:00:00');

		$r = new When();
		$r->recur('19970905T090000')->rrule('FREQ=MONTHLY;INTERVAL=2;COUNT=10;BYDAY=1SU,-1SU');

		foreach($results as $result)
		{
			$this->assertEquals($result, $r->next());
		}
	}

	/**
	 * Monthly on the second to last Monday of the month for 6 months:
	 * DTSTART;TZID=US-Eastern:19970922T090000
	 * RRULE:FREQ=MONTHLY;COUNT=6;BYDAY=-2MO
	 */
	function testTwelve()
	{
		$results[] = new DateTime('1997-09-22 09:00:00');
		$results[] = new DateTime('1997-10-20 09:00:00');
		$results[] = new DateTime('1997-11-17 09:00:00');
		$results[] = new DateTime('1997-12-22 09:00:00');
		$results[] = new DateTime('1998-01-19 09:00:00');
		$results[] = new DateTime('1998-02-16 09:00:00');

		$r = new When();
		$r->recur('19970922T090000')->rrule('FREQ=MONTHLY;COUNT=6;BYDAY=-2MO');

		foreach($results as $result)
		{
			$this->assertEquals($result, $r->next());
		}
	}

	/**
	 * Monthly on the third to the last day of the month, forever:
	 * DTSTART;TZID=US-Eastern:19970928T090000
	 * RRULE:FREQ=MONTHLY;BYMONTHDAY=-3
	 */
	function testThirteen()
	{
		$results[] = new DateTime('1997-09-28 09:00:00');
		$results[] = new DateTime('1997-10-29 09:00:00');
		$results[] = new DateTime('1997-11-28 09:00:00');
		$results[] = new DateTime('1997-12-29 09:00:00');
		$results[] = new DateTime('1998-01-29 09:00:00');
		$results[] = new DateTime('1998-02-26 09:00:00');

		$r = new When();
		$r->recur('19970902T090000')->count(6)->rrule('FREQ=MONTHLY;BYMONTHDAY=-3');

		foreach($results as $result)
		{
			$this->assertEquals($result, $r->next());
		}
	}

	/**
	 * Monthly on the 2nd and 15th of the month for 10 occurrences:
	 * DTSTART;TZID=US-Eastern:19970902T090000
	 * RRULE:FREQ=MONTHLY;COUNT=10;BYMONTHDAY=2,15
	 */
	function testFourteen()
	{
		$results[] = new DateTime('1997-09-02 09:00:00');
		$results[] = new DateTime('1997-09-15 09:00:00');
		$results[] = new DateTime('1997-10-02 09:00:00');
		$results[] = new DateTime('1997-10-15 09:00:00');
		$results[] = new DateTime('1997-11-02 09:00:00');
		$results[] = new DateTime('1997-11-15 09:00:00');
		$results[] = new DateTime('1997-12-02 09:00:00');
		$results[] = new DateTime('1997-12-15 09:00:00');
		$results[] = new DateTime('1998-01-02 09:00:00');
		$results[] = new DateTime('1998-01-15 09:00:00');

		$r = new When();
		$r->recur('19970902T090000')->rrule('FREQ=MONTHLY;COUNT=10;BYMONTHDAY=2,15');

		foreach($results as $result)
		{
			$this->assertEquals($result, $r->next());
		}
	}

	/**
	 * Monthly on the first and last day of the month for 10 occurrences:
	 * DTSTART;TZID=US-Eastern:19970930T090000
	 * RRULE:FREQ=MONTHLY;COUNT=10;BYMONTHDAY=1,-1
	 */
	function testFifteen()
	{
		$results[] = new DateTime('1997-09-30 09:00:00');
		$results[] = new DateTime('1997-10-01 09:00:00');
		$results[] = new DateTime('1997-10-31 09:00:00');
		$results[] = new DateTime('1997-11-01 09:00:00');
		$results[] = new DateTime('1997-11-30 09:00:00');
		$results[] = new DateTime('1997-12-01 09:00:00');
		$results[] = new DateTime('1997-12-31 09:00:00');
		$results[] = new DateTime('1998-01-01 09:00:00');
		$results[] = new DateTime('1998-01-31 09:00:00');
		$results[] = new DateTime('1998-02-01 09:00:00');

		$r = new When();
		$r->recur('19970930T090000')->rrule('RRULE:FREQ=MONTHLY;COUNT=10;BYMONTHDAY=1,-1');

		foreach($results as $result)
		{
			$this->assertEquals($result, $r->next());
		}
	}

	/**
	 * Every 18 months on the 10th thru 15th of the month for 10 occurrences:
	 * DTSTART;TZID=US-Eastern:19970910T090000
	 * RRULE:FREQ=MONTHLY;INTERVAL=18;COUNT=10;BYMONTHDAY=10,11,12,13,14,15
	 */
	function testSixteen()
	{
		$results[] = new DateTime('1997-09-10 09:00:00');
		$results[] = new DateTime('1997-09-11 09:00:00');
		$results[] = new DateTime('1997-09-12 09:00:00');
		$results[] = new DateTime('1997-09-13 09:00:00');
		$results[] = new DateTime('1997-09-14 09:00:00');
		$results[] = new DateTime('1997-09-15 09:00:00');
		$results[] = new DateTime('1999-03-10 09:00:00');
		$results[] = new DateTime('1999-03-11 09:00:00');
		$results[] = new DateTime('1999-03-12 09:00:00');
		$results[] = new DateTime('1999-03-13 09:00:00');

		$r = new When();
		$r->recur('19970910T090000')->rrule('FREQ=MONTHLY;INTERVAL=18;COUNT=10;BYMONTHDAY=10,11,12,13,14,15');

		foreach($results as $result)
		{
			$this->assertEquals($result, $r->next());
		}
	}

	/**
	 * Every Tuesday, every other month:
	 * DTSTART;TZID=US-Eastern:19970902T090000
	 * RRULE:FREQ=MONTHLY;INTERVAL=2;BYDAY=TU
	 */
	function testSeventeen()
	{
		$results[] = new DateTime('1997-09-02 09:00:00');
		$results[] = new DateTime('1997-09-09 09:00:00');
		$results[] = new DateTime('1997-09-16 09:00:00');
		$results[] = new DateTime('1997-09-23 09:00:00');
		$results[] = new DateTime('1997-09-30 09:00:00');
		$results[] = new DateTime('1997-11-04 09:00:00');
		$results[] = new DateTime('1997-11-11 09:00:00');
		$results[] = new DateTime('1997-11-18 09:00:00');
		$results[] = new DateTime('1997-11-25 09:00:00');
		$results[] = new DateTime('1998-01-06 09:00:00');
		$results[] = new DateTime('1998-01-13 09:00:00');
		$results[] = new DateTime('1998-01-20 09:00:00');
		$results[] = new DateTime('1998-01-27 09:00:00');
		$results[] = new DateTime('1998-03-03 09:00:00');
		$results[] = new DateTime('1998-03-10 09:00:00');
		$results[] = new DateTime('1998-03-17 09:00:00');
		$results[] = new DateTime('1998-03-24 09:00:00');
		$results[] = new DateTime('1998-03-31 09:00:00');

		$r = new When();
		$r->recur('19970902T090000')->count(18)->rrule('FREQ=MONTHLY;INTERVAL=2;BYDAY=TU');

		foreach($results as $result)
		{
			$this->assertEquals($result, $r->next());
		}
	}

	/**
	 * Every Friday the 13th, forever:
	 * DTSTART;TZID=US-Eastern:19970902T090000
	 * RRULE:FREQ=MONTHLY;BYDAY=FR;BYMONTHDAY=13
	 */
	function testEighteen()
	{
		$results[] = new DateTime('1998-02-13 09:00:00');
		$results[] = new DateTime('1998-03-13 09:00:00');
		$results[] = new DateTime('1998-11-13 09:00:00');
		$results[] = new DateTime('1999-08-13 09:00:00');
		$results[] = new DateTime('2000-10-13 09:00:00');

		$r = new When();
		$r->recur('19970902T090000')->count(5)->rrule('FREQ=MONTHLY;BYDAY=FR;BYMONTHDAY=13');

		foreach($results as $result)
		{
			$this->assertEquals($result, $r->next());
		}
	}

	/**
	 * The first Saturday that follows the first Sunday of the month, forever:
	 * DTSTART;TZID=US-Eastern:19970913T090000
	 * RRULE:FREQ=MONTHLY;BYDAY=SA;BYMONTHDAY=7,8,9,10,11,12,13
	 */
	function testNineteen()
	{
		$results[] = new DateTime('1997-09-13 09:00:00');
		$results[] = new DateTime('1997-10-11 09:00:00');
		$results[] = new DateTime('1997-11-08 09:00:00');
		$results[] = new DateTime('1997-12-13 09:00:00');
		$results[] = new DateTime('1998-01-10 09:00:00');
		$results[] = new DateTime('1998-02-07 09:00:00');
		$results[] = new DateTime('1998-03-07 09:00:00');
		$results[] = new DateTime('1998-04-11 09:00:00');
		$results[] = new DateTime('1998-05-09 09:00:00');
		$results[] = new DateTime('1998-06-13 09:00:00');

		$r = new When();
		$r->recur('19970913T090000')->count(10)->rrule('FREQ=MONTHLY;BYDAY=SA;BYMONTHDAY=7,8,9,10,11,12,13');

		foreach($results as $result)
		{
			$this->assertEquals($result, $r->next());
		}
	}

	/**
     * The 3rd instance into the month of one of Tuesday, Wednesday or Thursday, for the next 3 months:
	 * DTSTART;TZID=US-Eastern:19970904T090000
	 * RRULE:FREQ=MONTHLY;COUNT=3;BYDAY=TU,WE,TH;BYSETPOS=3
	 */
	function testTwenty()
	{
		$results[] = new DateTime('1997-09-04 09:00:00');
		$results[] = new DateTime('1997-10-07 09:00:00');
		$results[] = new DateTime('1997-11-06 09:00:00');

		$r = new When();
		$r->recur('19970904T090000')->rrule('FREQ=MONTHLY;COUNT=3;BYDAY=TU,WE,TH;BYSETPOS=3');

		foreach($results as $result)
		{
			$this->assertEquals($result, $r->next());
		}
	}

	/**
	 * An example where an invalid date (i.e., February 30) is ignored.
	 * DTSTART;TZID=America/New_York:20070115T090000
	 * RRULE:FREQ=MONTHLY;BYMONTHDAY=15,30;COUNT=5
	 */
	function testTwentyOne()
	{
		$results[] = new DateTime('2007-01-15 09:00:00');
		$results[] = new DateTime('2007-01-30 09:00:00');
		$results[] = new DateTime('2007-02-15 09:00:00');
		$results[] = new DateTime('2007-03-15 09:00:00');
		$results[] = new DateTime('2007-03-30 09:00:00');

		$r = new When();
		$r->recur('20070115T090000')->count(5)->rrule('FREQ=MONTHLY;BYMONTHDAY=15,30;COUNT=5');

		foreach($results as $result)
		{
			$this->assertEquals($result, $r->next());
		}
	}

	/**
	 * The second-to-last weekday of the month:
	 * DTSTART;TZID=America/New_York:19970929T090000
	 * RRULE:FREQ=MONTHLY;BYDAY=MO,TU,WE,TH,FR;BYSETPOS=-2
	 */
	function testTwentyTwo()
	{
		$results[] = new DateTime('1997-09-29 09:00:00');
		$results[] = new DateTime('1997-10-30 09:00:00');
		$results[] = new DateTime('1997-11-27 09:00:00');
		$results[] = new DateTime('1997-12-30 09:00:00');
		$results[] = new DateTime('1998-01-29 09:00:00');
		$results[] = new DateTime('1998-02-26 09:00:00');
		$results[] = new DateTime('1998-03-30 09:00:00');

		$r = new When();
		$r->recur('19970929T090000')->count(7)->rrule('FREQ=MONTHLY;BYDAY=MO,TU,WE,TH,FR;BYSETPOS=-2');

		foreach($results as $result)
		{
			$this->assertEquals($result, $r->next());
		}
	}

	/**
	 * The same day of every month:
	 * RRULE:FREQ=MONTHLY
	 */
	function testTwentyThree()
	{
		$results[] = new DateTime('1997-09-14 09:00:00');
		$results[] = new DateTime('1997-10-14 09:00:00');
		$results[] = new DateTime('1997-11-14 09:00:00');
		$results[] = new DateTime('1997-12-14 09:00:00');
		$results[] = new DateTime('1998-01-14 09:00:00');
		$results[] = new DateTime('1998-02-14 09:00:00');
		$results[] = new DateTime('1998-03-14 09:00:00');
		$results[] = new DateTime('1998-04-14 09:00:00');
		$results[] = new DateTime('1998-05-14 09:00:00');
		$results[] = new DateTime('1998-06-14 09:00:00');
		$results[] = new DateTime('1998-07-14 09:00:00');
		$results[] = new DateTime('1998-08-14 09:00:00');
		$results[] = new DateTime('1998-09-14 09:00:00');
		$results[] = new DateTime('1998-10-14 09:00:00');
		$results[] = new DateTime('1998-11-14 09:00:00');
		$results[] = new DateTime('1998-12-14 09:00:00');
		$results[] = new DateTime('1999-01-14 09:00:00');

		$r = new When();
		$r->recur('19970914T090000')->count(17)->rrule('FREQ=MONTHLY');

		foreach($results as $result)
		{
			$date = $r->next();
			$this->assertEquals($result, $date);
		}
	}
}
