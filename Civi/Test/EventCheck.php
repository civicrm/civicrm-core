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
   * @param \PHPUnit\Framework\Test|null $test
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
   * @param \PHPUnit\Framework\Test|null $test
   */
  public function setTest($test): void {
    $this->test = $test;
  }

  /**
   * Assert that a variable has a given type.
   *
   * @param string|string[] $types
   *   List of types, per `gettype()` or `get_class()`
   *   Ex: 'int|string|NULL'
   *   Ex: [`array`, `NULL`, `CRM_Core_DAO`]
   * @param mixed $value
   *   The variable to check
   * @param string|null $msg
   * @see \CRM_Utils_Type::validatePhpType
   */
  public function assertType($types, $value, ?string $msg = NULL) {
    if (!\CRM_Utils_Type::validatePhpType($value, $types, FALSE)) {
      $defactoType = is_object($value) ? get_class($value) : gettype($value);
      $types = is_array($types) ? implode('|', $types) : $types;
      $this->fail(sprintf("Expected one of (%s) but found %s\n%s", $types, $defactoType, $msg));
    }
  }

  public function setUp() {
  }

  public function tearDown() {
  }

}
