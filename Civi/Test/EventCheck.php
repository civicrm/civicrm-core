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

/**
 * An EventCheck is a fragment of a unit-test -- it is mixed into
 * various test-scenarios and applies extra assertions.
 */
class EventCheck {

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
   * Assert that a variable has a given type.
   *
   * @param string|string[] $types
   *   List of types, per `gettype()` or `get_class()`
   *   Ex: 'int|string|NULL'
   *   Ex: [`array`, `NULL`, `CRM_Core_DAO`]
   * @param mixed $value
   *   The variable to check
   * @param string|null $message
   * @see \CRM_Utils_Type::validatePhpType
   */
  public function assertType($types, $value, string $message = '') {
    if (!\CRM_Utils_Type::validatePhpType($value, $types, FALSE)) {
      $defactoType = is_object($value) ? get_class($value) : gettype($value);
      $types = is_array($types) ? implode('|', $types) : $types;
      $comment = sprintf('Expected one of (%s) but found %s', $types, $defactoType);
      static::fail(trim("$message\n$comment"));
    }
  }

  public static function assertEquals($expected, $actual, string $message = ''): void {
    if ($expected !== $actual) {
      $comment = sprintf('Expected value %s and actual value %s should match.', json_encode($expected), json_encode($actual));
      static::fail(trim("$message\n$comment"));
    }
  }

  public static function assertContains($needle, iterable $haystack, string $message = ''): void {
    $haystack = ($haystack instanceof \Traversable) ? iterator_to_array($haystack) : (array) $haystack;
    if (!in_array($needle, $haystack)) {
      $comment = sprintf('Item %s not found in list: %s', json_encode($needle), json_encode($haystack));
      static::fail(trim("$message\n$comment"));
    }
  }

  public static function assertMatchesRegularExpression(string $pattern, string $string, string $message = ''): void {
    if (!preg_match($pattern, $string)) {
      $comment = sprintf('Value %s does not match pattern %s.', json_encode($string), json_encode($pattern));
      static::fail(trim("$message\n$comment"));
    }
  }

  public static function assertNotEmpty($actual, string $message = ''): void {
    if (empty($actual)) {
      $comment = 'Value should not be empty.';
      static::fail(trim("$message\n$comment"));
    }
  }

  public static function assertTrue($condition, string $message = ''): void {
    if ($condition !== TRUE) {
      $comment = 'Value should be TRUE.';
      static::fail(trim("$message\n$comment"));
    }
  }

  public static function fail(string $message = ''): void {
    throw new EventCheckException($message);
  }

}
