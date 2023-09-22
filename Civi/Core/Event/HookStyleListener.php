<?php

namespace Civi\Core\Event;

/**
 * This is an adapter which allows you to attach hook-style functions directly to the dispatcher.
 * Example:
 *
 * ```php
 * function listen_to_hook_foo($arg1, &$arg2, $arg3) { ... }
 * Civi::dispatcher()->addListener('hook_civicrm_foo', new HookStyleListener('listen_to_hook_foo'));
 * ```
 *
 * @package Civi\Core\Event
 */
class HookStyleListener {

  /**
   * @var array
   *   Ex: ['SomeClass', 'someMethod']
   */
  private $callback = NULL;

  /**
   * @param array|callable $callback
   *   Ex: ['SomeClass', 'someMethod']
   */
  public function __construct($callback) {
    $this->callback = $callback;
  }

  public function __invoke(GenericHookEvent $e) {
    $result = call_user_func_array($this->callback, $e->getHookValues());
    $e->addReturnValues($result);
  }

  public function __toString(): string {
    $name = EventPrinter::formatName($this->callback);
    return preg_replace('/\(\$?e?\)$/', '(&...)', $name);
  }

}
