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
   * Set up for tests.
   */
  public function setUp(): void {
    parent::setUp();
    $this->useTransaction();
  }

  /**
   * Used by testGetFromTo
   */
  private function fromToData(): array {
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
  public function testGetFromTo(): void {
    $cases = $this->fromToData();
    foreach ($cases as $caseDescription => $case) {
      [$calculatedFrom, $calculatedTo] = CRM_Utils_Date::getFromTo($case['relative'], $case['from'], $case['to']);
      $this->assertEquals($case['expectedFrom'], $calculatedFrom, "Expected From failed for case $caseDescription");
      $this->assertEquals($case['expectedTo'], $calculatedTo, "Expected To failed for case $caseDescription");
    }
  }

  /**
   * Test relativeToAbsolute function on a range of fiscal year options.
   *
   * Go backwards one year at a time through the sequence.
   */
  public function testRelativeToAbsoluteFiscalYear(): void {
    $sequence = ['this', 'previous', 'previous_before'];
    Civi::settings()->set('fiscalYearStart', ['M' => 7, 'd' => 1]);
    $fiscalYearStartYear = (time() > strtotime((date('Y-07-01')))) ? date('Y') : (date('Y') - 1);

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
  public function testRelativeToAbsoluteYear(): void {
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
  public function testRelativeEnding(): void {
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
  public function testRelativeThisFiscal(): void {
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
  public function testRelativeToAbsoluteYearRange(): void {
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
  public function testRelativeToAbsoluteFiscalYearRange(): void {
    $sequence = ['previous_2', 'previous_3', 'previous_4'];
    Civi::settings()->set('fiscalYearStart', ['M' => 7, 'd' => 1]);
    $lastFiscalYearEnd = (time() > strtotime((date('Y-07-01')))) ? (date('Y')) : (date('Y') - 1);

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
   * Extendable test of relative dates.
   *
   * This is similar to the existing tests but can quickly add a variation
   * by adding an array to the dataProvider.
   *
   * @dataProvider relativeDateProvider
   * @param array $input
   * @param array $expected
   */
  public function testRelativeToAbsoluteGeneral(array $input, array $expected): void {
    if (isset($input['fiscalYearStart'])) {
      Civi::settings()->set('fiscalYearStart', $input['fiscalYearStart']);
    }
    putenv('TIME_FUNC=frozen');
    CRM_Utils_Time::setTime($input['now']);
    // The data is in a more human-readable form to make it easier for people,
    // but we need it "squashed" so take out punctuation.
    $expected = [
      'from' => preg_replace('/[^0-9]/', '', $expected['from']),
      'to' => preg_replace('/[^0-9]/', '', $expected['to']),
    ];
    $this->assertEquals($expected, CRM_Utils_Date::relativeToAbsolute($input['term'], $input['unit']));
    putenv('TIME_FUNC=');
    CRM_Utils_Time::resetTime();
  }

  /**
   * dataProvider for testRelativeToAbsoluteGeneral()
   * @return array
   */
  public function relativeDateProvider(): array {
    return [
      [
        'input' => [
          'now' => '2022-01-02',
          'fiscalYearStart' => ['M' => 1, 'd' => 1],
          'term' => 'previous',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2021-01-01',
          'to' => '2021-12-31 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-01-02',
          'fiscalYearStart' => ['M' => 1, 'd' => 1],
          // previous_1 should be equivalent to previous
          'term' => 'previous_1',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2021-01-01',
          'to' => '2021-12-31 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-01-02',
          'fiscalYearStart' => ['M' => 1, 'd' => 1],
          'term' => 'previous_2',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2020-01-01',
          'to' => '2021-12-31 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-01-02',
          'fiscalYearStart' => ['M' => 1, 'd' => 1],
          'term' => 'previous_3',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2019-01-01',
          'to' => '2021-12-31 23:59:59',
        ],
      ],

      [
        'input' => [
          'now' => '2022-01-02',
          'fiscalYearStart' => ['M' => 2, 'd' => 1],
          'term' => 'previous',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2020-02-01',
          'to' => '2021-01-31 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-01-02',
          'fiscalYearStart' => ['M' => 2, 'd' => 1],
          'term' => 'previous_1',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2020-02-01',
          'to' => '2021-01-31 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-01-02',
          'fiscalYearStart' => ['M' => 2, 'd' => 1],
          'term' => 'previous_2',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2019-02-01',
          'to' => '2021-01-31 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-01-02',
          'fiscalYearStart' => ['M' => 2, 'd' => 1],
          'term' => 'previous_3',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2018-02-01',
          'to' => '2021-01-31 23:59:59',
        ],
      ],

      [
        'input' => [
          'now' => '2022-01-02',
          'fiscalYearStart' => ['M' => 3, 'd' => 1],
          'term' => 'previous',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2020-03-01',
          'to' => '2021-02-28 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-01-02',
          'fiscalYearStart' => ['M' => 3, 'd' => 1],
          'term' => 'previous_1',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2020-03-01',
          'to' => '2021-02-28 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-01-02',
          'fiscalYearStart' => ['M' => 3, 'd' => 1],
          'term' => 'previous_2',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2019-03-01',
          'to' => '2021-02-28 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-01-02',
          'fiscalYearStart' => ['M' => 3, 'd' => 1],
          'term' => 'previous_3',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2018-03-01',
          'to' => '2021-02-28 23:59:59',
        ],
      ],

      // leap year
      [
        'input' => [
          'now' => '2021-01-02',
          'fiscalYearStart' => ['M' => 3, 'd' => 1],
          'term' => 'previous',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2019-03-01',
          'to' => '2020-02-29 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2021-01-02',
          'fiscalYearStart' => ['M' => 3, 'd' => 1],
          'term' => 'previous_1',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2019-03-01',
          'to' => '2020-02-29 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2021-01-02',
          'fiscalYearStart' => ['M' => 3, 'd' => 1],
          'term' => 'previous_2',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2018-03-01',
          'to' => '2020-02-29 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2021-01-02',
          'fiscalYearStart' => ['M' => 3, 'd' => 1],
          'term' => 'previous_3',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2017-03-01',
          'to' => '2020-02-29 23:59:59',
        ],
      ],

      [
        'input' => [
          'now' => '2022-01-02',
          'fiscalYearStart' => ['M' => 4, 'd' => 1],
          'term' => 'previous',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2020-04-01',
          'to' => '2021-03-31 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-01-02',
          'fiscalYearStart' => ['M' => 4, 'd' => 1],
          'term' => 'previous_1',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2020-04-01',
          'to' => '2021-03-31 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-01-02',
          'fiscalYearStart' => ['M' => 4, 'd' => 1],
          'term' => 'previous_2',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2019-04-01',
          'to' => '2021-03-31 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-01-02',
          'fiscalYearStart' => ['M' => 4, 'd' => 1],
          'term' => 'previous_3',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2018-04-01',
          'to' => '2021-03-31 23:59:59',
        ],
      ],

      [
        'input' => [
          'now' => '2022-01-02',
          'fiscalYearStart' => ['M' => 11, 'd' => 1],
          'term' => 'previous',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2020-11-01',
          'to' => '2021-10-31 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-01-02',
          'fiscalYearStart' => ['M' => 11, 'd' => 1],
          'term' => 'previous_1',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2020-11-01',
          'to' => '2021-10-31 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-01-02',
          'fiscalYearStart' => ['M' => 11, 'd' => 1],
          'term' => 'previous_2',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2019-11-01',
          'to' => '2021-10-31 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-01-02',
          'fiscalYearStart' => ['M' => 11, 'd' => 1],
          'term' => 'previous_3',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2018-11-01',
          'to' => '2021-10-31 23:59:59',
        ],
      ],

      [
        'input' => [
          'now' => '2022-01-02',
          'fiscalYearStart' => ['M' => 12, 'd' => 1],
          'term' => 'previous',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2020-12-01',
          'to' => '2021-11-30 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-01-02',
          'fiscalYearStart' => ['M' => 12, 'd' => 1],
          'term' => 'previous_1',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2020-12-01',
          'to' => '2021-11-30 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-01-02',
          'fiscalYearStart' => ['M' => 12, 'd' => 1],
          'term' => 'previous_2',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2019-12-01',
          'to' => '2021-11-30 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-01-02',
          'fiscalYearStart' => ['M' => 12, 'd' => 1],
          'term' => 'previous_3',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2018-12-01',
          'to' => '2021-11-30 23:59:59',
        ],
      ],

      /////////////////
      [
        'input' => [
          'now' => '2022-02-02',
          'fiscalYearStart' => ['M' => 1, 'd' => 1],
          'term' => 'previous',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2021-01-01',
          'to' => '2021-12-31 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-02-02',
          'fiscalYearStart' => ['M' => 1, 'd' => 1],
          // previous_1 should be equivalent to previous
          'term' => 'previous_1',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2021-01-01',
          'to' => '2021-12-31 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-02-02',
          'fiscalYearStart' => ['M' => 1, 'd' => 1],
          'term' => 'previous_2',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2020-01-01',
          'to' => '2021-12-31 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-02-02',
          'fiscalYearStart' => ['M' => 1, 'd' => 1],
          'term' => 'previous_3',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2019-01-01',
          'to' => '2021-12-31 23:59:59',
        ],
      ],

      [
        'input' => [
          'now' => '2022-02-02',
          'fiscalYearStart' => ['M' => 2, 'd' => 1],
          'term' => 'previous',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2021-02-01',
          'to' => '2022-01-31 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-02-02',
          'fiscalYearStart' => ['M' => 2, 'd' => 1],
          'term' => 'previous_1',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2021-02-01',
          'to' => '2022-01-31 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-02-02',
          'fiscalYearStart' => ['M' => 2, 'd' => 1],
          'term' => 'previous_2',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2020-02-01',
          'to' => '2022-01-31 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-02-02',
          'fiscalYearStart' => ['M' => 2, 'd' => 1],
          'term' => 'previous_3',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2019-02-01',
          'to' => '2022-01-31 23:59:59',
        ],
      ],

      [
        'input' => [
          'now' => '2022-02-02',
          'fiscalYearStart' => ['M' => 3, 'd' => 1],
          'term' => 'previous',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2020-03-01',
          'to' => '2021-02-28 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-02-02',
          'fiscalYearStart' => ['M' => 3, 'd' => 1],
          'term' => 'previous_1',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2020-03-01',
          'to' => '2021-02-28 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-02-02',
          'fiscalYearStart' => ['M' => 3, 'd' => 1],
          'term' => 'previous_2',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2019-03-01',
          'to' => '2021-02-28 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-02-02',
          'fiscalYearStart' => ['M' => 3, 'd' => 1],
          'term' => 'previous_3',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2018-03-01',
          'to' => '2021-02-28 23:59:59',
        ],
      ],

      // leap year
      [
        'input' => [
          'now' => '2021-02-02',
          'fiscalYearStart' => ['M' => 3, 'd' => 1],
          'term' => 'previous',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2019-03-01',
          'to' => '2020-02-29 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2021-02-02',
          'fiscalYearStart' => ['M' => 3, 'd' => 1],
          'term' => 'previous_1',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2019-03-01',
          'to' => '2020-02-29 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2021-02-02',
          'fiscalYearStart' => ['M' => 3, 'd' => 1],
          'term' => 'previous_2',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2018-03-01',
          'to' => '2020-02-29 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2021-02-02',
          'fiscalYearStart' => ['M' => 3, 'd' => 1],
          'term' => 'previous_3',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2017-03-01',
          'to' => '2020-02-29 23:59:59',
        ],
      ],

      [
        'input' => [
          'now' => '2022-02-02',
          'fiscalYearStart' => ['M' => 4, 'd' => 1],
          'term' => 'previous',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2020-04-01',
          'to' => '2021-03-31 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-02-02',
          'fiscalYearStart' => ['M' => 4, 'd' => 1],
          'term' => 'previous_1',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2020-04-01',
          'to' => '2021-03-31 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-02-02',
          'fiscalYearStart' => ['M' => 4, 'd' => 1],
          'term' => 'previous_2',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2019-04-01',
          'to' => '2021-03-31 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-02-02',
          'fiscalYearStart' => ['M' => 4, 'd' => 1],
          'term' => 'previous_3',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2018-04-01',
          'to' => '2021-03-31 23:59:59',
        ],
      ],

      [
        'input' => [
          'now' => '2022-02-02',
          'fiscalYearStart' => ['M' => 11, 'd' => 1],
          'term' => 'previous',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2020-11-01',
          'to' => '2021-10-31 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-02-02',
          'fiscalYearStart' => ['M' => 11, 'd' => 1],
          'term' => 'previous_1',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2020-11-01',
          'to' => '2021-10-31 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-02-02',
          'fiscalYearStart' => ['M' => 11, 'd' => 1],
          'term' => 'previous_2',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2019-11-01',
          'to' => '2021-10-31 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-02-02',
          'fiscalYearStart' => ['M' => 11, 'd' => 1],
          'term' => 'previous_3',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2018-11-01',
          'to' => '2021-10-31 23:59:59',
        ],
      ],

      [
        'input' => [
          'now' => '2022-02-02',
          'fiscalYearStart' => ['M' => 12, 'd' => 1],
          'term' => 'previous',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2020-12-01',
          'to' => '2021-11-30 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-02-02',
          'fiscalYearStart' => ['M' => 12, 'd' => 1],
          'term' => 'previous_1',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2020-12-01',
          'to' => '2021-11-30 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-02-02',
          'fiscalYearStart' => ['M' => 12, 'd' => 1],
          'term' => 'previous_2',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2019-12-01',
          'to' => '2021-11-30 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-02-02',
          'fiscalYearStart' => ['M' => 12, 'd' => 1],
          'term' => 'previous_3',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2018-12-01',
          'to' => '2021-11-30 23:59:59',
        ],
      ],

      /////////////////
      [
        'input' => [
          'now' => '2022-03-02',
          'fiscalYearStart' => ['M' => 1, 'd' => 1],
          'term' => 'previous',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2021-01-01',
          'to' => '2021-12-31 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-03-02',
          'fiscalYearStart' => ['M' => 1, 'd' => 1],
          // previous_1 should be equivalent to previous
          'term' => 'previous_1',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2021-01-01',
          'to' => '2021-12-31 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-03-02',
          'fiscalYearStart' => ['M' => 1, 'd' => 1],
          'term' => 'previous_2',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2020-01-01',
          'to' => '2021-12-31 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-03-02',
          'fiscalYearStart' => ['M' => 1, 'd' => 1],
          'term' => 'previous_3',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2019-01-01',
          'to' => '2021-12-31 23:59:59',
        ],
      ],

      [
        'input' => [
          'now' => '2022-03-02',
          'fiscalYearStart' => ['M' => 2, 'd' => 1],
          'term' => 'previous',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2021-02-01',
          'to' => '2022-01-31 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-03-02',
          'fiscalYearStart' => ['M' => 2, 'd' => 1],
          'term' => 'previous_1',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2021-02-01',
          'to' => '2022-01-31 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-03-02',
          'fiscalYearStart' => ['M' => 2, 'd' => 1],
          'term' => 'previous_2',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2020-02-01',
          'to' => '2022-01-31 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-03-02',
          'fiscalYearStart' => ['M' => 2, 'd' => 1],
          'term' => 'previous_3',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2019-02-01',
          'to' => '2022-01-31 23:59:59',
        ],
      ],

      [
        'input' => [
          'now' => '2022-03-02',
          'fiscalYearStart' => ['M' => 3, 'd' => 1],
          'term' => 'previous',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2021-03-01',
          'to' => '2022-02-28 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-03-02',
          'fiscalYearStart' => ['M' => 3, 'd' => 1],
          'term' => 'previous_1',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2021-03-01',
          'to' => '2022-02-28 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-03-02',
          'fiscalYearStart' => ['M' => 3, 'd' => 1],
          'term' => 'previous_2',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2020-03-01',
          'to' => '2022-02-28 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-03-02',
          'fiscalYearStart' => ['M' => 3, 'd' => 1],
          'term' => 'previous_3',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2019-03-01',
          'to' => '2022-02-28 23:59:59',
        ],
      ],

      // leap year
      [
        'input' => [
          'now' => '2020-03-02',
          'fiscalYearStart' => ['M' => 3, 'd' => 1],
          'term' => 'previous',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2019-03-01',
          'to' => '2020-02-29 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2020-03-02',
          'fiscalYearStart' => ['M' => 3, 'd' => 1],
          'term' => 'previous_1',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2019-03-01',
          'to' => '2020-02-29 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2020-03-02',
          'fiscalYearStart' => ['M' => 3, 'd' => 1],
          'term' => 'previous_2',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2018-03-01',
          'to' => '2020-02-29 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2020-03-02',
          'fiscalYearStart' => ['M' => 3, 'd' => 1],
          'term' => 'previous_3',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2017-03-01',
          'to' => '2020-02-29 23:59:59',
        ],
      ],

      [
        'input' => [
          'now' => '2022-03-02',
          'fiscalYearStart' => ['M' => 4, 'd' => 1],
          'term' => 'previous',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2020-04-01',
          'to' => '2021-03-31 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-03-02',
          'fiscalYearStart' => ['M' => 4, 'd' => 1],
          'term' => 'previous_1',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2020-04-01',
          'to' => '2021-03-31 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-03-02',
          'fiscalYearStart' => ['M' => 4, 'd' => 1],
          'term' => 'previous_2',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2019-04-01',
          'to' => '2021-03-31 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-03-02',
          'fiscalYearStart' => ['M' => 4, 'd' => 1],
          'term' => 'previous_3',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2018-04-01',
          'to' => '2021-03-31 23:59:59',
        ],
      ],

      [
        'input' => [
          'now' => '2022-03-02',
          'fiscalYearStart' => ['M' => 11, 'd' => 1],
          'term' => 'previous',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2020-11-01',
          'to' => '2021-10-31 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-03-02',
          'fiscalYearStart' => ['M' => 11, 'd' => 1],
          'term' => 'previous_1',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2020-11-01',
          'to' => '2021-10-31 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-03-02',
          'fiscalYearStart' => ['M' => 11, 'd' => 1],
          'term' => 'previous_2',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2019-11-01',
          'to' => '2021-10-31 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-03-02',
          'fiscalYearStart' => ['M' => 11, 'd' => 1],
          'term' => 'previous_3',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2018-11-01',
          'to' => '2021-10-31 23:59:59',
        ],
      ],

      [
        'input' => [
          'now' => '2022-03-02',
          'fiscalYearStart' => ['M' => 12, 'd' => 1],
          'term' => 'previous',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2020-12-01',
          'to' => '2021-11-30 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-03-02',
          'fiscalYearStart' => ['M' => 12, 'd' => 1],
          'term' => 'previous_1',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2020-12-01',
          'to' => '2021-11-30 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-03-02',
          'fiscalYearStart' => ['M' => 12, 'd' => 1],
          'term' => 'previous_2',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2019-12-01',
          'to' => '2021-11-30 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-03-02',
          'fiscalYearStart' => ['M' => 12, 'd' => 1],
          'term' => 'previous_3',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2018-12-01',
          'to' => '2021-11-30 23:59:59',
        ],
      ],

      /////////////////
      [
        'input' => [
          'now' => '2022-04-02',
          'fiscalYearStart' => ['M' => 1, 'd' => 1],
          'term' => 'previous',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2021-01-01',
          'to' => '2021-12-31 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-04-02',
          'fiscalYearStart' => ['M' => 1, 'd' => 1],
          // previous_1 should be equivalent to previous
          'term' => 'previous_1',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2021-01-01',
          'to' => '2021-12-31 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-04-02',
          'fiscalYearStart' => ['M' => 1, 'd' => 1],
          'term' => 'previous_2',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2020-01-01',
          'to' => '2021-12-31 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-04-02',
          'fiscalYearStart' => ['M' => 1, 'd' => 1],
          'term' => 'previous_3',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2019-01-01',
          'to' => '2021-12-31 23:59:59',
        ],
      ],

      [
        'input' => [
          'now' => '2022-04-02',
          'fiscalYearStart' => ['M' => 2, 'd' => 1],
          'term' => 'previous',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2021-02-01',
          'to' => '2022-01-31 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-04-02',
          'fiscalYearStart' => ['M' => 2, 'd' => 1],
          'term' => 'previous_1',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2021-02-01',
          'to' => '2022-01-31 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-04-02',
          'fiscalYearStart' => ['M' => 2, 'd' => 1],
          'term' => 'previous_2',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2020-02-01',
          'to' => '2022-01-31 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-04-02',
          'fiscalYearStart' => ['M' => 2, 'd' => 1],
          'term' => 'previous_3',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2019-02-01',
          'to' => '2022-01-31 23:59:59',
        ],
      ],

      [
        'input' => [
          'now' => '2022-04-02',
          'fiscalYearStart' => ['M' => 3, 'd' => 1],
          'term' => 'previous',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2021-03-01',
          'to' => '2022-02-28 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-04-02',
          'fiscalYearStart' => ['M' => 3, 'd' => 1],
          'term' => 'previous_1',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2021-03-01',
          'to' => '2022-02-28 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-04-02',
          'fiscalYearStart' => ['M' => 3, 'd' => 1],
          'term' => 'previous_2',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2020-03-01',
          'to' => '2022-02-28 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-04-02',
          'fiscalYearStart' => ['M' => 3, 'd' => 1],
          'term' => 'previous_3',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2019-03-01',
          'to' => '2022-02-28 23:59:59',
        ],
      ],

      // leap year
      [
        'input' => [
          'now' => '2020-04-02',
          'fiscalYearStart' => ['M' => 3, 'd' => 1],
          'term' => 'previous',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2019-03-01',
          'to' => '2020-02-29 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2020-04-02',
          'fiscalYearStart' => ['M' => 3, 'd' => 1],
          'term' => 'previous_1',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2019-03-01',
          'to' => '2020-02-29 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2020-04-02',
          'fiscalYearStart' => ['M' => 3, 'd' => 1],
          'term' => 'previous_2',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2018-03-01',
          'to' => '2020-02-29 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2020-04-02',
          'fiscalYearStart' => ['M' => 3, 'd' => 1],
          'term' => 'previous_3',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2017-03-01',
          'to' => '2020-02-29 23:59:59',
        ],
      ],

      [
        'input' => [
          'now' => '2022-04-02',
          'fiscalYearStart' => ['M' => 4, 'd' => 1],
          'term' => 'previous',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2021-04-01',
          'to' => '2022-03-31 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-04-02',
          'fiscalYearStart' => ['M' => 4, 'd' => 1],
          'term' => 'previous_1',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2021-04-01',
          'to' => '2022-03-31 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-04-02',
          'fiscalYearStart' => ['M' => 4, 'd' => 1],
          'term' => 'previous_2',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2020-04-01',
          'to' => '2022-03-31 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-04-02',
          'fiscalYearStart' => ['M' => 4, 'd' => 1],
          'term' => 'previous_3',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2019-04-01',
          'to' => '2022-03-31 23:59:59',
        ],
      ],

      [
        'input' => [
          'now' => '2022-04-02',
          'fiscalYearStart' => ['M' => 11, 'd' => 1],
          'term' => 'previous',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2020-11-01',
          'to' => '2021-10-31 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-04-02',
          'fiscalYearStart' => ['M' => 11, 'd' => 1],
          'term' => 'previous_1',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2020-11-01',
          'to' => '2021-10-31 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-04-02',
          'fiscalYearStart' => ['M' => 11, 'd' => 1],
          'term' => 'previous_2',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2019-11-01',
          'to' => '2021-10-31 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-04-02',
          'fiscalYearStart' => ['M' => 11, 'd' => 1],
          'term' => 'previous_3',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2018-11-01',
          'to' => '2021-10-31 23:59:59',
        ],
      ],

      [
        'input' => [
          'now' => '2022-04-02',
          'fiscalYearStart' => ['M' => 12, 'd' => 1],
          'term' => 'previous',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2020-12-01',
          'to' => '2021-11-30 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-04-02',
          'fiscalYearStart' => ['M' => 12, 'd' => 1],
          'term' => 'previous_1',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2020-12-01',
          'to' => '2021-11-30 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-04-02',
          'fiscalYearStart' => ['M' => 12, 'd' => 1],
          'term' => 'previous_2',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2019-12-01',
          'to' => '2021-11-30 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-04-02',
          'fiscalYearStart' => ['M' => 12, 'd' => 1],
          'term' => 'previous_3',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2018-12-01',
          'to' => '2021-11-30 23:59:59',
        ],
      ],

      /////////////////
      [
        'input' => [
          'now' => '2022-11-02',
          'fiscalYearStart' => ['M' => 1, 'd' => 1],
          'term' => 'previous',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2021-01-01',
          'to' => '2021-12-31 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-11-02',
          'fiscalYearStart' => ['M' => 1, 'd' => 1],
          // previous_1 should be equivalent to previous
          'term' => 'previous_1',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2021-01-01',
          'to' => '2021-12-31 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-11-02',
          'fiscalYearStart' => ['M' => 1, 'd' => 1],
          'term' => 'previous_2',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2020-01-01',
          'to' => '2021-12-31 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-11-02',
          'fiscalYearStart' => ['M' => 1, 'd' => 1],
          'term' => 'previous_3',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2019-01-01',
          'to' => '2021-12-31 23:59:59',
        ],
      ],

      [
        'input' => [
          'now' => '2022-11-02',
          'fiscalYearStart' => ['M' => 2, 'd' => 1],
          'term' => 'previous',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2021-02-01',
          'to' => '2022-01-31 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-11-02',
          'fiscalYearStart' => ['M' => 2, 'd' => 1],
          'term' => 'previous_1',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2021-02-01',
          'to' => '2022-01-31 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-11-02',
          'fiscalYearStart' => ['M' => 2, 'd' => 1],
          'term' => 'previous_2',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2020-02-01',
          'to' => '2022-01-31 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-11-02',
          'fiscalYearStart' => ['M' => 2, 'd' => 1],
          'term' => 'previous_3',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2019-02-01',
          'to' => '2022-01-31 23:59:59',
        ],
      ],

      [
        'input' => [
          'now' => '2022-11-02',
          'fiscalYearStart' => ['M' => 3, 'd' => 1],
          'term' => 'previous',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2021-03-01',
          'to' => '2022-02-28 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-11-02',
          'fiscalYearStart' => ['M' => 3, 'd' => 1],
          'term' => 'previous_1',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2021-03-01',
          'to' => '2022-02-28 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-11-02',
          'fiscalYearStart' => ['M' => 3, 'd' => 1],
          'term' => 'previous_2',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2020-03-01',
          'to' => '2022-02-28 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-11-02',
          'fiscalYearStart' => ['M' => 3, 'd' => 1],
          'term' => 'previous_3',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2019-03-01',
          'to' => '2022-02-28 23:59:59',
        ],
      ],

      // leap year
      [
        'input' => [
          'now' => '2020-11-02',
          'fiscalYearStart' => ['M' => 3, 'd' => 1],
          'term' => 'previous',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2019-03-01',
          'to' => '2020-02-29 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2020-11-02',
          'fiscalYearStart' => ['M' => 3, 'd' => 1],
          'term' => 'previous_1',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2019-03-01',
          'to' => '2020-02-29 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2020-11-02',
          'fiscalYearStart' => ['M' => 3, 'd' => 1],
          'term' => 'previous_2',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2018-03-01',
          'to' => '2020-02-29 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2020-11-02',
          'fiscalYearStart' => ['M' => 3, 'd' => 1],
          'term' => 'previous_3',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2017-03-01',
          'to' => '2020-02-29 23:59:59',
        ],
      ],

      [
        'input' => [
          'now' => '2022-11-02',
          'fiscalYearStart' => ['M' => 4, 'd' => 1],
          'term' => 'previous',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2021-04-01',
          'to' => '2022-03-31 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-11-02',
          'fiscalYearStart' => ['M' => 4, 'd' => 1],
          'term' => 'previous_1',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2021-04-01',
          'to' => '2022-03-31 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-11-02',
          'fiscalYearStart' => ['M' => 4, 'd' => 1],
          'term' => 'previous_2',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2020-04-01',
          'to' => '2022-03-31 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-11-02',
          'fiscalYearStart' => ['M' => 4, 'd' => 1],
          'term' => 'previous_3',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2019-04-01',
          'to' => '2022-03-31 23:59:59',
        ],
      ],

      [
        'input' => [
          'now' => '2022-11-02',
          'fiscalYearStart' => ['M' => 11, 'd' => 1],
          'term' => 'previous',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2021-11-01',
          'to' => '2022-10-31 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-11-02',
          'fiscalYearStart' => ['M' => 11, 'd' => 1],
          'term' => 'previous_1',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2021-11-01',
          'to' => '2022-10-31 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-11-02',
          'fiscalYearStart' => ['M' => 11, 'd' => 1],
          'term' => 'previous_2',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2020-11-01',
          'to' => '2022-10-31 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-11-02',
          'fiscalYearStart' => ['M' => 11, 'd' => 1],
          'term' => 'previous_3',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2019-11-01',
          'to' => '2022-10-31 23:59:59',
        ],
      ],

      [
        'input' => [
          'now' => '2022-11-02',
          'fiscalYearStart' => ['M' => 12, 'd' => 1],
          'term' => 'previous',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2020-12-01',
          'to' => '2021-11-30 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-11-02',
          'fiscalYearStart' => ['M' => 12, 'd' => 1],
          'term' => 'previous_1',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2020-12-01',
          'to' => '2021-11-30 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-11-02',
          'fiscalYearStart' => ['M' => 12, 'd' => 1],
          'term' => 'previous_2',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2019-12-01',
          'to' => '2021-11-30 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-11-02',
          'fiscalYearStart' => ['M' => 12, 'd' => 1],
          'term' => 'previous_3',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2018-12-01',
          'to' => '2021-11-30 23:59:59',
        ],
      ],

      /////////////////
      [
        'input' => [
          'now' => '2022-12-02',
          'fiscalYearStart' => ['M' => 1, 'd' => 1],
          'term' => 'previous',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2021-01-01',
          'to' => '2021-12-31 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-12-02',
          'fiscalYearStart' => ['M' => 1, 'd' => 1],
          // previous_1 should be equivalent to previous
          'term' => 'previous_1',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2021-01-01',
          'to' => '2021-12-31 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-12-02',
          'fiscalYearStart' => ['M' => 1, 'd' => 1],
          'term' => 'previous_2',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2020-01-01',
          'to' => '2021-12-31 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-12-02',
          'fiscalYearStart' => ['M' => 1, 'd' => 1],
          'term' => 'previous_3',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2019-01-01',
          'to' => '2021-12-31 23:59:59',
        ],
      ],

      [
        'input' => [
          'now' => '2022-12-02',
          'fiscalYearStart' => ['M' => 2, 'd' => 1],
          'term' => 'previous',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2021-02-01',
          'to' => '2022-01-31 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-12-02',
          'fiscalYearStart' => ['M' => 2, 'd' => 1],
          'term' => 'previous_1',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2021-02-01',
          'to' => '2022-01-31 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-12-02',
          'fiscalYearStart' => ['M' => 2, 'd' => 1],
          'term' => 'previous_2',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2020-02-01',
          'to' => '2022-01-31 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-12-02',
          'fiscalYearStart' => ['M' => 2, 'd' => 1],
          'term' => 'previous_3',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2019-02-01',
          'to' => '2022-01-31 23:59:59',
        ],
      ],

      [
        'input' => [
          'now' => '2022-12-02',
          'fiscalYearStart' => ['M' => 3, 'd' => 1],
          'term' => 'previous',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2021-03-01',
          'to' => '2022-02-28 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-12-02',
          'fiscalYearStart' => ['M' => 3, 'd' => 1],
          'term' => 'previous_1',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2021-03-01',
          'to' => '2022-02-28 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-12-02',
          'fiscalYearStart' => ['M' => 3, 'd' => 1],
          'term' => 'previous_2',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2020-03-01',
          'to' => '2022-02-28 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-12-02',
          'fiscalYearStart' => ['M' => 3, 'd' => 1],
          'term' => 'previous_3',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2019-03-01',
          'to' => '2022-02-28 23:59:59',
        ],
      ],

      // leap year
      [
        'input' => [
          'now' => '2020-12-02',
          'fiscalYearStart' => ['M' => 3, 'd' => 1],
          'term' => 'previous',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2019-03-01',
          'to' => '2020-02-29 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2020-12-02',
          'fiscalYearStart' => ['M' => 3, 'd' => 1],
          'term' => 'previous_1',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2019-03-01',
          'to' => '2020-02-29 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2020-12-02',
          'fiscalYearStart' => ['M' => 3, 'd' => 1],
          'term' => 'previous_2',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2018-03-01',
          'to' => '2020-02-29 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2020-12-02',
          'fiscalYearStart' => ['M' => 3, 'd' => 1],
          'term' => 'previous_3',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2017-03-01',
          'to' => '2020-02-29 23:59:59',
        ],
      ],

      [
        'input' => [
          'now' => '2022-12-02',
          'fiscalYearStart' => ['M' => 4, 'd' => 1],
          'term' => 'previous',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2021-04-01',
          'to' => '2022-03-31 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-12-02',
          'fiscalYearStart' => ['M' => 4, 'd' => 1],
          'term' => 'previous_1',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2021-04-01',
          'to' => '2022-03-31 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-12-02',
          'fiscalYearStart' => ['M' => 4, 'd' => 1],
          'term' => 'previous_2',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2020-04-01',
          'to' => '2022-03-31 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-12-02',
          'fiscalYearStart' => ['M' => 4, 'd' => 1],
          'term' => 'previous_3',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2019-04-01',
          'to' => '2022-03-31 23:59:59',
        ],
      ],

      [
        'input' => [
          'now' => '2022-12-02',
          'fiscalYearStart' => ['M' => 11, 'd' => 1],
          'term' => 'previous',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2021-11-01',
          'to' => '2022-10-31 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-12-02',
          'fiscalYearStart' => ['M' => 11, 'd' => 1],
          'term' => 'previous_1',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2021-11-01',
          'to' => '2022-10-31 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-12-02',
          'fiscalYearStart' => ['M' => 11, 'd' => 1],
          'term' => 'previous_2',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2020-11-01',
          'to' => '2022-10-31 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-12-02',
          'fiscalYearStart' => ['M' => 11, 'd' => 1],
          'term' => 'previous_3',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2019-11-01',
          'to' => '2022-10-31 23:59:59',
        ],
      ],

      [
        'input' => [
          'now' => '2022-12-02',
          'fiscalYearStart' => ['M' => 12, 'd' => 1],
          'term' => 'previous',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2021-12-01',
          'to' => '2022-11-30 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-12-02',
          'fiscalYearStart' => ['M' => 12, 'd' => 1],
          'term' => 'previous_1',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2021-12-01',
          'to' => '2022-11-30 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-12-02',
          'fiscalYearStart' => ['M' => 12, 'd' => 1],
          'term' => 'previous_2',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2020-12-01',
          'to' => '2022-11-30 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-12-02',
          'fiscalYearStart' => ['M' => 12, 'd' => 1],
          'term' => 'previous_3',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2019-12-01',
          'to' => '2022-11-30 23:59:59',
        ],
      ],

      // additional leap year cases
      [
        'input' => [
          'now' => '2020-02-29',
          'fiscalYearStart' => ['M' => 3, 'd' => 1],
          'term' => 'previous',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2018-03-01',
          'to' => '2019-02-28 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2020-03-01',
          'fiscalYearStart' => ['M' => 3, 'd' => 1],
          'term' => 'previous',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2019-03-01',
          'to' => '2020-02-29 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2020-02-29',
          'fiscalYearStart' => ['M' => 3, 'd' => 1],
          'term' => 'previous_2',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2017-03-01',
          'to' => '2019-02-28 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2020-03-01',
          'fiscalYearStart' => ['M' => 3, 'd' => 1],
          'term' => 'previous_2',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2018-03-01',
          'to' => '2020-02-29 23:59:59',
        ],
      ],

      // ************ previous_before.fiscal_year **********
      [
        'input' => [
          'now' => '2022-01-01',
          'fiscalYearStart' => ['M' => 1, 'd' => 1],
          'term' => 'previous_before',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2020-01-01',
          'to' => '2020-12-31 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-01-02',
          'fiscalYearStart' => ['M' => 1, 'd' => 1],
          'term' => 'previous_before',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2020-01-01',
          'to' => '2020-12-31 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-12-31',
          'fiscalYearStart' => ['M' => 1, 'd' => 1],
          'term' => 'previous_before',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2020-01-01',
          'to' => '2020-12-31 23:59:59',
        ],
      ],

      [
        'input' => [
          'now' => '2022-01-02',
          'fiscalYearStart' => ['M' => 2, 'd' => 1],
          'term' => 'previous_before',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2019-02-01',
          'to' => '2020-01-31 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-02-02',
          'fiscalYearStart' => ['M' => 2, 'd' => 1],
          'term' => 'previous_before',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2020-02-01',
          'to' => '2021-01-31 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-12-31',
          'fiscalYearStart' => ['M' => 2, 'd' => 1],
          'term' => 'previous_before',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2020-02-01',
          'to' => '2021-01-31 23:59:59',
        ],
      ],

      [
        'input' => [
          'now' => '2022-02-28',
          'fiscalYearStart' => ['M' => 3, 'd' => 1],
          'term' => 'previous_before',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2019-03-01',
          // leap year
          'to' => '2020-02-29 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2021-02-28',
          'fiscalYearStart' => ['M' => 3, 'd' => 1],
          'term' => 'previous_before',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2018-03-01',
          // not leap year
          'to' => '2019-02-28 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-03-01',
          'fiscalYearStart' => ['M' => 3, 'd' => 1],
          'term' => 'previous_before',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2020-03-01',
          'to' => '2021-02-28 23:59:59',
        ],
      ],
      [
        'input' => [
          'now' => '2022-12-31',
          'fiscalYearStart' => ['M' => 3, 'd' => 1],
          'term' => 'previous_before',
          'unit' => 'fiscal_year',
        ],
        'expected' => [
          'from' => '2020-03-01',
          'to' => '2021-02-28 23:59:59',
        ],
      ],
    ];
  }

  /**
   * Test customFormat() function
   */
  public function testCustomFormat(): void {
    $currentTimezone = date_default_timezone_get();
    date_default_timezone_set('America/Los_Angeles');
    $dateTime = "2018-11-08 21:46:44";
    $this->assertEquals("Nov", CRM_Utils_Date::customFormat($dateTime, "%b"));
    $this->assertEquals("November", CRM_Utils_Date::customFormat($dateTime, "%B"));
    $this->assertEquals("08", CRM_Utils_Date::customFormat($dateTime, "%d"));
    $this->assertEquals(" 8", CRM_Utils_Date::customFormat($dateTime, "%e"));
    $this->assertEquals("8", CRM_Utils_Date::customFormat($dateTime, "%E"));
    $this->assertEquals("th", CRM_Utils_Date::customFormat($dateTime, "%f"));
    $this->assertEquals("21", CRM_Utils_Date::customFormat($dateTime, "%H"));
    $this->assertEquals("09", CRM_Utils_Date::customFormat($dateTime, "%I"));
    $this->assertEquals("21", CRM_Utils_Date::customFormat($dateTime, "%k"));
    $this->assertEquals(" 9", CRM_Utils_Date::customFormat($dateTime, "%l"));
    $this->assertEquals("11", CRM_Utils_Date::customFormat($dateTime, "%m"));
    $this->assertEquals("46", CRM_Utils_Date::customFormat($dateTime, "%M"));
    $this->assertEquals("pm", CRM_Utils_Date::customFormat($dateTime, "%p"));
    $this->assertEquals("PM", CRM_Utils_Date::customFormat($dateTime, "%P"));
    $this->assertEquals("2018", CRM_Utils_Date::customFormat($dateTime, "%Y"));
    $this->assertEquals("44", CRM_Utils_Date::customFormat($dateTime, "%s"));
    $this->assertEquals("Thursday", CRM_Utils_Date::customFormat($dateTime, "%A"));
    $this->assertEquals("Thu", CRM_Utils_Date::customFormat($dateTime, "%a"));
    $this->assertEquals("PST", CRM_Utils_Date::customFormat($dateTime, "%Z"));
    date_default_timezone_set($currentTimezone);
  }

  /**
   * Test customFormat() function
   */
  public function testCustomFormatTs(): void {
    $currentTimezone = date_default_timezone_get();
    date_default_timezone_set('America/Los_Angeles');
    $ts = mktime(21, 46, 44, 11, 8, 2018);
    $this->assertEquals("Nov", CRM_Utils_Date::customFormatTs($ts, "%b"));
    $this->assertEquals("November", CRM_Utils_Date::customFormatTs($ts, "%B"));
    $this->assertEquals("08", CRM_Utils_Date::customFormatTs($ts, "%d"));
    $this->assertEquals(" 8", CRM_Utils_Date::customFormatTs($ts, "%e"));
    $this->assertEquals("8", CRM_Utils_Date::customFormatTs($ts, "%E"));
    $this->assertEquals("th", CRM_Utils_Date::customFormatTs($ts, "%f"));
    $this->assertEquals("21", CRM_Utils_Date::customFormatTs($ts, "%H"));
    $this->assertEquals("09", CRM_Utils_Date::customFormatTs($ts, "%I"));
    $this->assertEquals("21", CRM_Utils_Date::customFormatTs($ts, "%k"));
    $this->assertEquals(" 9", CRM_Utils_Date::customFormatTs($ts, "%l"));
    $this->assertEquals("11", CRM_Utils_Date::customFormatTs($ts, "%m"));
    $this->assertEquals("46", CRM_Utils_Date::customFormatTs($ts, "%M"));
    $this->assertEquals("pm", CRM_Utils_Date::customFormatTs($ts, "%p"));
    $this->assertEquals("PM", CRM_Utils_Date::customFormatTs($ts, "%P"));
    $this->assertEquals("2018", CRM_Utils_Date::customFormatTs($ts, "%Y"));
    $this->assertEquals("Thursday", CRM_Utils_Date::customFormatTs($ts, "%A"));
    $this->assertEquals("Thu", CRM_Utils_Date::customFormatTs($ts, "%a"));
    $this->assertEquals("PST", CRM_Utils_Date::customFormatTs($ts, "%Z"));
    date_default_timezone_set($currentTimezone);
  }

  /**
   * Verify that the Timezone works for daylight savings based on the passed in date
   */
  public function testCustomFormatTimezoneDaylightSavings(): void {
    $currentTimezone = date_default_timezone_get();
    date_default_timezone_set('Australia/Sydney');
    $dateTime = '2018-11-08 21:46:44';
    $this->assertEquals('AEDT', CRM_Utils_Date::customFormat($dateTime, "%Z"));
    date_default_timezone_set($currentTimezone);
  }

  /**
   * Test Earlier Day Relative term to absolute
   */
  public function testRelativeEarlierDay(): void {
    $date = CRM_Utils_Date::relativeToAbsolute('earlier', 'day');

    $this->assertEquals([
      'from' => NULL,
      'to' => date('Ymd000000', strtotime('-1 day')),
    ], $date);
  }

  public function testLocalizeConstants(): void {
    $expect['en_US'] = ['Jan', 'Tue', 'March', 'Thursday'];
    $expect['fr_FR'] = ['janv.', 'mar.', 'mars', 'jeudi'];
    $expect['es_MX'] = ['ene.', 'mar.', 'marzo', 'jueves'];

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

  public function testWeekDayArrayOrder(): void {
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
      ['date' => '2022-10-01', 'format' => CRM_Utils_Date::DATE_yyyy_mm_dd, 'expected' => '20221001'],
      ['date' => '2022-10-01 15:54', 'format' => CRM_Utils_Date::DATE_yyyy_mm_dd, 'expected' => '20221001155400'],
      ['date' => '2022-10-01 15:54:56', 'format' => CRM_Utils_Date::DATE_yyyy_mm_dd, 'expected' => '20221001155456'],
    ];
  }

}
