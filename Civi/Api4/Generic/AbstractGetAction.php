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

use Civi\Api4\Utils\CoreUtil;

/**
 * Base class for all `Get` api actions.
 *
 * @package Civi\Api4\Generic
 */
abstract class AbstractGetAction extends AbstractQueryAction {

  use Traits\SelectParamTrait;

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
   * This is of questionable value, but locked in by tests so we're stuck with it:
   * APIv4.get automatically adds certain default conditions to the WHERE clause,
   * e.g. `domain_id = current_domain` or `is_template = 0`.
   *
   * For the source of these defaults,
   * @see GetActionDefaultsProvider
   *
   * Note: this will skip adding field defaults when fetching records by a unique field like name or id,
   * or if that field has already been added to the where clause.
   *
   * @throws \CRM_Core_Exception
   */
  public function setDefaultWhereClause() {
    // If the entity is being fetched by unique id or a unique combo, disable these defaults
    if (!$this->isFetchByUniqueIdentifier()) {
      $fields = $this->entityFields();
      foreach ($fields as $field) {
        if (isset($field['default_value']) && !$this->_whereContains($field['name'])) {
          $this->addWhere($field['name'], '=', $field['default_value']);
        }
      }
    }
  }

  /**
   * Check whether this get operation is fetching a single record by id, name, etc.
   *
   * @return bool
   */
  protected function isFetchByUniqueIdentifier(): bool {
    // Collect unique indices, starting with the primary key
    $uniqueIndices = [
      CoreUtil::getInfoItem($this->getEntityName(), 'primary_key'),
    ];
    // Get other unique index combos
    try {
      $entity = \Civi::entity($this->getEntityName());
      foreach ($entity->getMeta('indices') ?? [] as $index) {
        if (!empty($index['unique']) && !empty($index['fields'])) {
          $uniqueIndices[] = array_keys($index['fields']);
        }
      }
    }
    catch (\Exception $e) {
    }
    foreach ($uniqueIndices as $indexFields) {
      $fetchByUnique = TRUE;
      foreach ($indexFields ?: [] as $fieldName) {
        if (!$this->_itemsToGet($fieldName)) {
          $fetchByUnique = FALSE;
        }
      }
      if ($fetchByUnique) {
        return TRUE;
      }
    }
    return FALSE;
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
      if ($clause[0] == $field && (in_array($clause[1], ['=', 'IN'], TRUE) || ($clause[1] == 'LIKE' && !(is_string($clause[2]) && str_contains($clause[2], '%'))))) {
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
   * and this function will return TRUE for field expressions that don't contain a :pseudoconstant suffix.
   *
   * @param string ...$fieldNames
   *   One or more field names to check (uses OR if multiple)
   * @return bool
   *   Returns true if any given fields are in use.
   */
  protected function _isFieldSelected(string ...$fieldNames) {
    if ((!$this->select && !str_contains($fieldNames[0], ':')) || array_intersect($fieldNames, array_merge($this->select, array_keys($this->orderBy)))) {
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
        if (is_array($clause[1])) {
          if ($this->_whereContains($fieldName, $clause[1])) {
            return TRUE;
          }
        }
        elseif (in_array($clause[0], $fieldName)) {
          return TRUE;
        }
      }
    }
    return FALSE;
  }

}
