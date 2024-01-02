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

use Civi\Api4\Utils\FormattingUtil;

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
   * Retrieve a single value, transforming pseudoconstants as necessary
   *
   * @param string $fieldExpr
   * @return mixed|null
   */
  public function getValue(string $fieldExpr) {
    if (array_key_exists($fieldExpr, $this->values)) {
      return $this->values[$fieldExpr];
    }
    // If exact match not found, try pseudoconstants
    [$fieldName, $suffix] = array_pad(explode(':', $fieldExpr), 2, NULL);
    $field = civicrm_api4($this->getEntityName(), 'getFields', [
      'checkPermissions' => FALSE,
      'where' => [['name', '=', $fieldName]],
    ])->first();
    if (empty($field['options'])) {
      return NULL;
    }
    foreach ($this->values as $key => $value) {
      // Resolve pseudoconstant expressions
      if (!array_key_exists($fieldName, $this->values) && str_starts_with($key, "$fieldName:")) {
        $options = FormattingUtil::getPseudoconstantList($field, $key, $this->getValues());
        $this->values[$fieldName] = FormattingUtil::replacePseudoconstant($options, $value, TRUE);
      }
    }
    if ($suffix && array_key_exists($fieldName, $this->values)) {
      $options = FormattingUtil::getPseudoconstantList($field, $fieldExpr, $this->getValues());
      $this->values[$fieldExpr] = FormattingUtil::replacePseudoconstant($options, $this->values[$fieldName]);
    }
    return $this->values[$fieldExpr] ?? NULL;
  }

  /**
   * @return array
   */
  public function getValues() {
    return $this->values;
  }

}
