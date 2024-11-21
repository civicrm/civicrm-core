<?php

namespace Civi\Api4\Action\CustomGroup;

use CRM_Afform_ExtensionUtil as E;

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

    // get active fields for this group to include as columns
    $item['fields'] = (array) \Civi\Api4\CustomField::get(FALSE)
      ->addSelect('name', 'label')
      ->addWhere('custom_group_id', '=', $item['id'])
      ->addWhere('is_active', '=', TRUE)
      // respect "Display in table" config on each field
      // (Q: should we respect this for other displays?)
      ->addWhere('in_selector', '=', TRUE)
      ->execute();

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
    $select = array_map(fn ($field) => $field['name'], $group['fields']);
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
    $columns = $this->getFieldColumns($group['fields']);
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
            'limit' => 50,
            'placeholder' => 5,
            'columns' => $columns,
            // only for table but harmless otherwise
            'actions' => TRUE,
            'classes' => ['table', 'table-striped'],
            'actions_display_mode' => 'menu',
            // only for grid but harmless otherwise
            'colno' => '3',
          ],
        ],
        'match' => [
          'saved_search_id',
          'name',
        ],
      ],
    ];
  }

  protected function getFieldColumns($fields) {
    return array_map(fn ($field) => [
      'type' => 'field',
      'key' => $field['name'],
      'label' => $field['label'],
      'sortable' => TRUE,
      'break' => TRUE,
    ], $fields);
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
          // TODO: can we register this as the canonical Update link
          'path' => "civicrm/af/custom/{$groupName}/update#?Record=[id]",
          'target' => 'crm-popup',
          'icon' => 'fa-pencil',
          'text' => E::ts('Edit'),
          'style' => 'warning',
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
    // for now we only fetch for Groups that have a Tab with table on the contact summary
    $all = \Civi\Api4\CustomGroup::getSearchKit(FALSE)
      ->addWhere('is_active', '=', TRUE)
      ->addWhere('is_multiple', '=', TRUE)
      ->addWhere('style', 'IN', ['Tab', 'Tab with table'])
      ->addWhere('extends', 'IN', ['Contact', 'Individual', 'Household', 'Organization'])
      ->execute()
      ->column('managed');

    return array_merge(...$all);
  }

}
