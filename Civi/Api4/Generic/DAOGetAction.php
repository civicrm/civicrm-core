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
 */


namespace Civi\Api4\Generic;

use Civi\Api4\Query\Api4SelectQuery;

/**
 * Retrieve $ENTITIES based on criteria specified in the `where` parameter.
 *
 * Use the `select` param to determine which fields are returned, defaults to `[*]`.
 *
 * Perform joins on other related entities using a dot notation.
 *
 * @method $this setHaving(array $clauses)
 * @method array getHaving()
 */
class DAOGetAction extends AbstractGetAction {
  use Traits\DAOActionTrait;

  /**
   * Fields to return. Defaults to all non-custom fields `[*]`.
   *
   * Use the dot notation to perform joins in the select clause, e.g. selecting `['*', 'contact.*']` from `Email::get()`
   * will select all fields for the email + all fields for the related contact.
   *
   * @var array
   * @inheritDoc
   */
  protected $select = [];

  /**
   * Field(s) by which to group the results.
   *
   * @var array
   */
  protected $groupBy = [];

  /**
   * Clause for filtering results after grouping and filters are applied.
   *
   * Each expression should correspond to an item from the SELECT array.
   *
   * @var array
   */
  protected $having = [];

  public function _run(Result $result) {
    $this->setDefaultWhereClause();
    $this->expandSelectClauseWildcards();
    $result->exchangeArray($this->getObjects());
  }

  /**
   * @return array|int
   */
  protected function getObjects() {
    $query = new Api4SelectQuery($this);

    $result = $query->run();
    if (is_array($result)) {
      \CRM_Utils_API_HTMLInputCoder::singleton()->decodeRows($result);
    }
    return $result;
  }

  /**
   * @return array
   */
  public function getGroupBy(): array {
    return $this->groupBy;
  }

  /**
   * @param array $groupBy
   * @return $this
   */
  public function setGroupBy(array $groupBy) {
    $this->groupBy = $groupBy;
    return $this;
  }

  /**
   * @param string $field
   * @return $this
   */
  public function addGroupBy(string $field) {
    $this->groupBy[] = $field;
    return $this;
  }

  /**
   * @param string $expr
   * @param string $op
   * @param mixed $value
   * @return $this
   * @throws \API_Exception
   */
  public function addHaving(string $expr, string $op, $value = NULL) {
    if (!in_array($op, \CRM_Core_DAO::acceptedSQLOperators())) {
      throw new \API_Exception('Unsupported operator');
    }
    $this->having[] = [$expr, $op, $value];
    return $this;
  }

}
