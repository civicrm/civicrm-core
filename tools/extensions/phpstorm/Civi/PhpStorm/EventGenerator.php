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

    $entities = \Civi\Api4\Entity::get(FALSE)->addSelect('name')->execute()->column('name');
    $specialEvents = ['hook_civicrm_post', 'hook_civicrm_pre', 'civi.api4.validate'];
    foreach ($entities as $entity) {
      foreach ($specialEvents as $specialEvent) {
        $entityEvents [] = "$specialEvent::$entity";
      }
    }
    // PHP 7.4 can simplify:
    // $entityEvents = array_map(fn($pair) => implode('::', $pair), \CRM_Utils_Array::product([$entities, $specialEvents]));


    $all = array_merge(array_keys($inspector->getAll()), $entityEvents);

    $builder = new PhpStormMetadata('events', __CLASS__);
    $builder->registerArgumentsSet('events', ...$all);

    foreach ([CiviEventDispatcher::class, CiviEventDispatcherInterface::class] as $class) {
      foreach (['dispatch', 'addListener', 'removeListener', 'getListeners', 'hasListeners'] as $method) {
        $builder->addExpectedArguments(sprintf("\\%s::%s()", $class, $method), 0, 'events');
      }
    }

    $builder->write();
  }

}
