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

  /**
   * Used by testGetFromTo
   */
  private function fromToData() {
    $cases = [];
    // Absolute dates
    $cases['absolute'] = [
      'expectedFrom' => '20170901000000',
      'expectedTo' => '20170913235959',
      'relative' => 0,
      'from' => '09/01/2017',
      'to' => '09/13/2017',
    ];
    // "Today" relative date filter
    $date = new DateTime();
    $cases['today'] = [
      'expectedFrom' => $date->format('Ymd') . '000000',
      'expectedTo' => $date->format('Ymd') . '235959',
      'relative' => 'this.day',
      'from' => '',
      'to' => '',
    ];
    // "yesterday" relative date filter
    $date = new DateTime();
    $date->sub(new DateInterval('P1D'));
    $cases['yesterday'] = [
      'expectedFrom' => $date->format('Ymd') . '000000',
      'expectedTo' => $date->format('Ymd') . '235959',
      'relative' => 'previous.day',
      'from' => '',
      'to' => '',
    ];
    return $cases;
  }

  /**
   * Test that getFromTo returns the correct dates.
   */
  public function testGetFromTo() {
    $cases = $this->fromToData();
    foreach ($cases as $caseDescription => $case) {
      $obj = new CRM_Utils_Date();
      [$calculatedFrom, $calculatedTo] = $obj->getFromTo($case['relative'], $case['from'], $case['to']);
      $this->assertEquals($case['expectedFrom'], $calculatedFrom, "Expected From failed for case $caseDescription");
      $this->assertEquals($case['expectedTo'], $calculatedTo, "Expected To failed for case $caseDescription");
    }
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

    //  this_2 = 'These  2 Fiscal  years'
    $date = CRM_Utils_Date::relativeToAbsolute('this_2', 'fiscal_year');
    $this->assertEquals([
      'from' => ($fiscalYearStartYear - 1) . '0701000000',
      'to' => ($fiscalYearStartYear + 1) . '0630235959',
    ], $date, 'relative term is this_2.fiscal_year');

    foreach ($sequence as $relativeString) {
      $date = CRM_Utils_Date::relativeToAbsolute($relativeString, 'fiscal_year');
      $this->assertEquals([
        'from' => $fiscalYearStartYear . '0701',
        'to' => ($fiscalYearStartYear + 1) . '0630235959',
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

    //  this_2 = 'These  2 years'
    $date = CRM_Utils_Date::relativeToAbsolute('this_2', 'year');
    $thisYear = date('Y');
    $this->assertEquals([
      'from' => ($thisYear - 1) . '0101',
      'to' => $thisYear . '1231',
    ], $date, 'relative term is this_2 year');
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
  public function testRelativeThisFiscal() {
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
        'to' => $lastFiscalYearEnd . '0630235959',
      ], $date, 'relative term is ' . $relativeString);
    }
  }

  /**
   * Test customFormat() function
   */
  public function testCustomFormat() {
    $currentTimezone = date_default_timezone_get();
    date_default_timezone_set('America/Los_Angeles');
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
    $this->assertEquals(CRM_Utils_Date::customFormat($dateTime, "%s"), "44");
    $this->assertEquals(CRM_Utils_Date::customFormat($dateTime, "%A"), "Thursday");
    $this->assertEquals(CRM_Utils_Date::customFormat($dateTime, "%a"), "Thu");
    $this->assertEquals(CRM_Utils_Date::customFormat($dateTime, "%Z"), "PST");
    date_default_timezone_set($currentTimezone);
  }

  /**
   * Test customFormat() function
   */
  public function testCustomFormatTs() {
    $currentTimezone = date_default_timezone_get();
    date_default_timezone_set('America/Los_Angeles');
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
    $this->assertEquals(CRM_Utils_Date::customFormatTs($ts, "%A"), "Thursday");
    $this->assertEquals(CRM_Utils_Date::customFormatTs($ts, "%a"), "Thu");
    $this->assertEquals(CRM_Utils_Date::customFormatTs($ts, "%Z"), "PST");
    date_default_timezone_set($currentTimezone);
  }

  /**
   * Verify that the Timezone works for daylight savings based on the passed in date
   */
  public function testCustomFormatTimezoneDaylightSavings() {
    $currentTimezone = date_default_timezone_get();
    date_default_timezone_set('Australia/Sydney');
    $dateTime = '2018-11-08 21:46:44';
    $this->assertEquals("AEDT", CRM_Utils_Date::customFormat($dateTime, "%Z"));
    date_default_timezone_set($currentTimezone);
  }

  /**
   * Test Earlier Day Relative term to absolute
   */
  public function testRelativeEarlierDay() {
    $date = CRM_Utils_Date::relativeToAbsolute('earlier', 'day');

    $this->assertEquals([
      'from' => NULL,
      'to' => date('Ymd000000', strtotime('-1 day')),
    ], $date);
  }

  public function testLocalizeConsts() {
    $expect['en_US'] = ['Jan', 'Tue', 'March', 'Thursday'];
    $expect['fr_FR'] = ['janv.', 'mar.', 'mars', 'jeudi'];
    $expect['es_MX'] = ['ene.', 'mar.', 'Marzo', 'jueves'];

    foreach ($expect as $lang => $expectNames) {
      $useLocale = CRM_Utils_AutoClean::swapLocale($lang);
      $actualNames = [
        CRM_Utils_Date::getAbbrMonthNames()[1],
        CRM_Utils_Date::getAbbrWeekdayNames()[2],
        CRM_Utils_Date::getFullMonthNames()[3],
        CRM_Utils_Date::getFullWeekdayNames()[4],
      ];
      $this->assertEquals($expectNames, $actualNames, "Check temporal names in $lang");
      unset($useLocale);
    }
  }

  public function testWeekDayArrayOrder() {
    $this->callAPISuccess('Setting', 'create', ['weekBegins' => 1]);
    $this->assertEquals([
      1 => 'Monday',
      2 => 'Tuesday',
      3 => 'Wednesday',
      4 => 'Thursday',
      5 => 'Friday',
      6 => 'Saturday',
      0 => 'Sunday',
    ], CRM_Utils_Date::getFullWeekdayNames());
  }

  /**
   * Test formatDate function.
   *
   * @dataProvider dateDataProvider
   *
   * Test the format function used in imports. Note most forms
   * are able to format pre-submit but the import needs to parse the date.
   */
  public function testFormatDate($date, $format, $expected): void {
    $this->assertEquals($expected, CRM_Utils_Date::formatDate($date, $format));
  }

  /**
   * Data provider for date formats.
   *
   * @return array[]
   */
  public function dateDataProvider(): array {
    return [
      ['date' => '2022-10-01', 'format' => CRM_Core_Form_Date::DATE_yyyy_mm_dd, 'expected' => '20221001'],
      ['date' => '2022-10-01 15:54', 'format' => CRM_Core_Form_Date::DATE_yyyy_mm_dd, 'expected' => '20221001155400'],
      ['date' => '2022-10-01 15:54:56', 'format' => CRM_Core_Form_Date::DATE_yyyy_mm_dd, 'expected' => '20221001155456'],
    ];
  }

}
