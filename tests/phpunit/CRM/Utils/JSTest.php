<?php
/*
+--------------------------------------------------------------------+
| CiviCRM version 4.7                                                |
+--------------------------------------------------------------------+
| Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * Tests for linking to resource files
 */
class CRM_Utils_JSTest extends CiviUnitTestCase {
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
      'alert("Hello world")',
      array(),
    );
    $cases[] = array(// basic function call
      'alert(ts("Hello world"));',
      array('Hello world'),
    );
    $cases[] = array(// with arg
      'alert(ts("Hello world", {1: "whiz"}));',
      array('Hello world'),
    );
    $cases[] = array(// not really ts()
      'alert(clients("Hello world"));',
      array(),
    );
    $cases[] = array(// not really ts()
      'alert(clients("Hello world", {1: "whiz"}));',
      array(),
    );
    $cases[] = array(// with arg
      "\n" .
      "public function whits() {\n" .
      "  for (a in b) {\n" .
      "    mitts(\"wallaby\", function(zoo) {\n" .
      "      alert(zoo + ts(\"Hello\"))\n" .
      "    });\n" .
      "  }\n" .
      "}\n",
      array('Hello'),
    );
    $cases[] = array(// duplicate
      'alert(ts("Hello world") + "-" + ts("Hello world"));',
      array('Hello world'),
    );
    $cases[] = array(// two strings, addition
      'alert(ts("Hello world") + "-" + ts("How do you do?"));',
      array('Hello world', 'How do you do?'),
    );
    $cases[] = array(// two strings, separate calls
      'alert(ts("Hello world");\nalert(ts("How do you do?"));',
      array('Hello world', 'How do you do?'),
    );
    $cases[] = array(
      'alert(ts(\'Single quoted\'));',
      array('Single quoted'),
    );
    $cases[] = array(// unclear string
      'alert(ts(message));',
      array(),
    );
    $cases[] = array(// ts() within a string
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
  public function testParseStrings($jsCode, $expectedStrings) {
    $actualStrings = CRM_Utils_JS::parseStrings($jsCode);
    sort($expectedStrings);
    sort($actualStrings);
    $this->assertEquals($expectedStrings, $actualStrings);
  }

  public function dedupeClosureExamples() {
    // Each example string here is named for its body, eg the body of $a calls "a()".
    $a = "(function (angular, $, _) {\na();\n})(angular, CRM.$, CRM._);";
    $b = "(function(angular,$,_){\nb();\n})(angular,CRM.$,CRM._);";
    $c = "(function( angular, $,_) {\nc();\n})(angular,CRM.$, CRM._);";
    $d = "(function (angular, $, _, whiz) {\nd();\n})(angular, CRM.$, CRM._, CRM.whizbang);";
    $m = "alert('i is the trickster (function( angular, $,_) {\nm();\n})(angular,CRM.$, CRM._);)'";
    // Note: $d has a fundamentally different closure.

    // Each example string here is a deduped combination of others,
    // eg "$ab" is the deduping of $a+$b.
    $ab = "(function (angular, $, _) {\na();\n\nb();\n})(angular,CRM.$,CRM._);";
    $abc = "(function (angular, $, _) {\na();\n\nb();\n\nc();\n})(angular,CRM.$, CRM._);";
    $cb = "(function( angular, $,_) {\nc();\n\nb();\n})(angular,CRM.$,CRM._);";

    $cases = array();
    $cases[] = array(array($a), "$a");
    $cases[] = array(array($b), "$b");
    $cases[] = array(array($c), "$c");
    $cases[] = array(array($d), "$d");
    $cases[] = array(array($m), "$m");
    $cases[] = array(array($a, $b), "$ab");
    $cases[] = array(array($a, $m, $b), "$a$m$b");
    $cases[] = array(array($a, $d), "$a$d");
    $cases[] = array(array($a, $d, $b), "$a$d$b");
    $cases[] = array(array($a, $b, $c), "$abc");
    $cases[] = array(array($a, $b, $d, $c, $b), "$ab$d$cb");
    return $cases;
  }

  /**
   * @param array $scripts
   * @param string $expectedOutput
   * @dataProvider dedupeClosureExamples
   */
  public function testDedupeClosure($scripts, $expectedOutput) {
    $actualOutput = CRM_Utils_JS::dedupeClosures(
      $scripts,
      array('angular', '$', '_'),
      array('angular', 'CRM.$', 'CRM._')
    );
    $this->assertEquals($expectedOutput, implode("", $actualOutput));
  }

  public function stripCommentsExamples() {
    $cases = array();
    $cases[] = array(
      "a();\n//# sourceMappingURL=../foo/bar/baz.js\nb();",
      "a();\n\nb();",
    );
    $cases[] = array(
      "// foo\na();",
      "\na();",
    );
    $cases[] = array(
      "b();\n  // foo",
      "b();\n",
    );
    $cases[] = array(
      "/// foo\na();\n\t \t//bar\nb();\n// whiz",
      "\na();\n\nb();\n",
    );
    $cases[] = array(
      "alert('//# sourceMappingURL=../foo/bar/baz.js');\n//zoop\na();",
      "alert('//# sourceMappingURL=../foo/bar/baz.js');\n\na();",
    );
    return $cases;
  }

  /**
   * @param string $input
   * @param string $expectedOutput
   * @dataProvider stripCommentsExamples
   */
  public function testStripComments($input, $expectedOutput) {
    $this->assertEquals($expectedOutput, CRM_Utils_JS::stripComments($input));
  }

}
