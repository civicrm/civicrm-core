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
   * @param string $expected
   *   Ex: 'array', 'object', 'int'
   * @param $actual
   *   The variable/item to check.
   * @param string $message
   */
  public function assertType($expected, $actual, $message = '') {
    return $this->assertInternalType($expected, $actual, $message);
  }

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
   * Assert that two numbers are approximately equal,
   * give or take some $tolerance.
   *
   * @param int|float $expected
   * @param int|float $actual
   * @param int|float $tolerance
   *   Any differences <$tolerance are considered irrelevant.
   *   Differences >=$tolerance are considered relevant.
   * @param string $message
   */
  public function assertApproxEquals($expected, $actual, $tolerance, $message = NULL) {
    if ($tolerance == 1 && is_int($expected) && is_int($actual)) {
      //           ^^ loose equality is on purpose
      throw new \CRM_Core_Exception('assertApproxEquals is a fractions-first thinking function and compares integers with a tolerance of 1 as if they are identical. You want a bigger number, such as 2, or 5.');
    }
    $diff = abs($actual - $expected);
    if ($message === NULL) {
      $message = sprintf("approx-equals: expected=[%.3f] actual=[%.3f] diff=[%.3f] tolerance=[%.3f]", $expected, $actual, $diff, $tolerance);
    }
    $this->assertTrue($diff < $tolerance, $message);
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
   * @param string|int $key
   * @param array $list
   */
  public function assertArrayValueNotNull($key, &$list) {
    $this->assertArrayKeyExists($key, $list);

    $value = $list[$key] ?? NULL;
    $this->assertTrue($value,
      sprintf("%s element not null?", $key)
    );
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

}
