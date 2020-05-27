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
 *  File for the FormTest class
 *
 *  (PHP 5)
 *
 * @author Jon Goldberg <jon@megaphonetech.com>
 */

/**
 *  Test CRM_Report_Form functions.
 *
 * @package   CiviCRM
 * @group headless
 */
class CRM_Report_FormTest extends CiviUnitTestCase {

  public function setUp() {
    // There are only unit tests here at present, we can skip database loading.
    return TRUE;
  }

  public function tearDown() {
    // There are only unit tests here at present, we can skip database loading.
    return TRUE;
  }

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
      $obj = new CRM_Report_Form();
      list($calculatedFrom, $calculatedTo) = $obj->getFromTo($case['relative'], $case['from'], $case['to']);
      $this->assertEquals([$case['expectedFrom'], $case['expectedTo']], [$calculatedFrom, $calculatedTo], "fail on data set '{$caseDescription}'. Local php time is " . date('Y-m-d H:i:s') . ' and mysql time is ' . CRM_Core_DAO::singleValueQuery('SELECT NOW()'));
    }
  }

}
