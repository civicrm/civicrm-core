<?php

/**
 * Class CRM_Utils_DateTest
 * @group headless
 */
class CRM_Utils_DateTest extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();
  }

  /**
   * Check the different Fiscal date ranges using Date util function - relativeToAbsolute
   */
  public function testFiscalRelativeDateRanges() {
    Civi::settings()->set('fiscalYearStart', array('d' => '01', 'M' => '02'));
    $unit = 'fiscal_year';
    $relativeTerm = 'this';
    $mappingDates = array(
      'fiscal_year.this' => array(
        'from' => date('Y') . '0201',
        'to' => date('Y', strtotime('+1 Year')) . '0131',
      ),
      'fiscal_year.previous_before' => array(
        'from' => date('Y', strtotime('2 Years ago')) . '0201',
        'to' => date('Y', strtotime('1 Year ago')) . '0131',
      ),
      'fiscal_year.previous' => array(
        'from' => date('Y', strtotime('1 Year ago')) . '0201',
        'to' => date('Y') . '0131',
      ),
      'fiscal_year.previous_2' => array(
        'from' => date('Y', strtotime('2 Years ago')) . '0201',
        'to' => date('Y') . '0131',
      ),
      'fiscal_year.previous_3' => array(
        'from' => date('Y', strtotime('3 Years ago')) . '0201',
        'to' => date('Y') . '0131',
      ),
      'fiscal_year.earlier' => array(
        'from' => NULL,
        'to' => date('Y') . '0131',
      ),
      'fiscal_year.greater' => array(
        'from' => date('Y') . '0201',
        'to' => NULL,
      ),
      'fiscal_year.greater_previous' => array(
        'from' => date('Y', strtotime('1 Year ago')) . '0201',
        'to' => NULL,
      ),
      'fiscal_year.current' => array(
        'from' => date('Y') . '0201',
        'to' => date('Ymd') . '235959',
      ),
      'fiscal_year.less' => array(
        'from' => NULL,
        'to' => date('Y') . '0131',
      ),
      'fiscal_year.next' => array(
        'from' => date('Y', strtotime('+1 Year')) . '0201',
        'to' => date('Y', strtotime('+2 Years')) . '0131',
      ),
      'fiscal_year.this_2' => array(
        'from' => date('Y', strtotime('2 Years ago')) . '0201',
        'to' => date('Y', strtotime('+1 Year')) . '0131',
      ),
      'fiscal_year.this_3' => array(
        'from' => date('Y', strtotime('3 Years ago')) . '0201',
        'to' => date('Y', strtotime('+1 Year')) . '0131',
      ),
    );
    foreach ($mappingDates as $relativeString => $expectedDates) {
      list($unit, $relativeTerm) = explode('.', $relativeString);
      $actualDates = CRM_Utils_Date::relativeToAbsolute($relativeTerm, $unit);
      $this->checkArrayEquals($expectedDates, $actualDates, "Relative date term - {$relativeTerm} for Fiscal year does not match");
    }
  }

}
