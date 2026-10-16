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
   * @param array $records
   * @throws \CRM_Core_Exception
   */
  protected function formatRawValues(&$records) {
    if (empty($records)) {
      return;
    }
    $fields = $this->entityFields();

    // Gather field defaults to be applied to each row
    $defaults = [];
    foreach ($fields as $field) {
      $defaults[$field['name']] = $field['default_value'] ?? NULL;
    }

    // Gather pseudoconstant expressions used by this api request
    $pseudofields = [];
    foreach ($this->getSuffixedFields() as $pseudofield) {
      [$rawField, $suffix] = explode(':', $pseudofield, 2);
      if (!empty($fields[$rawField]['options'])) {
        if (empty($fields[$rawField]['suffixes']) || in_array($suffix, $fields[$rawField]['suffixes'], TRUE)) {
          $pseudofields[$pseudofield] = $rawField;
        }
      }
    }

    // Update each row
    foreach ($records as &$values) {
      $values += $defaults;
      // Fill pseudoconstant expressions with raw values, which will then get replaced by FormattingUtil::formatOutputValues
      foreach ($pseudofields as $pseudofield => $rawField) {
        $values[$pseudofield] ??= $values[$rawField] ?? NULL;
      }
    }
    unset($values);

    // Swap raw values with pseudoconstants
    FormattingUtil::formatOutputValues($records, $fields, $this->getActionName());
  }

  private function getSuffixedFields(): array {
    $suffixed = [];
    foreach ($this->select as $s) {
      if (is_string($s) && str_contains($s, ':')) {
        $suffixed[$s] = $s;
      }
    }
    foreach ($this->orderBy as $o => $dir) {
      if (is_string($o) && str_contains($o, ':')) {
        $suffixed[$o] = $o;
      }
    }
    $this->addWhereSuffixedFields($this->where, $suffixed);
    return $suffixed;
  }

  private function addWhereSuffixedFields(array $clauses, array &$suffixed): void {
    foreach ($clauses as $clause) {
      if (is_array($clause)) {
        if (isset($clause[0]) && is_string($clause[0]) && str_contains($clause[0], ':')) {
          $suffixed[$clause[0]] = $clause[0];
        }
        if (isset($clause[1]) && is_array($clause[1])) {
          $this->addWhereSuffixedFields($clause[1], $suffixed);
        }
      }
    }
  }

}
