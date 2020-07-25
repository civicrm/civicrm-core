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
 */


namespace Civi\Api4\Generic;

/**
 * Copies one or more $ENTITIES based on the WHERE criterla.
 *
 * @method $this setValues(array $values) Set all field values from an array of key => value pairs.
 * @method array getValues() Get field values.
 *
 * @package Civi\Api4\Generic
 */
class BasicCopyAction extends AbstractGetAction {

  /**
   * Unique fields that should be excluded from the copy.
   * @var array
   */
  private $idFields;

  /**
   * Criteria for selecting $ENTITIES to copy.
   *
   * @var array
   * @required
   */
  protected $where = [];

  /**
   * Fields to copy over. Defaults to all core + custom fields, select `['*', 'custom.*']`.
   *
   * @var array
   */
  protected $select = [];

  /**
   * Field values to set in each copy.
   *
   * @var array
   * @required
   */
  protected $values = [];

  /**
   * BasicCopyAction constructor.
   * @param string $entityName
   * @param string $actionName
   * @param string|string[] $idFields
   * @throws \API_Exception
   */
  public function __construct($entityName, $actionName, $idFields = ['id']) {
    parent::__construct($entityName, $actionName);
    $this->idFields = (array) $idFields;
  }

  /**
   * @param Result $result
   */
  public function _run(Result $result) {
    $records = $this->getBatchRecords();
    foreach ($records as $record) {
      \CRM_Utils_Array::remove($record, $this->idFields);
      $record = $this->values + $record;
      $result[] = $copy = civicrm_api4($this->getEntityName(), 'create', ['values' => $record])->first();
      // Call hook_civicrm_copy, which expects an object rather than an array
      $copy = (object) ($copy + $record);
      \CRM_Utils_Hook::copy($this->getEntityName(), $copy);
    }
  }

  /**
   * @return Result
   */
  protected function getBatchRecords() {
    $params = [
      'checkPermissions' => $this->checkPermissions,
      'select' => $this->select ?: ['*', 'custom.*'],
      'where' => $this->where,
      'orderBy' => $this->orderBy,
      'limit' => $this->limit,
      'offset' => $this->offset,
    ];
    return civicrm_api4($this->getEntityName(), 'get', $params);
  }

  /**
   * Add an item to the values array.
   *
   * @param string $fieldName
   * @param mixed $value
   * @return $this
   */
  public function addValue(string $fieldName, $value) {
    $this->values[$fieldName] = $value;
    return $this;
  }

}
