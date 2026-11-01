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

use Civi\Core\Attribute\CiviEventListener;
use Civi\Api4\Event\AuthorizeRecordEvent;

/**
 * Enforce constraints on Custom entities
 *
 * Currently:
 * - deny create if max_multiple reached for the given entity_id
 *
 * TODO:
 * - deny delete if min_multiple under threat
 * - deny replace / save if it will threaten min/max
 * - ?
 */
class CustomValueAccessSubscriber {

  /**
   * @param \Civi\Api4\Event\AuthorizeRecordEvent $event
   *   API record authorize event
   */
  #[CiviEventListener(event: 'civi.api4.authorizeRecord', priority: -100)]
  public function onApiAuthorizeRecord(AuthorizeRecordEvent $event) {
    $apiRequest = $event->getApiRequest();

    if (!in_array(
        'Civi\Api4\Generic\Traits\CustomValueActionTrait',
        class_uses($apiRequest)
        )) {
      return;
    }

    $action = $apiRequest->getActionName();

    // TODO: what api actions should we apply to?
    // min/max should apply to create + delete
    // also save and replace but trickier
    // also update if we ever allow changing entity_id

    // for now only apply to create
    if ($action !== 'create') {
      return;
    }

    $parentId = $event->getRecord()['entity_id'] ?? NULL;
    if (!$parentId) {
      // we dont know the parent so cant check existing records
      return;
    }

    $group = \CRM_Core_BAO_CustomGroup::getGroup(['name' => $apiRequest->getCustomGroup()]);

    if (!$group) {
      return;
    }
    if ($group['max_multiple']) {
      $currentCount = civicrm_api4($apiRequest->getEntityName(), 'get', [
        'select' => ['row_count'],
        'where' => [['entity_id', '=', $parentId]],
      ])->count();

      if ($currentCount >= $group['max_multiple']) {
        // would be good if we could provide a clearer error message
        // at the moment will just get ACL check failed
        $event->setAuthorized(FALSE);
      }
    }
  }

}
