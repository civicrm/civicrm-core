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

use Civi\Api4\Query\Api4SelectQuery;
use Civi\Api4\Utils\CoreUtil;

/**
 * Retrieve $ENTITIES based on criteria specified in the `where` parameter.
 *
 * Use the `select` param to determine which fields are returned, defaults to `[*]`.
 *
 * Perform joins on other related entities using a dot notation.
 *
 * @method $this setTranslationMode(string|null $mode)
 * @method string|null getTranslationMode()
 */
class DAOGetAction extends AbstractGetAction {
  use Traits\DAOActionTrait;
  use Traits\GroupAndHavingParamTrait;

  /**
   * Fields to return. Defaults to all standard (non-custom, non-extra) fields `['*']`.
   *
   * The keyword `"custom.*"` selects all custom fields (except those belonging to multi-record custom field sets). So to select all standard + custom fields, select `['*', 'custom.*']`.
   *
   * Multi-record custom field sets are represented as their own entity, so join to that entity to get those custom fields.
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
   * Should we automatically overload the result with translated data?
   * How do we pick the suitable translation?
   *
   * @var string|null
   * @options fuzzy,strict
   */
  protected $translationMode;

  /**
   * @throws \CRM_Core_Exception
   */
  public function _run(Result $result) {
    // Early return if table doesn't exist yet due to pending upgrade
    $baoName = $this->getBaoName();
    if (!$baoName) {
      // In some cases (eg. site spin-up) the code may attempt to call the api before the entity name is registered.
      throw new \CRM_Core_Exception("BAO for {$this->getEntityName()} is not available. This could be a load-order issue");
    }
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
      // Typical case: fetch various fields.
      $query = new Api4SelectQuery($this);
      $rows = $query->run();
      \CRM_Utils_API_HTMLInputCoder::singleton()->decodeRows($rows);
      $result->exchangeArray($rows);

      // No need to fetch count if we got a result set below the limit
      if (!$this->getLimit() || count($rows) < $this->getLimit()) {
        if ($getCount) {
          $result->setCountMatched(count($rows) + $this->getOffset());
          $getCount = FALSE;
        }
        // Set rowCount for backward compatibility.
        $result->rowCount = count($rows) + $this->getOffset();
      }
    }

    if ($getCount) {
      $query = new Api4SelectQuery($this);
      $result->setCountMatched($query->getCount());
      // Set rowCount for backward compatibility.
      $result->rowCount = $result->countMatched();
    }
  }

  /**
   * @param string $fieldName
   * @param string $op
   * @param mixed $value
   * @param bool $isExpression
   * @return $this
   * @throws \CRM_Core_Exception
   */
  public function addWhere(string $fieldName, string $op, $value = NULL, bool $isExpression = FALSE) {
    if (!in_array($op, CoreUtil::getOperators())) {
      throw new \CRM_Core_Exception('Unsupported operator');
    }
    $this->where[] = [$fieldName, $op, $value, $isExpression];
    return $this;
  }

  /**
   * @param string $entity
   *   Name of api entity to join with
   * @param string|bool $type
   *   Should be 'LEFT' or 'INNER' (bool preserved for legacy support)
   * @param string $bridge
   *   Optional name of bridge entity. This can be omitted, as a 3rd argument to the function would be interpreted as the first condition.
   * @param array ...$conditions
   *   One or more conditions, each condition is an array like ['field', '=', 'expr']
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
