<?php

namespace Civi\Core\Event;

/**
 * A hook-style service-listener is a callable with two properties:
 *
 * - The parameters are given in hook style.
 * - The callback is a method in a service-class.
 *
 * It is comparable to running:
 *
 *   Civi::service('foo')->hook_civicrm_foo($arg1, $arg2, ...);
 */
class HookStyleServiceListener extends ServiceListener {

  public function __invoke(...$args) {
    if ($this->liveCb === NULL) {
      $c = $this->container ?: \Civi::container();
      $this->liveCb = [$c->get($this->inertCb[0]), $this->inertCb[1]];
    }

    $result = call_user_func_array($this->liveCb, $args[0]->getHookValues());
    $args[0]->addReturnValues($result);
  }

  public function __toString() {
    $class = $this->getServiceClass();
    if ($class) {
      return sprintf('$(%s)->%s(...$args) [%s]', $this->inertCb[0], $this->inertCb[1], $class);
    }
    else {
      return sprintf('\$(%s)->%s(...$args)', $this->inertCb[0], $this->inertCb[1]);
    }
  }

}
