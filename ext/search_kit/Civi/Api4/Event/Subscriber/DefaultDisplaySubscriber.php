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
    $labelField = CoreUtil::getInfoItem($entityName, 'label_field');
    if (!$labelField) {
      throw new \CRM_Core_Exception("Entity $entityName has no default label field.");
    }

    // Default sort order
    $e->display['settings']['sort'] = self::getDefaultSort($entityName);

    $fields = CoreUtil::getApiClass($entityName)::get()->entityFields();
    $columns = [$labelField];
    // Add grouping fields like "event_type_id" in the description
    $grouping = (array) (CoreUtil::getCustomGroupExtends($entityName)['grouping'] ?? []);
    foreach ($grouping as $fieldName) {
      $columns[] = "$fieldName:label";
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
    // Include entity id on the second line
    $e->display['settings']['columns'][1] = [
      'type' => 'field',
      'key' => $idField,
      'rewrite' => "#[$idField]" . (isset($columns[1]) ? " [$columns[1]]" : ''),
    ];

    // Default icons
    $iconFields = CoreUtil::getInfoItem($entityName, 'icon_field') ?? [];
    foreach ($iconFields as $iconField) {
      $e->display['settings']['columns'][0]['icons'][] = ['field' => $iconField];
    }

    // Color field
    if (isset($fields['color'])) {
      $e->display['settings']['color'] = 'color';
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
    foreach ($e->apiAction->getSelectClause() as $key => $clause) {
      $e->display['settings']['columns'][] = $e->apiAction->configureColumn($clause, $key);
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
        'links' => $e->apiAction->getLinksMenu(),
      ];
    }
  }

  /**
   * @param $entityName
   * @return array
   */
  protected static function getDefaultSort($entityName) {
    $sortField = CoreUtil::getInfoItem($entityName, 'order_by') ?: CoreUtil::getInfoItem($entityName, 'label_field');
    return $sortField ? [[$sortField, 'ASC']] : [];
  }

}
