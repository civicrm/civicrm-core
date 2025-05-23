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

use Civi\API\Request;
use Civi\Api4\Utils\CoreUtil;
use Civi\Core\Event\GenericHookEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Provides default display for type 'table' and type 'autocomplete'
 *
 * Other extensions can override or modify these defaults on a per-type or per-entity basis.
 *
 * @service
 * @internal
 */
class DefaultDisplaySubscriber extends \Civi\Core\Service\AutoService implements EventSubscriberInterface {

  /**
   * @return array
   */
  public static function getSubscribedEvents() {
    return [
      'civi.search.defaultDisplay' => [
        // Responding in-between W_MIDDLE and W_LATE so that other subscribers can either:
        // 1. Override these defaults (W_MIDDLE and earlier)
        // 2. Supplement these defaults (W_LATE)
        ['autocompleteDefault', -10],
        ['fallbackDefault', -20],
      ],
    ];
  }

  /**
   * Defaults for Autocomplete display type.
   *
   * These defaults work for any entity with a @labelField declared.
   * For other entity types, it's necessary to override these defaults.
   *
   * @param \Civi\Core\Event\GenericHookEvent $e
   */
  public static function autocompleteDefault(GenericHookEvent $e) {
    // Only fill autocomplete defaults if another subscriber hasn't already done the work
    if ($e->display['settings'] || $e->display['type'] !== 'autocomplete') {
      return;
    }
    $entityName = $e->savedSearch['api_entity'];
    if (!$entityName) {
      throw new \CRM_Core_Exception("Entity name is required to get autocomplete default display.");
    }
    $idField = CoreUtil::getIdFieldName($entityName);

    // If there's no label field, fall back on id. That's a pretty lame autocomplete but better than nothing.
    $searchFields = CoreUtil::getSearchFields($entityName) ?: [$idField];

    // Default sort order
    $e->display['settings']['sort'] = self::getDefaultSort($entityName);

    $apiGet = Request::create($entityName, 'get', ['version' => 4]);
    $fields = $apiGet->entityFields();
    $columns = array_slice($searchFields, 0, 1);
    // Add grouping fields like "event_type_id" in the description
    $grouping = (array) (CoreUtil::getCustomGroupExtends($entityName)['grouping'] ?? ['financial_type_id']);
    foreach ($grouping as $fieldName) {
      if (!empty($fields[$fieldName]['options']) && !in_array("$fieldName:label", $searchFields)) {
        $columns[] = "$fieldName:label";
      }
    }
    $statusField = $fields['status_id'] ?? $fields[strtolower($entityName) . '_status_id'] ?? NULL;
    if (!empty($statusField['options']) && !in_array("{$statusField['name']}:label", $searchFields)) {
      $columns[] = "{$statusField['name']}:label";
    }
    if (isset($fields['description'])) {
      $columns[] = 'description';
    }

    // First column is the main label
    foreach ($columns as $columnField) {
      $e->display['settings']['columns'][] = [
        'type' => 'field',
        'key' => $columnField,
      ];
    }
    if (count($searchFields) > 1) {
      $e->display['settings']['columns'][0]['rewrite'] = '[' . implode('] - [', $searchFields) . ']';
    }
    // Include entity id on the second line
    $e->display['settings']['columns'][1] = [
      'type' => 'field',
      'key' => $columns[1] ?? $idField,
      'rewrite' => "#[$idField]" . (isset($columns[1]) ? " [$columns[1]]" : ''),
      'empty_value' => "#[$idField]",
    ];

    // Default icons
    $iconFields = CoreUtil::getInfoItem($entityName, 'icon_field') ?? [];
    foreach ($iconFields as $iconField) {
      $e->display['settings']['columns'][0]['icons'][] = ['field' => $iconField];
    }

    // Color field
    if (isset($fields['color'])) {
      $e->display['settings']['extra']['color'] = 'color';
    }
  }

  /**
   * @param \Civi\Core\Event\GenericHookEvent $e
   */
  public static function fallbackDefault(GenericHookEvent $e) {
    // Early return if another subscriber has already done the work
    if ($e->display['settings']) {
      return;
    }
    /** @var \Civi\Api4\Action\SearchDisplay\GetDefault $getDefaultAction */
    $getDefaultAction = $e->apiAction;
    $e->display['settings'] += [
      'description' => $e->savedSearch['description'] ?? NULL,
      'sort' => [],
      'limit' => (int) \Civi::settings()->get('default_pager_size'),
      'pager' => [
        'show_count' => TRUE,
        'expose_limit' => TRUE,
      ],
      'placeholder' => 5,
      'columns' => [],
    ];
    // Supply default sort if no orderBy given in api params
    if (!empty($e->savedSearch['api_entity']) && empty($e->savedSearch['api_params']['orderBy'])) {
      $e->display['settings']['sort'] = self::getDefaultSort($e->savedSearch['api_entity']);
    }
    foreach ($getDefaultAction->getSelectClause() as $key => $clause) {
      $e->display['settings']['columns'][] = $getDefaultAction->configureColumn($clause, $key);
    }
    // Table-specific settings
    if ($e->display['type'] === 'table') {
      $e->display['settings']['actions'] = TRUE;
      $e->display['settings']['classes'] = ['table', 'table-striped'];
      $e->display['settings']['columns'][] = [
        'label' => '',
        'type' => 'menu',
        'icon' => 'fa-bars',
        'size' => 'btn-xs',
        'style' => 'secondary-outline',
        'alignment' => 'text-right',
        'links' => $getDefaultAction->getLinksMenu(),
      ];
    }
  }

  /**
   * @param $entityName
   * @return array
   */
  protected static function getDefaultSort($entityName) {
    $result = [];
    $sortFields = (array) (CoreUtil::getInfoItem($entityName, 'order_by') ?: CoreUtil::getSearchFields($entityName));
    foreach ($sortFields as $sortField) {
      $result[] = [$sortField, 'ASC'];
    }
    return $result;
  }

}
