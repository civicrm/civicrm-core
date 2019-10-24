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
 * Test class for Dashboard BAO
 *
 * @package CiviCRM
 * @group headless
 */
class CRM_Core_BAO_DashboardTest extends CiviUnitTestCase {

  /**
   * Sets up the fixture, for example, opens a network connection.
   *
   * This method is called before a test is executed.
   */
  protected function setUp() {
    parent::setUp();
  }

  /**
   * @dataProvider parseUrlTestData
   * @param $input
   * @param $expectedResult
   */
  public function testParseUrl($input, $expectedResult) {
    $this->assertEquals($expectedResult, CRM_Core_BAO_Dashboard::parseUrl($input));
  }

  public function parseUrlTestData() {
    return [
      ['https://foo.bar', 'https://foo.bar'],
      ['civicrm/path?reset=1&unit=test', CRM_Utils_System::url('civicrm/path', 'reset=1&unit=test', FALSE, NULL, FALSE)],
    ];
  }

}
