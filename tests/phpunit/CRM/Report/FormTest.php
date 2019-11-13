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

  public function fromToData() {
    $cases = [];
    // Absolute dates
    $cases[] = ['20170901000000', '20170913235959', 0, '09/01/2017', '09/13/2017'];
    // "Today" relative date filter
    $date = new DateTime();
    $expectedFrom = $date->format('Ymd') . '000000';
    $expectedTo = $date->format('Ymd') . '235959';
    $cases[] = [$expectedFrom, $expectedTo, 'this.day', '', ''];
    // "yesterday" relative date filter
    $date = new DateTime();
    $date->sub(new DateInterval('P1D'));
    $expectedFrom = $date->format('Ymd') . '000000';
    $expectedTo = $date->format('Ymd') . '235959';
    $cases[] = [$expectedFrom, $expectedTo, 'previous.day', '', ''];
    return $cases;
  }

  /**
   * Test that getFromTo returns the correct dates.
   *
   * @dataProvider fromToData
   *
   * @param string $expectedFrom
   * @param string $expectedTo
   * @param string $relative
   * @param string $from
   * @param string $to
   */
  public function testGetFromTo($expectedFrom, $expectedTo, $relative, $from, $to) {
    $obj = new CRM_Report_Form();
    if (date('H-i') === '00:00') {
      $this->markTestIncomplete('The date might have changed since the dataprovider was called. Skip to avoid flakiness');
    }
    list($calculatedFrom, $calculatedTo) = $obj->getFromTo($relative, $from, $to);
    $this->assertEquals([$expectedFrom, $expectedTo], [$calculatedFrom, $calculatedTo], "fail on data set [ $relative , $from , $to ]. Local php time is " . date('Y-m-d H:i:s') . ' and mysql time is ' . CRM_Core_DAO::singleValueQuery('SELECT NOW()'));
  }

}
