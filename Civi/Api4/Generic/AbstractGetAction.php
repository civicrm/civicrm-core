<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
 * $Id$
 *
 */


namespace Civi\Api4\Generic;

/**
 * Base class for all "Get" api actions.
 *
 * @package Civi\Api4\Generic
 *
 * @method $this addSelect(string $select)
 * @method $this setSelect(array $selects)
 * @method array getSelect()
 */
abstract class AbstractGetAction extends AbstractQueryAction {

  /**
   * Fields to return. Defaults to all fields.
   *
   * Set to ["row_count"] to return only the number of items found.
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
   * Helper to parse the WHERE param for getRecords to perform simple pre-filtering.
   *
   * This is intended to optimize some common use-cases e.g. calling the api to get
   * one or more records by name or id.
   *
   * Ex: If getRecords fetches a long list of items each with a unique name,
   * but the user has specified a single record to retrieve, you can optimize the call
   * by checking $this->_itemsToGet('name') and only fetching the item(s) with that name.
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
   * Helper to see if a field should be selected by the getRecords function.
   *
   * Checks the SELECT, WHERE and ORDER BY params to see what fields are needed.
   *
   * Note that if no SELECT clause has been set then all fields should be selected
   * and this function will always return TRUE.
   *
   * @param string $field
   * @return bool
   */
  protected function _isFieldSelected($field) {
    if (!$this->select || in_array($field, $this->select) || isset($this->orderBy[$field])) {
      return TRUE;
    }
    return $this->_whereContains($field);
  }

  /**
   * Walk through the where clause and check if a field is in use.
   *
   * @param string $field
   * @param array $clauses
   * @return bool
   */
  protected function _whereContains($field, $clauses = NULL) {
    if ($clauses === NULL) {
      $clauses = $this->where;
    }
    foreach ($clauses as $clause) {
      if (is_array($clause) && is_string($clause[0])) {
        if ($clause[0] == $field) {
          return TRUE;
        }
        elseif (is_array($clause[1])) {
          return $this->_whereContains($field, $clause[1]);
        }
      }
    }
    return FALSE;
  }

}
