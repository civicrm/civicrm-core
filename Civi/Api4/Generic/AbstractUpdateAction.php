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

namespace Civi\Api4\Generic;

use Civi\API\Exception\UnauthorizedException;
use Civi\Api4\Event\ValidateValuesEvent;
use Civi\Api4\Utils\CoreUtil;

/**
 * Base class for all `Update` api actions
 *
 * @method $this setValues(array $values) Set all field values from an array of key => value pairs.
 * @method array getValues() Get field values.
 * @method $this setReload(?array $reload) Optionally reload $ENTITIES after saving with an extra SELECT.
 * @method bool getReload()
 *
 * @package Civi\Api4\Generic
 */
abstract class AbstractUpdateAction extends AbstractBatchAction {

  use Traits\GetSetValueTrait;

  /**
   * Field values to update.
   *
   * @var array
   * @required
   */
  protected $values = [];

  /**
   * Optionally reload $ENTITIES after saving with an extra SELECT.
   *
   * By default, this action typically returns partial records containing only the fields
   * that were updated. If more is needed, set `reload` to an array of fields to SELECT
   * (use `['*']` to select all) and they will be returned via an extra get request.
   *
   * @var array|bool
   */
  protected $reload;

  /**
   * Criteria for selecting items to update.
   *
   * Required if no id is supplied in values.
   *
   * @var array
   */
  protected $where = [];

  abstract protected function updateRecords(array $items): array;

  /**
   * @inheritDoc
   */
  public function _run(Result $result) {
    $primaryKeys = CoreUtil::getInfoItem($this->getEntityName(), 'primary_key');
    $this->formatWriteValues($this->values);

    // Add primary keys from values to WHERE clause and check for mismatch
    foreach ($primaryKeys as $id) {
      if (!empty($this->values[$id])) {
        $wheres = array_column($this->where, NULL, 0);
        if (!isset($wheres[$id])) {
          $this->addWhere($id, '=', $this->values[$id]);
        }
        elseif (!($wheres[$id][1] === '=' && $wheres[$id][2] == $this->values[$id])) {
          throw new \Exception("Cannot update the $id of an existing " . $this->getEntityName() . '.');
        }
      }
    }

    // Require WHERE if we didn't get primary keys from values
    if (!$this->where) {
      throw new \CRM_Core_Exception('Parameter "where" is required unless primary keys are supplied in values.');
    }

    // Update a single record by primary key (if this entity has a single primary key)
    if (count($this->where) === 1 && count($primaryKeys) === 1 && $primaryKeys === $this->getSelect() && $this->where[0][0] === $id && $this->where[0][1] === '=' && !empty($this->where[0][2])) {
      $this->values[$id] = $this->where[0][2];
      if ($this->checkPermissions && !CoreUtil::checkAccessRecord($this, $this->values, \CRM_Core_Session::getLoggedInContactID() ?: 0)) {
        throw new UnauthorizedException("ACL check failed");
      }
      $items = [$this->values];
      $this->validateValues();
      $result->exchangeArray($this->updateRecords($items));
      return;
    }

    // Batch update 1 or more records based on WHERE clause
    $items = $this->getBatchRecords();
    foreach ($items as &$item) {
      $item = $this->values + $item;
      if ($this->checkPermissions && !CoreUtil::checkAccessRecord($this, $item, \CRM_Core_Session::getLoggedInContactID() ?: 0)) {
        throw new UnauthorizedException("ACL check failed");
      }
    }

    $this->validateValues();
    $result->exchangeArray($this->updateRecords($items));
  }

  /**
   * @throws \CRM_Core_Exception
   */
  protected function validateValues() {
    // FIXME: There should be a protocol to report a full list of errors... Perhaps a subclass of CRM_Core_Exception?
    $e = new ValidateValuesEvent($this, [$this->values], new \CRM_Utils_LazyArray(function () {
      $existing = $this->getBatchAction()->setSelect(['*'])->execute();
      $result = [];
      foreach ($existing as $record) {
        $result[] = ['old' => $record, 'new' => $this->values];
      }
      return $result;
    }));
    \Civi::dispatcher()->dispatch('civi.api4.validate', $e);
    if (!empty($e->errors)) {
      throw $e->toException();
    }
  }

}
