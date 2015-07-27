<?php



require_once './When.php';

class When_Yearly_Test extends PHPUnit_Framework_TestCase
{
	/**
	 * DTSTART;TZID=US-Eastern:19970610T090000
	 * RRULE:FREQ=YEARLY;COUNT=10;BYMONTH=6,7
	 */
	public function testOne()
	{
		$results[] = new DateTime('1997-06-10 09:00:00');
		$results[] = new DateTime('1997-07-10 09:00:00');
		$results[] = new DateTime('1998-06-10 09:00:00');
		$results[] = new DateTime('1998-07-10 09:00:00');
		$results[] = new DateTime('1999-06-10 09:00:00');
		$results[] = new DateTime('1999-07-10 09:00:00');
		$results[] = new DateTime('2000-06-10 09:00:00');
		$results[] = new DateTime('2000-07-10 09:00:00');
		$results[] = new DateTime('2001-06-10 09:00:00');
		$results[] = new DateTime('2001-07-10 09:00:00');

		$r = new When();
		$r->recur('19970610T090000', 'yearly')->count(10)->bymonth(array(6,7));

		foreach($results as $result)
		{
			$this->assertEquals($result, $r->next());
		}
	}

	/**
	 * DTSTART;TZID=US-Eastern:19970101T090000
	 * RRULE:FREQ=YEARLY;INTERVAL=3;COUNT=10;BYYEARDAY=1,100,200
	 */
	public function testTwo()
	{
		$results[] = new DateTime('1997-01-01 09:00:00');
		$results[] = new DateTime('1997-04-10 09:00:00');
		$results[] = new DateTime('1997-07-19 09:00:00');
		$results[] = new DateTime('2000-01-01 09:00:00');
		$results[] = new DateTime('2000-04-09 09:00:00');
		$results[] = new DateTime('2000-07-18 09:00:00');
		$results[] = new DateTime('2003-01-01 09:00:00');
		$results[] = new DateTime('2003-04-10 09:00:00');
		$results[] = new DateTime('2003-07-19 09:00:00');
		$results[] = new DateTime('2006-01-01 09:00:00');

		$r = new When();
		$r->recur('19970101T090000', 'yearly')->interval(3)->count(10)->byyearday(array(1,100,200));

		foreach($results as $result)
		{
			$this->assertEquals($result, $r->next());
		}
	}

	/**
	 * DTSTART;TZID=US-Eastern:19970310T090000
	 * RRULE:FREQ=YEARLY;INTERVAL=2;COUNT=10;BYMONTH=1,2,3
	 */
	public function testThree()
	{
		$results[] = new DateTime('1997-03-10 09:00:00');
		$results[] = new DateTime('1999-01-10 09:00:00');
		$results[] = new DateTime('1999-02-10 09:00:00');
		$results[] = new DateTime('1999-03-10 09:00:00');
		$results[] = new DateTime('2001-01-10 09:00:00');
		$results[] = new DateTime('2001-02-10 09:00:00');
		$results[] = new DateTime('2001-03-10 09:00:00');
		$results[] = new DateTime('2003-01-10 09:00:00');
		$results[] = new DateTime('2003-02-10 09:00:00');
		$results[] = new DateTime('2003-03-10 09:00:00');

		$r = new When();
		$r->recur('19970310T090000', 'yearly')->interval(2)->count(10)->bymonth(array(1,2,3));

		foreach($results as $result)
		{
			$this->assertEquals($result, $r->next());
		}
	}

	/**
	 * DTSTART;TZID=US-Eastern:19980101T090000
	 * RRULE:FREQ=YEARLY;UNTIL=20000131T090000Z;BYMONTH=1;BYDAY=SU,MO,TU,WE,TH,FR,SA
	 */
	public function testFour()
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
		$r->recur('19980101T090000', 'yearly')->until('20000131T090000')->bymonth(array(1))->byday(array('SU','MO','TU','WE','TH','FR','SA'));

		foreach($results as $result)
		{
			$this->assertEquals($result, $r->next());
		}
	}

	/**
	 * Monday of week number 20 (where the default start of the week is Monday), forever:
	 * DTSTART;TZID=US-Eastern:19970512T090000
	 * RRULE:FREQ=YEARLY;BYWEEKNO=20;BYDAY=MO
	 * Results limited to first 10 since this has no enddate or count.
	 */
	 function testFive()
	 {
		$results[] = new DateTime('1997-05-12 09:00:00');
		$results[] = new DateTime('1998-05-11 09:00:00');
		$results[] = new DateTime('1999-05-17 09:00:00');
		$results[] = new DateTime('2000-05-15 09:00:00');
		$results[] = new DateTime('2001-05-14 09:00:00');
		$results[] = new DateTime('2002-05-13 09:00:00');
		$results[] = new DateTime('2003-05-12 09:00:00');
		$results[] = new DateTime('2004-05-10 09:00:00');
		$results[] = new DateTime('2005-05-16 09:00:00');
		$results[] = new DateTime('2006-05-15 09:00:00');

		$r = new When();
		$r->recur('19970512T090000', 'yearly')->count(10)->byweekno(array(20))->byday(array('MO'));

		foreach($results as $result)
		{
			$this->assertEquals($result, $r->next());
		}
	 }

	 /**
	  * Every Thursday in March, forever:
	  * DTSTART;TZID=US-Eastern:19970313T090000
	  * RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=TH
	  */
	 function testSix()
	 {
		$results[] = new DateTime('1997-03-13 09:00:00');
		$results[] = new DateTime('1997-03-20 09:00:00');
		$results[] = new DateTime('1997-03-27 09:00:00');
		$results[] = new DateTime('1998-03-05 09:00:00');
		$results[] = new DateTime('1998-03-12 09:00:00');
		$results[] = new DateTime('1998-03-19 09:00:00');
		$results[] = new DateTime('1998-03-26 09:00:00');
		$results[] = new DateTime('1999-03-04 09:00:00');
		$results[] = new DateTime('1999-03-11 09:00:00');
		$results[] = new DateTime('1999-03-18 09:00:00');

		$r = new When();
		$r->recur('19970313T090000', 'yearly')->count(10)->bymonth(array(3))->byday(array('TH'));

		foreach($results as $result)
		{
			$this->assertEquals($result, $r->next());
		}
	 }

	/**
	 * Every Thursday, but only during June, July, and August, forever:
	 * DTSTART;TZID=US-Eastern:19970605T090000
	 * RRULE:FREQ=YEARLY;BYDAY=TH;BYMONTH=6,7,8
	 */
	function testSeven()
	{
		$results[] = new DateTime('1997-06-05 09:00:00');
		$results[] = new DateTime('1997-06-12 09:00:00');
		$results[] = new DateTime('1997-06-19 09:00:00');
		$results[] = new DateTime('1997-06-26 09:00:00');
		$results[] = new DateTime('1997-07-03 09:00:00');
		$results[] = new DateTime('1997-07-10 09:00:00');
		$results[] = new DateTime('1997-07-17 09:00:00');
		$results[] = new DateTime('1997-07-24 09:00:00');
		$results[] = new DateTime('1997-07-31 09:00:00');
		$results[] = new DateTime('1997-08-07 09:00:00');
		$results[] = new DateTime('1997-08-14 09:00:00');
		$results[] = new DateTime('1997-08-21 09:00:00');
		$results[] = new DateTime('1997-08-28 09:00:00');
		$results[] = new DateTime('1998-06-04 09:00:00');
		$results[] = new DateTime('1998-06-11 09:00:00');
		$results[] = new DateTime('1998-06-18 09:00:00');
		$results[] = new DateTime('1998-06-25 09:00:00');
		$results[] = new DateTime('1998-07-02 09:00:00');
		$results[] = new DateTime('1998-07-09 09:00:00');
		$results[] = new DateTime('1998-07-16 09:00:00');
		$results[] = new DateTime('1998-07-23 09:00:00');
		$results[] = new DateTime('1998-07-30 09:00:00');
		$results[] = new DateTime('1998-08-06 09:00:00');
		$results[] = new DateTime('1998-08-13 09:00:00');
		$results[] = new DateTime('1998-08-20 09:00:00');
		$results[] = new DateTime('1998-08-27 09:00:00');
		$results[] = new DateTime('1999-06-03 09:00:00');
		$results[] = new DateTime('1999-06-10 09:00:00');
		$results[] = new DateTime('1999-06-17 09:00:00');
		$results[] = new DateTime('1999-06-24 09:00:00');
		$results[] = new DateTime('1999-07-01 09:00:00');
		$results[] = new DateTime('1999-07-08 09:00:00');
		$results[] = new DateTime('1999-07-15 09:00:00');
		$results[] = new DateTime('1999-07-22 09:00:00');
		$results[] = new DateTime('1999-07-29 09:00:00');
		$results[] = new DateTime('1999-08-05 09:00:00');
		$results[] = new DateTime('1999-08-12 09:00:00');
		$results[] = new DateTime('1999-08-19 09:00:00');
		$results[] = new DateTime('1999-08-26 09:00:00');

		$r = new When();
		$r->recur('19970605T090000', 'yearly')->count(39)->byday(array('TH'))->bymonth(array(6,7,8));

		foreach($results as $result)
		{
			$this->assertEquals($result, $r->next());
		}
	}

	/**
	 * Every four years, the first Tuesday after a Monday in November, forever (U.S. Presidential Election day):
	 * DTSTART;TZID=US-Eastern:19961105T090000
	 * RRULE:FREQ=YEARLY;INTERVAL=4;BYMONTH=11;BYDAY=TU;BYMONTHDAY=2,3,4,5,6,7,8
	 */
	function testEight()
	{
		$results[] = new DateTime('1996-11-05 09:00:00');
		$results[] = new DateTime('2000-11-07 09:00:00');
		$results[] = new DateTime('2004-11-02 09:00:00');
		$results[] = new DateTime('2008-11-04 09:00:00');
		$results[] = new DateTime('2012-11-06 09:00:00');
		$results[] = new DateTime('2016-11-08 09:00:00');
		$results[] = new DateTime('2020-11-03 09:00:00');
		$results[] = new DateTime('2024-11-05 09:00:00');
		$results[] = new DateTime('2028-11-07 09:00:00');
		$results[] = new DateTime('2032-11-02 09:00:00');

		$r = new When();
		$r->recur('19961105T090000', 'yearly')->count(10)->interval(4)->bymonth(array(11))->byday(array('TU'))->bymonthday(array(2,3,4,5,6,7,8));

		foreach($results as $result)
		{
			$this->assertEquals($result, $r->next());
		}
	}

	/**
	 * Every third year on the 1st, 100th, and 200th day for 10 occurrences:
	 * DTSTART;TZID=America/New_York:19970101T090000
	 * RRULE:FREQ=YEARLY;INTERVAL=3;COUNT=10;BYYEARDAY=1,100,200
	 */
	function testTwentyThree()
	{
		$results[] = new DateTime('1997-01-01 09:00:00');
		$results[] = new DateTime('1997-04-10 09:00:00');
		$results[] = new DateTime('1997-07-19 09:00:00');
		$results[] = new DateTime('2000-01-01 09:00:00');
		$results[] = new DateTime('2000-04-09 09:00:00');
		$results[] = new DateTime('2000-07-18 09:00:00');
		$results[] = new DateTime('2003-01-01 09:00:00');
		$results[] = new DateTime('2003-04-10 09:00:00');
		$results[] = new DateTime('2003-07-19 09:00:00');
		$results[] = new DateTime('2006-01-01 09:00:00');

		$r = new When();
		$r->recur('19970101T090000', 'yearly')->interval(3)->count(10)->byyearday(array(1,100,200));

		foreach($results as $result)
		{
			$this->assertEquals($result, $r->next());
		}
	}

	/**
	 * Every year on the -1th, -100th, and -200th day for 5 occurrences (checked via google calendar import below)
	 * BEGIN:VCALENDAR
	 * PRODID:-//Google Inc//Google Calendar 70.9054//EN
	 * VERSION:2.0
	 * CALSCALE:GREGORIAN
	 * METHOD:PUBLISH
	 * BEGIN:VTIMEZONE
	 * TZID:America/New_York
	 * X-LIC-LOCATION:America/New_York
	 * BEGIN:DAYLIGHT
	 * TZOFFSETFROM:-0500
	 * TZOFFSETTO:-0400
	 * TZNAME:EDT
	 * DTSTART:19700308T020000
	 * RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=2SU
	 * END:DAYLIGHT
	 * BEGIN:STANDARD
	 * TZOFFSETFROM:-0400
	 * TZOFFSETTO:-0500
	 * TZNAME:EST
	 * DTSTART:19701101T020000
	 * RRULE:FREQ=YEARLY;BYMONTH=11;BYDAY=1SU
	 * END:STANDARD
	 * END:VTIMEZONE
	 * BEGIN:VEVENT
	 * DTSTART;VALUE=DATE:20101231
	 * RRULE:FREQ=YEARLY;COUNT=5;BYYEARDAY=-1,-100,-200
	 * DTSTAMP:20101231T090000
	 * CREATED:20101231T090000
	 * DESCRIPTION:
	 * LAST-MODIFIED:20101231T090000
	 * LOCATION:
	 * SEQUENCE:2
	 * STATUS:CONFIRMED
	 * SUMMARY:testing yearly event
	 * TRANSP:TRANSPARENT
	 * END:VEVENT
	 * END:VCALENDAR
	 */
	function testTwentyFour()
	{
		$results[] = new DateTime('2010-12-31 09:00:00');
		$results[] = new DateTime('2010-09-23 09:00:00');
		$results[] = new DateTime('2010-06-15 09:00:00');
		$results[] = new DateTime('2011-12-31 09:00:00');
		$results[] = new DateTime('2011-09-23 09:00:00');

		$r = new When();
		$r->recur('20101231T090000', 'yearly')->count(5)->byyearday(array(-1, -100, -200));

		foreach($results as $result)
		{
			$this->assertEquals($result, $r->next());
		}
	}

}