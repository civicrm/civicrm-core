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
 * $Id$
 *
 */


namespace Civi\Api4\Generic;

use Civi\Api4\Utils\SelectUtil;

/**
 * Base class for all `Get` api actions.
 *
 * @package Civi\Api4\Generic
 *
 * @method $this setSelect(array $selects) Set array of fields to be selected (wildcard * allowed)
 * @method array getSelect()
 */
abstract class AbstractGetAction extends AbstractQueryAction {

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
   * Only return the number of found items.
   *
   * @return $this
   */
  public function selectRowCount() {
    $this->select = ['row_count'];
    return $this;
  }

  /**
   * Adds field defaults to the where clause.
   *
   * Note: it will skip adding field defaults when fetching records by id,
   * or if that field has already been added to the where clause.
   *
   * @throws \API_Exception
   */
  protected function setDefaultWhereClause() {
    if (!$this->_itemsToGet('id')) {
      $fields = $this->entityFields();
      foreach ($fields as $field) {
        if (isset($field['default_value']) && !$this->_whereContains($field['name'])) {
          $this->addWhere($field['name'], '=', $field['default_value']);
        }
      }
    }
  }

  /**
   * Adds all fields matched by the * wildcard
   *
   * @throws \API_Exception
   */
  protected function expandSelectClauseWildcards() {
    foreach ($this->select as $item) {
      if (strpos($item, '*') !== FALSE && strpos($item, '.') === FALSE) {
        $this->select = array_diff($this->select, [$item]);
        $this->select = array_unique(array_merge($this->select, SelectUtil::getMatchingFields($item, array_column($this->entityFields(), 'name'))));
      }
    }
  }

  /**
   * Helper to parse the WHERE param for getRecords to perform simple pre-filtering.
   *
   * This is intended to optimize some common use-cases e.g. calling the api to get
   * one or more records by name or id.
   *
   * Ex: If getRecords fetches a long list of items each with a unique name,
   * but the user has specified a single record to retrieve, you can optimize the call
   * by checking `$this->_itemsToGet('name')` and only fetching the item(s) with that name.
   *
   * @param string $field
   * @return array|null
   */
  protected function _itemsToGet($field) {
    foreach ($this->where as $clause) {
      // Look for exact-match operators (=, IN, or LIKE with no wildcard)
      if ($clause[0] == $field && (in_array($clause[1], ['=', 'IN']) || ($clause[1] == 'LIKE' && !(is_string($clause[2]) && strpos($clause[2], '%') !== FALSE)))) {
        return (array) $clause[2];
      }
    }
    return NULL;
  }

  /**
   * Helper to see if field(s) should be selected by the getRecords function.
   *
   * Checks the SELECT, WHERE and ORDER BY params to see what fields are needed.
   *
   * Note that if no SELECT clause has been set then all fields should be selected
   * and this function will always return TRUE.
   *
   * @param string ...$fieldNames
   *   One or more field names to check (uses OR if multiple)
   * @return bool
   *   Returns true if any given fields are in use.
   */
  protected function _isFieldSelected(string ...$fieldNames) {
    if (!$this->select || array_intersect($fieldNames, array_merge($this->select, array_keys($this->orderBy)))) {
      return TRUE;
    }
    return $this->_whereContains($fieldNames);
  }

  /**
   * Walk through the where clause and check if field(s) are in use.
   *
   * @param string|array $fieldName
   *   A single fieldName or an array of names (uses OR if multiple)
   * @param array $clauses
   * @return bool
   *   Returns true if any given fields are found in the where clause.
   */
  protected function _whereContains($fieldName, $clauses = NULL) {
    if ($clauses === NULL) {
      $clauses = $this->where;
    }
    $fieldName = (array) $fieldName;
    foreach ($clauses as $clause) {
      if (is_array($clause) && is_string($clause[0])) {
        if (in_array($clause[0], $fieldName)) {
          return TRUE;
        }
        elseif (is_array($clause[1])) {
          return $this->_whereContains($fieldName, $clause[1]);
        }
      }
    }
    return FALSE;
  }

  /**
   * Add one or more fields to be selected (wildcard * allowed)
   * @param string ...$fieldNames
   * @return $this
   */
  public function addSelect(string ...$fieldNames) {
    $this->select = array_merge($this->select, $fieldNames);
    return $this;
  }

}
