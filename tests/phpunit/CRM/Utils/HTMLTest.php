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
 * Tests for parsing translatable strings in HTML content.
 * @group headless
 */
class CRM_Utils_HTMLTest extends CiviUnitTestCase {

  /**
   * @return array
   */
  public function translateExamples() {
    $cases = array();
    $cases[] = array(
      '',
      array(),
    );
    // missing ts
    $cases[] = array(
      '<div>Hello world</div>',
      array(),
    );
    // text, no arg
    $cases[] = array(
      '<div>{{ts("Hello world")}}</div>',
      array('Hello world'),
    );
    // text, no arg, alternate text
    $cases[] = array(
      '<div>{{ts("Good morning, Dave")}}</div>',
      array('Good morning, Dave'),
    );
    // text, with arg
    $cases[] = array(
      '<div>{{ts("Hello world", {1: "whiz"})}}</div>',
      array('Hello world'),
    );
    // text, not really ts(), no arg
    $cases[] = array(
      '<div>{{clients("Hello world")}}</div>',
      array(),
    );
    // text, not really ts(), with arg
    $cases[] = array(
      '<div>{{clients("Hello world", {1: "whiz"})}}</div>',
      array(),
    );
    // two strings, duplicate
    $cases[] = array(
      '<div>{{ts("Hello world")}}</div> <p>{{ts("Hello world")}}</p>',
      array('Hello world'),
    );
    // two strings, addition
    $cases[] = array(
      '<div>{{ts("Hello world") + "-" + ts("How do you do?")}}</p>',
      array('Hello world', 'How do you do?'),
    );
    // two strings, separate calls
    $cases[] = array(
      '<div>{{ts("Hello world")}}</div> <p>{{ts("How do you do?")}}</p>',
      array('Hello world', 'How do you do?'),
    );
    // single quoted
    $cases[] = array(
      '<div>{{ts(\'Hello world\')}}</div>',
      array('Hello world'),
    );
    // unclear string
    $cases[] = array(
      '<div>{{ts(message)}}</div>',
      array(),
    );
    // ts() within a string
    $cases[] = array(
      '<div>{{ts("Does the ts(\'example\') notation work?")}}</div>',
      array('Does the ts(\'example\') notation work?'),
    );
    // attribute, no arg
    $cases[] = array(
      '<div crm-title="ts("Hello world")"></div>',
      array('Hello world'),
    );
    // attribute, with arg
    $cases[] = array(
      '<div crm-title="ts("Hello world", {1: "whiz"})"></div>',
      array('Hello world'),
    );
    // attribute, two strings, with arg
    $cases[] = array(
      '<div crm-title="ts("Hello world", {1: "whiz"}) + ts("How do you do, %1?", {2: "funky"})"></div>',
      array('Hello world', 'How do you do, %1?'),
    );
    // trick question! Not used on Smarty templates.
    $cases[] = array(
      '<div>{ts}Hello world{/ts}</div>',
      array(),
    );

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
