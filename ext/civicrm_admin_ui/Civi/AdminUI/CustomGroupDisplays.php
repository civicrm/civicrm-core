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

use Civi\Core\Attribute\CiviEventListener;
use Civi\Core\Event\GenericHookEvent;
use Civi\Core\Event\PostEvent;
use CRM_CivicrmAdminUi_ExtensionUtil as E;

/**
 * Add SearchKit/FormBuilder displays for CustomGroups
 *
 * @service
 * @internal
 * @see \Civi\Api4\Action\CustomGroup\GetSearchKit
 */
class CustomGroupDisplays extends \Civi\Core\Service\AutoService {

  #[CiviEventListener(event: 'hook_civicrm_managed', priority: \Civi\API\Events::W_EARLY)]
  public function registerSearchKits(GenericHookEvent $e): void {
    if ($e->modules && !in_array(E::LONG_NAME, $e->modules, TRUE)) {
      return;
    }

    // for now we only fetch for Groups that have a Tab
    $declarationsByGroup = \Civi\Api4\CustomGroup::getSearchKit(FALSE)
      ->addWhere('is_active', '=', TRUE)
      ->addWhere('is_multiple', '=', TRUE)
      ->addWhere('style', 'IN', ['Tab', 'Tab with table'])
      ->execute()
      ->column('managed');

    foreach ($declarationsByGroup as $declarations) {
      array_push($e->entities, ...$declarations);
    }
  }

  #[CiviEventListener(event: 'hook_civicrm_post::CustomGroup')]
  public function onGroupChange(PostEvent $e): void {
    $groupName = $e->object->name;

    // TODO: why is $e->object->name sometimes NULL?
    if (!$groupName) {
      $groupName = \Civi\Api4\CustomGroup::get(FALSE)
        ->addWhere('id', '=', $e->id)
        ->addSelect('name')
        ->execute()
        ->first()['name'] ?? NULL;
      if (!$groupName) {
        // we need a name to reconcile. if some reason we cant
        // find one, give up
        return;
      }
    }
    $this->reconcileGroup($groupName);
  }

  protected function reconcileGroup(string $groupName): void {
    $declarations = \Civi\Api4\CustomGroup::getSearchKit(FALSE)
      ->addWhere('name', '=', $groupName)
      ->addWhere('is_active', '=', TRUE)
      ->addWhere('is_multiple', '=', TRUE)
      ->addWhere('style', 'IN', ['Tab', 'Tab with table'])
      ->execute()
      ->first()['managed'] ?? NULL;

    if (!$declarations) {
      // if this group wasn't a multi / tab group then it never had any search kits
      // in which case nothing to do
      // BUT if this group was deactivated or deleted there may be search kits to clean up
      // - delete the saved search, any linked saved searches should be deleted by cascade
      // - we need to also delete the managed records, to prevent this being treated as
      //   a deliberate deletion (which prevents recreation if the group is re-enabled)
      // - don't delete if the user has mode local edits so we dont destroy these
      //   (the search might be a bit broken if the group has been deleted - but this
      //   is not uncommon with searchkits)
      \Civi\Api4\Managed::delete(FALSE)
        ->addWhere('module', '=', E::LONG_NAME)
        ->addWhere('name', 'IN', ["SavedSearch_Custom_{$groupName}_Search", "SavedSearch_Custom_{$groupName}_Search_SearchDisplay_Custom_{$groupName}_Tab"])
        ->execute();
      \Civi\Api4\SavedSearch::delete(FALSE)
        ->addWhere('name', '=', "Custom_{$groupName}_Search")
        ->addWhere('base_module', '=', E::LONG_NAME)
        ->addWhere('local_modified_date', 'IS EMPTY')
        ->execute();
      return;
    }

    \CRM_Core_ManagedEntities::singleton()->reconcileDeclarations($declarations);
  }

  #[CiviEventListener(event: 'hook_civicrm_post::CustomField')]
  public function onFieldChange(PostEvent $e): void {
    // NOTE: group should still exist even if deleting field
    $groupId = $e->object->custom_group_id;
    $groupName = \Civi\Api4\CustomGroup::get(FALSE)
      ->addWhere('id', '=', $groupId)
      ->addSelect('name')
      ->execute()
      ->first()['name'] ?? NULL;
    $this->reconcileGroup($groupName);
  }

  /**
   * @param \Civi\Core\Event\GenericHookEvent $event
   */
  #[CiviEventListener(event: 'civi.api4.entityTypes', priority: \Civi\API\Events::W_LATE)]
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
