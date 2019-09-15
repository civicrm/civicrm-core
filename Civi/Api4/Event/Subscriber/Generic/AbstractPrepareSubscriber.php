<?php

namespace Civi\Api4\Event\Subscriber\Generic;

use Civi\API\Event\PrepareEvent;
use Civi\API\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

abstract class AbstractPrepareSubscriber implements EventSubscriberInterface {

  /**
   * @return array
   */
  public static function getSubscribedEvents() {
    return [
      Events::PREPARE => 'onApiPrepare',
    ];
  }

  /**
   * @param \Civi\API\Event\PrepareEvent $event
   */
  abstract public function onApiPrepare(PrepareEvent $event);

}
