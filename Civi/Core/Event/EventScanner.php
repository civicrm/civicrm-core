<?php

namespace Civi\Core\Event;

/**
 * The `EventScanner` is a utility for scanning a class to see if it has any event-listeners. It may check
 * for common interfaces and conventions. Example:
 *
 * ```
 * $map = EventScanner::findListeners($someObject);
 * $dispatcher->addListenerMap($someObject, $map);
 * ```
 */
class EventScanner {

  /**
   * In-memory cache of the listener-maps found on various classes.
   *
   * This cache is a little unusual -- it is geared toward improving unit-tests. Bear in mind:
   *
   * - The `EventScanner` is fundamentally scanning class-structure.
   * - Within a given PHP process, the class-structure cannot change. Therefore, the cached view in `static::$listenerMaps` cannot be stale.
   * - There are three kinds of PHP processes:
   *    1. System-flushes -- During this operation, we rebuild the `Container`. This may do some scanning, and the results will be recorded in `Container`.
   *    2. Ordinary page-loads -- We use the `Container` cache. It shouldn't need any more scans.
   *    3. Headless unit-tests -- For these, we must frequently tear-down and rebuild a fresh `Container`, often with varying decisions about
   *       which extensions/services/classes to activate. The container-cache does not operate.
   *
   * Here's how `$listenerMaps` plays out in each:
   *
   * 1. The `$listenerMaps` is not needed or used.
   * 2. The `$listenerMaps` (and `EventScanner` generally) is not needed or used.
   * 3. The `$listenerMaps` is used frequently, preventing redundant scanning.
   *
   * A more common approach would be to use `Civi::$statics` or `Civi::cache()`. These would be inappropriate because we want the data to be
   * preserved across multiple test-runs -- and because the underlying data (PHP class-structure) does not change within a unit-test.
   *
   * @var array
   *   Ex: ['api_v3_SyntaxConformanceTest' => [...listener-names...]]
   */
  private static $listenerMaps = [];

  /**
   * Scan an object or class for event listeners.
   *
   * Note: This requires scanning. Consequently, it should not be run in bulk on a regular (runtime) basis. Instead, store
   * the listener-maps in a cache (e.g. `Container`).
   *
   * @param string|object $target
   *   The object/class which will receive the notifications.
   *   Use a string (class-name) if the listeners are static methods.
   *   Use an object-instance if the listeners are regular methods.
   * @param string|null $self
   *   If the target $class is focused on a specific entity/form/etc, use the `$self` parameter to specify it.
   *   This will activate support for `self_{$event}` methods.
   *   Ex: if '$self' is 'Contact', then 'function self_hook_civicrm_pre()' maps to 'on_hook_civicrm_pre::Contact'.
   * @return array
   *   List of events/listeners. Format is compatible with 'getSubscribedEvents()'.
   *   Ex: ['some.event' => [['firstFunc'], ['secondFunc']]
   */
  public static function findListeners($target, $self = NULL): array {
    $class = is_object($target) ? get_class($target) : $target;
    $key = "$class::" . ($self ?: '');
    if (isset(self::$listenerMaps[$key])) {
      return self::$listenerMaps[$key];
    }

    $listenerMap = [];
    // These 2 interfaces do the same thing; one is meant for unit tests and the other for runtime code
    if (is_subclass_of($class, '\Civi\Core\HookInterface')) {
      $listenerMap = static::mergeListenerMap($listenerMap, static::findFunctionListeners($class, $self));
    }
    if (is_subclass_of($class, '\Symfony\Component\EventDispatcher\EventSubscriberInterface')) {
      $listenerMap = static::mergeListenerMap($listenerMap, static::normalizeListenerMap($class::getSubscribedEvents()));
    }

    if (CIVICRM_UF === 'UnitTests') {
      self::$listenerMaps[$key] = $listenerMap;
    }
    return $listenerMap;
  }

  /**
   * @param string $class
   * @param string|null $self
   *   If the target $class is focused on a specific entity/form/etc, use the `$self` parameter to specify it.
   *   This will activate support for `self_{$event}` methods.
   *   Ex: if '$self' is 'Contact', then 'function self_hook_civicrm_pre()' maps to 'hook_civicrm_pre::Contact'.
   * @return array
   */
  protected static function findFunctionListeners(string $class, $self = NULL): array {
    $listenerMap = [];

    /**
     * @param string $underscore
     *   Ex: 'civi_foo_bar', 'hook_civicrm_foo'
     * @return string
     *   Ex: 'civi.foo.bar', 'hook_civicrm_foo'
     */
    $toEventName = function ($underscore) {
      if (substr($underscore, 0, 5) === 'hook_') {
        return $underscore;
      }
      else {
        return str_replace('_', '.', $underscore);
      }
    };

    $addListener = function ($event, $func, $priority = 0) use (&$listenerMap) {
      $listenerMap[$event][] = [$func, $priority];
    };

    foreach (get_class_methods($class) as $func) {
      if (preg_match('/^(hook_|on_|self_)/', $func, $m)) {
        switch ($m[1]) {
          case 'hook_':
            $addListener('&' . $func, $func);
            break;

          case 'on_':
            $addListener($toEventName(substr($func, 3)), $func);
            break;

          case 'self_':
            if ($self === NULL) {
              throw new \RuntimeException("Cannot add self_*() listeners for $class");
            }
            $addListener($toEventName(substr($func, 5)) . '::' . $self, $func);
            break;
        }
      }
    }

    return $listenerMap;
  }

  /**
   * Convert the listeners to a standard flavor.
   *
   * @param iterable $listenerMap
   *   List of events/listeners. Listeners may be given in singular or plural form.
   *   Ex: ['some.event' => 'oneListener']
   *   Ex: ['some.event' => ['oneListener', 100]]
   *   Ex: ['some.event' => [['firstListener', 100], ['secondListener']]]
   * @return array
   *   List of events/listeners. All listeners are described in plural form.
   *   Ex: ['some.event' => [['firstListener', 100], ['secondListener']]]
   */
  protected static function normalizeListenerMap(iterable $listenerMap): array {
    $r = [];
    foreach ($listenerMap as $eventName => $params) {
      $r[$eventName] = [];
      if (\is_string($params)) {
        $r[$eventName][] = [$params];
      }
      elseif (\is_string($params[0])) {
        $r[$eventName][] = $params;
      }
      else {
        $r[$eventName] = array_merge($r[$eventName], $params);
      }
    }
    return $r;
  }

  protected static function mergeListenerMap(array $left, array $right): array {
    if ($left === []) {
      return $right;
    }
    foreach ($right as $eventName => $listeners) {
      $left[$eventName] = array_merge($left[$eventName] ?? [], $listeners);
    }
    return $left;
  }

}
