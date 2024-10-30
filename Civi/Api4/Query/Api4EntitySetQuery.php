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

namespace Civi\Api4\Query;

use Civi\API\Request;
use Civi\Api4\Utils\FormattingUtil;

/**
 * Constructs queries for set operations (UNION, etc).
 */
class Api4EntitySetQuery extends Api4Query {

  private $subqueries = [];

  /**
   * @param \Civi\Api4\Action\EntitySet\Get $api
   */
  public function __construct($api) {
    parent::__construct($api);

    $this->query = \CRM_Utils_SQL_Select::fromSet(['setAlias' => static::MAIN_TABLE_ALIAS]);
    $isAggregate = $this->isAggregateQuery();

    foreach ($api->getSets() as $index => $set) {
      [$type, $entity, $action, $params] = $set + [NULL, NULL, 'get', []];
      $params['checkPermissions'] = $api->getCheckPermissions();
      $params['version'] = 4;
      $apiRequest = Request::create($entity, $action, $params);
      // For non-aggregated queries, add a tracking id so the rows can be identified
      // for output-formatting purposes
      if (!$isAggregate) {
        if (!$apiRequest->getSelect()) {
          $apiRequest->addSelect('*');
        }
        $apiRequest->addSelect($index . ' AS _api_set_index');
      }
      $apiRequest->expandSelectClauseWildcards();
      $subQuery = new Api4SelectQuery($apiRequest);
      $subQuery->forceSelectId = FALSE;
      $subQuery->getSql();
      // Update field aliases of all subqueries to match the first query
      if ($index) {
        $subQuery->selectAliases = array_combine(array_keys($this->getSubquery()->selectAliases), $subQuery->selectAliases);
      }
      $this->subqueries[] = [$type, $subQuery];
    }
  }

  /**
   * Why walk when you can
   *
   * @return array
   */
  public function run(): array {
    $results = $this->getResults();
    foreach ($results as &$result) {
      // Format fields based on which set this row belongs to
      // This index is only available for non-aggregated queries
      $index = $result['_api_set_index'] ?? NULL;
      unset($result['_api_set_index']);
      if (isset($index)) {
        $fieldSpec = $this->getSubquery($index)->apiFieldSpec;
        $selectAliases = $this->getSubquery($index)->selectAliases;
      }
      // Aggregated queries will have to make due with limited field info
      else {
        $fieldSpec = $this->apiFieldSpec;
        $selectAliases = $this->selectAliases;
      }
      FormattingUtil::formatOutputValues($result, $fieldSpec, 'get', $selectAliases);
    }
    return $results;
  }

  private function getSubquery(int $index = 0): Api4SelectQuery {
    return $this->subqueries[$index][1];
  }

  /**
   * Select * from all sets
   */
  protected function buildSelectClause() {
    // Default is to SELECT * FROM (subqueries)
    $select = $this->api->getSelect();
    if ($select === ['*']) {
      $select = [];
    }
    // Add all subqueries to the FROM clause
    foreach ($this->subqueries as $index => $set) {
      [$type, $selectQuery] = $set;
      $this->query->setOp($type, [$selectQuery->getQuery()]);
    }
    // Build apiFieldSpec from the select clause of the first query
    foreach ($this->getSubquery()->selectAliases as $alias => $sql) {
      // If this outer query uses the default of SELECT * then effectively we are selecting
      // all the fields of the first subquery
      if (!$select) {
        $this->selectAliases[$alias] = $alias;
      }
      $expr = SqlExpression::convert($sql);
      $field = $expr->getType() === 'SqlField' ? $this->getSubquery()->getField($expr->getFields()[0]) : NULL;
      $this->addSpecField($alias, [
        'sql_name' => "`$alias`",
        'entity' => $field['entity'] ?? NULL,
        'data_type' => $field['data_type'] ?? $expr::getDataType(),
      ]);
    }
    // Parse select clause if not using default of *
    foreach ($select as $item) {
      $expr = SqlExpression::convert($item, TRUE);
      $alias = $expr->getAlias();
      $this->selectAliases[$alias] = $expr->getExpr();
      $this->query->select($expr->render($this, TRUE));
    }
  }

  /**
   * @param string $expr
   * @return array|null
   */
  public function getField($expr) {
    $col = strpos($expr, ':');
    $fieldName = $col ? substr($expr, 0, $col) : $expr;
    return $this->apiFieldSpec[$fieldName] ?? NULL;
  }

  protected function buildWhereClause() {
    foreach ($this->getWhere() as $clause) {
      $sql = $this->treeWalkClauses($clause, 'WHERE');
      if ($sql) {
        $this->query->where($sql);
      }
    }
  }

  /**
   * Add HAVING clause to query
   *
   * Every expression referenced must also be in the SELECT clause.
   */
  protected function buildHavingClause() {
    foreach ($this->getHaving() as $clause) {
      $sql = $this->treeWalkClauses($clause, 'HAVING');
      if ($sql) {
        $this->query->having($sql);
      }
    }
  }

  /**
   * Add ORDER BY to query
   */
  protected function buildOrderBy() {
    foreach ($this->getOrderBy() as $item => $dir) {
      if ($dir !== 'ASC' && $dir !== 'DESC') {
        throw new \CRM_Core_Exception("Invalid sort direction. Cannot order by $item $dir");
      }
      if (!empty($this->selectAliases[$item])) {
        $column = '`' . $item . '`';
      }
      else {
        $expr = $this->getExpression($item);
        $column = $this->renderExpr($expr);
      }
      $this->query->orderBy("$column $dir");
    }
  }

  /**
   * Returns rendered expression or alias if it is already aliased in the SELECT clause.
   *
   * @param $expr
   * @return mixed|string
   */
  protected function renderExpr($expr) {
    $exprVal = explode(':', $expr->getExpr())[0];
    // If this expression is already aliased in the select clause, use the existing alias.
    foreach ($this->selectAliases as $alias => $selectVal) {
      $selectVal = explode(':', $selectVal)[0];
      if ($exprVal === $selectVal) {
        return "`$alias`";
      }
    }
    return $expr->render($this);
  }

}
