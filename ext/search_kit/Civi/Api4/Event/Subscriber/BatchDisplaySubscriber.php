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

use Civi\Api4\Action\SearchDisplay\AbstractRunAction;
use Civi\Api4\Generic\Traits\SavedSearchInspectorTrait;
use Civi\Core\Event\GenericHookEvent;
use Civi\Core\Event\PreEvent;
use Civi\Core\Service\AutoService;
use Civi\Search\Meta;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Presave helper for search displays of type "batch"
 * @service
 * @internal
 */
class BatchDisplaySubscriber extends AutoService implements EventSubscriberInterface {

  use SavedSearchInspectorTrait;

  /**
   * @return array
   */
  public static function getSubscribedEvents(): array {
    return [
      'hook_civicrm_pre::SearchDisplay' => 'onPreSaveDisplay',
    ];
  }

  /**
   * @param \Civi\Core\Event\PreEvent $event
   */
  public function onPreSaveDisplay(PreEvent $event): void {
    if (!$this->applies($event)) {
      return;
    }
    if ($event->action === 'delete') {
      // TODO: Delete related userJobs?
      return;
    }
    $newSettings = $event->params['settings'] ?? NULL;
    if (!$newSettings) {
      return;
    }
    $savedSearchID = $event->params['saved_search_id'] ?? \CRM_Core_DAO::getFieldValue('CRM_Search_DAO_SearchDisplay', $event->id, 'saved_search_id');
    $this->loadSavedSearch($savedSearchID);
    $pseudoFields = array_column(AbstractRunAction::getPseudoFields(), 'name');
    $columnNames = [];
    foreach ($newSettings['columns'] as $i => &$column) {
      if (empty($column['key']) || in_array($column['key'], $pseudoFields)) {
        continue;
      }
      $expr = $this->getSelectExpression($column['key']);
      if ($expr) {
        $column['spec'] = Meta::formatFieldSpec($column, $expr);
        // Ensure column names are unique
        if (in_array($column['spec']['name'], $columnNames)) {
          $column['spec']['name'] .= $i;
        }
        $column['spec']['required'] = !empty($column['required']);
        $column['spec']['nullable'] = empty($column['required']);
        $column['spec']['api_default'] = $column['default'] ?? NULL;
        $columnNames[] = $column['spec']['name'];
      }
      // Redundant with spec and less-reliable
      unset($column['dataType']);
    }
    // Store new settings with added column spec
    $event->params['settings'] = $newSettings;
  }

  /**
   * Check if pre/post hook applies to a SearchDisplay type 'entity'
   *
   * @param \Civi\Core\Event\PreEvent|\Civi\Core\Event\PostEvent $event
   * @return bool
   */
  private function applies(GenericHookEvent $event): bool {
    if ($event->entity !== 'SearchDisplay') {
      return FALSE;
    }
    $type = $event->params['type'] ?? \CRM_Core_DAO::getFieldValue('CRM_Search_DAO_SearchDisplay', $event->id, 'type');
    return $type === 'batch';
  }

}
