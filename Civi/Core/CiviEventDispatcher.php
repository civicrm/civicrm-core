<?php

namespace Civi\Core;

use Symfony\Component\EventDispatcher\ContainerAwareEventDispatcher;
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
class CiviEventDispatcher extends ContainerAwareEventDispatcher {

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
   * @inheritDoc
   */
  public function dispatch($eventName, Event $event = NULL) {
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

}
