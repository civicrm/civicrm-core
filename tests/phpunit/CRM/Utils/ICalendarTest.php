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
 * Tests for linking to resource files
 * @group headless
 */
class CRM_Utils_ICalendarTest extends CiviUnitTestCase {

  /**
   * @return array
   */
  public function escapeExamples() {
    $cases = [];
    $cases[] = ["Hello
    this is, a test!",
    ];
    $cases[] = ["Hello!!

    this is, a \"test\"!",
    ];
    return $cases;
  }

  /**
   * @param string $testString
   * @dataProvider escapeExamples
   */
  public function testParseStrings($testString) {
    $this->assertEquals($testString, CRM_Utils_ICalendar::unformatText(CRM_Utils_ICalendar::formatText($testString)));
  }

}
