<?php

/*
  +--------------------------------------------------------------------+
  | CiviCRM version 5                                                  |
  +--------------------------------------------------------------------+
  | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 *  File for the DateTest class
 *
 *  (PHP 5)
 *
 * @author Jon Goldberg <jon@megaphonetech.com>
 */

/**
 *  Test CRM_Utils_Date functions.
 *
 * @package   CiviCRM
 * @group headless
 */
class CRM_Utils_DateTest extends CiviUnitTestCase {

  public function setUp() {
    // There are only unit tests here at present, we can skip database loading.
    return TRUE;
  }

  public function tearDown() {
    // There are only unit tests here at present, we can skip database loading.
    return TRUE;
  }

  public function fromToData() {
    $cases = array();
    // Absolute dates
    $cases[] = array('20170901000000', '20170913235959', 0, '09/01/2017', '09/13/2017');
    // "Today" relative date filter
    $date = new DateTime();
    $expectedFrom = $date->format('Ymd') . '000000';
    $expectedTo = $date->format('Ymd') . '235959';
    $cases[] = array($expectedFrom, $expectedTo, 'this.day', '', '');
    // "yesterday" relative date filter
    $date = new DateTime();
    $date->sub(new DateInterval('P1D'));
    $expectedFrom = $date->format('Ymd') . '000000';
    $expectedTo = $date->format('Ymd') . '235959';
    $cases[] = array($expectedFrom, $expectedTo, 'previous.day', '', '');
    return $cases;
  }

  /**
   * Test that getFromTo returns the correct dates.
   *
   * @dataProvider fromToData
   * @param $expectedFrom
   * @param $expectedTo
   * @param $relative
   * @param $from
   * @param $to
   */
  public function testGetFromTo($expectedFrom, $expectedTo, $relative, $from, $to) {
    $obj = new CRM_Utils_Date();
    list($calculatedFrom, $calculatedTo) = $obj->getFromTo($relative, $from, $to);
    $this->assertEquals($expectedFrom, $calculatedFrom);
    $this->assertEquals($expectedTo, $calculatedTo);
  }

  /**
   * Test relativeToAbsolute function on a range of fiscal year options.
   *
   * Go backwards one year at a time through the sequence.
   */
  public function testRelativeToAbsoluteFiscalYear() {
    $sequence = ['this', 'previous', 'previous_before'];
    Civi::settings()->set('fiscalYearStart', ['M' => 7, 'd' => 1]);
    $fiscalYearStartYear = (strtotime('now') > strtotime((date('Y-07-01')))) ? date('Y') : (date('Y') - 1);

    foreach ($sequence as $relativeString) {
      $date = CRM_Utils_Date::relativeToAbsolute($relativeString, 'fiscal_year');
      $this->assertEquals([
        'from' => $fiscalYearStartYear . '0701',
        'to' => ($fiscalYearStartYear + 1) . '0630',
      ], $date, 'relative term is ' . $relativeString);

      $fiscalYearStartYear--;
    }
  }

  /**
   * Test relativeToAbsolute function on a range of year options.
   *
   * Go backwards one year at a time through the sequence.
   */
  public function testRelativeToAbsoluteYear() {
    $sequence = ['this', 'previous', 'previous_before'];
    $year = date('Y');

    foreach ($sequence as $relativeString) {
      $date = CRM_Utils_Date::relativeToAbsolute($relativeString, 'year');
      $this->assertEquals([
        'from' => $year . '0101',
        'to' => $year . '1231',
      ], $date, 'relative term is ' . $relativeString);

      $year--;
    }
  }

  /**
   * Test relativeToAbsolute function on a range of year options.
   *
   * Go backwards one year at a time through the sequence.
   */
  public function testRelativeEnding() {
    $relativeDateValues = [
      'ending.week' => '- 6 days',
      'ending_30.day' => '- 29 days',
      'ending.year' => '- 1 year + 1 day',
      'ending_90.day' => '- 89 days',
      'ending_60.day' => '- 59 days',
      'ending_2.year' => '- 2 years + 1 day',
      'ending_3.year' => '- 3 years + 1 day',
      'ending_18.year' => '- 18 years + 1 day',
      'ending_18.quarter' => '- 54 months + 1 day',
      'ending_18.week' => '- 18 weeks + 1 day',
      'ending_18.month' => '- 18 months + 1 day',
      'ending_18.day' => '- 17 days',
    ];

    foreach ($relativeDateValues as $key => $value) {
      $parts = explode('.', $key);
      $date = CRM_Utils_Date::relativeToAbsolute($parts[0], $parts[1]);
      $this->assertEquals([
        'from' => date('Ymd000000', strtotime($value)),
        'to' => date('Ymd235959'),
      ], $date, 'relative term is ' . $key);
    }

    $date = CRM_Utils_Date::relativeToAbsolute('ending', 'month');
    $this->assertEquals([
      'from' => date('Ymd000000', strtotime('- 29 days')),
      'to' => date('Ymd235959'),
    ], $date, 'relative term is ending.week');
  }

  /**
   * Test relativeToAbsolute function on a range of year options.
   *
   * Go backwards one year at a time through the sequence.
   */
  public function testRelativeToAbsoluteYearRange() {
    $sequence = ['previous_2'];
    $lastYear = (date('Y') - 1);

    foreach ($sequence as $relativeString) {
      $date = CRM_Utils_Date::relativeToAbsolute($relativeString, 'year');
      // For previous 2 years the range is e.g 2016-01-01 to 2017-12-31 so we have to subtract
      // one from the range count to reflect the calendar year being one less apart due
      // to it being from the beginning of one to the end of the next.
      $offset = (substr($relativeString, -1, 1)) - 1;
      $this->assertEquals([
        'from' => $lastYear - $offset . '0101',
        'to' => $lastYear . '1231',
      ], $date, 'relative term is ' . $relativeString);
    }
  }

  /**
   * Test relativeToAbsolute function on a range of year options.
   *
   * Go backwards one year at a time through the sequence.
   */
  public function testRelativeToAbsoluteFiscalYearRange() {
    $sequence = ['previous_2', 'previous_3', 'previous_4'];
    Civi::settings()->set('fiscalYearStart', ['M' => 7, 'd' => 1]);
    $lastFiscalYearEnd = (strtotime('now') > strtotime((date('Y-07-01')))) ? (date('Y')) : (date('Y') - 1);

    foreach ($sequence as $relativeString) {
      $date = CRM_Utils_Date::relativeToAbsolute($relativeString, 'fiscal_year');
      // For previous 2 years the range is e.g 2015-07-01 to 2017-06-30 so we have to subtract
      // one from the range count to reflect the calendar year being one less apart due
      // to it being from the beginning of one to the end of the next.
      $offset = (substr($relativeString, -1, 1));
      $this->assertEquals([
        'from' => $lastFiscalYearEnd - $offset . '0701',
        'to' => $lastFiscalYearEnd . '0630',
      ], $date, 'relative term is ' . $relativeString);
    }
  }

  /**
   * Test customFormat() function
   */
  public function testCustomFormat() {
    $dateTime = "2018-11-08 21:46:44";
    $this->assertEquals(CRM_Utils_Date::customFormat($dateTime, "%b"), "Nov");
    $this->assertEquals(CRM_Utils_Date::customFormat($dateTime, "%B"), "November");
    $this->assertEquals(CRM_Utils_Date::customFormat($dateTime, "%d"), "08");
    $this->assertEquals(CRM_Utils_Date::customFormat($dateTime, "%e"), " 8");
    $this->assertEquals(CRM_Utils_Date::customFormat($dateTime, "%E"), "8");
    $this->assertEquals(CRM_Utils_Date::customFormat($dateTime, "%f"), "th");
    $this->assertEquals(CRM_Utils_Date::customFormat($dateTime, "%H"), "21");
    $this->assertEquals(CRM_Utils_Date::customFormat($dateTime, "%I"), "09");
    $this->assertEquals(CRM_Utils_Date::customFormat($dateTime, "%k"), "21");
    $this->assertEquals(CRM_Utils_Date::customFormat($dateTime, "%l"), " 9");
    $this->assertEquals(CRM_Utils_Date::customFormat($dateTime, "%m"), "11");
    $this->assertEquals(CRM_Utils_Date::customFormat($dateTime, "%M"), "46");
    $this->assertEquals(CRM_Utils_Date::customFormat($dateTime, "%p"), "pm");
    $this->assertEquals(CRM_Utils_Date::customFormat($dateTime, "%P"), "PM");
    $this->assertEquals(CRM_Utils_Date::customFormat($dateTime, "%Y"), "2018");
  }

  /**
   * Test customFormat() function
   */
  public function testCustomFormatTs() {
    $ts = mktime(21, 46, 44, 11, 8, 2018);
    $this->assertEquals(CRM_Utils_Date::customFormatTs($ts, "%b"), "Nov");
    $this->assertEquals(CRM_Utils_Date::customFormatTs($ts, "%B"), "November");
    $this->assertEquals(CRM_Utils_Date::customFormatTs($ts, "%d"), "08");
    $this->assertEquals(CRM_Utils_Date::customFormatTs($ts, "%e"), " 8");
    $this->assertEquals(CRM_Utils_Date::customFormatTs($ts, "%E"), "8");
    $this->assertEquals(CRM_Utils_Date::customFormatTs($ts, "%f"), "th");
    $this->assertEquals(CRM_Utils_Date::customFormatTs($ts, "%H"), "21");
    $this->assertEquals(CRM_Utils_Date::customFormatTs($ts, "%I"), "09");
    $this->assertEquals(CRM_Utils_Date::customFormatTs($ts, "%k"), "21");
    $this->assertEquals(CRM_Utils_Date::customFormatTs($ts, "%l"), " 9");
    $this->assertEquals(CRM_Utils_Date::customFormatTs($ts, "%m"), "11");
    $this->assertEquals(CRM_Utils_Date::customFormatTs($ts, "%M"), "46");
    $this->assertEquals(CRM_Utils_Date::customFormatTs($ts, "%p"), "pm");
    $this->assertEquals(CRM_Utils_Date::customFormatTs($ts, "%P"), "PM");
    $this->assertEquals(CRM_Utils_Date::customFormatTs($ts, "%Y"), "2018");
  }

}
