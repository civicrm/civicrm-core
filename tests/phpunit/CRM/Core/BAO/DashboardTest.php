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
