<?php

namespace Civi\Api4\Action\CustomGroup;

use CRM_CivicrmAdminUi_ExtensionUtil as E;

/**
 * For multi-value custom groups, produce managed records
 * to generate a search kit SavedSearch and SearchDisplays
 * for listing the custom records
 *
 * @package Civi\Api4\Action\CustomGroup
 */
class GetSearchKit extends \Civi\Api4\Generic\BasicBatchAction {

  protected function getSelect() {
    return ['id', 'name', 'title', 'is_multiple', 'style'];
  }

  protected function doTask($item) {
    // searches only apply to multi-record CustomGroups
    if (!$item['is_multiple'] || ($item['style'] !== 'Tab with table')) {
      return [
        'id' => $item['id'],
        'managed' => [],
      ];
    }

    // derive the entity name for later use
    $item['entity_name'] = 'Custom_' . $item['name'];
    // derive the search name here - must be consistent between
    // SavedSearch and SearchDisplays
    $item['search_name'] = $item['entity_name'] . '_Search';

    // get Active + Display In Table fields for this group to include as columns
    $groupFields = \CRM_Core_BAO_CustomGroup::getGroup(['id' => $item['id']])['fields'];
    // note: `in_selector` is the field key for "display in table"
    $item['fields'] = array_filter($groupFields, fn ($field) => $field['in_selector'] && $field['is_active']);

    $managed = [];

    // Get SavedSearch
    $managed[] = $this->getSavedSearch($item);

    foreach ($this->getSearchDisplays($item) as $display) {
      $managed[] = $display;
    }

    return [
      'id' => $item['id'],
      'managed' => $managed,
    ];
  }

  protected function getSavedSearch(array $group): array {
    $entityName = $group['entity_name'];
    $searchName = $group['search_name'];
    $searchLabel = E::ts('%1 Search', [1 => $group['title']]);

    // select all fields by name
    $select = array_column($group['fields'], 'name');
    // add id and entity_id - always useful
    $select[] = 'id';
    $select[] = 'entity_id';

    return [
      'name' => "SavedSearch_{$searchName}",
      'entity' => 'SavedSearch',
      'cleanup' => 'unused',
      'update' => 'unmodified',
      'params' => [
        'version' => 4,
        'values' => [
          'name' => $searchName,
          'label' => $searchLabel,
          'api_entity' => $entityName,
          'api_params' => [
            'version' => 4,
            'select' => $select,
          ],
        ],
        'match' => ['name'],
      ],
    ];
  }

  protected function getSearchDisplays(array $group): array {
    $searchDisplays = [];

    // most columns are reusable across displays
    $columns = [];

    foreach ($group['fields'] as $field) {
      $columns[] = $this->getColumnForField($field);
    }
    $columns[] = $this->getButtonColumn($group);

    $searchDisplays[] = $this->getTabSearchDisplay($group, $columns);

    return $searchDisplays;
  }

  protected function getTabSearchDisplay($group, $columns, $displayType = 'table') {
    $searchName = $group['search_name'];
    $displayName = $group['entity_name'] . '_Tab';
    $displayLabel = $group['title'];
    $description = E::ts('Tab display for %1', [1 => $group['title']]);

    return [
      'name' => "SavedSearch_{$searchName}_SearchDisplay_{$displayName}",
      'entity' => 'SearchDisplay',
      'cleanup' => 'unused',
      'update' => 'unmodified',
      'params' => [
        'version' => 4,
        'values' => [
          'name' => $displayName,
          'label' => $displayLabel,
          'saved_search_id.name' => $searchName,
          'type' => $displayType,
          'settings' => [
            'placeholder' => 5,
            'columns' => $columns,
            'pager' => [
              'show_count' => TRUE,
              'expose_limit' => TRUE,
              'hide_single' => TRUE,
            ],
            'headerCount' => TRUE,
            // only for table but harmless otherwise
            'actions' => TRUE,
            'classes' => ['table', 'table-striped'],
            'actions_display_mode' => 'menu',
            // only for grid but harmless otherwise
            'colno' => '3',
            'toolbar' => [
              [
                'action' => 'add',
                'entity' => $group['entity_name'],
                'text' => E::ts('Add %1', [1 => $group['title']]),
                'icon' => 'fa-plus',
                'style' => 'default',
                'target' => 'crm-popup',
                'join' => '',
                'task' => '',
                //'condition' => ['check user permission', TRUE],
              ],
            ],
          ],
        ],
        'match' => [
          'saved_search_id',
          'name',
        ],
      ],
    ];
  }

  protected function getColumnForField($field) {
    $key = $field['name'];
    if ($field['option_group_id']) {
      $key .= ':label';
    }
    // TODO: for entity ref columns, we would like to use
    // a display-friendly column from the joined entity
    // but
    // a) we will need to determine the column based on
    //    the entity (e.g. display_name for contact)
    // b) we will need to add it to the SavedSearch as well
    return [
      'type' => 'field',
      'key' => $key,
      'label' => $field['label'],
      'sortable' => TRUE,
      'break' => TRUE,
    ];
  }

  protected function getButtonColumn($group) {
    $groupName = $group['name'];
    $entityName = $group['entity_name'];
    return [
      'size' => 'btn-xs',
      'links' => [
        [
          'entity' => $entityName,
          'action' => 'view',
          'target' => 'crm-popup',
          'icon' => 'fa-eye',
          'text' => E::ts('View'),
          'style' => 'default',
        ],
        [
          'entity' => $entityName,
          'action' => 'update',
          'target' => 'crm-popup',
          'icon' => 'fa-pencil',
          'text' => E::ts('Edit'),
          'style' => 'default',
        ],
        [
          'entity' => $entityName,
          'task' => 'delete',
          'target' => 'crm-popup',
          'icon' => 'fa-trash',
          'text' => E::ts('Delete'),
          'style' => 'danger',
        ],
      ],
      'type' => 'buttons',
      'alignment' => 'text-right',
    ];
  }

  public static function getAllManaged() {
    // for now we only fetch for Groups that have a Tab
    $all = \Civi\Api4\CustomGroup::getSearchKit(FALSE)
      ->addWhere('is_active', '=', TRUE)
      ->addWhere('is_multiple', '=', TRUE)
      ->addWhere('style', 'IN', ['Tab', 'Tab with table'])
      ->execute()
      ->column('managed');

    return array_merge(...$all);
  }

}
