<?php

namespace Civi\Api4\Action\SearchDisplay;

use CRM_Search_ExtensionUtil as E;
use Civi\Api4\Result\SearchDisplayRunResult;
use Civi\Api4\Utils\CoreUtil;

/**
 * Perform an inline-edit to a search display row, then re-run the search to return created/updated row.
 *
 * @method $this setValues(mixed $values)
 * @method mixed getValues()
 * @method $this setRowKey(mixed $rowKey)
 * @method mixed getRowKey()
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
    if (isset($this->rowKey)) {
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
    $keyName = CoreUtil::getIdFieldName($this->savedSearch['api_entity']);
    $this->applyFilter($keyName, $this->rowKey);
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
      if (array_key_exists($column['key'], $this->values)) {
        $editableInfo = $existingValues['columns'][$columnIndex]['edit'] ?? NULL;
        if (!$editableInfo) {
          throw new \CRM_Core_Exception('Cannot edit column ' . $column['key']);
        }
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
    }

    $checkPermissions = empty($this->display['settings']['acl_bypass']);
    // Run create/update tasks
    foreach ($tasks as $editableItems) {
      foreach ($editableItems as $editableItem) {
        civicrm_api4($editableItem['entity'], $editableItem['action'], [
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

    $checkPermissions = empty($this->display['settings']['acl_bypass']);
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

}
