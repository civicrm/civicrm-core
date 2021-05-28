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

namespace Civi\Test;

use PHPUnit\Framework\Assert;

/**
 * An EventCheck is a fragment of a unit-test -- it is mixed into
 * various test-scenarios and applies extra assertions.
 */
class EventCheck extends Assert {

  /**
   * @var \PHPUnit\Framework\Test
   */
  private $test;

  /**
   * Determine whether this check should be used during the current test.
   *
   * @param \PHPUnit\Framework\Test|NULL $test
   *
   * @return bool|string
   *   FALSE: The check will be completely skipped.
   *   TRUE: The check will be enabled. However, if the events never
   *         execute, that is OK. Useful for general compliance-testing.
   */
  public function isSupported($test) {
    return TRUE;
  }

  /**
   * @return \PHPUnit\Framework\Test|NULL
   */
  public function getTest() {
    return $this->test;
  }

  /**
   * @param \PHPUnit\Framework\Test|NULL $test
   */
  public function setTest($test): void {
    $this->test = $test;
  }

  /**
   * Assert that a variable has a given type.
   *
   * @param string|string[] $types
   *   List of types, per `gettype()` or `get_class()`
   *   Ex: [`array`, `NULL`, `CRM_Core_DAO`]
   * @param mixed $var
   *   The variable to check
   * @param string|NULL $msg
   */
  public function assertType($types, $var, $msg = NULL) {
    $types = (array) $types;
    if (in_array(gettype($var), $types)) {
      return;
    }
    if (is_object($var)) {
      foreach ($types as $type) {
        if ($var instanceof $type) {
          return;
        }
      }
    }
    $defactoType = is_object($var) ? get_class($var) : gettype($var);
    $this->fail(sprintf("Expected one of (%s) but found %s\n%s", implode(' ', $types), $defactoType, $msg));
  }

  public function setUp() {
  }

  public function tearDown() {
  }

}
