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
 * Tests for linking to resource files
 * @group headless
 */
class CRM_Utils_JSTest extends CiviUnitTestCase {

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
      'alert("Hello world")',
      [],
    ];
    // basic function call
    $cases[] = [
      'alert(ts("Hello world"));',
      ['Hello world'],
    ];
    // with arg
    $cases[] = [
      'alert(ts("Hello world", {1: "whiz"}));',
      ['Hello world'],
    ];
    // not really ts()
    $cases[] = [
      'alert(clients("Hello world"));',
      [],
    ];
    // not really ts()
    $cases[] = [
      'alert(clients("Hello world", {1: "whiz"}));',
      [],
    ];
    // with arg
    $cases[] = [
      "\n" .
      "public function whits() {\n" .
      "  for (a in b) {\n" .
      "    mitts(\"wallaby\", function(zoo) {\n" .
      "      alert(zoo + ts(\"Hello\"))\n" .
      "    });\n" .
      "  }\n" .
      "}\n",
      ['Hello'],
    ];
    // duplicate
    $cases[] = [
      'alert(ts("Hello world") + "-" + ts("Hello world"));',
      ['Hello world'],
    ];
    // two strings, addition
    $cases[] = [
      'alert(ts("Hello world") + "-" + ts("How do you do?"));',
      ['Hello world', 'How do you do?'],
    ];
    // two strings, separate calls
    $cases[] = [
      'alert(ts("Hello world");\nalert(ts("How do you do?"));',
      ['Hello world', 'How do you do?'],
    ];
    $cases[] = [
      'alert(ts(\'Single quoted\'));',
      ['Single quoted'],
    ];
    // unclear string
    $cases[] = [
      'alert(ts(message));',
      [],
    ];
    // ts() within a string
    $cases[] = [
      'alert(ts("Does the ts(\'example\') notation work?"));',
      ['Does the ts(\'example\') notation work?'],
    ];
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

    $cases = [];
    $cases[] = [[$a], "$a"];
    $cases[] = [[$b], "$b"];
    $cases[] = [[$c], "$c"];
    $cases[] = [[$d], "$d"];
    $cases[] = [[$m], "$m"];
    $cases[] = [[$a, $b], "$ab"];
    $cases[] = [[$a, $m, $b], "$a$m$b"];
    $cases[] = [[$a, $d], "$a$d"];
    $cases[] = [[$a, $d, $b], "$a$d$b"];
    $cases[] = [[$a, $b, $c], "$abc"];
    $cases[] = [[$a, $b, $d, $c, $b], "$ab$d$cb"];
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
      ['angular', '$', '_'],
      ['angular', 'CRM.$', 'CRM._']
    );
    $this->assertEquals($expectedOutput, implode("", $actualOutput));
  }

  public function stripCommentsExamples() {
    $cases = [];
    $cases[] = [
      "a();\n//# sourceMappingURL=../foo/bar/baz.js\n\n\nb();",
      "a();\nb();",
    ];
    $cases[] = [
      "// foo\na();",
      "\na();",
    ];
    $cases[] = [
      "b();\n  // foo",
      "b();\n",
    ];
    $cases[] = [
      "/// foo\na();\n\t \t//bar\nb();\n// whiz",
      "\na();\nb();\n",
    ];
    $cases[] = [
      "alert('//# sourceMappingURL=../foo/bar/baz.js');\n//zoop\na();",
      "alert('//# sourceMappingURL=../foo/bar/baz.js');\na();",
    ];
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

  public static function decodeExamples() {
    return [
      ['{a: \'Apple\', \'b\': "Banana", c: [1, 2, 3]}', ['a' => 'Apple', 'b' => 'Banana', 'c' => [1, 2, 3]]],
      ['true', TRUE],
      [' ', NULL],
      ['false', FALSE],
      ['null', NULL],
      ['"true"', 'true'],
      ['0.5', 0.5],
      [" {}", []],
      ["[]", []],
      ["{  }", []],
      [" [   ]", []],
      [" [ 2   ]", [2]],
      [
        '{a: "parse error no closing bracket"',
        NULL,
      ],
      [
        '{a: ["foo", \'bar\'], "b": {a: [\'foo\', "bar"], b: {\'a\': ["foo", "bar"], b: {}}}}',
        ['a' => ['foo', 'bar'], 'b' => ['a' => ['foo', 'bar'], 'b' => ['a' => ['foo', 'bar'], 'b' => []]]],
      ],
      [
        ' [{a: {aa: true}, b: [false, null, {x: 1, y: 2, z: 3}] , "c": -1}, ["fee", "fie", \'foe\']]',
        [['a' => ['aa' => TRUE], 'b' => [FALSE, NULL, ['x' => 1, 'y' => 2, 'z' => 3]], "c" => -1], ["fee", "fie", "foe"]],
      ],
    ];
  }

  /**
   * @param string $input
   * @param string $expectedOutput
   * @dataProvider decodeExamples
   */
  public function testDecode($input, $expectedOutput) {
    $this->assertEquals($expectedOutput, CRM_Utils_JS::decode($input));
  }

  public static function encodeExamples() {
    return [
      [
        ['a' => 'Apple', 'b' => 'Banana', 'c' => [1, 2, 3]],
        "{a: 'Apple', b: 'Banana', c: [1, 2, 3]}",
      ],
      [
        ['a' => ['foo', 'bar'], 'b' => ["'a'" => ['foo/bar', 'bar(foo)'], 'b' => ['a' => ["fo'oo", 'bar'], 'b' => []]]],
        "{a: ['foo', 'bar'], b: {\"'a'\": ['foo/bar', 'bar(foo)'], b: {a: [\"fo'oo\", 'bar'], b: {}}}}",
      ],
      [TRUE, 'true'],
      [' ', "' '"],
      [FALSE, 'false'],
      [NULL, 'null'],
      ['true', "'true'"],
      ['0.5', "'0.5'"],
      [0.5, '0.5'],
      [[], "{}"],
    ];
  }

  /**
   * @param string $input
   * @param string $expectedOutput
   * @dataProvider encodeExamples
   */
  public function testEncode($input, $expectedOutput) {
    $this->assertEquals($expectedOutput, CRM_Utils_JS::encode($input));
  }

  /**
   * @return array
   */
  public static function objectExamples() {
    return [
      [
        '{a: \'Apple\', \'b\': "Banana", "c ": [1,2,3]}',
        ['a' => "'Apple'", 'b' => '"Banana"', 'c ' => '[1,2,3]'],
        '{a: \'Apple\', b: "Banana", \'c \': [1,2,3]}',
      ],
      [
        " {}",
        [],
        "{}",
      ],
      [
        " [ ] ",
        [],
        "{}",
      ],
      [
        "  {'fn' : function (foo, bar, baz) { return \"One, two, three\"; }, esc: /[1-9]\\\\/.test('5\\\\') , number  :  55.5/2 }   ",
        ['fn' => 'function (foo, bar, baz) { return "One, two, three"; }', 'esc' => "/[1-9]\\\\/.test('5\\\\')", 'number' => '55.5/2'],
        "{fn: function (foo, bar, baz) { return \"One, two, three\"; }, esc: /[1-9]\\\\/.test('5\\\\'), number: 55.5/2}",
      ],
      [
        "{ string :
          'this, has(some : weird, \\'stuff [{}!' ,
           expr: sum(1, 2, 3) / 2 + 1, ' notes ' : [Do, re mi],
        }",
        ['string' => "'this, has(some : weird, \\'stuff [{}!'", 'expr' => 'sum(1, 2, 3) / 2 + 1', ' notes ' => "[Do, re mi]"],
        "{string: 'this, has(some : weird, \\'stuff [{}!', expr: sum(1, 2, 3) / 2 + 1, ' notes ': [Do, re mi]}",
      ],
      [
        '{status: /^http:\/\/civicrm\.com/.test(url) ? \'good\' : \'bad\' , \'foo\&\': getFoo("Some \"quoted\" thing"), "ba\'[(r": function() {return "bar"}}',
        ['status' => '/^http:\/\/civicrm\.com/.test(url) ? \'good\' : \'bad\'', 'foo&' => 'getFoo("Some \"quoted\" thing")', "ba'[(r" => 'function() {return "bar"}'],
        '{status: /^http:\/\/civicrm\.com/.test(url) ? \'good\' : \'bad\', "foo&": getFoo("Some \"quoted\" thing"), "ba\'[(r": function() {return "bar"}}',
      ],
      [
        '{"some\"key": typeof foo === \'number\' ? true : false , "O\'Really?": ",((,", \'A"quote"\': 1 + 1 , "\\\\\\&\\/" : 0}',
        ['some"key' => 'typeof foo === \'number\' ? true : false', "O'Really?" => '",((,"', 'A"quote"' => '1 + 1', '\\&/' => '0'],
        '{\'some"key\': typeof foo === \'number\' ? true : false, "O\'Really?": ",((,", \'A"quote"\': 1 + 1, "\\\\&/": 0}',
      ],
      [
        '[foo ? 1 : 2 , 3 ,  function() {return 1 + 1;}, /^http:\/\/civicrm\.com/.test(url) ? \'good\' : \'bad\' , 3.14   ]',
        ['foo ? 1 : 2', '3', 'function() {return 1 + 1;}', '/^http:\/\/civicrm\.com/.test(url) ? \'good\' : \'bad\'', '3.14'],
        '[foo ? 1 : 2, 3, function() {return 1 + 1;}, /^http:\/\/civicrm\.com/.test(url) ? \'good\' : \'bad\', 3.14]',
      ],
    ];
  }

  /**
   * Test converting a js string to a php array and back again.
   *
   * @param string $input
   * @param string $expectedPHP
   * @param $expectedJS
   * @dataProvider objectExamples
   */
  public function testObjectToAndFromString($input, $expectedPHP, $expectedJS) {
    $objectProps = CRM_Utils_JS::getRawProps($input);
    $this->assertEquals($expectedPHP, $objectProps);
    $reformattedJS = CRM_Utils_JS::writeObject($objectProps);
    $this->assertEquals($expectedJS, $reformattedJS);
    $this->assertEquals($expectedPHP, CRM_Utils_JS::getRawProps($reformattedJS));
  }

}
