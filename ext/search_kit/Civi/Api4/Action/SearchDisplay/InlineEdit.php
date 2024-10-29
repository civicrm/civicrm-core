<?php

namespace Civi\Api4\Action\SearchDisplay;

use CRM_Search_ExtensionUtil as E;
use Civi\Api4\Result\SearchDisplayRunResult;
use Civi\Api4\Utils\CoreUtil;

/**
 * Perform an inline-edit to a search display result, then re-run the search to return the updated row.
 *
 * @method $this setValue(mixed $value)
 * @method mixed getValue()
 * @method $this setRowKey(mixed $rowKey)
 * @method mixed getRowKey()
 * @method $this setCowKey(string $colKey)
 * @method string getColKey()
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
   * @required
   */
  protected $rowKey;

  /**
   * @var string
   * @required
   */
  protected $colKey;

  /**
   * @var mixed
   */
  protected $value;

  protected function processResult(SearchDisplayRunResult $result) {
    $checkPermissions = empty($this->display['settings']['acl_bypass']);

    // Apply rowKey to filters
    $keyName = CoreUtil::getIdFieldName($this->savedSearch['api_entity']);
    $this->applyFilter($keyName, $this->rowKey);
    $this->return = NULL;
    $this->_apiParams['offset'] = 0;

    // First get existing values
    $existingValues = new SearchDisplayRunResult();
    parent::processResult($existingValues);
    $existingValues = $existingValues->single();

    $columns = $this->display['settings']['columns'];
    foreach ($columns as $columnIndex => $column) {
      if (($column['key'] ?? NULL) === $this->colKey) {
        break;
      }
    }
    if ($column['key'] !== $this->colKey || empty($existingValues['columns'][$columnIndex]['edit'])) {
      throw new \CRM_Core_Exception('Cannot edit column ' . $this->colKey);
    }
    $editableInfo = $existingValues['columns'][$columnIndex]['edit'];
    $entityValues = $editableInfo['record'];
    $entityValues[$editableInfo['value_key']] = $this->value;
    civicrm_api4($editableInfo['entity'], $editableInfo['action'], [
      'checkPermissions' => $checkPermissions,
      'values' => $entityValues,
    ]);

    // This prevents unnecessary metadata from being returned in the reload
    $this->colKey = NULL;

    // Now reload the updated row to refresh client-side
    parent::processResult($result);
  }

}
