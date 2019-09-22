<?php

namespace Civi\Api4\Generic;

/**
 * Base class for all "Create" api actions.
 *
 * @method $this setValues(array $values) Set all field values from an array of key => value pairs.
 * @method $this addValue($field, $value) Set field value.
 * @method array getValues() Get field values.
 *
 * @package Civi\Api4\Generic
 */
abstract class AbstractCreateAction extends AbstractAction {

  /**
   * Field values to set
   *
   * @var array
   */
  protected $values = [];

  /**
   * @param string $key
   *
   * @return mixed|null
   */
  public function getValue($key) {
    return isset($this->values[$key]) ? $this->values[$key] : NULL;
  }

  /**
   * @throws \API_Exception
   */
  protected function validateValues() {
    $unmatched = $this->checkRequiredFields($this->getValues());
    if ($unmatched) {
      throw new \API_Exception("Mandatory values missing from Api4 {$this->getEntityName()}::{$this->getActionName()}: " . implode(", ", $unmatched), "mandatory_missing", ["fields" => $unmatched]);
    }
  }

}
