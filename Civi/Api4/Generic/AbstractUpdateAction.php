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

use Civi\Api4\Event\ValidateValuesEvent;

/**
 * Base class for all `Update` api actions
 *
 * @method $this setValues(array $values) Set all field values from an array of key => value pairs.
 * @method array getValues() Get field values.
 * @method $this setReload(bool $reload) Specify whether complete objects will be returned after saving.
 * @method bool getReload()
 *
 * @package Civi\Api4\Generic
 */
abstract class AbstractUpdateAction extends AbstractBatchAction {

  /**
   * Field values to update.
   *
   * @var array
   * @required
   */
  protected $values = [];

  /**
   * Reload $ENTITIES after saving.
   *
   * Setting to `true` will load complete records and return them as the api result.
   * If `false` the api usually returns only the fields specified to be updated.
   *
   * @var bool
   */
  protected $reload = FALSE;

  /**
   * @param string $fieldName
   *
   * @return mixed|null
   */
  public function getValue(string $fieldName) {
    return $this->values[$fieldName] ?? NULL;
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

  /**
   * @throws \API_Exception
   */
  protected function validateValues() {
    // FIXME: There should be a protocol to report a full list of errors... Perhaps a subclass of API_Exception?
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
