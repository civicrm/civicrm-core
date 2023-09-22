<?php

namespace Civi\Test;

/**
 * The "Invasive"  helper makes it a bit easier to write unit-tests which
 * touch upon private or protected members.
 *
 * @package Civi\Test
 */
class Invasive {

  /**
   * Call a private/protected method.
   *
   * This is only intended for unit-testing.
   *
   * @param array $callable
   *   Ex: [$myObject, 'myPrivateMethod']
   *   Ex: ['MyClass', 'myPrivateStaticMethod']
   * @param array $args
   *   Ordered list of arguments.
   * @return mixed
   */
  public static function call($callable, $args = []) {
    list ($class, $object, $member) = self::parseRef($callable);
    $reflection = new \ReflectionMethod($class, $member);
    $reflection->setAccessible(TRUE);
    return $reflection->invokeArgs($object, $args);
  }

  /**
   * Get the content of a private/protected method.
   *
   * This is only intended for unit-testing.
   *
   * @param array $ref
   *   A reference to class+property.
   *   Ex: [$myObject, 'myPrivateField']
   *   Ex: ['MyClass', 'myPrivateStaticField']
   * @return mixed
   */
  public static function get($ref) {
    list ($class, $object, $member) = self::parseRef($ref);
    $reflection = new \ReflectionProperty($class, $member);
    $reflection->setAccessible(TRUE);
    return $reflection->getValue($object);
  }

  /**
   * Get the content of a private/protected method.
   *
   * This is only intended for unit-testing.
   *
   * @param array $ref
   *   A reference to class+property.
   *   Ex: [$myObject, 'myPrivateField']
   *   Ex: ['MyClass', 'myPrivateStaticField']
   * @param mixed $value
   * @return mixed
   */
  public static function set($ref, $value) {
    list ($class, $object, $member) = self::parseRef($ref);
    $reflection = new \ReflectionProperty($class, $member);
    $reflection->setAccessible(TRUE);
    $reflection->setValue($object, $value);
  }

  /**
   * @param array $callable
   *   Ex: [$myObject, 'myPrivateMember']
   *   Ex: ['MyClass', 'myPrivateStaticMember']
   * @return array
   *   Ordered array of [string $class, object? $object, string $memberName].
   */
  private static function parseRef($callable) {
    if (is_string($callable)) {
      list ($class, $member) = explode('::', $callable);
      return [$class, NULL, $member];
    }
    elseif (is_string($callable[0])) {
      return [$callable[0], NULL, $callable[1]];
    }
    elseif (is_object($callable[0])) {
      return [get_class($callable[0]), $callable[0], $callable[1]];
    }
    else {
      throw new \RuntimeException("Cannot parse reference to private member");
    }
  }

}
