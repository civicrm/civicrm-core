<?php
/*
+--------------------------------------------------------------------+
| CiviCRM version 4.5                                                |
+--------------------------------------------------------------------+
| Copyright CiviCRM LLC (c) 2004-2014                                |
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

require_once 'CiviTest/CiviUnitTestCase.php';

/**
 * Tests for linking to resource files
 */
class CRM_Utils_JSTest extends CiviUnitTestCase {
  function translateExamples() {
    $cases = array();
    $cases[] = array(
      '',
      array(),
    );
    $cases[] = array( // missing ts
      'alert("Hello world")',
      array(),
    );
    $cases[] = array( // basic function call
      'alert(ts("Hello world"));',
      array('Hello world'),
    );
    $cases[] = array( // with arg
      'alert(ts("Hello world", {1: "whiz"}));',
      array('Hello world'),
    );
    $cases[] = array( // not really ts()
      'alert(clients("Hello world"));',
      array(),
    );
    $cases[] = array( // not really ts()
      'alert(clients("Hello world", {1: "whiz"}));',
      array(),
    );
    $cases[] = array( // with arg
      '
      function whits() {
        for (a in b) {
          mitts("wallaby", function(zoo){
            alert(zoo + ts("Hello"))
          });
        }
      }
      ',
      array('Hello'),
    );
    $cases[] = array( // duplicate
      'alert(ts("Hello world") + "-" + ts("Hello world"));',
      array('Hello world'),
    );
    $cases[] = array( // two strings, addition
      'alert(ts("Hello world") + "-" + ts("How do you do?"));',
      array('Hello world', 'How do you do?'),
    );
    $cases[] = array( // two strings, separate calls
      'alert(ts("Hello world");\nalert(ts("How do you do?"));',
      array('Hello world', 'How do you do?'),
    );
    $cases[] = array(
      'alert(ts(\'Single quoted\'));',
      array('Single quoted'),
    );
    $cases[] = array( // unclear string
      'alert(ts(message));',
      array(),
    );
    $cases[] = array( // ts() within a string
      'alert(ts("Does the ts(\'example\') notation work?"));',
      array('Does the ts(\'example\') notation work?'),
    );
    return $cases;
  }

  /**
   * @param string $jsCode
   * @param array $expectedStrings
   * @dataProvider translateExamples
   */
  function testParseStrings($jsCode, $expectedStrings) {
    $actualStrings = CRM_Utils_JS::parseStrings($jsCode);
    sort($expectedStrings);
    sort($actualStrings);
    $this->assertEquals($expectedStrings, $actualStrings);
  }
}
