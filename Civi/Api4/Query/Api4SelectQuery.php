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
use Civi\Api4\Event\Events;
use Civi\Api4\Event\PostSelectQueryEvent;
use Civi\Api4\Service\Schema\Joinable\CustomGroupJoinable;
use Civi\Api4\Service\Schema\Joinable\Joinable;
use Civi\Api4\Utils\FormattingUtil;
use Civi\Api4\Utils\CoreUtil;
use Civi\Api4\Utils\SelectUtil;
use CRM_Core_DAO_AllCoreTables as AllCoreTables;

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
   *   Maps select fields to [<table_alias>, <column_alias>]
   */
  protected $fkSelectAliases = [];

  /**
   * @var \Civi\Api4\Service\Schema\Joinable\Joinable[]
   *   The joinable tables that have been joined so far
   */
  protected $joinedTables = [];

  /**
   * If set to an array, this will start collecting debug info.
   *
   * @var null|array
   */
  public $debugOutput = NULL;

  /**
   * @param \Civi\Api4\Generic\DAOGetAction $apiGet
   */
  public function __construct($apiGet) {
    $this->entity = $apiGet->getEntityName();
    $this->checkPermissions = $apiGet->getCheckPermissions();
    $this->select = $apiGet->getSelect();
    $this->where = $apiGet->getWhere();
    $this->orderBy = $apiGet->getOrderBy();
    $this->limit = $apiGet->getLimit();
    $this->offset = $apiGet->getOffset();
    if ($apiGet->getDebug()) {
      $this->debugOutput =& $apiGet->_debugOutput;
    }
    $baoName = CoreUtil::getBAOFromApiName($this->entity);
    $this->entityFieldNames = array_column($baoName::fields(), 'name');
    $this->apiFieldSpec = $apiGet->entityFields();
    foreach ($this->apiFieldSpec as $key => $field) {
      $this->apiFieldSpec[$key]['sql_name'] = '`' . self::MAIN_TABLE_ALIAS . '`.`' . $field['column_name'] . '`';
    }

    $this->constructQueryObject($baoName);

    // Add ACLs first to avoid redundant subclauses
    $this->query->where($this->getAclClause(self::MAIN_TABLE_ALIAS, $baoName));
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
      $results[$query->id] = [];
      foreach ($this->select as $alias) {
        $returnName = $alias;
        if ($this->isOneToOneField($alias)) {
          $alias = str_replace('.', '_', $alias);
          $results[$query->id][$returnName] = property_exists($query, $alias) ? $query->$alias : NULL;
        }
      };
    }
    $event = new PostSelectQueryEvent($results, $this);
    \Civi::dispatcher()->dispatch(Events::POST_SELECT_QUERY, $event);

    return $event->getResults();
  }

  protected function buildSelectClause() {
    if (empty($this->select)) {
      $this->select = $this->entityFieldNames;
    }
    elseif (in_array('row_count', $this->select)) {
      $this->query->select("COUNT(*) AS `c`");
      return;
    }
    else {
      // Always select id field
      $this->select = array_merge(['id'], $this->select);

      // Expand wildcards in joins (the api wrapper already expanded non-joined wildcards)
      $wildFields = array_filter($this->select, function($item) {
        return strpos($item, '*') !== FALSE && strpos($item, '.') !== FALSE;
      });
      foreach ($wildFields as $item) {
        $pos = array_search($item, array_values($this->select));
        $this->joinFK($item);
        $matches = SelectUtil::getMatchingFields($item, array_keys($this->apiFieldSpec));
        array_splice($this->select, $pos, 1, $matches);
      }
      $this->select = array_unique($this->select);
    }
    foreach ($this->select as $fieldName) {
      $field = $this->getField($fieldName);
      if (!$this->isOneToOneField($fieldName)) {
        continue;
      }
      elseif ($field) {
        $this->query->select($field['sql_name'] . " AS `$fieldName`");
      }
      // Remove unknown fields without raising an error
      else {
        $this->select = array_diff($this->select, [$fieldName]);
        if (is_array($this->debugOutput)) {
          $this->debugOutput['undefined_fields'][] = $fieldName;
        }
      }
    }
  }

  /**
   * @inheritDoc
   */
  protected function buildWhereClause() {
    foreach ($this->where as $clause) {
      $sql_clause = $this->treeWalkWhereClause($clause);
      $this->query->where($sql_clause);
    }
  }

  /**
   * @inheritDoc
   */
  protected function buildOrderBy() {
    foreach ($this->orderBy as $fieldName => $dir) {
      if ($dir !== 'ASC' && $dir !== 'DESC') {
        throw new \API_Exception("Invalid sort direction. Cannot order by $fieldName $dir");
      }
      $this->query->orderBy($this->getField($fieldName, TRUE)['sql_name'] . " $dir");
    }
  }

  /**
   * @throws \CRM_Core_Exception
   */
  protected function buildLimit() {
    if (!empty($this->limit) || !empty($this->offset)) {
      $this->query->limit($this->limit, $this->offset);
    }
  }

  /**
   * Recursively validate and transform a branch or leaf clause array to SQL.
   *
   * @param array $clause
   * @return string SQL where clause
   *
   * @uses validateClauseAndComposeSql() to generate the SQL etc.
   * @todo if an 'and' is nested within and 'and' (or or-in-or) then should
   * flatten that to be a single list of clauses.
   */
  protected function treeWalkWhereClause($clause) {
    switch ($clause[0]) {
      case 'OR':
      case 'AND':
        // handle branches
        if (count($clause[1]) === 1) {
          // a single set so AND|OR is immaterial
          return $this->treeWalkWhereClause($clause[1][0]);
        }
        else {
          $sql_subclauses = [];
          foreach ($clause[1] as $subclause) {
            $sql_subclauses[] = $this->treeWalkWhereClause($subclause);
          }
          return '(' . implode("\n" . $clause[0], $sql_subclauses) . ')';
        }

      case 'NOT':
        // If we get a group of clauses with no operator, assume AND
        if (!is_string($clause[1][0])) {
          $clause[1] = ['AND', $clause[1]];
        }
        return 'NOT (' . $this->treeWalkWhereClause($clause[1]) . ')';

      default:
        return $this->validateClauseAndComposeSql($clause);
    }
  }

  /**
   * Validate and transform a leaf clause array to SQL.
   * @param array $clause [$fieldName, $operator, $criteria]
   * @return string SQL
   * @throws \API_Exception
   * @throws \Exception
   */
  protected function validateClauseAndComposeSql($clause) {
    // Pad array for unary operators
    list($fieldName, $operator, $value) = array_pad($clause, 3, NULL);
    $field = $this->getField($fieldName, TRUE);

    FormattingUtil::formatInputValue($value, $field, $this->getEntity());

    $sql_clause = \CRM_Core_DAO::createSQLFilter($field['sql_name'], [$operator => $value]);
    if ($sql_clause === NULL) {
      throw new \API_Exception("Invalid value in where clause for field '$fieldName'");
    }
    return $sql_clause;
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
   * @param string $fieldName
   * @param bool $strict
   *
   * @return string|null
   * @throws \API_Exception
   */
  public function getField($fieldName, $strict = FALSE) {
    // Perform join if field not yet available - this will add it to apiFieldSpec
    if (!isset($this->apiFieldSpec[$fieldName]) && strpos($fieldName, '.')) {
      $this->joinFK($fieldName);
    }
    $field = $this->apiFieldSpec[$fieldName] ?? NULL;
    // Check if field exists and we have permission to view it
    if ($field && (!$this->checkPermissions || empty($field['permission']) || \CRM_Core_Permission::check($field['permission']))) {
      return $field;
    }
    elseif ($strict) {
      throw new \API_Exception("Invalid field '$fieldName'");
    }
    return NULL;
  }

  /**
   * Joins a path and adds all fields in the joined eneity to apiFieldSpec
   *
   * @param $key
   * @return bool
   * @throws \API_Exception
   * @throws \Exception
   */
  protected function joinFK($key) {
    if (isset($this->apiFieldSpec[$key])) {
      return TRUE;
    }

    $pathArray = explode('.', $key);

    /** @var \Civi\Api4\Service\Schema\Joiner $joiner */
    $joiner = \Civi::container()->get('joiner');
    // The last item in the path is the field name. We don't care about that; we'll add all fields from the joined entity.
    array_pop($pathArray);
    $pathString = implode('.', $pathArray);

    if (!$joiner->canJoin($this, $pathString)) {
      return FALSE;
    }

    $joinPath = $joiner->join($this, $pathString);
    /** @var \Civi\Api4\Service\Schema\Joinable\Joinable $lastLink */
    $lastLink = array_pop($joinPath);

    // Custom field names are already prefixed
    if ($lastLink instanceof CustomGroupJoinable) {
      array_pop($pathArray);
    }
    $prefix = $pathArray ? implode('.', $pathArray) . '.' : '';
    // Cache field info for retrieval by $this->getField()
    $joinEntity = $lastLink->getEntity();
    foreach ($lastLink->getEntityFields() as $fieldObject) {
      $fieldArray = ['entity' => $joinEntity] + $fieldObject->toArray();
      $fieldArray['sql_name'] = '`' . $lastLink->getAlias() . '`.`' . $fieldArray['column_name'] . '`';
      $this->apiFieldSpec[$prefix . $fieldArray['name']] = $fieldArray;
    }

    return TRUE;
  }

  /**
   * @param \Civi\Api4\Service\Schema\Joinable\Joinable $joinable
   *
   * @return $this
   */
  public function addJoinedTable(Joinable $joinable) {
    $this->joinedTables[] = $joinable;

    return $this;
  }

  /**
   * @return FALSE|string
   */
  public function getFrom() {
    return AllCoreTables::getTableForClass(AllCoreTables::getFullName($this->entity));
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
   * @return bool
   */
  public function isFillUniqueFields() {
    return $this->isFillUniqueFields;
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
   * @return \Civi\Api4\Service\Schema\Joinable\Joinable[]
   */
  public function getJoinedTables() {
    return $this->joinedTables;
  }

  /**
   * @return \Civi\Api4\Service\Schema\Joinable\Joinable
   */
  public function getJoinedTable($alias) {
    foreach ($this->joinedTables as $join) {
      if ($join->getAlias() == $alias) {
        return $join;
      }
    }
  }

  /**
   * Get table name on basis of entity
   *
   * @param string $baoName
   *
   * @return void
   */
  public function constructQueryObject($baoName) {
    if (strstr($this->entity, 'Custom_')) {
      $this->query = \CRM_Utils_SQL_Select::from(CoreUtil::getCustomTableByName(str_replace('Custom_', '', $this->entity)) . ' ' . self::MAIN_TABLE_ALIAS);
      $this->entityFieldNames = array_keys($this->apiFieldSpec);
    }
    else {
      $bao = new $baoName();
      $this->query = \CRM_Utils_SQL_Select::from($bao->tableName() . ' ' . self::MAIN_TABLE_ALIAS);
    }
  }

  /**
   * Checks if a field either belongs to the main entity or is joinable 1-to-1.
   *
   * Used to determine if a field can be added to the SELECT of the main query,
   * or if it must be fetched post-query.
   *
   * @param string $fieldPath
   * @return bool
   */
  public function isOneToOneField(string $fieldPath) {
    return strpos($fieldPath, '.') === FALSE || !array_filter($this->getPathJoinTypes($fieldPath));
  }

  /**
   * Separates a string like 'emails.location_type.label' into an array, where
   * each value in the array tells whether it is 1-1 or 1-n join type
   *
   * @param string $pathString
   *   Dot separated path to the field
   *
   * @return array
   *   Index is table alias and value is boolean whether is 1-to-many join
   */
  public function getPathJoinTypes($pathString) {
    $pathParts = explode('.', $pathString);
    // remove field
    array_pop($pathParts);
    $path = [];
    $query = $this;
    $isMultipleChecker = function($alias) use ($query) {
      foreach ($query->getJoinedTables() as $table) {
        if ($table->getAlias() === $alias) {
          return $table->getJoinType() === Joinable::JOIN_TYPE_ONE_TO_MANY;
        }
      }
      return FALSE;
    };

    foreach ($pathParts as $part) {
      $path[$part] = $isMultipleChecker($part);
    }

    return $path;
  }

}
