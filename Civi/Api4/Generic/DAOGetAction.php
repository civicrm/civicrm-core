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
use Civi\Api4\Utils\CoreUtil;

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
   * Fields to return. Defaults to all non-custom fields `['*']`.
   *
   * The keyword `"custom.*"` selects all custom fields. So to select all core + custom fields, select `['*', 'custom.*']`.
   *
   * Use the dot notation to perform joins in the select clause, e.g. selecting `['*', 'contact.*']` from `Email::get()`
   * will select all fields for the email + all fields for the related contact.
   *
   * @var array
   * @inheritDoc
   */
  protected $select = [];

  /**
   * Joins to other entities.
   *
   * Each join is an array of properties:
   *
   * ```
   * [Entity, Required, Bridge, [field, op, value]...]
   * ```
   *
   * - `Entity`: the name of the api entity to join onto.
   * - `Required`: `TRUE` for an `INNER JOIN`, `FALSE` for a `LEFT JOIN`.
   * - `Bridge` (optional): Name of a Bridge to incorporate into the join.
   * - `[field, op, value]...`: zero or more conditions for the ON clause, using the same nested format as WHERE and HAVING
   *     but with the difference that "value" is interpreted as an expression (e.g. can be the name of a field).
   *     Enclose literal values with quotes.
   *
   * @var array
   * @see \Civi\Api4\Generic\Traits\EntityBridge
   */
  protected $join = [];

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
    // Early return if table doesn't exist yet due to pending upgrade
    $baoName = $this->getBaoName();
    if (!$baoName::tableHasBeenAdded()) {
      \Civi::log()->warning("Could not read from {$this->getEntityName()} before table has been added. Upgrade required.", ['civi.tag' => 'upgrade_needed']);
      return;
    }

    $this->setDefaultWhereClause();
    $this->expandSelectClauseWildcards();
    $this->getObjects($result);
  }

  /**
   * @param \Civi\Api4\Generic\Result $result
   */
  protected function getObjects(Result $result) {
    $getCount = in_array('row_count', $this->getSelect());
    $onlyCount = $this->getSelect() === ['row_count'];

    if (!$onlyCount) {
      $query = new Api4SelectQuery($this);
      $rows = $query->run();
      \CRM_Utils_API_HTMLInputCoder::singleton()->decodeRows($rows);
      $result->exchangeArray($rows);
      // No need to fetch count if we got a result set below the limit
      if (!$this->getLimit() || count($rows) < $this->getLimit()) {
        $result->rowCount = count($rows) + $this->getOffset();
        $getCount = FALSE;
      }
    }
    if ($getCount) {
      $query = new Api4SelectQuery($this);
      $result->rowCount = $query->getCount();
    }
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
    if (!in_array($op, CoreUtil::getOperators())) {
      throw new \API_Exception('Unsupported operator');
    }
    $this->having[] = [$expr, $op, $value];
    return $this;
  }

  /**
   * @param string $entity
   * @param string|bool $type
   * @param string $bridge
   * @param array ...$conditions
   * @return DAOGetAction
   */
  public function addJoin(string $entity, $type = 'LEFT', $bridge = NULL, ...$conditions): DAOGetAction {
    if ($bridge) {
      array_unshift($conditions, $bridge);
    }
    array_unshift($conditions, $entity, $type);
    $this->join[] = $conditions;
    return $this;
  }

  /**
   * @param array $join
   * @return DAOGetAction
   */
  public function setJoin(array $join): DAOGetAction {
    $this->join = $join;
    return $this;
  }

  /**
   * @return array
   */
  public function getJoin(): array {
    return $this->join;
  }

}
