<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
class SelectQuery {

  const
    MAX_JOINS = 4,
    MAIN_TABLE_ALIAS = 'a';

  /**
   * @var string
   */
  protected $entity;
  /**
   * @var array
   */
  protected $params;
  /**
   * @var array
   */
  protected $options;
  /**
   * @var bool
   */
  protected $isFillUniqueFields;
  /**
   * @var \CRM_Utils_SQL_Select
   */
  protected $query;
  /**
   * @var array
   */
  private $joins = array();
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
  protected $aclFields = array();
  /**
   * @var string|bool
   */
  protected $checkPermissions;

  /**
   * @param string $baoName
   *   Name of BAO
   * @param array $params
   *   As passed into api get function.
   * @param bool $isFillUniqueFields
   *   Do we need to ensure unique fields continue to be populated for this api? (backward compatibility).
   */
  public function __construct($baoName, $params, $isFillUniqueFields) {
    $bao = new $baoName();
    $this->entity = _civicrm_api_get_entity_name_from_dao($bao);
    $this->params = $params;
    $this->isFillUniqueFields = $isFillUniqueFields;
    $this->checkPermissions = \CRM_Utils_Array::value('check_permissions', $this->params, FALSE);
    $this->options = _civicrm_api3_get_options_from_params($this->params);

    $this->entityFieldNames = _civicrm_api3_field_names(_civicrm_api3_build_fields_array($bao));
    // Call this function directly instead of using the api wrapper to force unique field names off
    require_once 'api/v3/Generic.php';
    $apiSpec = \civicrm_api3_generic_getfields(array('entity' => $this->entity, 'version' => 3, 'params' => array('action' => 'get')), FALSE);
    $this->apiFieldSpec = $apiSpec['values'];

    $this->query = \CRM_Utils_SQL_Select::from($bao->tableName() . ' ' . self::MAIN_TABLE_ALIAS);
    $bao->free();

    // Add ACLs first to avoid redundant subclauses
    $this->query->where($this->getAclClause(self::MAIN_TABLE_ALIAS, $baoName));
  }

  /**
   * Build & execute the query and return results array
   *
   * @return array
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \Exception
   */
  public function run() {
    // $select_fields maps column names to the field names of the result values.
    $select_fields = $custom_fields = array();

    // populate $select_fields
    $return_all_fields = (empty($this->options['return']) || !is_array($this->options['return']));
    $return = $return_all_fields ? array_fill_keys($this->entityFieldNames, 1) : $this->options['return'];

    // core return fields
    foreach ($return as $field_name => $include) {
      if ($include) {
        $field = $this->getField($field_name);
        if ($field && in_array($field['name'], $this->entityFieldNames)) {
          $select_fields[self::MAIN_TABLE_ALIAS . ".{$field['name']}"] = $field['name'];
        }
        elseif ($include && strpos($field_name, '.')) {
          $fkField = $this->addFkField($field_name, 'LEFT');
          if ($fkField) {
            $select_fields[implode('.', $fkField)] = $field_name;
          }
        }
      }
    }

    // Do custom fields IF the params contain the word "custom" or we are returning *
    if ($return_all_fields || strpos(json_encode($this->params), 'custom')) {
      $custom_fields = _civicrm_api3_custom_fields_for_entity($this->entity);
      foreach ($custom_fields as $cf_id => $custom_field) {
        $field_name = "custom_$cf_id";
        if ($return_all_fields || !empty($this->options['return'][$field_name])
          ||
          // This is a tested format so we support it.
          !empty($this->options['return']['custom'])
        ) {
          list($table_name, $column_name) = $this->addCustomField($custom_field, 'LEFT');

          if ($custom_field["data_type"] != "ContactReference") {
            // 'ordinary' custom field. We will select the value as custom_XX.
            $select_fields["$table_name.$column_name"] = $field_name;
          }
          else {
            // contact reference custom field. The ID will be stored in custom_XX_id.
            // custom_XX will contain the sort name of the contact.
            $this->query->join("c_$cf_id", "LEFT JOIN civicrm_contact c_$cf_id ON c_$cf_id.id = `$table_name`.`$column_name`");
            $select_fields["$table_name.$column_name"] = $field_name . "_id";
            // We will call the contact table for the join c_XX.
            $select_fields["c_$cf_id.sort_name"] = $field_name;
          }
        }
      }
    }
    // Always select the ID.
    $select_fields[self::MAIN_TABLE_ALIAS . ".id"] = "id";

    // populate where_clauses
    foreach ($this->params as $key => $value) {
      $table_name = NULL;
      $column_name = NULL;

      if (substr($key, 0, 7) == 'filter.') {
        // Legacy support for old filter syntax per the test contract.
        // (Convert the style to the later one & then deal with them).
        $filterArray = explode('.', $key);
        $value = array($filterArray[1] => $value);
        $key = 'filters';
      }

      // Legacy support for 'filter's construct.
      if ($key == 'filters') {
        foreach ($value as $filterKey => $filterValue) {
          if (substr($filterKey, -4, 4) == 'high') {
            $key = substr($filterKey, 0, -5);
            $value = array('<=' => $filterValue);
          }

          if (substr($filterKey, -3, 3) == 'low') {
            $key = substr($filterKey, 0, -4);
            $value = array('>=' => $filterValue);
          }

          if ($filterKey == 'is_current' || $filterKey == 'isCurrent') {
            // Is current is almost worth creating as a 'sql filter' in the DAO function since several entities have the concept.
            $todayStart = date('Ymd000000', strtotime('now'));
            $todayEnd = date('Ymd235959', strtotime('now'));
            $a = self::MAIN_TABLE_ALIAS;
            $this->query->where("($a.start_date <= '$todayStart' OR $a.start_date IS NULL)
              AND ($a.end_date >= '$todayEnd' OR $a.end_date IS NULL)
              AND a.is_active = 1");
          }
        }
      }
      // Ignore the "options" param if it is referring to api options and not a field in this entity
      if (
        $key === 'options' && is_array($value)
        && !in_array(\CRM_Utils_Array::first(array_keys($value)), \CRM_Core_DAO::acceptedSQLOperators())
      ) {
        continue;
      }
      $field = $this->getField($key);
      if ($field) {
        $key = $field['name'];
      }
      if (in_array($key, $this->entityFieldNames)) {
        $table_name = self::MAIN_TABLE_ALIAS;
        $column_name = $key;
      }
      elseif (($cf_id = \CRM_Core_BAO_CustomField::getKeyID($key)) != FALSE) {
        list($table_name, $column_name) = $this->addCustomField($custom_fields[$cf_id], 'INNER');
      }
      elseif (strpos($key, '.')) {
        $fkInfo = $this->addFkField($key, 'INNER');
        if ($fkInfo) {
          list($table_name, $column_name) = $fkInfo;
          $this->validateNestedInput($key, $value);
        }
      }
      // I don't know why I had to specifically exclude 0 as a key - wouldn't the others have caught it?
      // We normally silently ignore null values passed in - if people want IS_NULL they can use acceptedSqlOperator syntax.
      if ((!$table_name) || empty($key) || is_null($value)) {
        // No valid filter field. This might be a chained call or something.
        // Just ignore this for the $where_clause.
        continue;
      }
      if (!is_array($value)) {
        $this->query->where(array("`$table_name`.`$column_name` = @value"), array(
          "@value" => $value,
        ));
      }
      else {
        // We expect only one element in the array, of the form
        // "operator" => "rhs".
        $operator = \CRM_Utils_Array::first(array_keys($value));
        if (!in_array($operator, \CRM_Core_DAO::acceptedSQLOperators())) {
          $this->query->where(array(
            "{$table_name}.{$column_name} = @value"), array("@value" => $value)
          );
        }
        else {
          $this->query->where(\CRM_Core_DAO::createSQLFilter("{$table_name}.{$column_name}", $value));
        }
      }
    }

    if (!$this->options['is_count']) {
      foreach ($select_fields as $column => $alias) {
        $this->query->select("$column as `$alias`");
      }
    }
    else {
      $this->query->select("count(*) as c");
    }

    // Order by
    if (!empty($this->options['sort'])) {
      $this->orderBy($this->options['sort']);
    }

    // Limit
    if (!empty($this->options['limit']) || !empty($this->options['offset'])) {
      $this->query->limit($this->options['limit'], $this->options['offset']);
    }

    $result_entities = array();
    $result_dao = \CRM_Core_DAO::executeQuery($this->query->toSQL());

    while ($result_dao->fetch()) {
      if ($this->options['is_count']) {
        $result_dao->free();
        return (int) $result_dao->c;
      }
      $result_entities[$result_dao->id] = array();
      foreach ($select_fields as $column => $alias) {
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
   * @return $this
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
  private function addFkField($fkFieldName, $side) {
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
      if (!isset($fkField['FKApiName']) || !isset($fkField['FKClassName'])) {
        // Join doesn't exist - might be another param with a dot in it for some reason, we'll just ignore it.
        return NULL;
      }
      $subStack = array_slice($stack, 0, $depth);
      // Ensure we have permission to access the other api
      if (!$this->checkPermissionToJoin($fkField['FKApiName'], $subStack)) {
        throw new UnauthorizedException("Authorization failed to join onto {$fkField['FKApiName']} api in parameter $fkFieldName");
      }
      if (!isset($fkField['FKApiSpec'])) {
        $fkField['FKApiSpec'] = \_civicrm_api_get_fields($fkField['FKApiName']);
      }
      $fieldInfo = \CRM_Utils_Array::value($fieldName, $fkField['FKApiSpec']);

      // FIXME: What if the foreign key is not the "id" column?
      if (!$fieldInfo || !isset($fkField['FKApiSpec']['id'])) {
        // Join doesn't exist - might be another param with a dot in it for some reason, we'll just ignore it.
        return NULL;
      }
      $fkTable = \CRM_Core_DAO_AllCoreTables::getTableForClass($fkField['FKClassName']);
      $tableAlias = implode('_to_', $subStack) . "_to_$fkTable";

      // Add acl condition
      $joinCondition = array_merge(
        array("$prev.$fk = $tableAlias.id"),
        $this->getAclClause($tableAlias, \_civicrm_api3_get_BAO($fkField['FKApiName']), $subStack)
      );

      $this->join($side, $fkTable, $tableAlias, $joinCondition);

      if (strpos($fieldName, 'custom_') === 0) {
        list($tableAlias, $fieldName) = $this->addCustomField($fieldInfo, $side, $tableAlias);
      }

      // Get ready to recurse to the next level
      $fk = $fieldName;
      $fkField = &$fkField['FKApiSpec'][$fieldName];
      $prev = $tableAlias;
    }
    return array($tableAlias, $fieldName);
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
  private function addCustomField($customField, $side, $baseTable = self::MAIN_TABLE_ALIAS) {
    $tableName = $customField["table_name"];
    $columnName = $customField["column_name"];
    $tableAlias = "{$baseTable}_to_$tableName";
    $this->join($side, $tableName, $tableAlias, array("`$tableAlias`.entity_id = `$baseTable`.id"));
    return array($tableAlias, $columnName);
  }

  /**
   * Fetch a field from the getFields list
   *
   * Searches by name, uniqueName, and api.aliases
   *
   * @param string $fieldName
   * @return array|null
   */
  private function getField($fieldName) {
    if (!$fieldName) {
      return NULL;
    }
    if (isset($this->apiFieldSpec[$fieldName])) {
      return $this->apiFieldSpec[$fieldName];
    }
    foreach ($this->apiFieldSpec as $field) {
      if (
        $fieldName == \CRM_Utils_Array::value('uniqueName', $field) ||
        array_search($fieldName, \CRM_Utils_Array::value('api.aliases', $field, array())) !== FALSE
      ) {
        return $field;
      }
    }
    return NULL;
  }

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
  private function validateNestedInput($fieldName, &$value) {
    $stack = explode('.', $fieldName);
    $spec = $this->apiFieldSpec;
    $fieldName = array_pop($stack);
    foreach ($stack as $depth => $name) {
      $entity = $spec[$name]['FKApiName'];
      $spec = $spec[$name]['FKApiSpec'];
    }
    $params = array($fieldName => $value);
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
  private function checkPermissionToJoin($entity, $fieldStack) {
    if (!$this->checkPermissions) {
      return TRUE;
    }
    // Build an array of params that relate to the joined entity
    $params = array(
      'version' => 3,
      'return' => array(),
      'check_permissions' => $this->checkPermissions,
    );
    $prefix = implode('.', $fieldStack) . '.';
    $len = strlen($prefix);
    foreach ($this->options['return'] as $key => $ret) {
      if (strpos($key, $prefix) === 0) {
        $params['return'][substr($key, $len)] = $ret;
      }
    }
    foreach ($this->params as $key => $param) {
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
  private function getAclClause($tableAlias, $baoName, $stack = array()) {
    if (!$this->checkPermissions) {
      return array();
    }
    // Prevent (most) redundant acl sub clauses if they have already been applied to the main entity.
    // FIXME: Currently this only works 1 level deep, but tracking through multiple joins would increase complexity
    // and just doing it for the first join takes care of most acl clause deduping.
    if (count($stack) === 1 && in_array($stack[0], $this->aclFields)) {
      return array();
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
   * e.g.
   * @code
   *   $this->orderBy(array('last_name DESC', 'birth_date'));
   * @endcode
   *
   * @param string|array $sortParams
   * @throws \API_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function orderBy($sortParams) {
    $orderBy = array();
    foreach (is_array($sortParams) ? $sortParams : explode(',', $sortParams) as $item) {
      $words = preg_split("/[\s]+/", trim($item));
      if ($words) {
        // Direction defaults to ASC unless DESC is specified
        $direction = strtoupper(\CRM_Utils_Array::value(1, $words, '')) == 'DESC' ? ' DESC' : '';
        $field = $this->getField($words[0]);
        if ($field) {
          $orderBy[] = self::MAIN_TABLE_ALIAS . '.' . $field['name'] . $direction;
        }
        elseif (strpos($words[0], '.')) {
          $join = $this->addFkField($words[0], 'LEFT');
          if ($join) {
            $orderBy[] = "`{$join[0]}`.`{$join[1]}`$direction";
          }
        }
        else {
          throw new \API_Exception("Unknown field specified for sort. Cannot order by '$item'");
        }
      }
    }
    $this->query->orderBy($orderBy);
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

}
