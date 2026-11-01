<?php
namespace Civi\Core\Compiler;

use Civi\Core\Attribute\CiviEventListener;
use Civi\Core\ClassScanner;
use Civi\Core\Event\EventScanner;
use Civi\Core\HookInterface;
use Civi\Core\Service\AutoDefinition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Scan for services that have the tag 'event_subscriber', as well as BAO and standalone
 * classes that listen to events.
 *
 * Specifically, any class tagged as `event_subscriber` will be scanned for event listeners.
 * The subscriber should implement a relevant interface or attribute, such as:
 *
 * - HookInterface: The class uses `hook_*()` methods.
 * - EventSubscriberInterface: the class provides a `getSubscribedEvents()` method.
 * - CiviEventListener: the class's methods use `#[CiviEventListener]` attributes.
 *
 * Scanned classes (core, or extensions with `scan-classes`) which use `#[CiviEventListener]`,
 * as well as BAO classes using `HookInterface` or `EventSubscriberInterface`, are registered
 * automatically.
 *
 * The list of listeners will be extracted and stored as part of the container-cache.
 *
 * NOTE: This is similar to Symfony's `RegisterListenersPass()` but differs in a few ways:
 *   - Works with HookInterface, EventSubscriberInterface, and CiviEventListener
 *   - Watches tag 'event_subscriber' (not 'kernel.event_listener' or 'kernel.event_subscriber')
 */
class EventScannerPass implements CompilerPassInterface {

  public function process(ContainerBuilder $container): void {
    $this->registerScannedClasses($container);

    $dispatcher = $container->getDefinition('dispatcher');
    $subscribers = $container->findTaggedServiceIds('event_subscriber');

    foreach (array_keys($subscribers) as $subscriber) {
      $listenerMap = EventScanner::findListeners($container->findDefinition($subscriber)->getClass());
      $dispatcher->addMethodCall('addSubscriberServiceMap', [$subscriber, $listenerMap]);
    }
  }

  /**
   * Register standalone and BAO classes discovered via `ClassScanner` that are not already in the container.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
   */
  private function registerScannedClasses(ContainerBuilder $container): void {
    $definedClasses = [];
    foreach ($container->getDefinitions() as $definition) {
      $definedClasses[ltrim((string) $definition->getClass(), '\\')] = TRUE;
    }

    $dispatcher = $container->getDefinition('dispatcher');
    $baoClasses = array_unique([
      ...ClassScanner::get(['interface' => HookInterface::class]),
      ...ClassScanner::get(['interface' => EventSubscriberInterface::class]),
      ...ClassScanner::get(['attribute' => CiviEventListener::class]),
    ]);
    foreach ($baoClasses as $class) {
      if (!isset($definedClasses[$class]) && is_subclass_of($class, \CRM_Core_DAO::class)) {
        $definedClasses[$class] = TRUE;
        $listenerMap = EventScanner::findListeners($class);
        if ($listenerMap) {
          $container->addObjectResource($class);
          $dispatcher->addMethodCall('addListenerMap', [$class, $listenerMap]);
        }
      }
    }

    foreach (ClassScanner::get(['attribute' => CiviEventListener::class]) as $class) {
      if (!isset($definedClasses[$class])) {
        $definedClasses[$class] = TRUE;
        $container->addObjectResource($class);
        $container->setDefinition($class, AutoDefinition::create($class));
      }
    }
  }

}
