<?php

namespace Civi\Api4\Generic\Traits;

use Civi\API\Exception\UnauthorizedException;
use Civi\API\Request;
use Civi\Api4\Query\SqlExpression;
use Civi\Api4\SavedSearch;
use Civi\Api4\Utils\CoreUtil;

/**
 * Trait for requiring a savedSearch as a param plus util functions for inspecting it.
 *
 * @method $this setSavedSearch(array|string $savedSearch)
 * @method array|string getSavedSearch()
 * @package Civi\Api4\Action\SearchDisplay
 */
trait SavedSearchInspectorTrait {

  /**
   * Either the name of the savedSearch or an array containing the savedSearch definition (for preview mode)
   * @var string|array
   * @required
   */
  protected $savedSearch;

  /**
   * @var array{select: array, where: array, having: array, orderBy: array, limit: int, offset: int, checkPermissions: bool, debug: bool}
   */
  protected $_apiParams;

  /**
   * @var \Civi\Api4\Query\Api4SelectQuery
   */
  private $_selectQuery;

  /**
   * @var array
   */
  private $_selectClause;

  /**
   * @var array
   */
  private $_searchEntityFields;

  /**
   * If SavedSearch is supplied as a string, this will load it as an array
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  protected function loadSavedSearch() {
    if (is_string($this->savedSearch)) {
      $this->savedSearch = SavedSearch::get(FALSE)
        ->addWhere('name', '=', $this->savedSearch)
        ->execute()->single();
    }
    if (is_array($this->savedSearch)) {
      $this->savedSearch += ['api_params' => []];
      $this->savedSearch['api_params'] += ['version' => 4, 'select' => [], 'where' => []];
    }
    $this->_apiParams = ($this->savedSearch['api_params'] ?? []) + ['select' => [], 'where' => []];
  }

  /**
   * Loads display if not already an array
   */
  protected function loadSearchDisplay(): void {
    // Display name given
    if (is_string($this->display)) {
      $this->display = \Civi\Api4\SearchDisplay::get(FALSE)
        ->setSelect(['*', 'type:name'])
        ->addWhere('name', '=', $this->display)
        ->addWhere('saved_search_id', '=', $this->savedSearch['id'])
        ->execute()->single();
    }
    // Null given - use default display
    elseif (is_null($this->display)) {
      $this->display = \Civi\Api4\SearchDisplay::getDefault(FALSE)
        ->addSelect('*', 'type:name')
        ->setSavedSearch($this->savedSearch)
        // Set by AutocompleteAction
        ->setType($this->_displayType ?? 'table')
        ->execute()->first();
    }
  }

  /**
   * Returns field definition for a given field or NULL if not found
   * @param $fieldName
   * @return array|null
   */
  protected function getField($fieldName) {
    [$fieldName] = explode(':', $fieldName);
    return $this->getQuery() ?
      $this->getQuery()->getField($fieldName, FALSE) :
      ($this->getEntityFields()[$fieldName] ?? NULL);
  }

  /**
   * @param $joinAlias
   * @return array{entity: string, alias: string, table: string, bridge: string|NULL}|NULL
   */
  protected function getJoin($joinAlias) {
    return $this->getQuery() ? $this->getQuery()->getExplicitJoin($joinAlias) : NULL;
  }

  /**
   * @return array{entity: string, alias: string, table: string, bridge: string|NULL}[]
   */
  protected function getJoins() {
    return $this->getQuery() ? $this->getQuery()->getExplicitJoins() : [];
  }

  /**
   * Returns a Query object for the search entity, or FALSE if it doesn't have a DAO
   *
   * @return \Civi\Api4\Query\Api4SelectQuery|bool
   */
  private function getQuery() {
    if (!isset($this->_selectQuery) && !empty($this->savedSearch['api_entity'])) {
      if (!in_array('DAOEntity', CoreUtil::getInfoItem($this->savedSearch['api_entity'], 'type'), TRUE)) {
        return $this->_selectQuery = FALSE;
      }
      $api = Request::create($this->savedSearch['api_entity'], 'get', $this->savedSearch['api_params']);
      $this->_selectQuery = new \Civi\Api4\Query\Api4SelectQuery($api);
    }
    return $this->_selectQuery;
  }

  /**
   * Used as a fallback for non-DAO entities which don't use the Query object
   *
   * @return array
   */
  private function getEntityFields() {
    if (!isset($this->_searchEntityFields)) {
      $this->_searchEntityFields = Request::create($this->savedSearch['api_entity'], 'get', $this->savedSearch['api_params'])
        ->entityFields();
    }
    return $this->_searchEntityFields;
  }

  /**
   * Returns the select clause enhanced with metadata
   *
   * @return array{fields: array, expr: SqlExpression, dataType: string}[]
   */
  public function getSelectClause() {
    if (!isset($this->_selectClause)) {
      $this->_selectClause = [];
      foreach ($this->_apiParams['select'] as $selectExpr) {
        $expr = SqlExpression::convert($selectExpr, TRUE);
        $item = [
          'fields' => [],
          'expr' => $expr,
          'dataType' => $expr->getDataType(),
        ];
        foreach ($expr->getFields() as $fieldAlias) {
          $fieldMeta = $this->getField($fieldAlias);
          if ($fieldMeta) {
            $item['fields'][$fieldAlias] = $fieldMeta;
          }
        }
        if (!isset($item['dataType']) && $item['fields']) {
          $item['dataType'] = \CRM_Utils_Array::first($item['fields'])['data_type'];
        }
        $this->_selectClause[$expr->getAlias()] = $item;
      }
    }
    return $this->_selectClause;
  }

  /**
   * @param string $key
   * @return array{fields: array, expr: SqlExpression, dataType: string}|NULL
   */
  protected function getSelectExpression($key) {
    $key = explode(' AS ', $key)[1] ?? $key;
    return $this->getSelectClause()[$key] ?? NULL;
  }

  /**
   * Determines if a column belongs to an aggregate grouping
   * @param string $fieldPath
   * @return bool
   */
  private function canAggregate($fieldPath) {
    // Disregard suffix
    [$fieldPath] = explode(':', $fieldPath);
    $field = $this->getField($fieldPath);
    $apiParams = $this->savedSearch['api_params'] ?? [];

    // If the query does not use grouping or the field doesn't exist, never
    if (empty($apiParams['groupBy']) || !$field) {
      return FALSE;
    }
    // If the column is used for a groupBy, no
    if (in_array($fieldPath, $apiParams['groupBy'])) {
      return FALSE;
    }

    // If the entity this column belongs to is being grouped by id, then also no
    $idField = substr($fieldPath, 0, 0 - strlen($field['name'])) . CoreUtil::getIdFieldName($field['entity']);
    return !in_array($idField, $apiParams['groupBy']);
  }

  /**
   * @param string|array $fieldName
   *   If multiple field names are given they will be combined in an OR clause
   * @param mixed $value
   */
  protected function applyFilter($fieldName, $value) {
    // Global setting determines if % wildcard should be added to both sides (default) or only the end of a search string
    $prefixWithWildcard = \Civi::settings()->get('includeWildCardInName');

    $fieldNames = (array) $fieldName;
    // Based on the first field, decide which clause to add this condition to
    $fieldName = $fieldNames[0];
    $field = $this->getField($fieldName);
    // If field is not found it must be an aggregated column & belongs in the HAVING clause.
    if (!$field) {
      $this->_apiParams += ['having' => []];
      $clause =& $this->_apiParams['having'];
    }
    // If field belongs to an EXCLUDE join, it should be added as a join condition
    else {
      $prefix = strpos($fieldName, '.') ? explode('.', $fieldName)[0] : NULL;
      foreach ($this->_apiParams['join'] ?? [] as $idx => $join) {
        if (($join[1] ?? 'LEFT') === 'EXCLUDE' && (explode(' AS ', $join[0])[1] ?? '') === $prefix) {
          $clause =& $this->_apiParams['join'][$idx];
        }
      }
    }
    // Default: add filter to WHERE clause
    if (!isset($clause)) {
      $clause =& $this->_apiParams['where'];
    }

    $filterClauses = [];

    foreach ($fieldNames as $fieldName) {
      $field = $this->getField($fieldName);
      $dataType = $field['data_type'] ?? NULL;
      $operators = ($field['operators'] ?? []) ?: CoreUtil::getOperators();
      // Array is either associative `OP => VAL` or sequential `IN (...)`
      if (is_array($value)) {
        $value = array_filter($value, [$this, 'hasValue']);
        // If array does not contain operators as keys, assume array of values
        if (array_diff_key($value, array_flip(CoreUtil::getOperators()))) {
          // Use IN for regular fields
          if (empty($field['serialize'])) {
            $filterClauses[] = [$fieldName, 'IN', $value];
          }
          // Use an OR group of CONTAINS for array fields
          else {
            $orGroup = [];
            foreach ($value as $val) {
              $orGroup[] = [$fieldName, 'CONTAINS', $val];
            }
            $filterClauses[] = ['OR', $orGroup];
          }
        }
        // Operator => Value array
        else {
          $andGroup = [];
          foreach ($value as $operator => $val) {
            $andGroup[] = [$fieldName, $operator, $val];
          }
          $filterClauses[] = ['AND', $andGroup];
        }
      }
      elseif (!empty($field['serialize']) && in_array('CONTAINS', $operators, TRUE)) {
        $filterClauses[] = [$fieldName, 'CONTAINS', $value];
      }
      elseif ((!empty($field['options']) || in_array($dataType, ['Integer', 'Boolean', 'Date', 'Timestamp'])) && in_array('=', $operators, TRUE)) {
        $filterClauses[] = [$fieldName, '=', $value];
      }
      elseif ($prefixWithWildcard && in_array('CONTAINS', $operators, TRUE)) {
        $filterClauses[] = [$fieldName, 'CONTAINS', $value];
      }
      elseif (in_array('LIKE', $operators, TRUE)) {
        $filterClauses[] = [$fieldName, 'LIKE', $value . '%'];
      }
      elseif (in_array('IN', $operators, TRUE)) {
        $filterClauses[] = [$fieldName, 'IN', (array) $value];
      }
      else {
        $filterClauses[] = [$fieldName, '=', $value];
      }
    }
    // Single field
    if (count($filterClauses) === 1) {
      $clause[] = $filterClauses[0];
    }
    else {
      $clause[] = ['OR', $filterClauses];
    }
  }

  /**
   * Checks if a filter contains a non-empty value
   *
   * "Empty" search values are [], '', and NULL.
   * Also recursively checks arrays to ensure they contain at least one non-empty value.
   *
   * @param $value
   * @return bool
   */
  protected function hasValue($value) {
    return $value !== '' && $value !== NULL && (!is_array($value) || array_filter($value, [$this, 'hasValue']));
  }

  /**
   * Search a string for all square bracket tokens and return their contents (without the brackets)
   *
   * @param string $str
   * @return array
   */
  protected function getTokens(string $str): array {
    $tokens = [];
    preg_match_all('/\\[([^]]+)\\]/', $str, $tokens);
    return array_unique($tokens[1]);
  }

  /**
   * Only SearchKit admins can use unsecured "preview mode" and pass an array for savedSearch or display
   *
   * @throws UnauthorizedException
   */
  protected function checkPermissionToLoadSearch() {
    if (
      (is_array($this->savedSearch) || (isset($this->display) && is_array($this->display))) && $this->checkPermissions &&
      !\CRM_Core_Permission::check([['administer CiviCRM data', 'administer search_kit']])
    ) {
      throw new UnauthorizedException('Access denied');
    }
  }

}
