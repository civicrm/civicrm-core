<?php

namespace Civi\Api4\Generic;

use Civi\Api4\Generic\Result;

/**
 * Given a set of records, will appropriately update the database.
 *
 * @method $this setRecords(array $records) Array of records.
 * @method $this addRecord($record) Add a record to update.
 * @method array getRecords()
 * @method $this setReload(bool $reload) Specify whether complete objects will be returned after saving.
 * @method bool getReload()
 */
class BasicReplaceAction extends AbstractBatchAction {

  /**
   * Array of records.
   *
   * Should be in the same format as returned by Get.
   *
   * @required
   * @var array
   */
  protected $records = [];

  /**
   * Reload objects after saving.
   *
   * Setting to TRUE will load complete records and return them as the api result.
   * If FALSE the api usually returns only the fields specified to be updated.
   *
   * @var bool
   */
  protected $reload = FALSE;

  /**
   * @inheritDoc
   */
  public function _run(Result $result) {
    $items = $this->getBatchRecords();

    // Copy params from where clause if the operator is =
    $paramsFromWhere = [];
    foreach ($this->where as $clause) {
      if (is_array($clause) && $clause[1] === '=') {
        $paramsFromWhere[$clause[0]] = $clause[2];
      }
    }

    $idField = $this->getSelect()[0];
    $toDelete = array_column($items, NULL, $idField);

    foreach ($this->records as $record) {
      $record += $paramsFromWhere;
      if (!empty($record[$idField])) {
        $id = $record[$idField];
        unset($toDelete[$id], $record[$idField]);
        $result[] = civicrm_api4($this->getEntityName(), 'update', [
          'reload' => $this->reload,
          'where' => [[$idField, '=', $id]],
          'values' => $record,
        ])->first();
      }
      else {
        $result[] = civicrm_api4($this->getEntityName(), 'create', [
          'values' => $record,
        ])->first();
      }
    }

    $result->deleted = [];
    if ($toDelete) {
      $result->deleted = (array) civicrm_api4($this->getEntityName(), 'delete', [
        'where' => [[$idField, 'IN', array_keys($toDelete)]],
      ]);
    }
  }

}
