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
use Civi\Api4\Service\Schema\Joiner;
use Civi\Api4\Utils\FormattingUtil;
use Civi\Api4\Utils\CoreUtil;
use Civi\Api4\Utils\SelectUtil;

/**
 * Constructs SELECT FROM queries for API4 GET actions.
 */
class Api4SelectQuery extends Api4Query {

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
   * @var array
   */
  protected $aclFields = [];

  /**
   * @var bool
   */
  public $forceSelectId = TRUE;

  /**
   * @var array{entity: string, alias: string, table: string, on: array, bridge: string|NULL}[]
   */
  private $explicitJoins = [];

  /**
   * @var array
   */
  private $entityAccess = [];

  /**
   * Explicit join currently being processed
   * @var array
   */
  private $openJoin;

  /**
   * @param \Civi\Api4\Generic\DAOGetAction $api
   */
  public function __construct($api) {
    parent::__construct($api);

    // Always select ID of main table unless grouping by something else
    $keys = (array) CoreUtil::getInfoItem($this->getEntity(), 'primary_key');
    $this->forceSelectId = !$this->isAggregateQuery() || array_intersect($this->getGroupBy(), $keys);

    // Build field lists
    foreach ($this->api->entityFields() as $field) {
      $field['sql_name'] = '`' . self::MAIN_TABLE_ALIAS . '`.`' . $field['column_name'] . '`';
      $this->addSpecField($field['name'], $field);
    }

    $tableExpr = CoreUtil::getTableExpr($this->getEntity());
    $this->query = \CRM_Utils_SQL_Select::from($tableExpr . ' ' . self::MAIN_TABLE_ALIAS);

    $this->fillEntityValues();

    $this->entityAccess[$this->getEntity()] = TRUE;

    // Add ACLs first to avoid redundant subclauses
    $this->query->where($this->getAclClause(self::MAIN_TABLE_ALIAS, $this->getEntity(), NULL, $this->getWhere()));

    // Add required conditions if specified by entity
    $requiredConditions = CoreUtil::getInfoItem($this->getEntity(), 'where') ?? [];
    foreach ($requiredConditions as $requiredField => $requiredValue) {
      $this->api->addWhere($requiredField, '=', $requiredValue);
    }

    // Add explicit joins. Other joins implied by dot notation may be added later
    $this->addExplicitJoins();
  }

  /**
   * Why walk when you can
   *
   * @return array
   */
  public function run(): array {
    $results = $this->getResults();
    FormattingUtil::formatOutputValues($results, $this->apiFieldSpec, 'get', $this->selectAliases);
    return $results;
  }

  /**
   * @return int
   * @throws \CRM_Core_Exception
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
   * @throws \CRM_Core_Exception
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
        $keys = (array) CoreUtil::getInfoItem($this->getEntity(), 'primary_key');
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
          $customSelect[] = str_replace([' '], '_', $groupName) . '.*';
        }
        array_splice($select, $customStar, 1, $customSelect);
      }

      // Expand wildcards in joins (the api wrapper already expanded non-joined wildcards)
      $wildFields = array_filter($select, function($item) {
        return str_contains($item, '*') && str_contains($item, '.') && !str_contains($item, '(') && !str_contains($item, ' ');
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
          throw new \CRM_Core_Exception('Cannot use existing field name as alias');
        }
        $this->selectAliases[$alias] = $expr->getExpr();
        $this->query->select($expr->render($this, TRUE));
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
        throw new \CRM_Core_Exception("Invalid sort direction. Cannot order by $item $dir");
      }

      try {
        $expr = $this->getExpression($item);
        $column = $this->renderExpr($expr);

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
      catch (\CRM_Core_Exception $e) {
        // Silently ignore fields the user lacks permission to see
        if (is_a($e, 'Civi\API\Exception\UnauthorizedException')) {
          $this->debug('unauthorized_fields', $item);
          continue;
        }
        if (!empty($this->selectAliases[$item])) {
          $column = '`' . $item . '`';
        }
        else {
          throw new \CRM_Core_Exception("Invalid field '{$item}'");
        }
      }

      $this->query->orderBy("$column $dir");
    }
  }

  /**
   * This takes all the where clauses that use `=` to build an array of known values which every record must have.
   *
   * This gets passed to `FormattingUtil::getPseudoconstantList` to evaluate conditional pseudoconstants.
   *
   * @throws \CRM_Core_Exception
   */
  private function fillEntityValues() {
    foreach ($this->getWhere() as $clause) {
      [$fieldName, $operator, $value] = array_pad($clause, 3, NULL);
      if (
        // If the operator is `=`
        $operator === '=' &&
        // And is using a literal value
        empty($clause[3]) &&
        // And references a field not a function
        !strpos($fieldName, ')')
      ) {
        $field = $this->getField($fieldName);
        if ($field) {
          // Resolve pseudoconstant suffix
          FormattingUtil::formatInputValue($value, $fieldName, $field, $this->entityValues, $operator);
          // If the operator is still `=` (so not a weird date range transformation)
          if ($operator === '=') {
            // Strip pseudoconstant suffix
            [$fieldNameOnly] = explode(':', $fieldName);
            $this->entityValues[$fieldNameOnly] = $value;
          }
        }
      }
    }
  }

  /**
   * Get acl clause for an entity
   *
   * @param string $tableAlias
   * @param string $entityName
   * @param array|null $stack
   * @param array[] $conditions
   * @return array
   */
  public function getAclClause(string $tableAlias, string $entityName, ?array $stack = NULL, array $conditions = []): array {
    if (!$this->getCheckPermissions()) {
      return [];
    }
    // Glean entity values from the WHERE or ON clause conditions
    $entityValues = [];
    foreach ($conditions as $condition) {
      [$fieldExpr, $operator, $valueExpr, $isExpr] = array_pad((array) $condition, 4, NULL);
      if (in_array($operator, ['=', 'IN'], TRUE)) {
        // If flag is set then value must be parsed as an expression
        if ($isExpr && is_string($valueExpr)) {
          $expr = SqlExpression::convert($valueExpr);
          $valueExpr = in_array($expr->getType(), ['SqlString', 'SqlNumber'], TRUE) ? $expr->getExpr() : NULL;
        }
        if (isset($valueExpr)) {
          [$fieldPath] = explode(':', $fieldExpr);
          $fieldSpec = $this->getField($fieldPath);
          $entityValues[$fieldPath] = $valueExpr;
          if ($fieldSpec) {
            FormattingUtil::formatInputValue($entityValues[$fieldPath], $fieldExpr, $fieldSpec, $entityValues, $operator);
          }
        }
      }
    }
    $baoName = CoreUtil::getBAOFromApiName($entityName);
    $clauses = $baoName::getSelectWhereClause($tableAlias, $entityName, $entityValues);
    if ($stack === NULL) {
      // Track field clauses added to the main entity
      $this->aclFields = array_keys($clauses);
    }
    // Dedupe these clauses with ones that have already been applied to the entity being joined on
    else {
      $stackLast = array_pop($stack);
      $stackPrev = array_pop($stack);
      $lastField = $stackLast;
      if ($stackLast && $stackPrev) {
        // Implicit join
        if (str_starts_with($lastField, "$stackPrev.")) {
          $lastField = substr($lastField, strlen($stackPrev) + 1);
        }
        // Explicit join
        elseif (str_starts_with($lastField, "$tableAlias.")) {
          $lastField = substr($lastField, strlen($tableAlias) + 1);
        }
        foreach ($clauses as $fieldName => $clause) {
          if ($fieldName === $lastField && in_array($stackPrev, $this->aclFields, TRUE)) {
            unset($clauses[$fieldName]);
            $this->aclFields[] = $stackLast;
          }
          $this->aclFields[] = $stackPrev . '.' . $fieldName;
        }
      }
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
   * @throws \CRM_Core_Exception
   * @throws UnauthorizedException
   */
  public function getField(string $expr, bool $strict = FALSE):? array {
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
      throw new \CRM_Core_Exception("Invalid field '$fieldName'");
    }
    if ($strict && $field === FALSE) {
      throw new UnauthorizedException("Unauthorized field '$fieldName'");
    }
    if ($field) {
      $this->apiFieldSpec[$expr] = $field;
    }
    return $field ?: NULL;
  }

  public function getFieldSibling(array $field, string $siblingFieldName) {
    $prefix = ($field['explicit_join'] ? $field['explicit_join'] . '.' : '') . ($field['implicit_join'] ? $field['implicit_join'] . '.' : '');
    return $this->getField($prefix . $siblingFieldName);
  }

  /**
   * Check the "gatekeeper" permissions for performing "get" on a given entity.
   *
   * @param $entity
   * @return bool
   */
  public function checkEntityAccess($entity) {
    if (!$this->getCheckPermissions()) {
      return CoreUtil::entityExists($entity);
    }
    if (!isset($this->entityAccess[$entity])) {
      try {
        $this->entityAccess[$entity] = (bool) civicrm_api4($entity, 'getActions', [
          'where' => [['name', '=', 'get']],
          'select' => ['name'],
        ])->first();
      }
      // Anonymous users might not even be allowed to use 'getActions'
      // Or tne entity might not exist
      catch (\CRM_Core_Exception $e) {
        $this->entityAccess[$entity] = FALSE;
      }
    }
    return $this->entityAccess[$entity];
  }

  /**
   * Join onto other entities as specified by the api call.
   *
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\NotImplementedException
   */
  private function addExplicitJoins() {
    foreach ($this->getJoin() as $join) {
      // First item in the array is the entity name
      $entity = array_shift($join);
      // Which might contain an alias. Split on the keyword "AS"
      [$entity, $alias] = array_pad(explode(' AS ', $entity), 2, NULL);
      // Ensure permissions
      if (!$this->checkEntityAccess($entity)) {
        continue;
      }
      // Ensure alias is a safe string, and supply default if not given
      $alias = $alias ?: strtolower($entity);
      if ($alias === self::MAIN_TABLE_ALIAS || !preg_match('/^[-\w]{1,256}$/', $alias)) {
        throw new \CRM_Core_Exception('Illegal join alias: "' . $alias . '"');
      }
      // First item in the array is a boolean indicating if the join is required (aka INNER or LEFT).
      // The rest are join conditions.
      $side = array_shift($join);
      // If omitted, supply default (LEFT); and legacy support for boolean values
      if (!is_string($side)) {
        $side = $side ? 'INNER' : 'LEFT';
      }
      if (!in_array($side, ['INNER', 'LEFT', 'EXCLUDE'])) {
        throw new \CRM_Core_Exception("Illegal value for join side: '$side'.");
      }
      if ($side === 'EXCLUDE') {
        $side = 'LEFT';
        $this->api->addWhere("$alias.id", 'IS NULL');
      }
      // Add required conditions if specified by entity
      $requiredConditions = CoreUtil::getInfoItem($entity, 'where') ?? [];
      foreach ($requiredConditions as $requiredField => $requiredValue) {
        $join[] = [$alias . '.' . $requiredField, '=', "'$requiredValue'"];
      }
      // Add all fields from joined entity to spec
      $joinEntityGet = \Civi\API\Request::create($entity, 'get', ['version' => 4, 'checkPermissions' => $this->getCheckPermissions()]);
      $joinEntityFields = $joinEntityGet->entityFields();
      foreach ($joinEntityFields as $field) {
        $field['sql_name'] = '`' . $alias . '`.`' . $field['column_name'] . '`';
        $field['explicit_join'] = $alias;
        $this->addSpecField($alias . '.' . $field['name'], $field);
      }
      $tableName = CoreUtil::getTableName($entity);
      $tableExpr = CoreUtil::getTableExpr($entity);
      $this->startNewJoin($tableExpr, $alias);
      // Save join info to be retrieved by $this->getExplicitJoin()
      $joinOn = array_filter(array_filter($join, 'is_array'));
      $this->explicitJoins[$alias] = [
        'entity' => $entity,
        'alias' => $alias,
        'table' => $tableName,
        'bridge' => NULL,
        'on' => $joinOn,
      ];
      // If the first condition is a string, it's the name of a bridge entity
      if (!empty($join[0]) && is_string($join[0]) && \CRM_Utils_Rule::alphanumeric($join[0])) {
        $conditions = $this->addBridgeJoin($join, $entity, $alias);
      }
      else {
        $conditions = $this->getJoinConditions($join, $entity, $alias, $joinEntityFields);
        foreach ($joinOn as $clause) {
          $conditions[] = $this->treeWalkClauses($clause, 'ON');
        }
      }
      $this->finishJoin($side, $conditions);
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
    // Used to dedupe acl clauses
    $aclStack = [];
    // See if the ON clause already contains an FK reference to joinEntity
    $explicitFK = array_filter($joinTree, function($clause) use ($alias, $joinEntityFields, &$aclStack) {
      [$sideA, $op, $sideB, $isExpr] = array_pad((array) $clause, 4, TRUE);
      if ($op !== '=' || !$isExpr || !is_string($sideB) || !strlen($sideB)) {
        return FALSE;
      }
      foreach ([2 => $sideA, 0 => $sideB] as $otherSide => $expr) {
        if (!str_starts_with($expr, "$alias.")) {
          continue;
        }
        $joinFieldName = str_replace("$alias.", '', $expr);
        $otherSideField = $this->apiFieldSpec[$clause[$otherSide]] ?? NULL;
        // Check for explicit link to FK entity (include entity_id for dynamic FKs)
        if (
          // FK FROM the other entity
          !empty($otherSideField['fk_entity']) ||
          // Unique field - might be a link FROM the other entity
          // FIXME: This is just guessing. We ought to check the schema for all unique fields
          in_array($joinFieldName, ['id', 'name'], TRUE) ||
          // FK field - might be a link TO the other entity
          !empty($joinEntityFields[$joinFieldName]['dfk_entities']) || !empty($joinEntityFields[$joinFieldName]['fk_entity'])
        ) {
          // If the join links to a field on another entity
          if (preg_match('/^[_a-z0-9.]+$/i', $clause[$otherSide])) {
            $aclStack = [$clause[$otherSide], $expr];
            return TRUE;
          }
        }
      }
      return FALSE;
    });
    // If we're not explicitly referencing the ID (or some other FK field) of the joinEntity, search for a default
    // FIXME: This guesswork ought to emit a deprecation notice. SearchKit doesn't use it.
    if (!$explicitFK) {
      foreach ($this->apiFieldSpec as $name => $field) {
        if (!is_array($field) || $field['type'] !== 'Field') {
          continue;
        }
        $fkColumn = $field['fk_column'] ?? 'id';
        if ($field['entity'] !== $joinEntity && $field['fk_entity'] === $joinEntity) {
          $conditions[] = $this->treeWalkClauses([$name, '=', "$alias.$fkColumn"], 'ON');
        }
        elseif (str_starts_with($name, "$alias.") && substr_count($name, '.') === 1 && $field['fk_entity'] === $this->getEntity()) {
          $conditions[] = $this->treeWalkClauses([$name, '=', $fkColumn], 'ON');
          $aclStack = ['id', $name];
        }
      }
      // Hmm, if we came up with > 1 condition, then it's ambiguous how it should be joined so we won't return anything but the generic ACLs
      if (count($conditions) > 1) {
        $aclStack = [];
        $conditions = [];
      }
    }
    // Gather join conditions to help optimize aclClause
    $joinOn = [];
    foreach ($joinTree as $clause) {
      if (is_array($clause) && isset($clause[2])) {
        // Set 4th param ($isExpr) default to TRUE because this is an ON clause
        $joinOn[] = array_pad($clause, 4, TRUE);
      }
    }
    $acls = array_values($this->getAclClause($alias, $joinEntity, $aclStack, $joinOn));
    return array_merge($acls, $conditions);
  }

  /**
   * Join via a Bridge table using a join within a join
   *
   * This creates a double-join in sql that appears to the API user like a single join.
   *
   * @param array $joinTree
   * @param string $joinEntity
   * @param string $alias
   * @throws \CRM_Core_Exception
   */
  protected function addBridgeJoin($joinTree, $joinEntity, $alias) {
    $bridgeEntity = array_shift($joinTree);
    $this->explicitJoins[$alias]['bridge'] = $bridgeEntity;

    $bridgeAlias = $alias . '_via_' . strtolower($bridgeEntity);

    $joinTableExpr = CoreUtil::getTableExpr($joinEntity);
    $bridgeTableExpr = CoreUtil::getTableExpr($bridgeEntity);
    [$baseRef, $joinRef] = $this->getBridgeRefs($bridgeEntity, $joinEntity);

    $this->registerBridgeJoinFields($bridgeEntity, $joinRef, $baseRef, $alias, $bridgeAlias);

    // Used to dedupe acl clauses
    $aclStack = [];

    $linkConditions = $this->getBridgeLinkConditions($bridgeAlias, $alias, $joinEntity, $joinRef);

    $bridgeConditions = $this->getBridgeJoinConditions($joinTree, $baseRef, $alias, $bridgeAlias, $bridgeEntity, $aclStack);

    $acls = array_values($this->getAclClause($alias, $joinEntity, $aclStack));

    // Info needed to attach custom fields to the bridge entity instead of the base entity
    // because the custom fields are joined first and the base entity might not be added yet.
    // @see Joinable::getConditionsForJoin
    $this->openJoin['bridgeAlias'] = $bridgeAlias;
    $this->openJoin['bridgeKey'] = $joinRef->getReferenceKey();
    $this->openJoin['bridgeCondition'] = array_intersect_key($linkConditions, [1 => 1]);

    $outerConditions = [];
    foreach (array_filter($joinTree) as $clause) {
      $outerConditions[] = $this->treeWalkClauses($clause, 'ON');
    }

    // Info needed for joining custom fields extending the bridge entity
    $this->explicitJoins[$alias]['bridge_table_alias'] = $bridgeAlias;
    // Invert the join so all nested joins will link to the bridge entity
    $this->openJoin['table'] = $bridgeTableExpr;
    $this->openJoin['alias'] = $bridgeAlias;

    // Add main table as inner join
    $innerConditions = array_merge($linkConditions, $acls);
    $this->addJoin('INNER', $joinTableExpr, $alias, $bridgeAlias, $innerConditions);
    return array_merge($outerConditions, $bridgeConditions);
  }

  /**
   * Get the table name and 2 reference columns from a bridge entity
   *
   * @param string $bridgeEntity
   * @param string $joinEntity
   * @return array
   * @throws \CRM_Core_Exception
   */
  private function getBridgeRefs(string $bridgeEntity, string $joinEntity): array {
    $bridges = CoreUtil::getInfoItem($bridgeEntity, 'bridge') ?? [];
    /** @var \CRM_Core_DAO $bridgeDAO */
    $bridgeDAO = CoreUtil::getInfoItem($bridgeEntity, 'dao');
    $bridgeEntityFields = \Civi\API\Request::create($bridgeEntity, 'get', ['version' => 4, 'checkPermissions' => $this->getCheckPermissions()])->entityFields();

    // Get the 2 bridge reference columns as CRM_Core_Reference_* objects
    $referenceColumns = $bridgeDAO::getReferenceColumns();
    foreach ($referenceColumns as $joinRef) {
      $refKey = $joinRef->getReferenceKey();
      if (array_key_exists($refKey, $bridges) && in_array($joinEntity, $joinRef->getTargetEntities())) {
        if (!empty($bridgeEntityFields[$refKey]['fk_entity']) && $joinEntity !== $bridgeEntityFields[$refKey]['fk_entity']) {
          continue;
        }
        foreach ($bridgeDAO::getReferenceColumns() as $baseRef) {
          if ($baseRef->getReferenceKey() === $bridges[$refKey]['to']) {
            return [$baseRef, $joinRef];
          }
        }
      }
    }
    throw new \CRM_Core_Exception("Unable to join $bridgeEntity to $joinEntity");
  }

  /**
   * Get the clause to link bridge entity with join entity
   *
   * @param string $bridgeAlias
   * @param string $joinAlias
   * @param string $joinEntity
   * @param \CRM_Core_Reference_Basic $joinRef
   * @return array
   */
  private function getBridgeLinkConditions(string $bridgeAlias, string $joinAlias, string $joinEntity, \CRM_Core_Reference_Basic $joinRef): array {
    $linkConditions = [
      "`$bridgeAlias`.`{$joinRef->getReferenceKey()}` = `$joinAlias`.`{$joinRef->getTargetKey()}`",
    ];
    // For dynamic references, also add the type column (e.g. `entity_table`)
    if ($joinRef->getTypeColumn()) {
      $dfkOption = array_search($joinEntity, $joinRef->getTargetEntities());
      $linkConditions[] = "`$bridgeAlias`.`{$joinRef->getTypeColumn()}` = '$dfkOption'";
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
   */
  private function registerBridgeJoinFields($bridgeEntity, $joinRef, $baseRef, string $alias, string $bridgeAlias): void {
    $bridgeFkFields = [$joinRef->getReferenceKey(), $joinRef->getTypeColumn(), $baseRef->getReferenceKey(), $baseRef->getTypeColumn()];
    $bridgeEntityClass = CoreUtil::getApiClass($bridgeEntity);
    $bridgeIdColumn = CoreUtil::getIdFieldName($bridgeEntity);
    foreach ($bridgeEntityClass::get($this->getCheckPermissions())->entityFields() as $name => $field) {
      if ($name === $bridgeIdColumn || in_array($name, $bridgeFkFields, TRUE)) {
        continue;
      }
      // Fields get a sql alias pointing to the bridge entity,
      $field['sql_name'] = '`' . $bridgeAlias . '`.`' . $field['column_name'] . '`';
      $field['explicit_join'] = $alias;
      $this->addSpecField($alias . '.' . $name, $field);
    }
  }

  /**
   * Extract bridge join conditions from the joinTree if any, else supply default conditions for join to base entity
   *
   * @param array $joinTree
   * @param \CRM_Core_Reference_Basic $baseRef
   * @param string $alias
   * @param string $bridgeAlias
   * @param string $bridgeEntity
   * @param array $aclStack
   * @return string[]
   * @throws \CRM_Core_Exception
   */
  private function getBridgeJoinConditions(array &$joinTree, \CRM_Core_Reference_Basic $baseRef, string $alias, string $bridgeAlias, string $bridgeEntity, array &$aclStack): array {
    $bridgeConditions = [];
    // Find explicit bridge join conditions and move them out of the joinTree
    $joinTree = array_filter($joinTree, function ($clause) use ($baseRef, $alias, $bridgeAlias, &$bridgeConditions, &$aclStack) {
      [$sideA, $op, $sideB] = array_pad((array) $clause, 3, NULL);
      // Skip AND/OR/NOT branches
      if (!$sideB) {
        return TRUE;
      }
      // If this condition makes an explicit link between the bridge and another entity
      if ($op === '=' && $sideB && ($sideA === "$alias.{$baseRef->getReferenceKey()}" || $sideB === "$alias.{$baseRef->getReferenceKey()}")) {
        $expr = $sideA === "$alias.{$baseRef->getReferenceKey()}" ? $sideB : $sideA;
        $bridgeConditions[] = "`$bridgeAlias`.`{$baseRef->getReferenceKey()}` = " . $this->getExpression($expr)->render($this);
        $aclStack = [$expr, $bridgeAlias . '.' . $baseRef->getReferenceKey()];
        return FALSE;
      }
      // Explicit link with dynamic "entity_table" column
      elseif ($op === '=' && $baseRef->getTypeColumn() && ($sideA === "$alias.{$baseRef->getTypeColumn()}" || $sideB === "$alias.{$baseRef->getTypeColumn()}")) {
        $expr = $sideA === "$alias.{$baseRef->getTypeColumn()}" ? $sideB : $sideA;
        $bridgeConditions[] = "`$bridgeAlias`.`{$baseRef->getTypeColumn()}` = " . $this->getExpression($expr)->render($this);
        return FALSE;
      }
      return TRUE;
    });
    // If no bridge conditions were specified, link it to the base entity
    if (!$bridgeConditions) {
      if (!in_array($this->getEntity(), $baseRef->getTargetEntities())) {
        throw new \CRM_Core_Exception("Unable to join $bridgeEntity to " . $this->getEntity());
      }
      $bridgeConditions[] = "`$bridgeAlias`.`{$baseRef->getReferenceKey()}` = a.`{$baseRef->getTargetKey()}`";
      $aclStack = [$baseRef->getTargetKey(), $bridgeAlias . '.' . $baseRef->getReferenceKey()];
      if ($baseRef->getTypeColumn()) {
        $dfkOption = array_search($this->getEntity(), $baseRef->getTargetEntities());
        $bridgeConditions[] = "`$bridgeAlias`.`{$baseRef->getTypeColumn()}` = '$dfkOption'";
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
    $joiner = new Joiner(CoreUtil::getSchemaMap());

    $pathArray = explode('.', $key);
    // The last item in the path is the field name. We don't care about that; we'll add all fields from the joined entity.
    array_pop($pathArray);

    $baseTableAlias = $this::MAIN_TABLE_ALIAS;

    // If the first item is the name of an explicit join, use it as the base & shift it off the path
    $explicitJoin = $this->getExplicitJoin($pathArray[0]);
    if ($explicitJoin) {
      $baseTableAlias = array_shift($pathArray);
    }
    $explicitJoinPrefix = $explicitJoin ? $explicitJoin['alias'] . '.' : '';

    // Ensure joinTree array contains base table
    $this->joinTree[$baseTableAlias]['#table_alias'] = $baseTableAlias;
    $this->joinTree[$baseTableAlias]['#path'] = $explicitJoinPrefix;
    // During iteration this variable will refer to the current position in the tree
    $joinTreeNode =& $this->joinTree[$baseTableAlias];

    $useBridgeTable = FALSE;
    try {
      $joinPath = $joiner->getPath($explicitJoin['table'] ?? $this->getFrom(), $pathArray);
    }
    catch (\CRM_Core_Exception $e) {
      if (!empty($explicitJoin['bridge'])) {
        // Try looking up custom field in bridge entity instead
        try {
          $useBridgeTable = TRUE;
          $joinPath = $joiner->getPath(CoreUtil::getTableName($explicitJoin['bridge']), $pathArray);
        }
        catch (\CRM_Core_Exception $e) {
          return;
        }
      }
      else {
        // Because the select clause silently ignores unknown fields, this function shouldn't throw exceptions
        return;
      }
    }

    // Used to dedupe acl clauses
    if (isset($pathArray[0])) {
      $aclStack = [$explicitJoinPrefix . $pathArray[0]];
    }

    foreach ($joinPath as $joinName => $link) {
      $aclStack[] = $joinName . '.' . $link->getTargetColumn();
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
          $allowedGroup = \CRM_Core_BAO_CustomGroup::getGroup(['table_name' => $link->getTargetTable()], \CRM_Core_Permission::VIEW);
          if (!$allowedGroup) {
            return;
          }
        }
        if ($link->isDeprecatedBy()) {
          $deprecatedAlias = $link->getAlias();
          \CRM_Core_Error::deprecatedWarning("Deprecated join alias '$deprecatedAlias' used in APIv4 {$this->getEntity()} join to $joinEntity. Should be changed to '{$link->isDeprecatedBy()}'.");
        }
        $virtualField = $link->getSerialize();
        $baseTableAlias = $joinTreeNode['#table_alias'];
        if ($useBridgeTable) {
          // When joining custom fields that directly extend the bridge entity
          $baseTableAlias = $explicitJoin['bridge_table_alias'];
        }

        // Cache field info for retrieval by $this->getField()
        foreach ($link->getEntityFields() as $fieldArray) {
          // Set sql name of field, using column name for real joins
          if (!$virtualField) {
            $fieldArray['sql_name'] = '`' . $tableAlias . '`.`' . $fieldArray['column_name'] . '`';
          }
          // For virtual joins on serialized fields, the callback function will need the sql name of the serialized field
          // @see self::renderSerializedJoin()
          else {
            $fieldArray['sql_name'] = '`' . $baseTableAlias . '`.`' . $link->getBaseColumn() . '`';
          }
          $fieldArray['implicit_join'] = rtrim($joinTreeNode[$joinName]['#path'], '.');
          $fieldArray['explicit_join'] = $explicitJoin ? $explicitJoin['alias'] : NULL;
          // Custom fields will already have the group name prefixed
          $fieldName = $isCustom ? explode('.', $fieldArray['name'])[1] : $fieldArray['name'];
          $this->addSpecField($joinTreeNode[$joinName]['#path'] . $fieldName, $fieldArray);
        }

        // Serialized joins are rendered by this::renderSerializedJoin. Don't add their tables.
        if (!$virtualField) {
          $conditions = $link->getConditionsForJoin($baseTableAlias, $tableAlias, $this->openJoin);
          if ($joinEntity) {
            $conditions = array_merge($conditions, $this->getAclClause($tableAlias, $joinEntity, $aclStack));
          }
          $this->addJoin('LEFT', $target, $tableAlias, $baseTableAlias, $conditions);
        }

      }
      $joinTreeNode =& $joinTreeNode[$joinName];
      $useBridgeTable = FALSE;
    }
  }

  /**
   * Begins a new join; as long as it's "open" then additional joins will nest inside it.
   */
  private function startNewJoin(string $tableExpr, string $joinAlias): void {
    $this->openJoin = [
      'table' => $tableExpr,
      'alias' => $joinAlias,
      'subjoins' => [],
    ];
  }

  private function finishJoin(string $side, $conditions): void {
    $tableAlias = $this->openJoin['alias'];
    $tableExpr = $this->openJoin['table'];
    $subjoinClause = '';
    foreach ($this->openJoin['subjoins'] as $subjoin) {
      $subjoinClause .= " INNER JOIN {$subjoin['table']} `{$subjoin['alias']}` ON (" . implode(' AND ', $subjoin['conditions']) . ")";
    }
    $this->query->join($tableAlias, "$side JOIN ($tableExpr `$tableAlias`$subjoinClause) ON " . implode(' AND ', $conditions));
    $this->openJoin = NULL;
  }

  /**
   * @param string $side
   * @param string $tableExpr
   * @param string $tableAlias
   * @param string $baseTableAlias
   * @param array $conditions
   */
  private function addJoin(string $side, string $tableExpr, string $tableAlias, string $baseTableAlias, array $conditions): void {
    // If this join is based off the current open join, incorporate it
    if ($baseTableAlias === ($this->openJoin['alias'] ?? NULL)) {
      $this->openJoin['subjoins'][] = [
        'table' => $tableExpr,
        'alias' => $tableAlias,
        'conditions' => $conditions,
      ];
    }
    else {
      $this->query->join($tableAlias, "$side JOIN $tableExpr `$tableAlias` ON " . implode(' AND ', $conditions));
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
   * @param string $alias
   * @return array{entity: string, alias: string, table: string, on: array, bridge: string|NULL}|NULL
   */
  public function getExplicitJoin($alias) {
    return $this->explicitJoins[$alias] ?? NULL;
  }

  /**
   * @return array{entity: string, alias: string, table: string, on: array, bridge: string|NULL}[]
   */
  public function getExplicitJoins(): array {
    return $this->explicitJoins;
  }

  /**
   * If a join is based on another join, return the name of the other.
   *
   * @param string $joinAlias
   * @return string|null
   */
  public function getJoinParent(string $joinAlias): ?string {
    $join = $this->getExplicitJoin($joinAlias);
    foreach ($join['on'] ?? [] as $clause) {
      $prefix = $join['alias'] . '.';
      if (
        count($clause) === 3 && $clause[1] === '=' &&
        (str_starts_with($clause[0], $prefix) || str_starts_with($clause[2], $prefix))
      ) {
        $otherField = str_starts_with($clause[0], $prefix) ? $clause[2] : $clause[0];
        [$otherJoin] = explode('.', $otherField);
        if (str_contains($otherField, '.') && $this->getExplicitJoin($otherJoin)) {
          return $otherJoin;
        }
      }
    }
    return NULL;
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
    // This allows calculated fields to be reused in SELECT, GROUP BY and ORDER BY.
    foreach ($this->selectAliases as $alias => $selectVal) {
      $selectVal = explode(':', $selectVal)[0];
      if ($alias !== $selectVal && $exprVal === $selectVal) {
        return "`$alias`";
      }
    }
    return $expr->render($this);
  }

}
