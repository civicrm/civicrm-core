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
   * @param $expected
   * @param $actual
   * @param string $message
   */
  public function assertType($expected, $actual, $message = '') {
    return $this->assertInternalType($expected, $actual, $message);
  }

  /**
   * Assert that two array-trees are exactly equal, notwithstanding
   * the sorting of keys
   *
   * @param array $expected
   * @param array $actual
   */
  public function assertTreeEquals($expected, $actual) {
    $e = array();
    $a = array();
    \CRM_Utils_Array::flatten($expected, $e, '', ':::');
    \CRM_Utils_Array::flatten($actual, $a, '', ':::');
    ksort($e);
    ksort($a);

    $this->assertEquals($e, $a);
  }

  /**
   * Assert that two numbers are approximately equal.
   *
   * @param int|float $expected
   * @param int|float $actual
   * @param int|float $tolerance
   * @param string $message
   */
  public function assertApproxEquals($expected, $actual, $tolerance, $message = NULL) {
    if ($message === NULL) {
      $message = sprintf("approx-equals: expected=[%.3f] actual=[%.3f] tolerance=[%.3f]", $expected, $actual, $tolerance);
    }
    $this->assertTrue(abs($actual - $expected) < $tolerance, $message);
  }

  /**
   * Assert attributes are equal.
   *
   * @param $expectedValues
   * @param $actualValues
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
   * @param $key
   * @param $list
   */
  public function assertArrayKeyExists($key, &$list) {
    $result = isset($list[$key]) ? TRUE : FALSE;
    $this->assertTrue($result, ts("%1 element exists?",
      array(1 => $key)
    ));
  }

  /**
   * @param $key
   * @param $list
   */
  public function assertArrayValueNotNull($key, &$list) {
    $this->assertArrayKeyExists($key, $list);

    $value = isset($list[$key]) ? $list[$key] : NULL;
    $this->assertTrue($value,
      ts("%1 element not null?",
        array(1 => $key)
      )
    );
  }

  /**
   * Assert the 2 arrays have the same values.
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
