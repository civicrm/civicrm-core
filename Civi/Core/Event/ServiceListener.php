<?php

namespace Civi\Core\Event;

/**
 * A ServiceListener is a `callable` (supporting "__invoke()") which references
 * a method of a service-object.
 *
 * The following two callables are conceptually similar:
 *
 *   (A) addListener('some.event', [Civi::service('foo'), 'doBar']);
 *   (B) addListener('some.event', new ServiceListener(['foo', 'doBar']));
 *
 * The difference is that (A) immediately instantiates the 'foo' service,
 * whereas (B) instantiates `foo` lazily. (B) is more amenable to serialization,
 * caching, etc. If you have a long-tail of many services/listeners/etc that
 * are not required for every page-load, then (B) should perform better.
 *
 * @package Civi\Core\Event
 */
class ServiceListener {

  /**
   * @var array
   *   Ex: ['service_name', 'someMethod']
   */
  protected $inertCb = NULL;

  /**
   * @var array|null
   *   Ex: [$svcObj, 'someMethod']
   */
  protected $liveCb = NULL;

  /**
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected $container = NULL;

  /**
   * @param array $callback
   *   Ex: ['service_name', 'someMethod']
   */
  public function __construct($callback) {
    $this->inertCb = $callback;
  }

  public function __invoke(...$args) {
    if ($this->liveCb === NULL) {
      $c = $this->container ?: \Civi::container();
      $this->liveCb = [$c->get($this->inertCb[0]), $this->inertCb[1]];
    }
    return call_user_func_array($this->liveCb, $args);
  }

  protected function getServiceClass(): ?string {
    $class = NULL;
    if (\Civi\Core\Container::isContainerBooted()) {
      try {
        $c = $this->container ?: \Civi::container();
        $class = $c->findDefinition($this->inertCb[0])->getClass();
      }
      catch (Throwable $t) {
      }
    }
    return $class;
  }

  public function __toString() {
    $class = $this->getServiceClass();
    if ($class) {
      return sprintf('$(%s)->%s($e) [%s]', $this->inertCb[0], $this->inertCb[1], $class);
    }
    else {
      return sprintf('\$(%s)->%s($e)', $this->inertCb[0], $this->inertCb[1]);
    }
  }

  public function __sleep() {
    return ['inertCb'];
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @return static
   */
  public function setContainer(\Symfony\Component\DependencyInjection\ContainerInterface $container) {
    $this->container = $container;
    return $this;
  }

}
