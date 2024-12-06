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
 * Helper function for formatting optionvalue/pseudoconstant fields
 *
 * @package Civi\Api4\Generic
 */
trait PseudoconstantOutputTrait {

  /**
   * Evaluate :pseudoconstant suffix expressions and replace raw values with option values
   *
   * @param $records
   * @throws \CRM_Core_Exception
   */
  protected function formatRawValues(&$records) {
    // Pad $records and $fields with pseudofields
    $fields = $this->entityFields();
    foreach ($records as &$values) {
      foreach ($this->entityFields() as $field) {
        $values += [$field['name'] => $field['default_value'] ?? NULL];
        if (!empty($field['options'])) {
          foreach ($field['suffixes'] ?? array_keys(\CRM_Core_SelectValues::optionAttributes()) as $suffix) {
            $pseudofield = $field['name'] . ':' . $suffix;
            if (!isset($values[$pseudofield]) && isset($values[$field['name']]) && $this->_isFieldSelected($pseudofield)) {
              $values[$pseudofield] = $values[$field['name']];
            }
          }
        }
      }
      // Swap raw values with pseudoconstants
      FormattingUtil::formatOutputValues($values, $fields, $this->getActionName());
    }
  }

}
