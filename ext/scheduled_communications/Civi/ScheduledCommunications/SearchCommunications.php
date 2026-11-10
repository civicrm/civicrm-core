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

namespace Civi\ScheduledCommunications;

use Civi\Core\Attribute\CiviEventListener;
use Civi\Core\Event\GenericHookEvent;
use Civi\Core\Event\PostEvent;
use Civi\Core\Event\PreEvent;
use CRM_ScheduledCommunications_ExtensionUtil as E;

/**
 * Permissions and cleanup for search-based communications.
 */
class SearchCommunications {

  #[CiviEventListener(event: 'hook_civicrm_permission')]
  public function addPermissions(GenericHookEvent $event): void {
    $event->permissions['schedule communications'] = [
      'label' => E::ts('SearchKit: schedule communications'),
      'description' => E::ts('Create, update & delete search-based communications'),
    ];
  }

  /**
   * Do not allow users without 'schedule communications' permission to edit a saved search used for communications.
   */
  #[CiviEventListener(event: 'hook_civicrm_pre::SavedSearch')]
  public function checkSavedSearchPermission(PreEvent $event): void {
    if ($event->id && !empty($event->params['check_permissions']) && !\CRM_Core_Permission::check('schedule communications')) {
      $relatedCommunications = civicrm_api4('ActionSchedule', 'get', [
        'checkPermissions' => FALSE,
        'select' => ['ROW_COUNT'],
        'where' => [
          ['mapping_id', '=', 'saved_search'],
          ['entity_value', '=', $event->id],
        ],
      ])->count();
      if ($relatedCommunications) {
        throw new \CRM_Core_Exception(E::ts('Permission denied to modify saved search that controls a scheduled communication.'));
      }
    }
  }

  /**
   * Do not allow search-based scheduled communications to be created/edited/deleted without permission.
   */
  #[CiviEventListener(event: 'hook_civicrm_pre::ActionSchedule')]
  public function checkActionSchedulePermission(PreEvent $event): void {
    if (!empty($event->params['check_permissions']) && !\CRM_Core_Permission::check('schedule communications')) {
      $mappingType = $event->params['mapping_id'] ?? \CRM_Core_DAO_ActionSchedule::getDbVal('mapping_id', $event->id);
      if ($mappingType === 'saved_search') {
        throw new \CRM_Core_Exception(E::ts('Permission denied: search-based communications require the "schedule communications" permission.'));
      }
    }
  }

  /**
   * Delete scheduled communications linked to a deleted saved search.
   */
  #[CiviEventListener(event: 'hook_civicrm_post::SavedSearch')]
  public function deleteSavedSearchCommunications(PostEvent $event): void {
    if ($event->action === 'delete' && $event->id) {
      civicrm_api4('ActionSchedule', 'delete', [
        'checkPermissions' => FALSE,
        'where' => [
          ['mapping_id', '=', 'saved_search'],
          ['entity_value', '=', $event->id],
        ],
      ]);
    }
  }

}
