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

use Civi\API\SelectQuery;
use Civi\Api4\Service\Schema\Joinable\CustomGroupJoinable;
use Civi\Api4\Utils\FormattingUtil;
use Civi\Api4\Utils\CoreUtil;
use Civi\Api4\Utils\SelectUtil;

/**
 * A query `node` may be in one of three formats:
 *
 * * leaf: [$fieldName, $operator, $criteria]
 * * negated: ['NOT', $node]
 * * branch: ['OR|NOT', [$node, $node, ...]]
 *
 * Leaf operators are one of:
 *
 * * '=', '<=', '>=', '>', '<', 'LIKE', "<>", "!=",
 * * "NOT LIKE", 'IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN',
 * * 'IS NOT NULL', or 'IS NULL'.
 */
class Api4SelectQuery extends SelectQuery {

  /**
   * @var int
   */
  protected $apiVersion = 4;

  /**
   * @var array
   * [alias => expr][]
   */
  protected $selectAliases = [];

  /**
   * If set to an array, this will start collecting debug info.
   *
   * @var null|array
   */
  public $debugOutput = NULL;

  /**
   * @var array
   */
  public $groupBy = [];

  public $forceSelectId = TRUE;

  /**
   * @var array
   */
  public $having = [];

  /**
   * @param \Civi\Api4\Generic\DAOGetAction $apiGet
   */
  public function __construct($apiGet) {
    $this->entity = $apiGet->getEntityName();
    $this->checkPermissions = $apiGet->getCheckPermissions();
    $this->select = $apiGet->getSelect();
    $this->where = $apiGet->getWhere();
    $this->groupBy = $apiGet->getGroupBy();
    $this->orderBy = $apiGet->getOrderBy();
    $this->limit = $apiGet->getLimit();
    $this->offset = $apiGet->getOffset();
    $this->having = $apiGet->getHaving();
    // Always select ID of main table unless grouping is used
    $this->forceSelectId = !$this->groupBy;
    if ($apiGet->getDebug()) {
      $this->debugOutput =& $apiGet->_debugOutput;
    }
    foreach ($apiGet->entityFields() as $field) {
      $this->entityFieldNames[] = $field['name'];
      $field['sql_name'] = '`' . self::MAIN_TABLE_ALIAS . '`.`' . $field['column_name'] . '`';
      $this->addSpecField($field['name'], $field);
    }

    $baoName = CoreUtil::getBAOFromApiName($this->entity);
    $this->constructQueryObject();

    // Add ACLs first to avoid redundant subclauses
    $this->query->where($this->getAclClause(self::MAIN_TABLE_ALIAS, $baoName));

    // Add explicit joins. Other joins implied by dot notation may be added later
    $this->addExplicitJoins($apiGet->getJoin());
  }

  /**
   * Builds final sql statement after all params are set.
   *
   * @return string
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function getSql() {
    $this->buildSelectClause();
    $this->buildWhereClause();
    $this->buildOrderBy();
    $this->buildLimit();
    $this->buildGroupBy();
    $this->buildHavingClause();
    return $this->query->toSQL();
  }

  /**
   * Why walk when you can
   *
   * @return array|int
   */
  public function run() {
    $results = [];
    $sql = $this->getSql();
    if (is_array($this->debugOutput)) {
      $this->debugOutput['sql'][] = $sql;
    }
    $query = \CRM_Core_DAO::executeQuery($sql);
    while ($query->fetch()) {
      if (in_array('row_count', $this->select)) {
        $results[]['row_count'] = (int) $query->c;
        break;
      }
      $result = [];
      foreach ($this->selectAliases as $alias => $expr) {
        $returnName = $alias;
        $alias = str_replace('.', '_', $alias);
        $result[$returnName] = property_exists($query, $alias) ? $query->$alias : NULL;
      }
      $results[] = $result;
    }
    FormattingUtil::formatOutputValues($results, $this->getApiFieldSpec(), $this->getEntity());
    return $results;
  }

  protected function buildSelectClause() {
    // An empty select is the same as *
    if (empty($this->select)) {
      $this->select = $this->entityFieldNames;
    }
    elseif (in_array('row_count', $this->select)) {
      $this->query->select("COUNT(*) AS `c`");
      return;
    }
    else {
      if ($this->forceSelectId) {
        $this->select = array_merge(['id'], $this->select);
      }

      // Expand wildcards in joins (the api wrapper already expanded non-joined wildcards)
      $wildFields = array_filter($this->select, function($item) {
        return strpos($item, '*') !== FALSE && strpos($item, '.') !== FALSE && strpos($item, '(') === FALSE && strpos($item, ' ') === FALSE;
      });
      foreach ($wildFields as $item) {
        $pos = array_search($item, array_values($this->select));
        $this->autoJoinFK($item);
        $matches = SelectUtil::getMatchingFields($item, array_keys($this->apiFieldSpec));
        array_splice($this->select, $pos, 1, $matches);
      }
      $this->select = array_unique($this->select);
    }
    foreach ($this->select as $item) {
      $expr = SqlExpression::convert($item, TRUE);
      $valid = TRUE;
      foreach ($expr->getFields() as $fieldName) {
        $field = $this->getField($fieldName);
        // Remove expressions with unknown fields without raising an error
        if (!$field) {
          $this->select = array_diff($this->select, [$item]);
          if (is_array($this->debugOutput)) {
            $this->debugOutput['undefined_fields'][] = $fieldName;
          }
          $valid = FALSE;
        }
      }
      if ($valid) {
        $alias = $expr->getAlias();
        if ($alias != $expr->getExpr() && isset($this->apiFieldSpec[$alias])) {
          throw new \API_Exception('Cannot use existing field name as alias');
        }
        $this->selectAliases[$alias] = $expr->getExpr();
        $this->query->select($expr->render($this->apiFieldSpec) . " AS `$alias`");
      }
    }
  }

  /**
   * @inheritDoc
   */
  protected function buildWhereClause() {
    foreach ($this->where as $clause) {
      $this->query->where($this->treeWalkClauses($clause, 'WHERE'));
    }
  }

  /**
   * Build HAVING clause.
   *
   * Every expression referenced must also be in the SELECT clause.
   */
  protected function buildHavingClause() {
    foreach ($this->having as $clause) {
      $this->query->having($this->treeWalkClauses($clause, 'HAVING'));
    }
  }

  /**
   * @inheritDoc
   */
  protected function buildOrderBy() {
    foreach ($this->orderBy as $item => $dir) {
      if ($dir !== 'ASC' && $dir !== 'DESC') {
        throw new \API_Exception("Invalid sort direction. Cannot order by $item $dir");
      }
      $expr = $this->getExpression($item);
      $column = $expr->render($this->apiFieldSpec);

      // Use FIELD() function to sort on pseudoconstant values
      $suffix = strstr($item, ':');
      if ($suffix && $expr->getType() === 'SqlField') {
        $field = $this->getField($item);
        $options = FormattingUtil::getPseudoconstantList($field['entity'], $field['name'], substr($suffix, 1));
        if ($options) {
          asort($options);
          $column = "FIELD($column,'" . implode("','", array_keys($options)) . "')";
        }
      }
      $this->query->orderBy("$column $dir");
    }
  }

  /**
   * @throws \CRM_Core_Exception
   */
  protected function buildLimit() {
    if (!empty($this->limit) || !empty($this->offset)) {
      // If limit is 0, mysql will actually return 0 results. Instead set to maximum possible.
      $this->query->limit($this->limit ?: '18446744073709551615', $this->offset);
    }
  }

  /**
   * Adds GROUP BY clause to query
   */
  protected function buildGroupBy() {
    foreach ($this->groupBy as $item) {
      $this->query->groupBy($this->getExpression($item)->render($this->apiFieldSpec));
    }
  }

  /**
   * Recursively validate and transform a branch or leaf clause array to SQL.
   *
   * @param array $clause
   * @param string $type
   *   WHERE|HAVING|ON
   * @return string SQL where clause
   *
   * @throws \API_Exception
   * @uses composeClause() to generate the SQL etc.
   */
  protected function treeWalkClauses($clause, $type) {
    switch ($clause[0]) {
      case 'OR':
      case 'AND':
        // handle branches
        if (count($clause[1]) === 1) {
          // a single set so AND|OR is immaterial
          return $this->treeWalkClauses($clause[1][0], $type);
        }
        else {
          $sql_subclauses = [];
          foreach ($clause[1] as $subclause) {
            $sql_subclauses[] = $this->treeWalkClauses($subclause, $type);
          }
          return '(' . implode("\n" . $clause[0], $sql_subclauses) . ')';
        }

      case 'NOT':
        // If we get a group of clauses with no operator, assume AND
        if (!is_string($clause[1][0])) {
          $clause[1] = ['AND', $clause[1]];
        }
        return 'NOT (' . $this->treeWalkClauses($clause[1], $type) . ')';

      default:
        return $this->composeClause($clause, $type);
    }
  }

  /**
   * Validate and transform a leaf clause array to SQL.
   * @param array $clause [$fieldName, $operator, $criteria]
   * @param string $type
   *   WHERE|HAVING|ON
   * @return string SQL
   * @throws \API_Exception
   * @throws \Exception
   */
  protected function composeClause(array $clause, string $type) {
    // Pad array for unary operators
    list($expr, $operator, $value) = array_pad($clause, 3, NULL);
    if (!in_array($operator, \CRM_Core_DAO::acceptedSQLOperators(), TRUE)) {
      throw new \API_Exception('Illegal operator');
    }

    // For WHERE clause, expr must be the name of a field.
    if ($type === 'WHERE') {
      $field = $this->getField($expr, TRUE);
      FormattingUtil::formatInputValue($value, $expr, $field);
      $fieldAlias = $field['sql_name'];
    }
    // For HAVING, expr must be an item in the SELECT clause
    elseif ($type === 'HAVING') {
      // Expr references a fieldName or alias
      if (isset($this->selectAliases[$expr])) {
        $fieldAlias = $expr;
        // Attempt to format if this is a real field
        if (isset($this->apiFieldSpec[$expr])) {
          FormattingUtil::formatInputValue($value, $expr, $this->apiFieldSpec[$expr]);
        }
      }
      // Expr references a non-field expression like a function; convert to alias
      elseif (in_array($expr, $this->selectAliases)) {
        $fieldAlias = array_search($expr, $this->selectAliases);
      }
      // If either the having or select field contains a pseudoconstant suffix, match and perform substitution
      else {
        list($fieldName) = explode(':', $expr);
        foreach ($this->selectAliases as $selectAlias => $selectExpr) {
          list($selectField) = explode(':', $selectAlias);
          if ($selectAlias === $selectExpr && $fieldName === $selectField && isset($this->apiFieldSpec[$fieldName])) {
            FormattingUtil::formatInputValue($value, $expr, $this->apiFieldSpec[$fieldName]);
            $fieldAlias = $selectAlias;
            break;
          }
        }
      }
      if (!isset($fieldAlias)) {
        throw new \API_Exception("Invalid expression in HAVING clause: '$expr'. Must use a value from SELECT clause.");
      }
      $fieldAlias = '`' . $fieldAlias . '`';
    }
    elseif ($type === 'ON') {
      $expr = $this->getExpression($expr);
      $fieldName = count($expr->getFields()) === 1 ? $expr->getFields()[0] : NULL;
      $fieldAlias = $expr->render($this->apiFieldSpec);
      if (is_string($value)) {
        $valExpr = $this->getExpression($value);
        if ($fieldName && $valExpr->getType() === 'SqlString') {
          FormattingUtil::formatInputValue($valExpr->expr, $fieldName, $this->apiFieldSpec[$fieldName]);
        }
        return sprintf('%s %s %s', $fieldAlias, $operator, $valExpr->render($this->apiFieldSpec));
      }
      elseif ($fieldName) {
        FormattingUtil::formatInputValue($value, $fieldName, $this->apiFieldSpec[$fieldName]);
      }
    }

    $sql_clause = \CRM_Core_DAO::createSQLFilter($fieldAlias, [$operator => $value]);
    if ($sql_clause === NULL) {
      throw new \API_Exception("Invalid value in $type clause for '$expr'");
    }
    return $sql_clause;
  }

  /**
   * @param string $expr
   * @return SqlExpression
   * @throws \API_Exception
   */
  protected function getExpression(string $expr) {
    $sqlExpr = SqlExpression::convert($expr);
    foreach ($sqlExpr->getFields() as $fieldName) {
      $this->getField($fieldName, TRUE);
    }
    return $sqlExpr;
  }

  /**
   * @inheritDoc
   */
  protected function getFields() {
    return $this->apiFieldSpec;
  }

  /**
   * Fetch a field from the getFields list
   *
   * @param string $expr
   * @param bool $strict
   *   In strict mode, this will throw an exception if the field doesn't exist
   *
   * @return string|null
   * @throws \API_Exception
   */
  public function getField($expr, $strict = FALSE) {
    // If the expression contains a pseudoconstant filter like activity_type_id:label,
    // strip it to look up the base field name, then add the field:filter key to apiFieldSpec
    $col = strpos($expr, ':');
    $fieldName = $col ? substr($expr, 0, $col) : $expr;
    // Perform join if field not yet available - this will add it to apiFieldSpec
    if (!isset($this->apiFieldSpec[$fieldName]) && strpos($fieldName, '.')) {
      $this->autoJoinFK($fieldName);
    }
    $field = $this->apiFieldSpec[$fieldName] ?? NULL;
    if ($strict && !$field) {
      throw new \API_Exception("Invalid field '$fieldName'");
    }
    $this->apiFieldSpec[$expr] = $field;
    return $field;
  }

  /**
   * Join onto other entities as specified by the api call.
   *
   * @param $joins
   * @throws \API_Exception
   * @throws \Civi\API\Exception\NotImplementedException
   */
  private function addExplicitJoins($joins) {
    foreach ($joins as $join) {
      // First item in the array is the entity name
      $entity = array_shift($join);
      // Which might contain an alias. Split on the keyword "AS"
      list($entity, $alias) = array_pad(explode(' AS ', $entity), 2, NULL);
      // Ensure alias is a safe string, and supply default if not given
      $alias = $alias ? \CRM_Utils_String::munge($alias) : strtolower($entity);
      // First item in the array is a boolean indicating if the join is required (aka INNER or LEFT).
      // The rest are join conditions.
      $side = array_shift($join) ? 'INNER' : 'LEFT';
      $joinEntityGet = \Civi\API\Request::create($entity, 'get', ['version' => 4, 'checkPermissions' => $this->checkPermissions]);
      foreach ($joinEntityGet->entityFields() as $field) {
        $field['sql_name'] = '`' . $alias . '`.`' . $field['column_name'] . '`';
        $field['is_join'] = TRUE;
        $this->addSpecField($alias . '.' . $field['name'], $field);
      }
      $conditions = $this->getJoinConditions($entity, $alias);
      foreach (array_filter($join) as $clause) {
        $conditions[] = $this->treeWalkClauses($clause, 'ON');
      }
      $tableName = CoreUtil::getTableName($entity);
      $this->join($side, $tableName, $alias, $conditions);
    }
  }

  /**
   * Supply conditions for an explicit join.
   *
   * @param $entity
   * @param $alias
   * @return array
   */
  private function getJoinConditions($entity, $alias) {
    $conditions = [];
    // getAclClause() expects a stack of 1-to-1 join fields to help it dedupe, but this is more flexible,
    // so unless this is a direct 1-to-1 join with the main entity, we'll just hack it
    // with a padded empty stack to bypass its deduping.
    $stack = [NULL, NULL];
    foreach ($this->apiFieldSpec as $name => $field) {
      if ($field['entity'] !== $entity && $field['fk_entity'] === $entity) {
        $conditions[] = $this->treeWalkClauses([$name, '=', "$alias.id"], 'ON');
      }
      elseif (strpos($name, "$alias.") === 0 && substr_count($name, '.') === 1 &&  $field['fk_entity'] === $this->entity) {
        $conditions[] = $this->treeWalkClauses([$name, '=', 'id'], 'ON');
        $stack = ['id'];
      }
    }
    // Hmm, if we came up with > 1 condition, then it's ambiguous how it should be joined so we won't return anything but the generic ACLs
    if (count($conditions) > 1) {
      $stack = [NULL, NULL];
      $conditions = [];
    }
    $baoName = CoreUtil::getBAOFromApiName($entity);
    $acls = array_values($this->getAclClause($alias, $baoName, $stack));
    return array_merge($acls, $conditions);
  }

  /**
   * Joins a path and adds all fields in the joined entity to apiFieldSpec
   *
   * @param $key
   * @throws \API_Exception
   * @throws \Exception
   */
  protected function autoJoinFK($key) {
    if (isset($this->apiFieldSpec[$key])) {
      return;
    }

    $pathArray = explode('.', $key);

    /** @var \Civi\Api4\Service\Schema\Joiner $joiner */
    $joiner = \Civi::container()->get('joiner');
    // The last item in the path is the field name. We don't care about that; we'll add all fields from the joined entity.
    array_pop($pathArray);
    $pathString = implode('.', $pathArray);

    if (!$joiner->canAutoJoin($this->getFrom(), $pathString)) {
      return;
    }

    $joinPath = $joiner->join($this, $pathString);

    $lastLink = array_pop($joinPath);

    // Custom field names are already prefixed
    $isCustom = $lastLink instanceof CustomGroupJoinable;
    if ($isCustom) {
      array_pop($pathArray);
    }
    $prefix = $pathArray ? implode('.', $pathArray) . '.' : '';
    // Cache field info for retrieval by $this->getField()
    foreach ($lastLink->getEntityFields() as $fieldObject) {
      $fieldArray = $fieldObject->toArray();
      $fieldArray['sql_name'] = '`' . $lastLink->getAlias() . '`.`' . $fieldArray['column_name'] . '`';
      $fieldArray['is_custom'] = $isCustom;
      $fieldArray['is_join'] = TRUE;
      $this->addSpecField($prefix . $fieldArray['name'], $fieldArray);
    }
  }

  /**
   * @return FALSE|string
   */
  public function getFrom() {
    return CoreUtil::getTableName($this->entity);
  }

  /**
   * @return string
   */
  public function getEntity() {
    return $this->entity;
  }

  /**
   * @return array
   */
  public function getSelect() {
    return $this->select;
  }

  /**
   * @return array
   */
  public function getWhere() {
    return $this->where;
  }

  /**
   * @return array
   */
  public function getOrderBy() {
    return $this->orderBy;
  }

  /**
   * @return mixed
   */
  public function getLimit() {
    return $this->limit;
  }

  /**
   * @return mixed
   */
  public function getOffset() {
    return $this->offset;
  }

  /**
   * @return array
   */
  public function getSelectFields() {
    return $this->selectFields;
  }

  /**
   * @return \CRM_Utils_SQL_Select
   */
  public function getQuery() {
    return $this->query;
  }

  /**
   * @return array
   */
  public function getJoins() {
    return $this->joins;
  }

  /**
   * @return array
   */
  public function getApiFieldSpec() {
    return $this->apiFieldSpec;
  }

  /**
   * @return array
   */
  public function getEntityFieldNames() {
    return $this->entityFieldNames;
  }

  /**
   * @return array
   */
  public function getAclFields() {
    return $this->aclFields;
  }

  /**
   * @return bool|string
   */
  public function getCheckPermissions() {
    return $this->checkPermissions;
  }

  /**
   * @return int
   */
  public function getApiVersion() {
    return $this->apiVersion;
  }

  /**
   * Get table name on basis of entity
   *
   * @return void
   */
  public function constructQueryObject() {
    $tableName = CoreUtil::getTableName($this->entity);
    $this->query = \CRM_Utils_SQL_Select::from($tableName . ' ' . self::MAIN_TABLE_ALIAS);
  }

  /**
   * @param $path
   * @param $field
   */
  private function addSpecField($path, $field) {
    // Only add field to spec if we have permission
    if ($this->checkPermissions && !empty($field['permission']) && !\CRM_Core_Permission::check($field['permission'])) {
      $this->apiFieldSpec[$path] = FALSE;
      return;
    }
    $defaults = [];
    $defaults['is_custom'] = $defaults['is_join'] = FALSE;
    $field += $defaults;
    $this->apiFieldSpec[$path] = $field;
  }

}
