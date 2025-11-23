<?php

namespace Civi\Test;

/**
 * Class ExtraAssertionsTrait
 * @package Civi\Test
 *
 * A small library of generic assertions - which are slightly more sophisticated than
 * the default (`assertEquals()`, `assertTrue()`) but *not* domain specific.
 */
trait GenericAssertionsTrait {

  /**
   * Assert that two array-trees are exactly equal.
   *
   * The ordering of keys do not affect the outcome (within either the roots
   * or in any child elements).
   *
   * Error messages will reveal a readable -path-, regardless of how many
   * levels of nesting are present.
   *
   * @param array $expected
   * @param array $actual
   */
  public function assertTreeEquals($expected, $actual) {
    $e = [];
    $a = [];
    \CRM_Utils_Array::flatten($expected, $e, '', ':::');
    \CRM_Utils_Array::flatten($actual, $a, '', ':::');
    ksort($e);
    ksort($a);

    $this->assertEquals($e, $a);
  }

  /**
   * Assert attributes are equal.
   *
   * @param array $expectedValues
   * @param array $actualValues
   * @param string $message
   *
   * @throws \PHPUnit_Framework_AssertionFailedError
   */
  public function assertAttributesEquals($expectedValues, $actualValues, $message = NULL) {
    foreach ($expectedValues as $paramName => $paramValue) {
      if (isset($actualValues[$paramName])) {
        $this->assertEquals($paramValue, $actualValues[$paramName], "Value Mismatch On $paramName - value 1 is " . print_r($paramValue, TRUE) . "  value 2 is " . print_r($actualValues[$paramName], TRUE));
      }
      else {
        $this->assertNull($expectedValues[$paramName], "Attribute '$paramName' not present in actual array and we expected it to be " . $expectedValues[$paramName]);
      }
    }
  }

  /**
   * @param string|int $key
   * @param array $list
   */
  public function assertArrayKeyExists($key, &$list) {
    $result = isset($list[$key]);
    $this->assertTrue($result, sprintf("%s element exists?", $key));
  }

  /**
   * Assert the 2 arrays have the same values.
   *
   * The order of arrays, and keys of the arrays, do not affect the outcome.
   *
   * @param array $array1
   * @param array $array2
   */
  public function assertArrayValuesEqual($array1, $array2) {
    $array1 = array_values($array1);
    $array2 = array_values($array2);
    sort($array1);
    sort($array2);
    $this->assertEquals($array1, $array2);
  }

  /**
   * Assert 2 sql strings are the same, ignoring double spaces.
   *
   * @param string $expectedSQL
   * @param string $actualSQL
   * @param string $message
   */
  protected function assertLike(string $expectedSQL, string $actualSQL, string $message = 'different sql'): void {
    // Normalize whitespace around brackets
    $expected = str_replace(['(', ')'], [' ( ', ' ) '], $expectedSQL);
    $actual = str_replace(['(', ')'], [' ( ', ' ) '], $actualSQL);
    // Normalize all whitespace
    $expected = trim(preg_replace('/\s+/', ' ', $expected));
    $actual = trim(preg_replace('/\s+/', ' ', $actual));
    $this->assertEquals($expected, $actual, $message);
  }

}
