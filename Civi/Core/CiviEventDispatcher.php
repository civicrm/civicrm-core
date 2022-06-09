<?php

namespace Civi\Core;

use Civi\Core\Event\HookStyleListener;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class CiviEventDispatcher
 * @package Civi\Core
 *
 * The CiviEventDispatcher is a Symfony dispatcher. Additionally, if an event
 * follows the naming convention of "hook_*", then it will also be dispatched
 * through CRM_Utils_Hook::invoke().
 *
 * @see \CRM_Utils_Hook
 */
class CiviEventDispatcher extends EventDispatcher {

  const DEFAULT_HOOK_PRIORITY = -100;

  /**
   * Track the list of hook-events for which we have autoregistered
   * the hook adapter.
   *
   * @var array
   *   Array(string $eventName => trueish).
   */
  private $autoListeners = [];

  /**
   * A list of dispatch-policies (based on an exact-match to the event name).
   *
   * Note: $dispatchPolicyExact and $dispatchPolicyRegex should coexist; e.g.
   * if one is NULL, then both are NULL. If one is an array, then both are arrays.
   *
   * @var array|null
   *   Array(string $eventName => string $action)
   */
  private $dispatchPolicyExact = NULL;

  /**
   * A list of dispatch-policies (based on an regex-match to the event name).
   *
   * Note: $dispatchPolicyExact and $dispatchPolicyRegex should coexist; e.g.
   * if one is NULL, then both are NULL. If one is an array, then both are arrays.
   *
   * @var array|null
   *   Array(string $eventRegex => string $action)
   */
  private $dispatchPolicyRegex = NULL;

  /**
   * Determine whether $eventName should delegate to the CMS hook system.
   *
   * @param string $eventName
   *   Ex: 'civi.token.eval', 'hook_civicrm_post`.
   * @return bool
   */
  protected function isHookEvent($eventName) {
    return (substr($eventName, 0, 5) === 'hook_') && (strpos($eventName, '::') === FALSE);
  }

  /**
   * Adds a series of event listeners from a subscriber object.
   *
   * This is particularly useful if you want to register the subscriber without
   * materializing the subscriber object.
   *
   * @param string $subscriber
   *   Service ID of the subscriber.
   * @param array $events
   *   List of events/methods/priorities.
   * @see \Symfony\Component\EventDispatcher\EventSubscriberInterface::getSubscribedEvents()
   */
  public function addSubscriberServiceMap(string $subscriber, array $events) {
    foreach ($events as $eventName => $params) {
      if (\is_string($params)) {
        $this->addListenerService($eventName, [$subscriber, $params]);
      }
      elseif (\is_string($params[0])) {
        $this->addListenerService($eventName, [$subscriber, $params[0]], isset($params[1]) ? $params[1] : 0);
      }
      else {
        foreach ($params as $listener) {
          $this->addListenerService($eventName, [$subscriber, $listener[0]], isset($listener[1]) ? $listener[1] : 0);
        }
      }
    }
  }

  /**
   * Add a test listener.
   *
   * @param string $eventName
   *   Ex: 'civi.internal.event'
   *   Ex: 'hook_civicrm_publicEvent'
   *   Ex: '&hook_civicrm_publicEvent' (an alias for 'hook_civicrm_publicEvent' in which the listener abides hook-style ordered parameters).
   *        This notation is handy when attaching via listener-maps (e.g. `getSubscribedEvents()`).
   * @param callable $listener
   * @param int $priority
   */
  public function addListener($eventName, $listener, $priority = 0) {
    if ($eventName[0] === '&') {
      $eventName = substr($eventName, 1);
      $listener = new HookStyleListener($listener);
    }
    parent::addListener($eventName, $listener, $priority);
  }

  /**
   * Adds a series of event listeners from methods in a class.
   *
   * @param string|object $target
   *   The object/class which will receive the notifications.
   *   Use a string (class-name) if the listeners are static methods.
   *   Use an object-instance if the listeners are regular methods.
   * @param array $events
   *   List of events/methods/priorities.
   * @see \Symfony\Component\EventDispatcher\EventSubscriberInterface::getSubscribedEvents()
   */
  public function addListenerMap($target, array $events) {
    foreach ($events as $eventName => $params) {
      if (\is_string($params)) {
        $this->addListener($eventName, [$target, $params]);
      }
      elseif (\is_string($params[0])) {
        $this->addListener($eventName, [$target, $params[0]], isset($params[1]) ? $params[1] : 0);
      }
      else {
        foreach ($params as $listener) {
          $this->addListener($eventName, [$target, $listener[0]], isset($listener[1]) ? $listener[1] : 0);
        }
      }
    }
  }

  /**
   * Adds a service as event listener.
   *
   * This provides partial backwards compatibility with ContainerAwareEventDispatcher.
   *
   * @param string $eventName Event for which the listener is added
   * @param array $callback The service ID of the listener service & the method
   *                        name that has to be called
   * @param int $priority The higher this value, the earlier an event listener
   *                      will be triggered in the chain.
   *                      Defaults to 0.
   *
   * @throws \InvalidArgumentException
   */
  public function addListenerService($eventName, $callback, $priority = 0) {
    if (!\is_array($callback) || 2 !== \count($callback)) {
      throw new \InvalidArgumentException('Expected an array("service", "method") argument');
    }

    $this->addListener($eventName, new \Civi\Core\Event\ServiceListener($callback), $priority);
  }

  /**
   * @inheritDoc
   */
  public function dispatch($eventName, Event $event = NULL) {
    // Dispatch policies add systemic overhead and (normally) should not be evaluated. JNZ.
    if ($this->dispatchPolicyRegex !== NULL) {
      switch ($mode = $this->checkDispatchPolicy($eventName)) {
        case 'run':
          // Continue on the normal execution.
          break;

        case 'drop':
          // Quietly ignore the event.
          return $event;

        case 'warn':
          // Run the event, but complain about it.
          error_log("Unexpectedly dispatching event \"$eventName\".");
          break;

        case 'warn-drop':
          // Ignore the event, but complaint about it.
          error_log("Unexpectedly dispatching event \"$eventName\".");
          return $event;

        case 'fail':
          throw new \RuntimeException("The dispatch policy prohibits event \"$eventName\".");

        case 'not-ready':
          // The system is not ready to run hooks -- eg it has not finished loading the extension main-files.
          // If you fire a hook at this point, it will not be received by the intended listeners.
          // In practice, many hooks involve cached data-structures, so a premature hook is liable to have spooky side-effects.
          // This condition indicates a structural problem and merits a consistent failure-mode.
          // If you believe some special case merits an exemption, then you could add it to `$bootDispatchPolicy`.

          // An `Exception` would be ideal for preventing new bugs, but it can be too noisy for systems with pre-existing bugs.
          // throw new \RuntimeException("The event \"$eventName\" attempted to fire before CiviCRM was fully loaded. Skipping.");
          // Complain to web-user and sysadmin. Log a backtrace. We're pre-boot, so don't use high-level services.
          error_log("The event \"$eventName\" attempted to fire before CiviCRM was fully loaded. Skipping.\n" . \CRM_Core_Error::formatBacktrace(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), FALSE));
          trigger_error("The event \"$eventName\" attempted to fire before CiviCRM was fully loaded. Skipping.", E_USER_WARNING);
          return $event;

        default:
          throw new \RuntimeException("The dispatch policy for \"$eventName\" is unrecognized ($mode).");

      }
    }
    $this->bindPatterns($eventName);
    return parent::dispatch($eventName, $event);
  }

  /**
   * @inheritDoc
   */
  public function getListeners($eventName = NULL) {
    $this->bindPatterns($eventName);
    return parent::getListeners($eventName);
  }

  /**
   * @inheritDoc
   */
  public function hasListeners($eventName = NULL) {
    // All hook_* events have default listeners, so hasListeners(NULL) is a truism.
    return ($eventName === NULL || $this->isHookEvent($eventName))
      ? TRUE : parent::hasListeners($eventName);
  }

  /**
   * Invoke hooks using an event object.
   *
   * @param \Civi\Core\Event\GenericHookEvent $event
   * @param string $eventName
   *   Ex: 'hook_civicrm_dashboard'.
   */
  public static function delegateToUF($event, $eventName) {
    $hookName = substr($eventName, 5);
    $hooks = \CRM_Utils_Hook::singleton();
    $params = $event->getHookValues();
    $count = count($params);

    switch ($count) {
      case 0:
        $fResult = $hooks->invokeViaUF($count, \CRM_Utils_Hook::$_nullObject, \CRM_Utils_Hook::$_nullObject, \CRM_Utils_Hook::$_nullObject, \CRM_Utils_Hook::$_nullObject, \CRM_Utils_Hook::$_nullObject, \CRM_Utils_Hook::$_nullObject, $hookName);
        break;

      case 1:
        $fResult = $hooks->invokeViaUF($count, $params[0], \CRM_Utils_Hook::$_nullObject, \CRM_Utils_Hook::$_nullObject, \CRM_Utils_Hook::$_nullObject, \CRM_Utils_Hook::$_nullObject, \CRM_Utils_Hook::$_nullObject, $hookName);
        break;

      case 2:
        $fResult = $hooks->invokeViaUF($count, $params[0], $params[1], \CRM_Utils_Hook::$_nullObject, \CRM_Utils_Hook::$_nullObject, \CRM_Utils_Hook::$_nullObject, \CRM_Utils_Hook::$_nullObject, $hookName);
        break;

      case 3:
        $fResult = $hooks->invokeViaUF($count, $params[0], $params[1], $params[2], \CRM_Utils_Hook::$_nullObject, \CRM_Utils_Hook::$_nullObject, \CRM_Utils_Hook::$_nullObject, $hookName);
        break;

      case 4:
        $fResult = $hooks->invokeViaUF($count, $params[0], $params[1], $params[2], $params[3], \CRM_Utils_Hook::$_nullObject, \CRM_Utils_Hook::$_nullObject, $hookName);
        break;

      case 5:
        $fResult = $hooks->invokeViaUF($count, $params[0], $params[1], $params[2], $params[3], $params[4], \CRM_Utils_Hook::$_nullObject, $hookName);
        break;

      case 6:
        $fResult = $hooks->invokeViaUF($count, $params[0], $params[1], $params[2], $params[3], $params[4], $params[5], $hookName);
        break;

      default:
        throw new \RuntimeException("hook_{$hookName} cannot support more than 6 parameters");
    }

    $event->addReturnValues($fResult);
  }

  /**
   * Attach any pattern-based listeners which may be interested in $eventName.
   *
   * @param string $eventName
   *   Ex: 'civi.api.resolve' or 'hook_civicrm_dashboard'.
   */
  protected function bindPatterns($eventName) {
    if ($eventName !== NULL && !isset($this->autoListeners[$eventName])) {
      $this->autoListeners[$eventName] = 1;
      if ($this->isHookEvent($eventName)) {
        // WISHLIST: For native extensions (and possibly D6/D7/D8/BD), enumerate
        // the listeners and list them one-by-one. This would make it easier to
        // inspect via "cv debug:event-dispatcher".
        $this->addListener($eventName, [
          '\Civi\Core\CiviEventDispatcher',
          'delegateToUF',
        ], self::DEFAULT_HOOK_PRIORITY);
      }
    }
  }

  /**
   * Set the dispatch policy. This allows you to filter certain events.
   * This can be useful during upgrades or debugging.
   *
   * Enforcement will add systemic overhead, so this should normally be NULL.
   *
   * @param array|null $dispatchPolicy
   *   Each key is either the string-literal name of an event, or a regex delimited by '/'.
   *   Each value is one of: 'run', 'drop', 'warn', 'fail'.
   *   Exact name matches take precedence over regexes. Regexes are evaluated in order.
   *
   *   Ex: ['hook_civicrm_pre' => 'fail']
   *   Ex: ['/^hook_/' => 'warn']
   *
   * @return static
   */
  public function setDispatchPolicy($dispatchPolicy) {
    if (is_array($dispatchPolicy)) {
      // Split $dispatchPolicy in two (exact rules vs regex rules).
      $this->dispatchPolicyExact = [];
      $this->dispatchPolicyRegex = [];
      foreach ($dispatchPolicy as $pattern => $action) {
        if ($pattern[0] === '/') {
          $this->dispatchPolicyRegex[$pattern] = $action;
        }
        else {
          $this->dispatchPolicyExact[$pattern] = $action;
        }
      }
    }
    else {
      $this->dispatchPolicyExact = NULL;
      $this->dispatchPolicyRegex = NULL;
    }

    return $this;
  }

  //  /**
  //   * @return array|NULL
  //   */
  //  public function getDispatchPolicy() {
  //    return  $this->dispatchPolicyRegex === NULL ? NULL : array_merge($this->dispatchPolicyExact, $this->dispatchPolicyRegex);
  //  }

  /**
   * Determine whether the dispatch policy applies to a given event.
   *
   * @param string $eventName
   *   Ex: 'civi.api.resolve' or 'hook_civicrm_dashboard'.
   * @return string
   *   Ex: 'run', 'drop', 'fail'
   */
  protected function checkDispatchPolicy($eventName) {
    if (isset($this->dispatchPolicyExact[$eventName])) {
      return $this->dispatchPolicyExact[$eventName];
    }
    foreach ($this->dispatchPolicyRegex as $eventPat => $action) {
      if ($eventPat[0] === '/' && preg_match($eventPat, $eventName)) {
        return $action;
      }
    }
    return 'fail';
  }

}
