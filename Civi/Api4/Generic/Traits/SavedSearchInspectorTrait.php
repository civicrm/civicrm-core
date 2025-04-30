<?php

namespace Civi\Api4\Generic\Traits;

use Civi\API\Exception\UnauthorizedException;
use Civi\API\Request;
use Civi\Api4\Action\SearchDisplay\AbstractRunAction;
use Civi\Api4\Query\SqlEquation;
use Civi\Api4\Query\SqlExpression;
use Civi\Api4\Query\SqlField;
use Civi\Api4\Query\SqlFunction;
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
   * @var array
   */
  private $_joinMap;

  /**
   * If SavedSearch is supplied as a string, this will load it as an array
   * @param int|null $id
   * @throws UnauthorizedException
   * @throws \CRM_Core_Exception
   */
  protected function loadSavedSearch(?int $id = NULL) {
    if ($id || is_string($this->savedSearch)) {
      $this->savedSearch = SavedSearch::get(FALSE)
        ->addWhere($id ? 'id' : 'name', '=', $id ?: $this->savedSearch)
        ->execute()->single();
    }
    if (is_array($this->savedSearch)) {
      // Ensure array keys are always defined even for unsaved "preview" mode
      $this->savedSearch += [
        'id' => NULL,
        'name' => NULL,
        'api_params' => [],
      ];
      $this->savedSearch['api_params'] += ['version' => 4, 'select' => [], 'where' => []];
    }
    // Reset internal cached metadata
    $this->_selectQuery = $this->_selectClause = $this->_searchEntityFields = $this->_joinMap = NULL;
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
        ->setContext([
          'filters' => $this->filters ?? NULL,
          'formName' => $this->formName ?? NULL,
          'fieldName' => $this->fieldName ?? NULL,
        ])
        // Set by AutocompleteAction
        ->setType($this->_displayType ?? 'table')
        ->execute()->first();
    }
    if (is_array($this->display)) {
      // Ensure array keys are always defined even for unsaved "preview" mode
      $this->display += [
        'id' => NULL,
        'name' => NULL,
      ];
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
   * @param string $joinAlias
   *   Alias of the join, with or without the trailing dot
   * @return array{entity: string, alias: string, table: string, bridge: string|NULL}|NULL
   */
  protected function getJoin(string $joinAlias) {
    return $this->getQuery() ? $this->getQuery()->getExplicitJoin(rtrim($joinAlias, '.')) : NULL;
  }

  /**
   * @return array{entity: string, alias: string, table: string, on: array, bridge: string|NULL}[]
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
      if (!CoreUtil::isType($this->savedSearch['api_entity'], 'DAOEntity')) {
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
    if (!isset($this->_searchEntityFields) && !empty($this->savedSearch['api_entity'])) {
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

    // If this is an implicit join, use the parent field
    if (str_ends_with($fieldPath, '.' . $field['name'])) {
      $baseFieldPath = substr($fieldPath, 0, -strlen('.' . $field['name']));
      $baseField = $this->getField($baseFieldPath);
      if ($baseField) {
        $fieldPath = $baseFieldPath;
        $field = $baseField;
      }
    }

    // If the entity this column belongs to is being grouped by id, then also no
    $idField = substr($fieldPath, 0, 0 - strlen($field['name'])) . CoreUtil::getIdFieldName($field['entity']);
    return !in_array($idField, $apiParams['groupBy']);
  }

  private function renameIfAggregate(string $fieldPath, bool $asSelect = FALSE): string {
    $renamed = $fieldPath;
    if ($this->canAggregate($fieldPath)) {
      $renamed = 'GROUP_CONCAT_' . str_replace(['.', ':'], '_', $fieldPath);
      if ($asSelect) {
        $renamed = "GROUP_CONCAT(UNIQUE $fieldPath) AS $renamed";
      }
    }
    return $renamed;
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
      $operators = array_values($field['operators'] ?? []) ?: CoreUtil::getOperators();
      // Array is either associative `OP => VAL` or sequential `IN (...)`
      if (is_array($value)) {
        $value = array_filter($value, [$this, 'hasValue']);
        // If array does not contain operators as keys, assume array of values
        if (array_diff_key($value, array_flip(CoreUtil::getOperators()))) {
          // Use IN for regular fields
          if (empty($field['serialize'])) {
            $op = in_array('IN', $operators, TRUE) ? 'IN' : $operators[0];
            $filterClauses[] = [$fieldName, $op, $value];
          }
          // Use an OR group of CONTAINS for array fields
          else {
            $op = in_array('CONTAINS', $operators, TRUE) ? 'CONTAINS' : $operators[0];
            $orGroup = [];
            foreach ($value as $val) {
              $orGroup[] = [$fieldName, $op, $val];
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
        $op = in_array('=', $operators, TRUE) ? '=' : $operators[0];
        $filterClauses[] = [$fieldName, $op, $value];
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
    return array_keys(\CRM_Utils_String::getSquareTokens($str));
  }

  /**
   * Only SearchKit admins can use unsecured "preview mode" and pass an array for savedSearch or display
   *
   * @throws UnauthorizedException
   */
  protected function checkPermissionToLoadSearch() {
    if (
      (is_array($this->savedSearch) || (isset($this->display) && is_array($this->display))) && $this->checkPermissions &&
      !\CRM_Core_Permission::check('administer search_kit')
    ) {
      throw new UnauthorizedException('Access denied');
    }
  }

  /**
   * @param \Civi\Api4\Query\SqlExpression $expr
   * @return string
   */
  protected function getColumnLabel(SqlExpression $expr) {
    if ($expr instanceof SqlFunction) {
      $args = [];
      foreach ($expr->getArgs() as $arg) {
        foreach ($arg['expr'] ?? [] as $ex) {
          $args[] = $this->getColumnLabel($ex);
        }
      }
      return '(' . $expr->getTitle() . ')' . ($args ? ' ' . implode(',', array_filter($args)) : '');
    }
    if ($expr instanceof SqlEquation) {
      $args = [];
      foreach ($expr->getArgs() as $arg) {
        if (is_array($arg) && !empty($arg['expr'])) {
          $args[] = $this->getColumnLabel(SqlExpression::convert($arg['expr']));
        }
      }
      return '(' . implode(',', array_filter($args)) . ')';
    }
    elseif ($expr instanceof SqlField) {
      $field = $this->getField($expr->getExpr());
      if (!$field) {
        $pseudoFields = array_column(AbstractRunAction::getPseudoFields(), NULL, 'name');
        $field = $pseudoFields[$expr->getExpr()] ?? NULL;
      }
      $label = '';
      if (!empty($field['explicit_join'])) {
        $label = $this->getJoinLabel($field['explicit_join']) . ': ';
      }
      if (!empty($field['implicit_join']) && empty($field['custom_field_id'])) {
        $field = $this->getField(substr($expr->getAlias(), 0, -1 - strlen($field['name'])));
      }
      return $label . $field['label'];
    }
    else {
      return NULL;
    }
  }

  /**
   * @param string $joinAlias
   * @return string
   */
  protected function getJoinLabel($joinAlias) {
    if (!isset($this->_joinMap)) {
      $this->_joinMap = [];
      $joinCount = [$this->savedSearch['api_entity'] => 1];
      foreach ($this->savedSearch['api_params']['join'] ?? [] as $join) {
        [$entityName, $alias] = explode(' AS ', $join[0]);
        $num = '';
        if (!empty($joinCount[$entityName])) {
          $num = ' ' . (++$joinCount[$entityName]);
        }
        else {
          $joinCount[$entityName] = 1;
        }
        $label = CoreUtil::getInfoItem($entityName, 'title');
        $this->_joinMap[$alias] = $this->savedSearch['form_values']['join'][$alias] ?? "$label$num";
      }
    }
    return $this->_joinMap[$joinAlias];
  }

}
