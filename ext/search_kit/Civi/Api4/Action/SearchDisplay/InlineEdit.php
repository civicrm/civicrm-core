<?php

namespace Civi\Api4\Action\SearchDisplay;

use CRM_Search_ExtensionUtil as E;
use Civi\Api4\Result\SearchDisplayRunResult;
use Civi\Api4\Utils\CoreUtil;

/**
 * Perform an inline-edit to a search display row, then re-run the search to return created/updated row.
 *
 * @method $this setValues(array $values)
 * @method array getValues()
 * @method $this setRowKey(int $rowKey)
 * @method int getRowKey()
 * @package Civi\Api4\Action\SearchDisplay
 */
class InlineEdit extends Run {

  /**
   * @var string|array
   * Either the name of the display or an array containing the definition (for preview mode)
   * @required
   */
  protected $display;

  /**
   * @var int
   */
  protected $rowKey;

  /**
   * @var array
   */
  protected $values;

  protected function processResult(SearchDisplayRunResult $result) {
    if (isset($this->rowKey) && $this->return === 'draggableWeight') {
      $this->updateDraggableWeight();
    }
    elseif (isset($this->rowKey)) {
      $this->updateExistingRow();
    }
    elseif (!empty($this->display['settings']['editableRow']['create'])) {
      $this->createNewRow();
    }
    else {
      throw new \CRM_Core_Exception('Cannot create new item in search display');
    }

    // This prevents unnecessary metadata from being returned in the reload
    $this->rowKey = NULL;

    // Now reload the updated row to refresh client-side
    parent::processResult($result);
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public function updateExistingRow(): void {
    // Apply rowKey to filters
    $entityName = $this->savedSearch['api_entity'];
    $keyName = $filterKey = CoreUtil::getIdFieldName($entityName);
    // Hack to support relationships
    if ($entityName === 'RelationshipCache') {
      $filterKey = 'relationship_id';
      $entityName = 'Relationship';
    }
    $this->applyFilter($filterKey, $this->rowKey);
    $this->return = NULL;
    $this->_apiParams['offset'] = 0;

    // First get existing values
    // This will include inline-edit metadata
    $existingValues = new SearchDisplayRunResult();
    parent::processResult($existingValues);
    $existingValues = $existingValues->single();

    $tasks = [];

    // Gather tasks from values; group by entity+action+id
    $columns = $this->display['settings']['columns'];
    foreach ($columns as $columnIndex => $column) {
      // Editable column
      $editableInfo = $existingValues['columns'][$columnIndex]['edit'] ?? NULL;
      if ($editableInfo && array_key_exists($column['key'], $this->values)) {
        $value = $this->values[$column['key']];
        if (empty($editableInfo['nullable']) && ($value === NULL || $value === '')) {
          continue;
        }
        $idField = CoreUtil::getIdFieldName($editableInfo['entity']);
        $taskKey = $editableInfo['action'] . ($editableInfo['record'][$idField] ?? '');
        if (!isset($tasks[$editableInfo['entity']][$taskKey])) {
          $tasks[$editableInfo['entity']][$taskKey] = $editableInfo;
        }
        $tasks[$editableInfo['entity']][$taskKey]['record'][$editableInfo['value_key']] = $value;
      }
      // Links column - check for matching apBatch tasks
      elseif (!empty($column['links']) || !empty($column['link'])) {
        $links = !empty($column['links']) ? $column['links'] : [$column['link']];
        foreach ($links as $link) {
          if (!empty($link['task']) && ($link['action'] === 'update') && ($link['api_params']['values'] ?? NULL) === $this->values) {
            $taskKey = 'update' . $this->rowKey;
            if (empty($tasks[$entityName][$taskKey]['record'])) {
              $tasks[$entityName][$taskKey]['action'] = 'update';
              $tasks[$entityName][$taskKey]['record'] = [
                $keyName => $this->rowKey,
              ];
              foreach ($this->values as $key => $value) {
                $tasks[$entityName][$taskKey]['record'][$key] = $value;
              }
            }
          }
        }
      }
    }

    if (!$tasks) {
      throw new \CRM_Core_Exception('Inline edit failed.');
    }

    $checkPermissions = empty($this->display['acl_bypass']);
    // Run create/update tasks
    foreach ($tasks as $editableEntity => $editableItems) {
      foreach ($editableItems as $editableItem) {
        civicrm_api4($editableEntity, $editableItem['action'], [
          'checkPermissions' => $checkPermissions,
          'values' => $editableItem['record'],
        ]);
      }
    }
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public function createNewRow(): void {
    // Gather tasks from values; key by explicit_join
    $joins = $this->getJoins();

    // Ensure tasks are in the same order as joins
    $tasks = [
      // Base entity is always the first task
      '' => NULL,
    ] + array_fill_keys(array_keys($joins), NULL);

    $this->applyFilters();
    $data = $this->getQueryData();

    $columns = $this->display['settings']['columns'];
    foreach ($columns as $column) {
      if (array_key_exists($column['key'], $this->values)) {
        $editableInfo = $this->getEditableInfo($column['key']);
        if (!$editableInfo) {
          throw new \CRM_Core_Exception('Cannot edit column ' . $column['key']);
        }
        $value = $this->values[$column['key']];
        if ($value === NULL || $value === '') {
          continue;
        }
        $joinName = $editableInfo['explicit_join'] ?? '';
        if (!isset($tasks[$joinName])) {
          $tasks[$joinName] = $editableInfo;
          $record = $data;
          $prefix = $joinName ? "$joinName." : '';
          $record = \CRM_Utils_Array::filterByPrefix($record, $prefix);
          $tasks[$joinName]['record'] = array_filter($record, fn($key) => !str_contains($key, '.'), ARRAY_FILTER_USE_KEY);
        }
        $tasks[$joinName]['record'][$editableInfo['value_key']] = $value;
      }
    }

    if (!isset($tasks[''])) {
      throw new \CRM_Core_Exception('Not enough data to create new row');
    }

    $checkPermissions = empty($this->display['acl_bypass']);
    $saved = [];

    foreach ($tasks as $joinName => $editableItem) {
      if (!$editableItem) {
        continue;
      }
      if ($joinName) {
        $join = $joins[$joinName];
        foreach ($join['on'] ?? [] as $clause) {
          $key = $value = NULL;
          if (isset($clause[2]) && $clause[1] === '=') {
            unset($clause[1]);
            foreach ($clause as $item) {
              if (is_string($item) && str_starts_with($item, "$joinName.")) {
                $key = substr($item, strlen($joinName) + 1);
              }
              else {
                $value = $item;
                if (\CRM_Utils_String::isQuotedString($value)) {
                  $value = \CRM_Utils_String::unquoteString($value);
                }
                elseif (is_string($value)) {
                  foreach (array_reverse($saved, TRUE) as $taskName => $taskValues) {
                    $taskPrefix = $taskName ? "$taskName." : '';
                    if (str_starts_with($value, $taskPrefix)) {
                      $taskValueKey = substr($value, strlen($taskPrefix));
                      $value = $taskValues[$taskValueKey] ?? NULL;
                      break;
                    }
                  }
                }
              }
            }
            if (isset($key) && isset($value)) {
              $editableItem['record'][$key] = $value;
            }
          }
        }
      }
      $result = civicrm_api4($editableItem['entity'], 'create', [
        'checkPermissions' => $checkPermissions,
        'values' => $editableItem['record'],
      ])->first();
      $saved[$joinName] = $result;
    }

    // Apply id of newly saved row to filters
    $keyName = CoreUtil::getIdFieldName($this->savedSearch['api_entity']);
    $this->applyFilter($keyName, $saved[''][$keyName]);
  }

  private function updateDraggableWeight(): void {
    $weightField = $this->display['settings']['draggable'];
    if (!$weightField) {
      throw new \CRM_Core_Exception('Search display is not configured for draggable sorting.');
    }
    if (!isset($this->values[$weightField])) {
      throw new \CRM_Core_Exception('Cannot update draggable weight: no value provided.');
    }
    $entityName = $this->savedSearch['api_entity'];
    $keyName = CoreUtil::getIdFieldName($entityName);
    // For security, do not accept arbitrary values; only update weight.
    $values = [
      $weightField => $this->values[$weightField],
    ];
    // For hierarchical entities, also allow parent_field to be updated.
    $parentField = CoreUtil::getInfoItem($entityName, 'parent_field');
    if ($parentField && array_key_exists($parentField, $this->values)) {
      $values[$parentField] = $this->values[$parentField];
    }
    civicrm_api4($entityName, 'update', [
      'checkPermissions' => empty($this->display['acl_bypass']),
      'where' => [
        [$keyName, '=', $this->rowKey],
      ],
      'values' => $values,
    ]);
  }

}
