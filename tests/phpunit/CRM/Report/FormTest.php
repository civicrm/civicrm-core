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
    $obj = new CRM_Report_Form();
    list($calculatedFrom, $calculatedTo) = $obj->getFromTo($relative, $from, $to);
    $this->assertEquals([$expectedFrom, $expectedTo], [$calculatedFrom, $calculatedTo], "fail on data set [ $relative , $from , $to ]. Local php time is " . date('Y-m-d H:i:s') . ' and mysql time is ' . CRM_Core_DAO::singleValueQuery('SELECT NOW()'));
  }

}
