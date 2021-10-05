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

use Civi\API\Exception\UnauthorizedException;
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
 * * 'NOT LIKE', 'IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN',
 * * 'IS NOT NULL', 'IS NULL', 'CONTAINS', 'IS EMPTY', 'IS NOT EMPTY',
 * * 'REGEXP', 'NOT REGEXP'.
 */
class Api4SelectQuery {

  const
    MAIN_TABLE_ALIAS = 'a',
    UNLIMITED = '18446744073709551615';

  /**
   * @var \CRM_Utils_SQL_Select
   */
  protected $query;

  /**
   * @var array
   */
  protected $joins = [];

  /**
   * Used to keep track of implicit join table aliases
   * @var array
   */
  protected $joinTree = [];

  /**
   * Used to create a unique table alias for each implicit join
   * @var int
   */
  protected $autoJoinSuffix = 0;

  /**
   * @var array[]
   */
  protected $apiFieldSpec;

  /**
   * @var array
   */
  protected $aclFields = [];

  /**
   * @var \Civi\Api4\Generic\DAOGetAction
   */
  private $api;

  /**
   * @var array
   * [alias => expr][]
   */
  protected $selectAliases = [];

  /**
   * @var bool
   */
  public $forceSelectId = TRUE;

  /**
   * @var array
   */
  private $explicitJoins = [];

  /**
   * @var array
   */
  private $entityAccess = [];

  /**
   * @param \Civi\Api4\Generic\DAOGetAction $apiGet
   */
  public function __construct($apiGet) {
    $this->api = $apiGet;

    // Always select ID of main table unless grouping by something else
    $keys = CoreUtil::getInfoItem($this->getEntity(), 'primary_key');
    $this->forceSelectId = !$this->isAggregateQuery() || array_intersect($this->getGroupBy(), $keys);

    // Build field lists
    foreach ($this->api->entityFields() as $field) {
      $field['sql_name'] = '`' . self::MAIN_TABLE_ALIAS . '`.`' . $field['column_name'] . '`';
      $this->addSpecField($field['name'], $field);
    }

    $tableName = CoreUtil::getTableName($this->getEntity());
    $this->query = \CRM_Utils_SQL_Select::from($tableName . ' ' . self::MAIN_TABLE_ALIAS);

    $this->entityAccess[$this->getEntity()] = TRUE;

    // Add ACLs first to avoid redundant subclauses
    $baoName = CoreUtil::getBAOFromApiName($this->getEntity());
    $this->query->where($this->getAclClause(self::MAIN_TABLE_ALIAS, $baoName));

    // Add explicit joins. Other joins implied by dot notation may be added later
    $this->addExplicitJoins();
  }

  protected function isAggregateQuery() {
    if ($this->getGroupBy()) {
      return TRUE;
    }
    foreach ($this->getSelect() as $sql) {
      $classname = get_class(SqlExpression::convert($sql, TRUE));
      if (method_exists($classname, 'getCategory') && $classname::getCategory() === SqlFunction::CATEGORY_AGGREGATE) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Builds main final sql statement after initialization.
   *
   * @return string
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
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
   * @return array
   */
  public function run() {
    $results = [];
    $sql = $this->getSql();
    $this->debug('sql', $sql);
    $query = \CRM_Core_DAO::executeQuery($sql);
    while ($query->fetch()) {
      $result = [];
      foreach ($this->selectAliases as $alias => $expr) {
        $returnName = $alias;
        $alias = str_replace('.', '_', $alias);
        $result[$returnName] = property_exists($query, $alias) ? $query->$alias : NULL;
      }
      $results[] = $result;
    }
    FormattingUtil::formatOutputValues($results, $this->apiFieldSpec, $this->getEntity(), 'get', $this->selectAliases);
    return $results;
  }

  /**
   * @return int
   * @throws \API_Exception
   */
  public function getCount() {
    $this->buildWhereClause();
    // If no having or groupBy, we only need to select count
    if (!$this->getHaving() && !$this->getGroupBy()) {
      $this->query->select('COUNT(*) AS `c`');
      $sql = $this->query->toSQL();
    }
    // Use a subquery to count groups from GROUP BY or results filtered by HAVING
    else {
      // With no HAVING, just select the last field grouped by
      if (!$this->getHaving()) {
        $select = array_slice($this->getGroupBy(), -1);
      }
      $this->buildSelectClause($select ?? NULL);
      $this->buildHavingClause();
      $this->buildGroupBy();
      $subquery = $this->query->toSQL();
      $sql = "SELECT count(*) AS `c` FROM ( $subquery ) AS `rows`";
    }
    $this->debug('sql', $sql);
    return (int) \CRM_Core_DAO::singleValueQuery($sql);
  }

  /**
   * @param array $select
   *   Array of select expressions; defaults to $this->getSelect
   * @throws \API_Exception
   */
  protected function buildSelectClause($select = NULL) {
    // Use default if select not provided, exclude row_count which is handled elsewhere
    $select = array_diff($select ?? $this->getSelect(), ['row_count']);
    // An empty select is the same as *
    if (empty($select)) {
      $select = $this->selectMatchingFields('*');
    }
    else {
      if ($this->forceSelectId) {
        $keys = CoreUtil::getInfoItem($this->getEntity(), 'primary_key');
        $select = array_merge($keys, $select);
      }

      // Expand the superstar 'custom.*' to select all fields in all custom groups
      $customStar = array_search('custom.*', array_values($select), TRUE);
      if ($customStar !== FALSE) {
        $customGroups = civicrm_api4($this->getEntity(), 'getFields', [
          'checkPermissions' => FALSE,
          'where' => [['custom_group', 'IS NOT NULL']],
        ], ['custom_group' => 'custom_group']);
        $customSelect = [];
        foreach ($customGroups as $groupName) {
          $customSelect[] = "$groupName.*";
        }
        array_splice($select, $customStar, 1, $customSelect);
      }

      // Expand wildcards in joins (the api wrapper already expanded non-joined wildcards)
      $wildFields = array_filter($select, function($item) {
        return strpos($item, '*') !== FALSE && strpos($item, '.') !== FALSE && strpos($item, '(') === FALSE && strpos($item, ' ') === FALSE;
      });

      foreach ($wildFields as $wildField) {
        $pos = array_search($wildField, array_values($select));
        // If the joined_entity.id isn't in the fieldspec already, autoJoinFK will attempt to add the entity.
        $fkField = substr($wildField, 0, strrpos($wildField, '.'));
        $fkEntity = $this->getField($fkField)['fk_entity'] ?? NULL;
        $id = $fkEntity ? CoreUtil::getIdFieldName($fkEntity) : 'id';
        $this->autoJoinFK($fkField . ".$id");
        $matches = $this->selectMatchingFields($wildField);
        array_splice($select, $pos, 1, $matches);
      }
      $select = array_unique($select);
    }
    foreach ($select as $item) {
      $expr = SqlExpression::convert($item, TRUE);
      $valid = TRUE;
      foreach ($expr->getFields() as $fieldName) {
        $field = $this->getField($fieldName);
        // Remove expressions with unknown fields without raising an error
        if (!$field || $field['type'] === 'Filter') {
          $select = array_diff($select, [$item]);
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
   * Get all fields for SELECT clause matching a wildcard pattern
   *
   * @param $pattern
   * @return array
   */
  private function selectMatchingFields($pattern) {
    // Only core & custom fields can be selected
    $availableFields = array_filter($this->apiFieldSpec, function($field) {
      return is_array($field) && in_array($field['type'], ['Field', 'Custom'], TRUE);
    });
    return SelectUtil::getMatchingFields($pattern, array_keys($availableFields));
  }

  /**
   * Add WHERE clause to query
   */
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
        throw new \API_Exception("Invalid sort direction. Cannot order by $item $dir");
      }

      try {
        $expr = $this->getExpression($item);
        $column = $expr->render($this->apiFieldSpec);

        // Use FIELD() function to sort on pseudoconstant values
        $suffix = strstr($item, ':');
        if ($suffix && $expr->getType() === 'SqlField') {
          $field = $this->getField($item);
          $options = FormattingUtil::getPseudoconstantList($field, $item);
          if ($options) {
            asort($options);
            $column = "FIELD($column,'" . implode("','", array_keys($options)) . "')";
          }
        }
      }
      // If the expression could not be rendered, it might be a field alias
      catch (\API_Exception $e) {
        // Silently ignore fields the user lacks permission to see
        if (is_a($e, 'Civi\API\Exception\UnauthorizedException')) {
          $this->debug('unauthorized_fields', $item);
          continue;
        }
        if (!empty($this->selectAliases[$item])) {
          $column = '`' . $item . '`';
        }
        else {
          throw new \API_Exception("Invalid field '{$item}'");
        }
      }

      $this->query->orderBy("$column $dir");
    }
  }

  /**
   * Add LIMIT to query
   *
   * @throws \CRM_Core_Exception
   */
  protected function buildLimit() {
    if ($this->getLimit() || $this->getOffset()) {
      // If limit is 0, mysql will actually return 0 results. Instead set to maximum possible.
      $this->query->limit($this->getLimit() ?: self::UNLIMITED, $this->getOffset());
    }
  }

  /**
   * Add GROUP BY clause to query
   */
  protected function buildGroupBy() {
    foreach ($this->getGroupBy() as $item) {
      $this->query->groupBy($this->getExpression($item)->render($this->apiFieldSpec));
    }
  }

  /**
   * Recursively validate and transform a branch or leaf clause array to SQL.
   *
   * @param array $clause
   * @param string $type
   *   WHERE|HAVING|ON
   * @param int $depth
   * @return string SQL where clause
   *
   * @throws \API_Exception
   * @uses composeClause() to generate the SQL etc.
   */
  protected function treeWalkClauses($clause, $type, $depth = 0) {
    // Skip empty leaf.
    if (in_array($clause[0], ['AND', 'OR', 'NOT']) && empty($clause[1])) {
      return '';
    }
    switch ($clause[0]) {
      case 'OR':
      case 'AND':
        // handle branches
        if (count($clause[1]) === 1) {
          // a single set so AND|OR is immaterial
          return $this->treeWalkClauses($clause[1][0], $type, $depth + 1);
        }
        else {
          $sql_subclauses = [];
          foreach ($clause[1] as $subclause) {
            $sql_subclauses[] = $this->treeWalkClauses($subclause, $type, $depth + 1);
          }
          return '(' . implode("\n" . $clause[0] . ' ', $sql_subclauses) . ')';
        }

      case 'NOT':
        // If we get a group of clauses with no operator, assume AND
        if (!is_string($clause[1][0])) {
          $clause[1] = ['AND', $clause[1]];
        }
        return 'NOT (' . $this->treeWalkClauses($clause[1], $type, $depth + 1) . ')';

      default:
        try {
          return $this->composeClause($clause, $type, $depth);
        }
        // Silently ignore fields the user lacks permission to see
        catch (UnauthorizedException $e) {
          return '';
        }
    }
  }

  /**
   * Validate and transform a leaf clause array to SQL.
   * @param array $clause [$fieldName, $operator, $criteria, $isExpression]
   * @param string $type
   *   WHERE|HAVING|ON
   * @param int $depth
   * @return string SQL
   * @throws \API_Exception
   * @throws \Exception
   */
  protected function composeClause(array $clause, string $type, int $depth) {
    $field = NULL;
    // Pad array for unary operators
    [$expr, $operator, $value] = array_pad($clause, 3, NULL);
    $isExpression = $clause[3] ?? FALSE;
    if (!in_array($operator, CoreUtil::getOperators(), TRUE)) {
      throw new \API_Exception('Illegal operator');
    }

    // For WHERE clause, expr must be the name of a field.
    if ($type === 'WHERE' && !$isExpression) {
      $field = $this->getField($expr, TRUE);
      FormattingUtil::formatInputValue($value, $expr, $field, $operator);
      $fieldAlias = $this->getExpression($expr)->render($this->apiFieldSpec);
    }
    // For HAVING, expr must be an item in the SELECT clause
    elseif ($type === 'HAVING') {
      // Expr references a fieldName or alias
      if (isset($this->selectAliases[$expr])) {
        $fieldAlias = $expr;
        // Attempt to format if this is a real field
        if (isset($this->apiFieldSpec[$expr])) {
          $field = $this->getField($expr);
          FormattingUtil::formatInputValue($value, $expr, $field, $operator);
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
            $field = $this->getField($fieldName);
            FormattingUtil::formatInputValue($value, $expr, $field, $operator);
            $fieldAlias = $selectAlias;
            break;
          }
        }
      }
      if (!isset($fieldAlias)) {
        if (in_array($expr, $this->getSelect())) {
          throw new UnauthorizedException("Unauthorized field '$expr'");
        }
        else {
          throw new \API_Exception("Invalid expression in HAVING clause: '$expr'. Must use a value from SELECT clause.");
        }
      }
      $fieldAlias = '`' . $fieldAlias . '`';
    }
    elseif ($type === 'ON' || ($type === 'WHERE' && $isExpression)) {
      $expr = $this->getExpression($expr);
      $fieldName = count($expr->getFields()) === 1 ? $expr->getFields()[0] : NULL;
      $fieldAlias = $expr->render($this->apiFieldSpec);
      if (is_string($value)) {
        $valExpr = $this->getExpression($value);
        if ($fieldName && $valExpr->getType() === 'SqlString') {
          $value = $valExpr->getExpr();
          FormattingUtil::formatInputValue($value, $fieldName, $this->apiFieldSpec[$fieldName], $operator);
          return $this->createSQLClause($fieldAlias, $operator, $value, $this->apiFieldSpec[$fieldName], $depth);
        }
        else {
          $value = $valExpr->render($this->apiFieldSpec);
          return sprintf('%s %s %s', $fieldAlias, $operator, $value);
        }
      }
      elseif ($fieldName) {
        $field = $this->getField($fieldName);
        FormattingUtil::formatInputValue($value, $fieldName, $field, $operator);
      }
    }

    $sqlClause = $this->createSQLClause($fieldAlias, $operator, $value, $field, $depth);
    if ($sqlClause === NULL) {
      throw new \API_Exception("Invalid value in $type clause for '$expr'");
    }
    return $sqlClause;
  }

  /**
   * @param string $fieldAlias
   * @param string $operator
   * @param mixed $value
   * @param array|null $field
   * @param int $depth
   * @return array|string|NULL
   * @throws \Exception
   */
  protected function createSQLClause($fieldAlias, $operator, $value, $field, int $depth) {
    if (!empty($field['operators']) && !in_array($operator, $field['operators'], TRUE)) {
      throw new \API_Exception('Illegal operator for ' . $field['name']);
    }
    // Some fields use a callback to generate their sql
    if (!empty($field['sql_filters'])) {
      $sql = [];
      foreach ($field['sql_filters'] as $filter) {
        $clause = is_callable($filter) ? $filter($field, $fieldAlias, $operator, $value, $this, $depth) : NULL;
        if ($clause) {
          $sql[] = $clause;
        }
      }
      return $sql ? implode(' AND ', $sql) : NULL;
    }
    if ($operator === 'CONTAINS') {
      switch ($field['serialize'] ?? NULL) {
        case \CRM_Core_DAO::SERIALIZE_JSON:
          $operator = 'LIKE';
          $value = '%"' . $value . '"%';
          // FIXME: Use this instead of the above hack once MIN_INSTALL_MYSQL_VER is bumped to 5.7.
          // return sprintf('JSON_SEARCH(%s, "one", "%s") IS NOT NULL', $fieldAlias, \CRM_Core_DAO::escapeString($value));
          break;

        case \CRM_Core_DAO::SERIALIZE_SEPARATOR_BOOKEND:
          $operator = 'LIKE';
          $value = '%' . \CRM_Core_DAO::VALUE_SEPARATOR . $value . \CRM_Core_DAO::VALUE_SEPARATOR . '%';
          break;

        default:
          $operator = 'LIKE';
          $value = '%' . $value . '%';
          break;
      }
    }

    if ($operator === 'IS EMPTY' || $operator === 'IS NOT EMPTY') {
      // If field is not a string or number, this will pass through and use IS NULL/IS NOT NULL
      $operator = str_replace('EMPTY', 'NULL', $operator);
      // For strings & numbers, create an OR grouping of empty value OR null
      if (in_array($field['data_type'] ?? NULL, ['String', 'Integer', 'Float'], TRUE)) {
        $emptyVal = $field['data_type'] === 'String' ? '""' : '0';
        $isEmptyClause = $operator === 'IS NULL' ? "= $emptyVal OR" : "<> $emptyVal AND";
        return "($fieldAlias $isEmptyClause $fieldAlias $operator)";
      }
    }

    if ($operator == 'REGEXP' || $operator == 'NOT REGEXP') {
      return sprintf('%s %s "%s"', $fieldAlias, $operator, \CRM_Core_DAO::escapeString($value));
    }

    if (is_bool($value)) {
      $value = (int) $value;
    }

    return \CRM_Core_DAO::createSQLFilter($fieldAlias, [$operator => $value]);
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
   * Get acl clause for an entity
   *
   * @param string $tableAlias
   * @param \CRM_Core_DAO|string $baoName
   * @param array $stack
   * @return array
   */
  public function getAclClause($tableAlias, $baoName, $stack = []) {
    if (!$this->getCheckPermissions()) {
      return [];
    }
    // Prevent (most) redundant acl sub clauses if they have already been applied to the main entity.
    // FIXME: Currently this only works 1 level deep, but tracking through multiple joins would increase complexity
    // and just doing it for the first join takes care of most acl clause deduping.
    if (count($stack) === 1 && in_array(reset($stack), $this->aclFields, TRUE)) {
      return [];
    }
    $clauses = $baoName::getSelectWhereClause($tableAlias);
    if (!$stack) {
      // Track field clauses added to the main entity
      $this->aclFields = array_keys($clauses);
    }
    return array_filter($clauses);
  }

  /**
   * Fetch a field from the getFields list
   *
   * @param string $expr
   * @param bool $strict
   *   In strict mode, this will throw an exception if the field doesn't exist
   *
   * @return array|null
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
    if (!$field) {
      $this->debug($field === FALSE ? 'unauthorized_fields' : 'undefined_fields', $fieldName);
    }
    if ($strict && $field === NULL) {
      throw new \API_Exception("Invalid field '$fieldName'");
    }
    if ($strict && $field === FALSE) {
      throw new UnauthorizedException("Unauthorized field '$fieldName'");
    }
    if ($field) {
      $this->apiFieldSpec[$expr] = $field;
    }
    return $field;
  }

  /**
   * Check the "gatekeeper" permissions for performing "get" on a given entity.
   *
   * @param $entity
   * @return bool
   */
  public function checkEntityAccess($entity) {
    if (!$this->getCheckPermissions()) {
      return TRUE;
    }
    if (!isset($this->entityAccess[$entity])) {
      $this->entityAccess[$entity] = (bool) civicrm_api4($entity, 'getActions', [
        'where' => [['name', '=', 'get']],
        'select' => ['name'],
      ])->first();
    }
    return $this->entityAccess[$entity];
  }

  /**
   * Join onto other entities as specified by the api call.
   *
   * @throws \API_Exception
   * @throws \Civi\API\Exception\NotImplementedException
   */
  private function addExplicitJoins() {
    foreach ($this->getJoin() as $join) {
      // First item in the array is the entity name
      $entity = array_shift($join);
      // Which might contain an alias. Split on the keyword "AS"
      list($entity, $alias) = array_pad(explode(' AS ', $entity), 2, NULL);
      // Ensure permissions
      if (!$this->checkEntityAccess($entity)) {
        continue;
      }
      // Ensure alias is a safe string, and supply default if not given
      $alias = $alias ?: strtolower($entity);
      if ($alias === self::MAIN_TABLE_ALIAS || !preg_match('/^[-\w]{1,256}$/', $alias)) {
        throw new \API_Exception('Illegal join alias: "' . $alias . '"');
      }
      // First item in the array is a boolean indicating if the join is required (aka INNER or LEFT).
      // The rest are join conditions.
      $side = array_shift($join);
      // If omitted, supply default (LEFT); and legacy support for boolean values
      if (!is_string($side)) {
        $side = $side ? 'INNER' : 'LEFT';
      }
      if (!in_array($side, ['INNER', 'LEFT', 'EXCLUDE'])) {
        throw new \API_Exception("Illegal value for join side: '$side'.");
      }
      if ($side === 'EXCLUDE') {
        $side = 'LEFT';
        $this->api->addWhere("$alias.id", 'IS NULL');
      }
      // Add all fields from joined entity to spec
      $joinEntityGet = \Civi\API\Request::create($entity, 'get', ['version' => 4, 'checkPermissions' => $this->getCheckPermissions()]);
      $joinEntityFields = $joinEntityGet->entityFields();
      foreach ($joinEntityFields as $field) {
        $field['sql_name'] = '`' . $alias . '`.`' . $field['column_name'] . '`';
        $this->addSpecField($alias . '.' . $field['name'], $field);
      }
      $tableName = CoreUtil::getTableName($entity);
      // Save join info to be retrieved by $this->getExplicitJoin()
      $this->explicitJoins[$alias] = [
        'entity' => $entity,
        'table' => $tableName,
        'bridge' => NULL,
      ];
      // If the first condition is a string, it's the name of a bridge entity
      if (!empty($join[0]) && is_string($join[0]) && \CRM_Utils_Rule::alphanumeric($join[0])) {
        $this->addBridgeJoin($join, $entity, $alias, $side);
      }
      else {
        $conditions = $this->getJoinConditions($join, $entity, $alias, $joinEntityFields);
        foreach (array_filter($join) as $clause) {
          $conditions[] = $this->treeWalkClauses($clause, 'ON');
        }
        $this->join($side, $tableName, $alias, $conditions);
      }
    }
  }

  /**
   * Supply conditions for an explicit join.
   *
   * @param array $joinTree
   * @param string $joinEntity
   * @param string $alias
   * @param array $joinEntityFields
   * @return array
   */
  private function getJoinConditions($joinTree, $joinEntity, $alias, $joinEntityFields) {
    $conditions = [];
    // getAclClause() expects a stack of 1-to-1 join fields to help it dedupe, but this is more flexible,
    // so unless this is a direct 1-to-1 join with the main entity, we'll just hack it
    // with a padded empty stack to bypass its deduping.
    $stack = [NULL, NULL];
    // See if the ON clause already contains an FK reference to joinEntity
    $explicitFK = array_filter($joinTree, function($clause) use ($alias, $joinEntityFields) {
      list($sideA, $op, $sideB) = array_pad((array) $clause, 3, NULL);
      if ($op !== '=' || !$sideB) {
        return FALSE;
      }
      foreach ([$sideA, $sideB] as $expr) {
        if ($expr === "$alias.id" || !empty($joinEntityFields[str_replace("$alias.", '', $expr)]['fk_entity'])) {
          return TRUE;
        }
      }
      return FALSE;
    });
    // If we're not explicitly referencing the ID (or some other FK field) of the joinEntity, search for a default
    if (!$explicitFK) {
      foreach ($this->apiFieldSpec as $name => $field) {
        if (is_array($field) && $field['entity'] !== $joinEntity && $field['fk_entity'] === $joinEntity) {
          $conditions[] = $this->treeWalkClauses([$name, '=', "$alias.id"], 'ON');
        }
        elseif (strpos($name, "$alias.") === 0 && substr_count($name, '.') === 1 && $field['fk_entity'] === $this->getEntity()) {
          $conditions[] = $this->treeWalkClauses([$name, '=', 'id'], 'ON');
          $stack = ['id'];
        }
      }
      // Hmm, if we came up with > 1 condition, then it's ambiguous how it should be joined so we won't return anything but the generic ACLs
      if (count($conditions) > 1) {
        $stack = [NULL, NULL];
        $conditions = [];
      }
    }
    $baoName = CoreUtil::getBAOFromApiName($joinEntity);
    $acls = array_values($this->getAclClause($alias, $baoName, $stack));
    return array_merge($acls, $conditions);
  }

  /**
   * Join via a Bridge table
   *
   * This creates a double-join in sql that appears to the API user like a single join.
   *
   * LEFT joins use a subquery so that the bridge + joined-entity can be treated like a single table.
   *
   * @param array $joinTree
   * @param string $joinEntity
   * @param string $alias
   * @param string $side
   * @throws \API_Exception
   */
  protected function addBridgeJoin($joinTree, $joinEntity, $alias, $side) {
    $bridgeEntity = array_shift($joinTree);
    $this->explicitJoins[$alias]['bridge'] = $bridgeEntity;

    // INNER joins require unique aliases, whereas left joins will be inside a subquery and short aliases are more readable
    $bridgeAlias = $side === 'INNER' ? $alias . '_via_' . strtolower($bridgeEntity) : 'b';
    $joinAlias = $side === 'INNER' ? $alias : 'c';

    $joinTable = CoreUtil::getTableName($joinEntity);
    [$bridgeTable, $baseRef, $joinRef] = $this->getBridgeRefs($bridgeEntity, $joinEntity);

    $bridgeFields = $this->registerBridgeJoinFields($bridgeEntity, $joinRef, $baseRef, $alias, $bridgeAlias, $side);

    $linkConditions = $this->getBridgeLinkConditions($bridgeAlias, $joinAlias, $joinTable, $joinRef);

    $bridgeConditions = $this->getBridgeJoinConditions($joinTree, $baseRef, $alias, $bridgeAlias, $bridgeEntity, $side);

    $acls = array_values($this->getAclClause($joinAlias, CoreUtil::getBAOFromApiName($joinEntity), [NULL, NULL]));

    $joinConditions = [];
    foreach (array_filter($joinTree) as $clause) {
      $joinConditions[] = $this->treeWalkClauses($clause, 'ON');
    }

    // INNER joins are done with 2 joins
    if ($side === 'INNER') {
      // Info needed for joining custom fields extending the bridge entity
      $this->explicitJoins[$alias]['bridge_table_alias'] = $bridgeAlias;
      $this->explicitJoins[$alias]['bridge_id_alias'] = 'id';
      $this->join('INNER', $bridgeTable, $bridgeAlias, $bridgeConditions);
      $this->join('INNER', $joinTable, $alias, array_merge($linkConditions, $acls, $joinConditions));
    }
    // For LEFT joins, construct a subquery to link the bridge & join tables as one
    else {
      $joinEntityClass = CoreUtil::getApiClass($joinEntity);
      foreach ($joinEntityClass::get($this->getCheckPermissions())->entityFields() as $name => $field) {
        if ($field['type'] === 'Field') {
          $bridgeFields[$field['column_name']] = '`' . $joinAlias . '`.`' . $field['column_name'] . '`';
        }
      }
      // Info needed for joining custom fields extending the bridge entity
      $this->explicitJoins[$alias]['bridge_table_alias'] = $alias;
      $this->explicitJoins[$alias]['bridge_id_alias'] = 'bridge_entity_id_key';
      $bridgeFields[] = "`$bridgeAlias`.`id` AS `bridge_entity_id_key`";
      $select = implode(',', $bridgeFields);
      $joinConditions = array_merge($joinConditions, $bridgeConditions);
      $innerConditions = array_merge($linkConditions, $acls);
      $subquery = "SELECT $select FROM `$bridgeTable` `$bridgeAlias`, `$joinTable` `$joinAlias` WHERE " . implode(' AND ', $innerConditions);
      $this->query->join($alias, "$side JOIN ($subquery) `$alias` ON " . implode(' AND ', $joinConditions));
    }
  }

  /**
   * Get the table name and 2 reference columns from a bridge entity
   *
   * @param string $bridgeEntity
   * @param string $joinEntity
   * @return array
   * @throws \API_Exception
   */
  private function getBridgeRefs(string $bridgeEntity, string $joinEntity): array {
    $bridgeFields = CoreUtil::getInfoItem($bridgeEntity, 'bridge') ?? [];
    // Sanity check - bridge entity should declare exactly 2 FK fields
    if (count($bridgeFields) !== 2) {
      throw new \API_Exception("Illegal bridge entity specified: $bridgeEntity. Expected 2 bridge fields, found " . count($bridgeFields));
    }
    /* @var \CRM_Core_DAO $bridgeDAO */
    $bridgeDAO = CoreUtil::getInfoItem($bridgeEntity, 'dao');
    $bridgeTable = $bridgeDAO::getTableName();

    // Get the 2 bridge reference columns as CRM_Core_Reference_* objects
    $joinRef = $baseRef = NULL;
    foreach ($bridgeDAO::getReferenceColumns() as $ref) {
      if (array_key_exists($ref->getReferenceKey(), $bridgeFields)) {
        if (!$joinRef && in_array($joinEntity, $ref->getTargetEntities())) {
          $joinRef = $ref;
        }
        else {
          $baseRef = $ref;
        }
      }
    }
    if (!$joinRef || !$baseRef) {
      throw new \API_Exception("Unable to join $bridgeEntity to $joinEntity");
    }
    return [$bridgeTable, $baseRef, $joinRef];
  }

  /**
   * Get the clause to link bridge entity with join entity
   *
   * @param string $bridgeAlias
   * @param string $joinAlias
   * @param string $joinTable
   * @param $joinRef
   * @return array
   */
  private function getBridgeLinkConditions(string $bridgeAlias, string $joinAlias, string $joinTable, $joinRef): array {
    $linkConditions = [
      "`$bridgeAlias`.`{$joinRef->getReferenceKey()}` = `$joinAlias`.`{$joinRef->getTargetKey()}`",
    ];
    // For dynamic references, also add the type column (e.g. `entity_table`)
    if ($joinRef->getTypeColumn()) {
      $linkConditions[] = "`$bridgeAlias`.`{$joinRef->getTypeColumn()}` = '$joinTable'";
    }
    return $linkConditions;
  }

  /**
   * Register fields (other than bridge FK fields) from the bridge entity as if they belong to the join entity
   *
   * @param $bridgeEntity
   * @param $joinRef
   * @param $baseRef
   * @param string $alias
   * @param string $bridgeAlias
   * @param string $side
   * @return array
   */
  private function registerBridgeJoinFields($bridgeEntity, $joinRef, $baseRef, string $alias, string $bridgeAlias, string $side): array {
    $fakeFields = [];
    $bridgeFkFields = [$joinRef->getReferenceKey(), $joinRef->getTypeColumn(), $baseRef->getReferenceKey(), $baseRef->getTypeColumn()];
    $bridgeEntityClass = CoreUtil::getApiClass($bridgeEntity);
    foreach ($bridgeEntityClass::get($this->getCheckPermissions())->entityFields() as $name => $field) {
      if ($name === 'id' || ($side === 'INNER' && in_array($name, $bridgeFkFields, TRUE))) {
        continue;
      }
      // For INNER joins, these fields get a sql alias pointing to the bridge entity,
      // but an api alias pretending they belong to the join entity.
      $field['sql_name'] = '`' . ($side === 'LEFT' ? $alias : $bridgeAlias) . '`.`' . $field['column_name'] . '`';
      $this->addSpecField($alias . '.' . $name, $field);
      if ($field['type'] === 'Field') {
        $fakeFields[$field['column_name']] = '`' . $bridgeAlias . '`.`' . $field['column_name'] . '`';
      }
    }
    return $fakeFields;
  }

  /**
   * Extract bridge join conditions from the joinTree if any, else supply default conditions for join to base entity
   *
   * @param array $joinTree
   * @param $baseRef
   * @param string $alias
   * @param string $bridgeAlias
   * @param string $bridgeEntity
   * @param string $side
   * @return string[]
   * @throws \API_Exception
   */
  private function getBridgeJoinConditions(array &$joinTree, $baseRef, string $alias, string $bridgeAlias, string $bridgeEntity, string $side): array {
    $bridgeConditions = [];
    $bridgeAlias = $side === 'INNER' ? $bridgeAlias : $alias;
    // Find explicit bridge join conditions and move them out of the joinTree
    $joinTree = array_filter($joinTree, function ($clause) use ($baseRef, $alias, $bridgeAlias, &$bridgeConditions) {
      list($sideA, $op, $sideB) = array_pad((array) $clause, 3, NULL);
      // Skip AND/OR/NOT branches
      if (!$sideB) {
        return TRUE;
      }
      // If this condition makes an explicit link between the bridge and another entity
      if ($op === '=' && $sideB && ($sideA === "$alias.{$baseRef->getReferenceKey()}" || $sideB === "$alias.{$baseRef->getReferenceKey()}")) {
        $expr = $sideA === "$alias.{$baseRef->getReferenceKey()}" ? $sideB : $sideA;
        $bridgeConditions[] = "`$bridgeAlias`.`{$baseRef->getReferenceKey()}` = " . $this->getExpression($expr)->render($this->apiFieldSpec);
        return FALSE;
      }
      // Explicit link with dynamic "entity_table" column
      elseif ($op === '=' && $baseRef->getTypeColumn() && ($sideA === "$alias.{$baseRef->getTypeColumn()}" || $sideB === "$alias.{$baseRef->getTypeColumn()}")) {
        $expr = $sideA === "$alias.{$baseRef->getTypeColumn()}" ? $sideB : $sideA;
        $bridgeConditions[] = "`$bridgeAlias`.`{$baseRef->getTypeColumn()}` = " . $this->getExpression($expr)->render($this->apiFieldSpec);
        return FALSE;
      }
      return TRUE;
    });
    // If no bridge conditions were specified, link it to the base entity
    if (!$bridgeConditions) {
      if (!in_array($this->getEntity(), $baseRef->getTargetEntities())) {
        throw new \API_Exception("Unable to join $bridgeEntity to " . $this->getEntity());
      }
      $bridgeConditions[] = "`$bridgeAlias`.`{$baseRef->getReferenceKey()}` = a.`{$baseRef->getTargetKey()}`";
      if ($baseRef->getTypeColumn()) {
        $bridgeConditions[] = "`$bridgeAlias`.`{$baseRef->getTypeColumn()}` = '" . $this->getFrom() . "'";
      }
    }
    return $bridgeConditions;
  }

  /**
   * Joins a path and adds all fields in the joined entity to apiFieldSpec
   *
   * @param $key
   */
  protected function autoJoinFK($key) {
    if (isset($this->apiFieldSpec[$key])) {
      return;
    }
    /** @var \Civi\Api4\Service\Schema\Joiner $joiner */
    $joiner = \Civi::container()->get('joiner');

    $pathArray = explode('.', $key);
    // The last item in the path is the field name. We don't care about that; we'll add all fields from the joined entity.
    array_pop($pathArray);

    $baseTableAlias = $this::MAIN_TABLE_ALIAS;

    // If the first item is the name of an explicit join, use it as the base & shift it off the path
    $explicitJoin = $this->getExplicitJoin($pathArray[0]);
    if ($explicitJoin) {
      $baseTableAlias = array_shift($pathArray);
    }

    // Ensure joinTree array contains base table
    $this->joinTree[$baseTableAlias]['#table_alias'] = $baseTableAlias;
    $this->joinTree[$baseTableAlias]['#path'] = $explicitJoin ? $baseTableAlias . '.' : '';
    // During iteration this variable will refer to the current position in the tree
    $joinTreeNode =& $this->joinTree[$baseTableAlias];

    $useBridgeTable = FALSE;
    try {
      $joinPath = $joiner->getPath($explicitJoin['table'] ?? $this->getFrom(), $pathArray);
    }
    catch (\API_Exception $e) {
      if (!empty($explicitJoin['bridge'])) {
        // Try looking up custom field in bridge entity instead
        try {
          $useBridgeTable = TRUE;
          $joinPath = $joiner->getPath(CoreUtil::getTableName($explicitJoin['bridge']), $pathArray);
        }
        catch (\API_Exception $e) {
          return;
        }
      }
      else {
        // Because the select clause silently ignores unknown fields, this function shouldn't throw exceptions
        return;
      }
    }

    foreach ($joinPath as $joinName => $link) {
      if (!isset($joinTreeNode[$joinName])) {
        $target = $link->getTargetTable();
        $tableAlias = $link->getAlias() . '_' . ++$this->autoJoinSuffix;
        $isCustom = $link instanceof CustomGroupJoinable;

        $joinTreeNode[$joinName] = [
          '#table_alias' => $tableAlias,
          '#path' => $joinTreeNode['#path'] . $joinName . '.',
        ];
        $joinEntity = CoreUtil::getApiNameFromTableName($target);

        if ($joinEntity && !$this->checkEntityAccess($joinEntity)) {
          return;
        }
        if ($this->getCheckPermissions() && $isCustom) {
          // Check access to custom group
          $groupId = \CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomGroup', $link->getTargetTable(), 'id', 'table_name');
          if (!\CRM_Core_BAO_CustomGroup::checkGroupAccess($groupId, \CRM_Core_Permission::VIEW)) {
            return;
          }
        }
        if ($link->isDeprecated()) {
          $deprecatedAlias = $link->getAlias();
          \CRM_Core_Error::deprecatedWarning("Deprecated join alias '$deprecatedAlias' used in APIv4 get. Should be changed to '{$deprecatedAlias}_id'");
        }
        $virtualField = $link->getSerialize();
        $baseTableAlias = $joinTreeNode['#table_alias'];
        if ($useBridgeTable) {
          // When joining custom fields that directly extend the bridge entity
          $baseTableAlias = $explicitJoin['bridge_table_alias'];
          if ($link->getBaseColumn() === 'id') {
            $link->setBaseColumn($explicitJoin['bridge_id_alias']);
          }
        }

        // Cache field info for retrieval by $this->getField()
        foreach ($link->getEntityFields() as $fieldObject) {
          $fieldArray = $fieldObject->toArray();
          // Set sql name of field, using column name for real joins
          if (!$virtualField) {
            $fieldArray['sql_name'] = '`' . $tableAlias . '`.`' . $fieldArray['column_name'] . '`';
          }
          // For virtual joins on serialized fields, the callback function will need the sql name of the serialized field
          // @see self::renderSerializedJoin()
          else {
            $fieldArray['sql_name'] = '`' . $baseTableAlias . '`.`' . $link->getBaseColumn() . '`';
          }
          // Custom fields will already have the group name prefixed
          $fieldName = $isCustom ? explode('.', $fieldArray['name'])[1] : $fieldArray['name'];
          $this->addSpecField($joinTreeNode[$joinName]['#path'] . $fieldName, $fieldArray);
        }

        // Serialized joins are rendered by this::renderSerializedJoin. Don't add their tables.
        if (!$virtualField) {
          $bao = $joinEntity ? CoreUtil::getBAOFromApiName($joinEntity) : NULL;
          $conditions = $link->getConditionsForJoin($baseTableAlias, $tableAlias);
          if ($bao) {
            $conditions = array_merge($conditions, $this->getAclClause($tableAlias, $bao, $joinPath));
          }
          $this->join('LEFT', $target, $tableAlias, $conditions);
        }

      }
      $joinTreeNode =& $joinTreeNode[$joinName];
      $useBridgeTable = FALSE;
    }
  }

  /**
   * @param string $side
   * @param string $tableName
   * @param string $tableAlias
   * @param array $conditions
   */
  public function join($side, $tableName, $tableAlias, $conditions) {
    // INNER JOINs take precedence over LEFT JOINs
    if ($side != 'LEFT' || !isset($this->joins[$tableAlias])) {
      $this->joins[$tableAlias] = $side;
      $this->query->join($tableAlias, "$side JOIN `$tableName` `$tableAlias` ON " . implode(' AND ', $conditions));
    }
  }

  /**
   * Performs a virtual join with a serialized field using FIND_IN_SET
   *
   * @param array $field
   * @return string
   */
  public static function renderSerializedJoin(array $field): string {
    $sep = \CRM_Core_DAO::VALUE_SEPARATOR;
    $id = CoreUtil::getIdFieldName($field['entity']);
    $searchFn = "FIND_IN_SET(`{$field['table_name']}`.`$id`, REPLACE({$field['sql_name']}, '$sep', ','))";
    return "(
      SELECT GROUP_CONCAT(
        `{$field['column_name']}`
        ORDER BY $searchFn
        SEPARATOR '$sep'
      )
      FROM `{$field['table_name']}`
      WHERE $searchFn
    )";
  }

  /**
   * @return FALSE|string
   */
  public function getFrom() {
    return CoreUtil::getTableName($this->getEntity());
  }

  /**
   * @return string
   */
  public function getEntity() {
    return $this->api->getEntityName();
  }

  /**
   * @return array
   */
  public function getSelect() {
    return $this->api->getSelect();
  }

  /**
   * @return array
   */
  public function getWhere() {
    return $this->api->getWhere();
  }

  /**
   * @return array
   */
  public function getHaving() {
    return $this->api->getHaving();
  }

  /**
   * @return array
   */
  public function getJoin() {
    return $this->api->getJoin();
  }

  /**
   * @return array
   */
  public function getGroupBy() {
    return $this->api->getGroupBy();
  }

  /**
   * @return array
   */
  public function getOrderBy() {
    return $this->api->getOrderBy();
  }

  /**
   * @return mixed
   */
  public function getLimit() {
    return $this->api->getLimit();
  }

  /**
   * @return mixed
   */
  public function getOffset() {
    return $this->api->getOffset();
  }

  /**
   * @return \CRM_Utils_SQL_Select
   */
  public function getQuery() {
    return $this->query;
  }

  /**
   * @return bool|string
   */
  public function getCheckPermissions() {
    return $this->api->getCheckPermissions();
  }

  /**
   * @param string $alias
   * @return array|NULL
   */
  public function getExplicitJoin($alias) {
    return $this->explicitJoins[$alias] ?? NULL;
  }

  /**
   * @param string $path
   * @param array $field
   */
  private function addSpecField($path, $field) {
    // Only add field to spec if we have permission
    if ($this->getCheckPermissions() && !empty($field['permission']) && !\CRM_Core_Permission::check($field['permission'])) {
      $this->apiFieldSpec[$path] = FALSE;
      return;
    }
    $this->apiFieldSpec[$path] = $field;
  }

  /**
   * Add something to the api's debug output if debugging is enabled
   *
   * @param $key
   * @param $item
   */
  public function debug($key, $item) {
    if ($this->api->getDebug()) {
      $this->api->_debugOutput[$key][] = $item;
    }
  }

}
