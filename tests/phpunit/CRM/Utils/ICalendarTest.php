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

  public function setUp(): void {
    parent::setUp();
    $this->useTransaction();
  }

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
    $cases[] = ["one, two, three; aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaahh ahh ahhh"];
    $cases[] = ["Bonjour! éèçô, этому скромному разработчику не нравится война на Украине 💓 💔 🌈 💕 💖"];
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
  public function testSendWithAttachment(): void {
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

  public function testIcalTimezones() {
    // The default timezone is UTC which makes it hard to test timezone
    // accuracy, so we set to an arbitrary different timezone.
    $oldTimeZone = date_default_timezone_get();
    date_default_timezone_set('America/Los_Angeles');

    // When using eventCreateUnpaid(), the default date start is back in 2008
    // and the default date end is in one month, which creates an unnecessarily
    // huge ics file. So, override start and end date for easier readability.
    // It's from 7:00 pm - 8:00 pm tomorrow (LA time). Note: it has to be in
    // the future because ics files are only generated for future events.
    $eventParameters = [
      'start_date' => 'tomorrow 19:00',
      'end_date' => 'tomorrow 20:00',
    ];
    $this->eventCreateUnpaid($eventParameters);

    $info = CRM_Event_BAO_Event::getCompleteInfo(NULL, NULL, $this->getEventId());
    $calendar = explode("\n", CRM_Utils_ICalendar::createCalendarFile($info));
    $expectedLines = [
      "TZID:America/Los_Angeles" => FALSE,
      "DTSTART:20240104T190000" => FALSE,
      "DTSTAMP;TZID=America/Los_Angeles:20240104T190000" => FALSE,
      "DTSTART;TZID=America/Los_Angeles:20240104T190000" => FALSE,
      "DTEND;TZID=America/Los_Angeles:20240104T200000" => FALSE,
    ];
    foreach ($calendar as $line) {
      $line = trim($line);
      if (array_key_exists($line, $expectedLines)) {
        $expectedLines[$line] = TRUE;
      }
    }
    foreach ($expectedLines as $line => $status) {
      $this->assertTrue($status, "Missing {$line} from ics file output.");
    }
    date_default_timezone_set($oldTimeZone);
  }

}
