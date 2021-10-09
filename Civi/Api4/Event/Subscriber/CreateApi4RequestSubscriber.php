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
use Civi\Api4\Utils\CoreUtil;
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
    // Multi-record custom data entities
    if (strpos($event->entityName, 'Custom_') === 0) {
      $groupName = substr($event->entityName, 7);
      if (CoreUtil::isCustomEntity($groupName)) {
        $event->className = 'Civi\Api4\CustomValue';
        $event->args = [$groupName];
      }
    }
    else {
      // Because "Case" is a reserved php keyword
      $className = 'Civi\Api4\\' . ($event->entityName === 'Case' ? 'CiviCase' : $event->entityName);
      if (class_exists($className)) {
        $event->className = $className;
      }
    }
  }

}
