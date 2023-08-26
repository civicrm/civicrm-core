<?php

namespace Civi\PhpStorm;

use Civi\Core\CiviEventDispatcher;
use Civi\Core\CiviEventDispatcherInterface;
use Civi\Core\CiviEventInspector;
use Civi\Core\Service\AutoService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @service civi.phpstorm.event
 */
class EventGenerator extends AutoService implements EventSubscriberInterface {

  public static function getSubscribedEvents() {
    return [
      'civi.phpstorm.flush' => 'generate',
    ];
  }

  public function generate() {
    $inspector = new CiviEventInspector();

    $builder = new PhpStormMetadata('events', __CLASS__);
    $builder->registerArgumentsSet('events', ...array_keys($inspector->getAll()));

    foreach ([CiviEventDispatcher::class, CiviEventDispatcherInterface::class] as $class) {
      foreach (['dispatch', 'addListener', 'removeListener', 'getListeners', 'hasListeners'] as $method) {
        $builder->addExpectedArguments(sprintf("\\%s::%s()", $class, $method), 0, 'events');
      }
    }

    $builder->write();
  }

}
