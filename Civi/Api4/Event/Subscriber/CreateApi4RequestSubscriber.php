<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

namespace Civi\Api4\Event\Subscriber;

use Civi\API\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Resolve class for core and custom entities
 *
 * This runs while getting the class name for an entity / action.
 */
class CreateApi4RequestSubscriber implements EventSubscriberInterface {

  /**
   * @return array
   */
  public static function getSubscribedEvents() {
    return [
      'civi.api4.createRequest' => [
        ['onApiRequestCreate', Events::W_LATE],
      ],
    ];
  }

  /**
   * @param \Civi\Api4\Event\CreateApi4RequestEvent $event
   */
  public function onApiRequestCreate(\Civi\Api4\Event\CreateApi4RequestEvent $event) {
    // Most entities match the name of the class
    $className = 'Civi\Api4\\' . $event->entityName;
    if (class_exists($className)) {
      $event->className = $className;
      return;
    }
    // Lookup non-standard entities requiring arguments or with a mismatched classname
    $provider = \Civi::service('action_object_provider');
    $info = $provider->getEntities()[$event->entityName] ?? NULL;
    if ($info) {
      $event->className = $info['class'];
      $event->args = $info['class_args'] ?? [];
    }
  }

}
