<?php
namespace Civi\Core\Compiler;

use Civi\Core\Event\EventScanner;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Scan for services that have the tag 'event_subscriber'.
 *
 * Specifically, any class tagged as `event_subscriber` will be scanned for event listeners.
 * The subscriber should implement a relevant interface, such as:
 *
 * - HookInterface: The class uses `hook_*()` methods.
 * - EventSubscriberInterface: the class provides a `getSubscribedEvents()` method.
 *
 * The list of listeners will be extracted stored as part of the container-cache.
 *
 * NOTE: This is similar to Symfony's `RegisterListenersPass()` but differs in a few ways:
 *   - Works with both HookInterface and EventSubscriberInterface
 *   - Watches tag 'event_subscriber' (not 'kernel.event_listener' or 'kernel.event_subscriber')
 */
class EventScannerPass implements CompilerPassInterface {

  public function process(ContainerBuilder $container) {
    $dispatcher = $container->getDefinition('dispatcher');
    $subscribers = $container->findTaggedServiceIds('event_subscriber');

    foreach (array_keys($subscribers) as $subscriber) {
      $listenerMap = EventScanner::findListeners($container->findDefinition($subscriber)->getClass());
      $dispatcher->addMethodCall('addSubscriberServiceMap', [$subscriber, $listenerMap]);
    }
  }

}
