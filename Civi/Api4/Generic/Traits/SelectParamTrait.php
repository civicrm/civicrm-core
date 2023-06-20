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

use Civi\Api4\Utils\SelectUtil;

/**
 * @method $this setSelect(array $selects) Set array of fields to be selected (wildcard * allowed)
 * @method array getSelect()
 * @package Civi\Api4\Generic
 */
trait SelectParamTrait {

  /**
   * Fields to return for each $ENTITY. Defaults to all fields `[*]`.
   *
   * Use the * wildcard by itself to select all available fields, or use it to match similarly-named fields.
   * E.g. `is_*` will match fields named is_primary, is_active, etc.
   *
   * Set to `["row_count"]` to return only the number of $ENTITIES found.
   *
   * @var array
   */
  protected $select = [];

  /**
   * Add one or more fields to be selected (wildcard * allowed)
   * @param string ...$fieldNames
   * @return $this
   */
  public function addSelect(string ...$fieldNames) {
    $this->select = array_merge($this->select, $fieldNames);
    return $this;
  }

  /**
   * Adds all standard fields matched by the * wildcard
   *
   * Note: this function only deals with simple wildcard expressions.
   * It ignores those containing special characters like dots or parentheses,
   * they are handled separately in Api4SelectQuery.
   *
   * @throws \CRM_Core_Exception
   */
  public function expandSelectClauseWildcards() {
    if (!$this->select) {
      $this->select = ['*'];
    }
    // Get expressions containing wildcards but no dots or parentheses
    $wildFields = array_filter($this->select, function($item) {
      return strpos($item, '*') !== FALSE && strpos($item, '.') === FALSE && strpos($item, '(') === FALSE && strpos($item, ' ') === FALSE;
    });
    if ($wildFields) {
      // Wildcards should not match "Extra" fields
      $standardFields = array_filter(array_map(function($field) {
        return $field['type'] === 'Extra' ? NULL : $field['name'];
      }, $this->entityFields()));
      foreach ($wildFields as $item) {
        $pos = array_search($item, array_values($this->select));
        $matches = SelectUtil::getMatchingFields($item, $standardFields);
        array_splice($this->select, $pos, 1, $matches);
      }
    }
    $this->select = array_unique($this->select);
  }

}
