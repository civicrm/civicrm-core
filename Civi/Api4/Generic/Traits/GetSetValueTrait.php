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

namespace Civi\Api4\Generic\Traits;

/**
 * Trait for actions with a `$values` array
 */
trait GetSetValueTrait {

  /**
   * Add an item to the values array.
   *
   * @param string $fieldName
   * @param mixed $value
   * @return $this
   */
  public function addValue(string $fieldName, $value) {
    // Prevent accidentally using this function like `addWhere` which takes 3 args.
    if ($value === '=' && func_num_args() > 2) {
      throw new \CRM_Core_Exception('APIv4 function `addValue` incorrectly called with 3 arguments.');
    }
    $this->values[$fieldName] = $value;
    return $this;
  }

  /**
   * Overwrite all values
   *
   * @param array $values
   * @return $this
   */
  public function setValues(array $values) {
    $this->values = $values;
    return $this;
  }

  /**
   * Retrieve a single value
   *
   * @param string $fieldName
   * @return mixed|null
   */
  public function getValue(string $fieldName) {
    return $this->values[$fieldName] ?? NULL;
  }

  /**
   * @return array
   */
  public function getValues() {
    return $this->values;
  }

}
