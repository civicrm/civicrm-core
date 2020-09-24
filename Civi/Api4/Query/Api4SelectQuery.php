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
 * * 'IS NOT NULL', or 'IS NULL', 'CONTAINS'.
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
   * @var array[]
   */
  protected $apiFieldSpec;

  /**
   * @var array
   */
  protected $entityFieldNames = [];

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
   * @param \Civi\Api4\Generic\DAOGetAction $apiGet
   */
  public function __construct($apiGet) {
    $this->api = $apiGet;

    // Always select ID of main table unless grouping by something else
    $this->forceSelectId = !$this->getGroupBy() || $this->getGroupBy() === ['id'];

    // Build field lists
    foreach ($this->api->entityFields() as $field) {
      $this->entityFieldNames[] = $field['name'];
      $field['sql_name'] = '`' . self::MAIN_TABLE_ALIAS . '`.`' . $field['column_name'] . '`';
      $this->addSpecField($field['name'], $field);
    }

    $tableName = CoreUtil::getTableName($this->getEntity());
    $this->query = \CRM_Utils_SQL_Select::from($tableName . ' ' . self::MAIN_TABLE_ALIAS);

    // Add ACLs first to avoid redundant subclauses
    $baoName = CoreUtil::getBAOFromApiName($this->getEntity());
    $this->query->where($this->getAclClause(self::MAIN_TABLE_ALIAS, $baoName));
  }

  /**
   * Builds main final sql statement after initialization.
   *
   * @return string
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   */
  public function getSql() {
    // Add explicit joins. Other joins implied by dot notation may be added later
    $this->addExplicitJoins();
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
    $this->addExplicitJoins();
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
      $sql = "SELECT count(*) AS `c` FROM ( $subquery ) AS rows";
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
      $select = $this->entityFieldNames;
    }
    else {
      if ($this->forceSelectId) {
        $select = array_merge(['id'], $select);
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

      foreach ($wildFields as $item) {
        $pos = array_search($item, array_values($select));
        $this->autoJoinFK($item);
        $matches = SelectUtil::getMatchingFields($item, array_keys($this->apiFieldSpec));
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
        if (!$field) {
          $select = array_diff($select, [$item]);
          $this->debug('undefined_fields', $fieldName);
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
      $this->query->having($this->treeWalkClauses($clause, 'HAVING'));
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
      $expr = $this->getExpression($item);
      $column = $expr->render($this->apiFieldSpec);

      // Use FIELD() function to sort on pseudoconstant values
      $suffix = strstr($item, ':');
      if ($suffix && $expr->getType() === 'SqlField') {
        $field = $this->getField($item);
        $options = FormattingUtil::getPseudoconstantList($field, substr($suffix, 1));
        if ($options) {
          asort($options);
          $column = "FIELD($column,'" . implode("','", array_keys($options)) . "')";
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
   * @return string SQL where clause
   *
   * @throws \API_Exception
   * @uses composeClause() to generate the SQL etc.
   */
  protected function treeWalkClauses($clause, $type) {
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
    if (!in_array($operator, CoreUtil::getOperators(), TRUE)) {
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
          $field = $this->getField($expr);
          FormattingUtil::formatInputValue($value, $expr, $field);
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
            FormattingUtil::formatInputValue($value, $expr, $field);
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
        $field = $this->getField($fieldName);
        FormattingUtil::formatInputValue($value, $fieldName, $field);
      }
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
    if (count($stack) === 1 && in_array($stack[0], $this->aclFields, TRUE)) {
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
    if ($strict && !$field) {
      throw new \API_Exception("Invalid field '$fieldName'");
    }
    $this->apiFieldSpec[$expr] = $field;
    return $field;
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
      // Ensure alias is a safe string, and supply default if not given
      $alias = $alias ? \CRM_Utils_String::munge($alias) : strtolower($entity);
      // First item in the array is a boolean indicating if the join is required (aka INNER or LEFT).
      // The rest are join conditions.
      $side = array_shift($join) ? 'INNER' : 'LEFT';
      // Add all fields from joined entity to spec
      $joinEntityGet = \Civi\API\Request::create($entity, 'get', ['version' => 4, 'checkPermissions' => $this->getCheckPermissions()]);
      foreach ($joinEntityGet->entityFields() as $field) {
        $field['sql_name'] = '`' . $alias . '`.`' . $field['column_name'] . '`';
        $this->addSpecField($alias . '.' . $field['name'], $field);
      }
      if (!empty($join[0]) && is_string($join[0]) && \CRM_Utils_Rule::alphanumeric($join[0])) {
        $conditions = $this->getBridgeJoin($join, $entity, $alias);
      }
      else {
        $conditions = $this->getJoinConditions($join, $entity, $alias);
      }
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
   * @param array $joinTree
   * @param string $joinEntity
   * @param string $alias
   * @return array
   */
  private function getJoinConditions($joinTree, $joinEntity, $alias) {
    $conditions = [];
    // getAclClause() expects a stack of 1-to-1 join fields to help it dedupe, but this is more flexible,
    // so unless this is a direct 1-to-1 join with the main entity, we'll just hack it
    // with a padded empty stack to bypass its deduping.
    $stack = [NULL, NULL];
    // If we're not explicitly referencing the joinEntity ID in the ON clause, search for a default
    $explicitId = array_filter($joinTree, function($clause) use ($alias) {
      list($sideA, $op, $sideB) = array_pad((array) $clause, 3, NULL);
      return $op === '=' && ($sideA === "$alias.id" || $sideB === "$alias.id");
    });
    if (!$explicitId) {
      foreach ($this->apiFieldSpec as $name => $field) {
        if ($field['entity'] !== $joinEntity && $field['fk_entity'] === $joinEntity) {
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
   * Join onto a BridgeEntity table
   *
   * @param array $joinTree
   * @param string $joinEntity
   * @param string $alias
   * @return array
   * @throws \API_Exception
   */
  protected function getBridgeJoin(&$joinTree, $joinEntity, $alias) {
    $bridgeEntity = array_shift($joinTree);
    if (!is_a('\Civi\Api4\\' . $bridgeEntity, '\Civi\Api4\Generic\BridgeEntity', TRUE)) {
      throw new \API_Exception("Illegal bridge entity specified: " . $bridgeEntity);
    }
    $bridgeAlias = $alias . '_via_' . strtolower($bridgeEntity);
    $bridgeTable = CoreUtil::getTableName($bridgeEntity);
    $joinTable = CoreUtil::getTableName($joinEntity);
    $bridgeEntityGet = \Civi\API\Request::create($bridgeEntity, 'get', ['version' => 4, 'checkPermissions' => $this->getCheckPermissions()]);
    $fkToJoinField = $fkToBaseField = NULL;
    // Find the bridge field that links to the joinEntity (either an explicit FK or an entity_id/entity_table combo)
    foreach ($bridgeEntityGet->entityFields() as $name => $field) {
      if ($field['fk_entity'] === $joinEntity || (!$fkToJoinField && $name === 'entity_id')) {
        $fkToJoinField = $name;
      }
    }
    // Get list of entities allowed for entity_table
    if (array_key_exists('entity_id', $bridgeEntityGet->entityFields())) {
      $entityTables = (array) civicrm_api4($bridgeEntity, 'getFields', [
        'checkPermissions' => FALSE,
        'where' => [['name', '=', 'entity_table']],
        'loadOptions' => TRUE,
      ], ['options'])->first();
    }
    // If bridge field to joinEntity is entity_id, validate entity_table is allowed
    if (!$fkToJoinField || ($fkToJoinField === 'entity_id' && !array_key_exists($joinTable, $entityTables))) {
      throw new \API_Exception("Unable to join $bridgeEntity to $joinEntity");
    }
    // Create link between bridge entity and join entity
    $joinConditions = [
      "`$bridgeAlias`.`$fkToJoinField` = `$alias`.`id`",
    ];
    if ($fkToJoinField === 'entity_id') {
      $joinConditions[] = "`$bridgeAlias`.`entity_table` = '$joinTable'";
    }
    // Register fields from the bridge entity as if they belong to the join entity
    foreach ($bridgeEntityGet->entityFields() as $name => $field) {
      if ($name == 'id' || $name == $fkToJoinField || ($name == 'entity_table' && $fkToJoinField == 'entity_id')) {
        continue;
      }
      if ($field['fk_entity'] || (!$fkToBaseField && $name == 'entity_id')) {
        $fkToBaseField = $name;
      }
      // Note these fields get a sql alias pointing to the bridge entity, but an api alias pretending they belong to the join entity
      $field['sql_name'] = '`' . $bridgeAlias . '`.`' . $field['column_name'] . '`';
      $this->addSpecField($alias . '.' . $field['name'], $field);
    }
    // Move conditions for the bridge join out of the joinTree
    $bridgeConditions = [];
    $joinTree = array_filter($joinTree, function($clause) use ($fkToBaseField, $alias, $bridgeAlias, &$bridgeConditions) {
      list($sideA, $op, $sideB) = array_pad((array) $clause, 3, NULL);
      if ($op === '=' && $sideB && ($sideA === "$alias.$fkToBaseField" || $sideB === "$alias.$fkToBaseField")) {
        $expr = $sideA === "$alias.$fkToBaseField" ? $sideB : $sideA;
        $bridgeConditions[] = "`$bridgeAlias`.`$fkToBaseField` = " . $this->getExpression($expr)->render($this->apiFieldSpec);
        return FALSE;
      }
      elseif ($op === '=' && $fkToBaseField == 'entity_id' && ($sideA === "$alias.entity_table" || $sideB === "$alias.entity_table")) {
        $expr = $sideA === "$alias.entity_table" ? $sideB : $sideA;
        $bridgeConditions[] = "`$bridgeAlias`.`entity_table` = " . $this->getExpression($expr)->render($this->apiFieldSpec);
        return FALSE;
      }
      return TRUE;
    });
    // If no bridge conditions were specified, link it to the base entity
    if (!$bridgeConditions) {
      $bridgeConditions[] = "`$bridgeAlias`.`$fkToBaseField` = a.id";
      if ($fkToBaseField == 'entity_id') {
        if (!array_key_exists($this->getFrom(), $entityTables)) {
          throw new \API_Exception("Unable to join $bridgeEntity to " . $this->getEntity());
        }
        $bridgeConditions[] = "`$bridgeAlias`.`entity_table` = '" . $this->getFrom() . "'";
      }
    }

    $this->join('LEFT', $bridgeTable, $bridgeAlias, $bridgeConditions);

    $baoName = CoreUtil::getBAOFromApiName($joinEntity);
    $acls = array_values($this->getAclClause($alias, $baoName, [NULL, NULL]));
    return array_merge($acls, $joinConditions);
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
      $this->addSpecField($prefix . $fieldArray['name'], $fieldArray);
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
