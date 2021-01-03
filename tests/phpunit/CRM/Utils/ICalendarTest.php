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

  /**
   * @return array
   */
  public function getSendParameters() {
    return [
      [
        ['calendar_data', 'text/xml', 'utf-8', NULL, NULL],
        [
          'Content-Language' => 'en_US',
          'Content-Type' => 'text/xml; charset=utf-8',
        ],
      ],
      [
        ['calendar_data', 'text/calendar', 'utf-8', NULL, NULL],
        [
          'Content-Language' => 'en_US',
          'Content-Type' => 'text/calendar; charset=utf-8',
        ],
      ],
    ];
  }

  /**
   * Test provided send parameters.
   *
   * @dataProvider getSendParameters
   */
  public function testSendParametersWithoutAttachment($parameters, $expected) {
    // we need to capture echo output
    ob_start();
    CRM_Utils_ICalendar::send(
      $parameters[0],
      $parameters[1],
      $parameters[2],
      $parameters[3],
      $parameters[4]
    );
    ob_end_clean();

    $headerList = \Civi::$statics['CRM_Utils_System_UnitTests']['header'];

    // Convert headers from simple array to associative array
    $headers = [];
    foreach ($headerList as $header) {
      $headerParts = explode(': ', $header);
      $headers[$headerParts[0]] = $headerParts[1];
    }

    $this->assertEquals($expected['Content-Language'], $headers['Content-Language']);
    $this->assertEquals($expected['Content-Type'], $headers['Content-Type']);
    $this->assertArrayNotHasKey('Content-Length', $headers);
    $this->assertArrayNotHasKey('Content-Disposition', $headers);
    $this->assertArrayNotHasKey('Pragma', $headers);
    $this->assertArrayNotHasKey('Expires', $headers);
    $this->assertArrayNotHasKey('Cache-Control', $headers);
  }

  /**
   * Test Send with attachment.
   */
  public function testSendWithAttachment() {
    $parameters = [
      'calendar_data', 'text/calendar', 'utf-8', 'civicrm_ical.ics', 'attachment',
    ];
    $expected = [
      'Content-Language' => 'en_US',
      'Content-Type' => 'text/calendar; charset=utf-8',
      'Content-Length' => '13',
      'Content-Disposition' => 'attachment; filename="civicrm_ical.ics"',
      'Pragma' => 'no-cache',
      'Expires' => '0',
      'Cache-Control' => 'no-cache, must-revalidate',
    ];

    // we need to capture echo output
    ob_start();
    CRM_Utils_ICalendar::send(
      $parameters[0],
      $parameters[1],
      $parameters[2],
      $parameters[3],
      $parameters[4]
    );
    ob_end_clean();

    $headerList = \Civi::$statics['CRM_Utils_System_UnitTests']['header'];

    // Convert headers from simple array to associative array
    $headers = [];
    foreach ($headerList as $header) {
      $headerParts = explode(': ', $header);
      $headers[$headerParts[0]] = $headerParts[1];
    }

    $this->assertEquals($expected['Content-Language'], $headers['Content-Language']);
    $this->assertEquals($expected['Content-Type'], $headers['Content-Type']);
    $this->assertEquals($expected['Content-Length'], $headers['Content-Length']);
    $this->assertEquals($expected['Content-Disposition'], $headers['Content-Disposition']);
    $this->assertEquals($expected['Pragma'], $headers['Pragma']);
    $this->assertEquals($expected['Expires'], $headers['Expires']);
    $this->assertEquals($expected['Cache-Control'], $headers['Cache-Control']);
  }

}
