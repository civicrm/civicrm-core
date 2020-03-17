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
 * Tests for parsing translatable strings in HTML content.
 * @group headless
 */
class CRM_Utils_HTMLTest extends CiviUnitTestCase {

  /**
   * @return array
   */
  public function translateExamples() {
    $cases = [];
    $cases[] = [
      '',
      [],
    ];
    // missing ts
    $cases[] = [
      '<div>Hello world</div>',
      [],
    ];
    // text, no arg
    $cases[] = [
      '<div>{{ts("Hello world")}}</div>',
      ['Hello world'],
    ];
    // text, no arg, alternate text
    $cases[] = [
      '<div>{{ts("Good morning, Dave")}}</div>',
      ['Good morning, Dave'],
    ];
    // text, with arg
    $cases[] = [
      '<div>{{ts("Hello world", {1: "whiz"})}}</div>',
      ['Hello world'],
    ];
    // text, not really ts(), no arg
    $cases[] = [
      '<div>{{clients("Hello world")}}</div>',
      [],
    ];
    // text, not really ts(), with arg
    $cases[] = [
      '<div>{{clients("Hello world", {1: "whiz"})}}</div>',
      [],
    ];
    // two strings, duplicate
    $cases[] = [
      '<div>{{ts("Hello world")}}</div> <p>{{ts("Hello world")}}</p>',
      ['Hello world'],
    ];
    // two strings, addition
    $cases[] = [
      '<div>{{ts("Hello world") + "-" + ts("How do you do?")}}</p>',
      ['Hello world', 'How do you do?'],
    ];
    // two strings, separate calls
    $cases[] = [
      '<div>{{ts("Hello world")}}</div> <p>{{ts("How do you do?")}}</p>',
      ['Hello world', 'How do you do?'],
    ];
    // single quoted
    $cases[] = [
      '<div>{{ts(\'Hello world\')}}</div>',
      ['Hello world'],
    ];
    // unclear string
    $cases[] = [
      '<div>{{ts(message)}}</div>',
      [],
    ];
    // ts() within a string
    $cases[] = [
      '<div>{{ts("Does the ts(\'example\') notation work?")}}</div>',
      ['Does the ts(\'example\') notation work?'],
    ];
    // attribute, no arg
    $cases[] = [
      '<div crm-title="ts("Hello world")"></div>',
      ['Hello world'],
    ];
    // attribute, with arg
    $cases[] = [
      '<div crm-title="ts("Hello world", {1: "whiz"})"></div>',
      ['Hello world'],
    ];
    // attribute, two strings, with arg
    $cases[] = [
      '<div crm-title="ts("Hello world", {1: "whiz"}) + ts("How do you do, %1?", {2: "funky"})"></div>',
      ['Hello world', 'How do you do, %1?'],
    ];
    // trick question! Not used on Smarty templates.
    $cases[] = [
      '<div>{ts}Hello world{/ts}</div>',
      [],
    ];

    return $cases;
  }

  /**
   * @param string $html
   *   Example HTML input.
   * @param array $expectedStrings
   *   List of expected strings.
   * @dataProvider translateExamples
   */
  public function testParseStrings($html, $expectedStrings) {
    // Magic! The JS parser works with HTML!
    $actualStrings = CRM_Utils_JS::parseStrings($html);
    sort($expectedStrings);
    sort($actualStrings);
    $this->assertEquals($expectedStrings, $actualStrings);
  }

}
