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
namespace Civi\API;
use Civi\API\Exception\UnauthorizedException;

/**
 * Query builder for civicrm_api_basic_get.
 *
 * Fetches an entity based on specified params for the "where" clause,
 * return properties for the "select" clause,
 * as well as limit and order.
 *
 * Automatically joins on custom fields to return or filter by them.
 *
 * Supports an additional sql fragment which the calling api can provide.
 *
 * @package Civi\API
 */
abstract class SelectQuery {

  const
    MAX_JOINS = 4,
    MAIN_TABLE_ALIAS = 'a';

  /**
   * @var string
   */
  protected $entity;
  public $select = [];
  public $where = [];
  public $orderBy = [];
  public $limit;
  public $offset;
  /**
   * @var array
   */
  protected $selectFields = [];
  /**
   * @var bool
   */
  public $isFillUniqueFields = FALSE;
  /**
   * @var \CRM_Utils_SQL_Select
   */
  protected $query;
  /**
   * @var array
   */
  protected $joins = [];
  /**
   * @var array
   */
  protected $apiFieldSpec;
  /**
   * @var array
   */
  protected $entityFieldNames;
  /**
   * @var array
   */
  protected $aclFields = [];
  /**
   * @var string|bool
   */
  protected $checkPermissions;

  protected $apiVersion;

  /**
   * @param string $entity
   * @param bool $checkPermissions
   */
  public function __construct($entity, $checkPermissions) {
    $this->entity = $entity;
    require_once 'api/v3/utils.php';
    $baoName = _civicrm_api3_get_BAO($entity);
    $bao = new $baoName();

    $this->entityFieldNames = _civicrm_api3_field_names(_civicrm_api3_build_fields_array($bao));
    $this->apiFieldSpec = $this->getFields();

    $this->query = \CRM_Utils_SQL_Select::from($bao->tableName() . ' ' . self::MAIN_TABLE_ALIAS);
    $bao->free();

    // Add ACLs first to avoid redundant subclauses
    $this->checkPermissions = $checkPermissions;
    $this->query->where($this->getAclClause(self::MAIN_TABLE_ALIAS, $baoName));
  }

  /**
   * Build & execute the query and return results array
   *
   * @return array|int
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \Exception
   */
  public function run() {
    $this->buildSelectFields();

    $this->buildWhereClause();
    if (in_array('count_rows', $this->select)) {
      $this->query->select("count(*) as c");
    }
    else {
      foreach ($this->selectFields as $column => $alias) {
        $this->query->select("$column as `$alias`");
      }
      // Order by
      $this->buildOrderBy();
    }

    // Limit
    if (!empty($this->limit) || !empty($this->offset)) {
      $this->query->limit($this->limit, $this->offset);
    }

    $result_entities = [];
    $result_dao = \CRM_Core_DAO::executeQuery($this->query->toSQL());

    while ($result_dao->fetch()) {
      if (in_array('count_rows', $this->select)) {
        $result_dao->free();
        return (int) $result_dao->c;
      }
      $result_entities[$result_dao->id] = [];
      foreach ($this->selectFields as $column => $alias) {
        $returnName = $alias;
        $alias = str_replace('.', '_', $alias);
        if (property_exists($result_dao, $alias) && $result_dao->$alias != NULL) {
          $result_entities[$result_dao->id][$returnName] = $result_dao->$alias;
        }
        // Backward compatibility on fields names.
        if ($this->isFillUniqueFields && !empty($this->apiFieldSpec[$alias]['uniqueName'])) {
          $result_entities[$result_dao->id][$this->apiFieldSpec[$alias]['uniqueName']] = $result_dao->$alias;
        }
        foreach ($this->apiFieldSpec as $returnName => $spec) {
          if (empty($result_entities[$result_dao->id][$returnName]) && !empty($result_entities[$result_dao->id][$spec['name']])) {
            $result_entities[$result_dao->id][$returnName] = $result_entities[$result_dao->id][$spec['name']];
          }
        }
      };
    }
    $result_dao->free();
    return $result_entities;
  }

  /**
   * @param \CRM_Utils_SQL_Select $sqlFragment
   * @return SelectQuery
   */
  public function merge($sqlFragment) {
    $this->query->merge($sqlFragment);
    return $this;
  }

  /**
   * Joins onto an fk field
   *
   * Adds one or more joins to the query to make this field available for use in a clause.
   *
   * Enforces permissions at the api level and by appending the acl clause for that entity to the join.
   *
   * @param $fkFieldName
   * @param $side
   *
   * @return array|null
   *   Returns the table and field name for adding this field to a SELECT or WHERE clause
   * @throws \API_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  protected function addFkField($fkFieldName, $side) {
    $stack = explode('.', $fkFieldName);
    if (count($stack) < 2) {
      return NULL;
    }
    $prev = self::MAIN_TABLE_ALIAS;
    foreach ($stack as $depth => $fieldName) {
      // Setup variables then skip the first level
      if (!$depth) {
        $fk = $fieldName;
        // We only join on core fields
        // @TODO: Custom contact ref fields could be supported too
        if (!in_array($fk, $this->entityFieldNames)) {
          return NULL;
        }
        $fkField = &$this->apiFieldSpec[$fk];
        continue;
      }
      // More than 4 joins deep seems excessive - DOS attack?
      if ($depth > self::MAX_JOINS) {
        throw new UnauthorizedException("Maximum number of joins exceeded in parameter $fkFieldName");
      }
      $subStack = array_slice($stack, 0, $depth);
      $this->getJoinInfo($fkField, $subStack);
      if (!isset($fkField['FKApiName']) || !isset($fkField['FKClassName'])) {
        // Join doesn't exist - might be another param with a dot in it for some reason, we'll just ignore it.
        return NULL;
      }
      // Ensure we have permission to access the other api
      if (!$this->checkPermissionToJoin($fkField['FKApiName'], $subStack)) {
        throw new UnauthorizedException("Authorization failed to join onto {$fkField['FKApiName']} api in parameter $fkFieldName");
      }
      if (!isset($fkField['FKApiSpec'])) {
        $fkField['FKApiSpec'] = \_civicrm_api_get_fields($fkField['FKApiName']);
      }
      $fieldInfo = \CRM_Utils_Array::value($fieldName, $fkField['FKApiSpec']);

      $keyColumn = \CRM_Utils_Array::value('FKKeyColumn', $fkField, 'id');
      if (!$fieldInfo || !isset($fkField['FKApiSpec'][$keyColumn])) {
        // Join doesn't exist - might be another param with a dot in it for some reason, we'll just ignore it.
        return NULL;
      }
      $fkTable = \CRM_Core_DAO_AllCoreTables::getTableForClass($fkField['FKClassName']);
      $tableAlias = implode('_to_', $subStack) . "_to_$fkTable";

      // Add acl condition
      $joinCondition = array_merge(
        ["$prev.$fk = $tableAlias.$keyColumn"],
        $this->getAclClause($tableAlias, \_civicrm_api3_get_BAO($fkField['FKApiName']), $subStack)
      );

      if (!empty($fkField['FKCondition'])) {
        $joinCondition[] = str_replace($fkTable, $tableAlias, $fkField['FKCondition']);
      }

      $this->join($side, $fkTable, $tableAlias, $joinCondition);

      if (strpos($fieldName, 'custom_') === 0) {
        list($tableAlias, $fieldName) = $this->addCustomField($fieldInfo, $side, $tableAlias);
      }

      // Get ready to recurse to the next level
      $fk = $fieldName;
      $fkField = &$fkField['FKApiSpec'][$fieldName];
      $prev = $tableAlias;
    }
    return [$tableAlias, $fieldName];
  }

  /**
   * Get join info for dynamically-joined fields (e.g. "entity_id", "option_group")
   *
   * @param $fkField
   * @param $stack
   */
  protected function getJoinInfo(&$fkField, $stack) {
    if ($fkField['name'] == 'entity_id') {
      $entityTableParam = substr(implode('.', $stack), 0, -2) . 'table';
      $entityTable = \CRM_Utils_Array::value($entityTableParam, $this->where);
      if ($entityTable && is_string($entityTable) && \CRM_Core_DAO_AllCoreTables::getClassForTable($entityTable)) {
        $fkField['FKClassName'] = \CRM_Core_DAO_AllCoreTables::getClassForTable($entityTable);
        $fkField['FKApiName'] = \CRM_Core_DAO_AllCoreTables::getBriefName($fkField['FKClassName']);
      }
    }
    if (!empty($fkField['pseudoconstant']['optionGroupName'])) {
      $fkField['FKClassName'] = 'CRM_Core_DAO_OptionValue';
      $fkField['FKApiName'] = 'OptionValue';
      $fkField['FKKeyColumn'] = 'value';
      $fkField['FKCondition'] = "civicrm_option_value.option_group_id = (SELECT id FROM civicrm_option_group WHERE name = '{$fkField['pseudoconstant']['optionGroupName']}')";
    }
  }

  /**
   * Joins onto a custom field
   *
   * Adds a join to the query to make this field available for use in a clause.
   *
   * @param array $customField
   * @param string $side
   * @param string $baseTable
   * @return array
   *   Returns the table and field name for adding this field to a SELECT or WHERE clause
   */
  protected function addCustomField($customField, $side, $baseTable = self::MAIN_TABLE_ALIAS) {
    $tableName = $customField["table_name"];
    $columnName = $customField["column_name"];
    $tableAlias = "{$baseTable}_to_$tableName";
    $this->join($side, $tableName, $tableAlias, ["`$tableAlias`.entity_id = `$baseTable`.id"]);
    return [$tableAlias, $columnName];
  }

  /**
   * Fetch a field from the getFields list
   *
   * @param string $fieldName
   * @return array|null
   */
  abstract protected function getField($fieldName);

  /**
   * Perform input validation on params that use the join syntax
   *
   * Arguably this should be done at the api wrapper level, but doing it here provides a bit more consistency
   * in that api permissions to perform the join are checked first.
   *
   * @param $fieldName
   * @param $value
   * @throws \Exception
   */
  protected function validateNestedInput($fieldName, &$value) {
    $stack = explode('.', $fieldName);
    $spec = $this->apiFieldSpec;
    $fieldName = array_pop($stack);
    foreach ($stack as $depth => $name) {
      $entity = $spec[$name]['FKApiName'];
      $spec = $spec[$name]['FKApiSpec'];
    }
    $params = [$fieldName => $value];
    \_civicrm_api3_validate_fields($entity, 'get', $params, $spec);
    $value = $params[$fieldName];
  }

  /**
   * Check permission to join onto another api entity
   *
   * @param string $entity
   * @param array $fieldStack
   *   The stack of fields leading up to this join
   * @return bool
   */
  protected function checkPermissionToJoin($entity, $fieldStack) {
    if (!$this->checkPermissions) {
      return TRUE;
    }
    // Build an array of params that relate to the joined entity
    $params = [
      'version' => 3,
      'return' => [],
      'check_permissions' => $this->checkPermissions,
    ];
    $prefix = implode('.', $fieldStack) . '.';
    $len = strlen($prefix);
    foreach ($this->select as $key => $ret) {
      if (strpos($key, $prefix) === 0) {
        $params['return'][substr($key, $len)] = $ret;
      }
    }
    foreach ($this->where as $key => $param) {
      if (strpos($key, $prefix) === 0) {
        $params[substr($key, $len)] = $param;
      }
    }

    return \Civi::service('civi_api_kernel')->runAuthorize($entity, 'get', $params);
  }

  /**
   * Get acl clause for an entity
   *
   * @param string $tableAlias
   * @param string $baoName
   * @param array $stack
   * @return array
   */
  protected function getAclClause($tableAlias, $baoName, $stack = []) {
    if (!$this->checkPermissions) {
      return [];
    }
    // Prevent (most) redundant acl sub clauses if they have already been applied to the main entity.
    // FIXME: Currently this only works 1 level deep, but tracking through multiple joins would increase complexity
    // and just doing it for the first join takes care of most acl clause deduping.
    if (count($stack) === 1 && in_array($stack[0], $this->aclFields)) {
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
   * Orders the query by one or more fields
   *
   * @throws \API_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  protected function buildOrderBy() {
    $sortParams = is_string($this->orderBy) ? explode(',', $this->orderBy) : (array) $this->orderBy;
    foreach ($sortParams as $index => $item) {
      $item = trim($item);
      if ($item == '(1)') {
        continue;
      }
      $words = preg_split("/[\s]+/", $item);
      if ($words) {
        // Direction defaults to ASC unless DESC is specified
        $direction = strtoupper(\CRM_Utils_Array::value(1, $words, '')) == 'DESC' ? ' DESC' : '';
        $field = $this->getField($words[0]);
        if ($field) {
          $this->query->orderBy(self::MAIN_TABLE_ALIAS . '.' . $field['name'] . $direction, NULL, $index);
        }
        elseif (strpos($words[0], '.')) {
          $join = $this->addFkField($words[0], 'LEFT');
          if ($join) {
            $this->query->orderBy("`{$join[0]}`.`{$join[1]}`$direction", NULL, $index);
          }
        }
        else {
          throw new \API_Exception("Unknown field specified for sort. Cannot order by '$item'");
        }
      }
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
   * Populate where clauses
   *
   * @throws \Civi\API\Exception\UnauthorizedException
   * @throws \Exception
   */
  abstract protected function buildWhereClause();

  /**
   * Populate $this->selectFields
   *
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  protected function buildSelectFields() {
    $return_all_fields = (empty($this->select) || !is_array($this->select));
    $return = $return_all_fields ? $this->entityFieldNames : $this->select;
    if ($return_all_fields || in_array('custom', $this->select)) {
      foreach (array_keys($this->apiFieldSpec) as $fieldName) {
        if (strpos($fieldName, 'custom_') === 0) {
          $return[] = $fieldName;
        }
      }
    }

    // Always select the ID if the table has one.
    if (array_key_exists('id', $this->apiFieldSpec)) {
      $this->selectFields[self::MAIN_TABLE_ALIAS . ".id"] = "id";
    }

    // core return fields
    foreach ($return as $fieldName) {
      $field = $this->getField($fieldName);
      if ($field && in_array($field['name'], $this->entityFieldNames)) {
        $this->selectFields[self::MAIN_TABLE_ALIAS . ".{$field['name']}"] = $field['name'];
      }
      elseif (strpos($fieldName, '.')) {
        $fkField = $this->addFkField($fieldName, 'LEFT');
        if ($fkField) {
          $this->selectFields[implode('.', $fkField)] = $fieldName;
        }
      }
      elseif ($field && strpos($fieldName, 'custom_') === 0) {
        list($table_name, $column_name) = $this->addCustomField($field, 'LEFT');

        if ($field['data_type'] != 'ContactReference') {
          // 'ordinary' custom field. We will select the value as custom_XX.
          $this->selectFields["$table_name.$column_name"] = $fieldName;
        }
        else {
          // contact reference custom field. The ID will be stored in custom_XX_id.
          // custom_XX will contain the sort name of the contact.
          $this->query->join("c_$fieldName", "LEFT JOIN civicrm_contact c_$fieldName ON c_$fieldName.id = `$table_name`.`$column_name`");
          $this->selectFields["$table_name.$column_name"] = $fieldName . "_id";
          // We will call the contact table for the join c_XX.
          $this->selectFields["c_$fieldName.sort_name"] = $fieldName;
        }
      }
    }
  }

  /**
   * Load entity fields
   * @return array
   */
  abstract protected function getFields();

}
