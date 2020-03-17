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

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 * $Id$
 *
 */


namespace Civi\Api4\Generic;

/**
 * Base class for all `Save` api actions.
 *
 * @method $this setRecords(array $records) Set array of records to be saved.
 * @method array getRecords()
 * @method $this setDefaults(array $defaults) Array of defaults.
 * @method array getDefaults()
 * @method $this setReload(bool $reload) Specify whether complete objects will be returned after saving.
 * @method bool getReload()
 *
 * @package Civi\Api4\Generic
 */
abstract class AbstractSaveAction extends AbstractAction {

  /**
   * Array of $ENTITIES to save.
   *
   * Should be in the same format as returned by `Get`.
   *
   * @var array
   * @required
   */
  protected $records = [];

  /**
   * Array of default values.
   *
   * These defaults will be merged into every $ENTITY in `records` before saving.
   * Values set in `records` will override these defaults if set in both places,
   * but updating existing $ENTITIES will overwrite current values with these defaults.
   *
   * @var array
   */
  protected $defaults = [];

  /**
   * Reload $ENTITIES after saving.
   *
   * By default this action typically returns partial records containing only the fields
   * that were updated. Set `reload` to `true` to do an additional lookup after saving
   * to return complete values for every $ENTITY.
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

  /**
   * Add one or more records to be saved.
   * @param array ...$records
   * @return $this
   */
  public function addRecord(array ...$records) {
    $this->records = array_merge($this->records, $records);
    return $this;
  }

  /**
   * Set default value for a field.
   * @param string $fieldName
   * @param mixed $defaultValue
   * @return $this
   */
  public function addDefault(string $fieldName, $defaultValue) {
    $this->defaults[$fieldName] = $defaultValue;
    return $this;
  }

}
