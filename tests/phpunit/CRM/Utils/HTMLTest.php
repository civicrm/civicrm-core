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
    $cases[] = array(// missing ts
      '<div>Hello world</div>',
      array(),
    );
    $cases[] = array(// text, no arg
      '<div>{{ts("Hello world")}}</div>',
      array('Hello world'),
    );
    $cases[] = array(// text, no arg, alternate text
      '<div>{{ts("Good morning, Dave")}}</div>',
      array('Good morning, Dave'),
    );
    $cases[] = array(// text, with arg
      '<div>{{ts("Hello world", {1: "whiz"})}}</div>',
      array('Hello world'),
    );
    $cases[] = array(// text, not really ts(), no arg
      '<div>{{clients("Hello world")}}</div>',
      array(),
    );
    $cases[] = array(// text, not really ts(), with arg
      '<div>{{clients("Hello world", {1: "whiz"})}}</div>',
      array(),
    );
    $cases[] = array(// two strings, duplicate
      '<div>{{ts("Hello world")}}</div> <p>{{ts("Hello world")}}</p>',
      array('Hello world'),
    );
    $cases[] = array(// two strings, addition
      '<div>{{ts("Hello world") + "-" + ts("How do you do?")}}</p>',
      array('Hello world', 'How do you do?'),
    );
    $cases[] = array(// two strings, separate calls
      '<div>{{ts("Hello world")}}</div> <p>{{ts("How do you do?")}}</p>',
      array('Hello world', 'How do you do?'),
    );
    $cases[] = array(// single quoted
      '<div>{{ts(\'Hello world\')}}</div>',
      array('Hello world'),
    );
    $cases[] = array(// unclear string
      '<div>{{ts(message)}}</div>',
      array(),
    );
    $cases[] = array(// ts() within a string
      '<div>{{ts("Does the ts(\'example\') notation work?")}}</div>',
      array('Does the ts(\'example\') notation work?'),
    );
    $cases[] = array(// attribute, no arg
      '<div crm-title="ts("Hello world")"></div>',
      array('Hello world'),
    );
    $cases[] = array(// attribute, with arg
      '<div crm-title="ts("Hello world", {1: "whiz"})"></div>',
      array('Hello world'),
    );
    $cases[] = array(// attribute, two strings, with arg
      '<div crm-title="ts("Hello world", {1: "whiz"}) + ts("How do you do, %1?", {2: "funky"})"></div>',
      array('Hello world', 'How do you do, %1?'),
    );
    $cases[] = array(// trick question! Not used on Smarty templates.
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
