<?php

namespace Civi\Api4\Generic;

/**
 * Base class for all "Update" api actions
 *
 * @method $this setValues(array $values) Set all field values from an array of key => value pairs.
 * @method $this addValue($field, $value) Set field value.
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
   * Reload objects after saving.
   *
   * Setting to TRUE will load complete records and return them as the api result.
   * If FALSE the api usually returns only the fields specified to be updated.
   *
   * @var bool
   */
  protected $reload = FALSE;

  /**
   * @param string $key
   *
   * @return mixed|null
   */
  public function getValue($key) {
    return isset($this->values[$key]) ? $this->values[$key] : NULL;
  }

}
