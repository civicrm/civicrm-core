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

namespace Civi\AdminUI;

use Civi\Core\Event\GenericHookEvent;
use Civi\Core\Event\PostEvent;
use CRM_CivicrmAdminUi_ExtensionUtil as E;

/**
 * Add SearchKit/FormBuilder displays for CustomGroups
 *
 * @see \Civi\Api4\Action\CustomGroup\GetSearchKit
 */
class CustomGroupDisplays extends \Civi\Core\Service\AutoSubscriber {

  /**
   * @return array
   */
  public static function getSubscribedEvents(): array {
    return [
      'hook_civicrm_managed' => 'registerSearchKits',
      'hook_civicrm_post::CustomGroup' => 'updateSearchKits',
      'hook_civicrm_post::CustomField' => 'updateSearchKits',
      // Register links to the displays in the entity schema
      'civi.api4.entityTypes' => ['addCustomGroupLinks', \Civi\API\Events::W_LATE],
    ];
  }

  public function registerSearchKits(GenericHookEvent $e): void {
    if ($e->modules && !in_array(E::LONG_NAME, $e->modules, TRUE)) {
      return;
    }

    $records = \Civi\Api4\Action\CustomGroup\GetSearchKit::getAllManaged();

    foreach ($records as $record) {
      $record['module'] = E::LONG_NAME;
      $e->entities[] = $record;
    }
  }

  public function updateSearchKits(PostEvent $e): void {
    // TODO: more specific update to avoid cascading reconciles
    \CRM_Core_ManagedEntities::singleton()->reconcile([E::LONG_NAME]);
  }

  /**
   * @param \Civi\Core\Event\GenericHookEvent $event
   */
  public function addCustomGroupLinks(GenericHookEvent $event) {
    foreach ($event->entities as $name => $entity) {
      if (str_starts_with($name, 'Custom_')) {
        $groupName = substr($name, 7);
        $event->entities[$name]['paths']['add'] = "civicrm/af/custom/{$groupName}/create#?entity_id=[entity_id]";
        $event->entities[$name]['paths']['update'] = "civicrm/af/custom/{$groupName}/update#?Record=[id]";
        $event->entities[$name]['paths']['view'] = "civicrm/af/custom/{$groupName}/view#?Record=[id]";
      }
    }
  }

}
