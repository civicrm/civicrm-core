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

/**
 * Register links for our new forms
 */
class CustomGroupEntityLinks extends \Civi\Core\Service\AutoSubscriber {

  /**
   * @return array
   */
  public static function getSubscribedEvents() {
    return [
      'civi.api4.entityTypes' => ['addCustomGroupLinks', \Civi\API\Events::W_LATE],
    ];
  }

  /**
   * @param \Civi\Core\Event\GenericHookEvent $event
   */
  public function addCustomGroupLinks(\Civi\Core\Event\GenericHookEvent $event) {
    foreach ($event->entities as $name => $entity) {
      if (strpos($name, 'Custom_') === 0) {
        $groupName = substr($name, 7);
        $event->entities[$name]['paths']['add'] = "civicrm/af/custom/{$groupName}/create#?entity_id=[entity_id]";
        $event->entities[$name]['paths']['update'] = "civicrm/af/custom/{$groupName}/update#?Record=[id]";
        $event->entities[$name]['paths']['view'] = "civicrm/af/custom/{$groupName}/view#?Record=[id]";
      }
    }
  }

}
