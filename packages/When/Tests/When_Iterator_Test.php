<?php

require_once 'PHPUnit/Framework.php';

require_once './When_Iterator.php';

class When_Iterator_Tests extends PHPUnit_Framework_TestCase
{

	function testDateWithoutCache()
	{
		$results[] = new DateTime('1997-09-29 09:00:00');
		$results[] = new DateTime('1997-10-30 09:00:00');
		$results[] = new DateTime('1997-11-27 09:00:00');
		$results[] = new DateTime('1997-12-30 09:00:00');
		$results[] = new DateTime('1998-01-29 09:00:00');
		$results[] = new DateTime('1998-02-26 09:00:00');
		$results[] = new DateTime('1998-03-30 09:00:00');

		$r = new When_Iterator();
		$r->recur('19970929T090000', 'monthly')->count(7)->byday(array('MO', 'TU', 'WE', 'TH', 'FR'))->bysetpos(array(-2));

		$counter = 0;
		foreach($r as $result)
		{
			$this->assertEquals($result, $results[$counter]);
			$counter++;
		}

		// if we rewind does it still work?
		$r->rewind();

		$counter = 0;
		foreach($r as $result)
		{
			$this->assertEquals($result, $results[$counter]);
			$counter++;
		}
	}

	function testDateWithCache()
	{
		$results[] = new DateTime('1997-09-29 09:00:00');
		$results[] = new DateTime('1997-10-30 09:00:00');
		$results[] = new DateTime('1997-11-27 09:00:00');
		$results[] = new DateTime('1997-12-30 09:00:00');
		$results[] = new DateTime('1998-01-29 09:00:00');
		$results[] = new DateTime('1998-02-26 09:00:00');
		$results[] = new DateTime('1998-03-30 09:00:00');

		$r = new When_Iterator(true);
		$r->recur('19970929T090000', 'monthly')->count(7)->byday(array('MO', 'TU', 'WE', 'TH', 'FR'))->bysetpos(array(-2));

		$counter = 0;
		foreach($r as $result)
		{
			$this->assertEquals($result, $results[$counter]);
			$counter++;
		}

		// if we rewind does it still work?
		$r->rewind();

		$counter = 0;
		foreach($r as $result)
		{
			$this->assertEquals($result, $results[$counter]);
			$counter++;
		}
	}

}