<?php

namespace Civi\Api4\Generic;

/**
 * Base class for all "Save" api actions.
 *
 * @method $this setRecords(array $records) Array of records.
 * @method $this addRecord($record) Add a record to update.
 * @method array getRecords()
 * @method $this setDefaults(array $defaults) Array of defaults.
 * @method $this addDefault($name, $value) Add a default value.
 * @method array getDefaults()
 * @method $this setReload(bool $reload) Specify whether complete objects will be returned after saving.
 * @method bool getReload()
 *
 * @package Civi\Api4\Generic
 */
abstract class AbstractSaveAction extends AbstractAction {

  /**
   * Array of records.
   *
   * Should be in the same format as returned by Get.
   *
   * @var array
   * @required
   */
  protected $records = [];

  /**
   * Array of default values.
   *
   * These defaults will be applied to all records unless they specify otherwise.
   *
   * @var array
   */
  protected $defaults = [];

  /**
   * Reload records after saving.
   *
   * By default this api typically returns partial records containing only the fields
   * that were updated. Set reload to TRUE to do an additional lookup after saving
   * to return complete records.
   *
   * @var bool
   */
  protected $reload = FALSE;

  /**
   * @var string
   */
  private $idField;

  /**
   * BatchAction constructor.
   * @param string $entityName
   * @param string $actionName
   * @param string $idField
   */
  public function __construct($entityName, $actionName, $idField = 'id') {
    // $idField should be a string but some apis (e.g. CustomValue) give us an array
    $this->idField = array_values((array) $idField)[0];
    parent::__construct($entityName, $actionName);
  }

  /**
   * @throws \API_Exception
   */
  protected function validateValues() {
    $unmatched = [];
    foreach ($this->records as $record) {
      if (empty($record[$this->idField])) {
        $unmatched = array_unique(array_merge($unmatched, $this->checkRequiredFields($record)));
      }
    }
    if ($unmatched) {
      throw new \API_Exception("Mandatory values missing from Api4 {$this->getEntityName()}::{$this->getActionName()}: " . implode(", ", $unmatched), "mandatory_missing", ["fields" => $unmatched]);
    }
  }

  /**
   * @return string
   */
  protected function getIdField() {
    return $this->idField;
  }

}
