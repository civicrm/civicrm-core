<?php

namespace Civi\Api4\Action\SearchDisplay;

use Civi\API\Exception\UnauthorizedException;
use Civi\Api4\SavedSearch;
use Civi\Api4\SearchDisplay;
use Civi\Api4\Utils\CoreUtil;

/**
 * Load the results for rendering a SearchDisplay.
 *
 * @package Civi\Api4\Action\SearchDisplay
 */
class Run extends \Civi\Api4\Generic\AbstractAction {

  /**
   * Either the name of the savedSearch or an array containing the savedSearch definition (for preview mode)
   * @var string|array
   * @required
   */
  protected $savedSearch;

  /**
   * Either the name of the display or an array containing the display definition (for preview mode)
   * @var string|array
   * @required
   */
  protected $display;

  /**
   * Array of fields to use for ordering the results
   * @var array
   */
  protected $sort = [];

  /**
   * Should this api call return a page of results or the row_count or the ids
   * E.g. "page:1" or "row_count" or "id"
   * @var string
   */
  protected $return;

  /**
   * Search conditions that will be automatically added to the WHERE or HAVING clauses
   * @var array
   */
  protected $filters = [];

  /**
   * Name of Afform, if this display is embedded (used for permissioning)
   * @var string
   */
  protected $afform;

  /**
   * @var \Civi\Api4\Query\Api4SelectQuery
   */
  private $_selectQuery;

  /**
   * @var array
   */
  private $_afform;

  /**
   * @var array
   */
  private $_extraEntityFields = [];

  /**
   * @param \Civi\Api4\Generic\Result $result
   * @throws UnauthorizedException
   * @throws \API_Exception
   */
  public function _run(\Civi\Api4\Generic\Result $result) {
    // Only administrators can use this in unsecured "preview mode"
    if (!(is_string($this->savedSearch) && is_string($this->display)) && $this->checkPermissions && !\CRM_Core_Permission::check('administer CiviCRM')) {
      throw new UnauthorizedException('Access denied');
    }
    if (is_string($this->savedSearch)) {
      $this->savedSearch = SavedSearch::get(FALSE)
        ->addWhere('name', '=', $this->savedSearch)
        ->execute()->first();
    }
    if (is_string($this->display) && !empty($this->savedSearch['id'])) {
      $this->display = SearchDisplay::get(FALSE)
        ->setSelect(['*', 'type:name'])
        ->addWhere('name', '=', $this->display)
        ->addWhere('saved_search_id', '=', $this->savedSearch['id'])
        ->execute()->first();
    }
    if (!$this->savedSearch || !$this->display) {
      throw new \API_Exception("Error: SearchDisplay not found.");
    }
    $entityName = $this->savedSearch['api_entity'];
    $apiParams =& $this->savedSearch['api_params'];
    $apiParams['checkPermissions'] = $this->checkPermissions;
    $apiParams += ['where' => []];
    $settings = $this->display['settings'];
    $page = NULL;

    switch ($this->return) {
      case 'row_count':
      case 'id':
        if (empty($apiParams['having'])) {
          $apiParams['select'] = [];
        }
        if (!in_array($this->return, $apiParams)) {
          $apiParams['select'][] = $this->return;
        }
        unset($apiParams['orderBy'], $apiParams['limit']);
        break;

      default:
        if (!empty($settings['pager']) && preg_match('/^page:\d+$/', $this->return)) {
          $page = explode(':', $this->return)[1];
        }
        $apiParams['limit'] = $settings['limit'] ?? NULL;
        $apiParams['offset'] = $page ? $apiParams['limit'] * ($page - 1) : 0;
        $apiParams['orderBy'] = $this->getOrderByFromSort();
        $this->augmentSelectClause($apiParams);
    }

    $this->applyFilters();

    $apiResult = civicrm_api4($entityName, 'get', $apiParams);

    $result->rowCount = $apiResult->rowCount;
    $result->exchangeArray($apiResult->getArrayCopy());
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
  private function hasValue($value) {
    return $value !== '' && $value !== NULL && (!is_array($value) || array_filter($value, [$this, 'hasValue']));
  }

  /**
   * Applies supplied filters to the where clause
   */
  private function applyFilters() {
    // Ignore empty strings
    $filters = array_filter($this->filters, [$this, 'hasValue']);
    if (!$filters) {
      return;
    }

    // Process all filters that are included in SELECT clause. These filters are implicitly allowed.
    foreach ($this->getSelectAliases() as $fieldName) {
      if (isset($filters[$fieldName])) {
        $value = $filters[$fieldName];
        unset($filters[$fieldName]);
        $this->applyFilter($fieldName, $value);
      }
    }

    // Other filters may be allowed if display is embedded in an afform.
    if ($filters) {
      foreach ($this->getAfformFilters() as $fieldName) {
        if (isset($filters[$fieldName])) {
          $value = $filters[$fieldName];
          $this->applyFilter($fieldName, $value);
        }
      }
    }
  }

  /**
   * @param string $fieldName
   * @param mixed $value
   */
  private function applyFilter(string $fieldName, $value) {
    // Global setting determines if % wildcard should be added to both sides (default) or only the end of a search string
    $prefixWithWildcard = \Civi::settings()->get('includeWildCardInName');

    $field = $this->getField($fieldName);
    // If field is not found it must be an aggregated column & belongs in the HAVING clause.
    if (!$field) {
      $this->savedSearch['api_params']['having'] = $this->savedSearch['api_params']['having'] ?? [];
      $clause =& $this->savedSearch['api_params']['having'];
    }
    // If field belongs to an EXCLUDE join, it should be added as a join condition
    else {
      $prefix = strpos($fieldName, '.') ? explode('.', $fieldName)[0] : NULL;
      foreach ($this->savedSearch['api_params']['join'] ?? [] as $idx => $join) {
        if (($join[1] ?? 'LEFT') === 'EXCLUDE' && (explode(' AS ', $join[0])[1] ?? '') === $prefix) {
          $clause =& $this->savedSearch['api_params']['join'][$idx];
        }
      }
    }
    // Default: add filter to WHERE clause
    if (!isset($clause)) {
      $clause =& $this->savedSearch['api_params']['where'];
    }

    $dataType = $field['data_type'] ?? NULL;

    // Array is either associative `OP => VAL` or sequential `IN (...)`
    if (is_array($value)) {
      $value = array_filter($value, [$this, 'hasValue']);
      // Use IN if array does not contain operators as keys
      if (array_diff_key($value, array_flip(CoreUtil::getOperators()))) {
        $clause[] = [$fieldName, 'IN', $value];
      }
      else {
        foreach ($value as $operator => $val) {
          $clause[] = [$fieldName, $operator, $val];
        }
      }
    }
    elseif (!empty($field['serialize'])) {
      $clause[] = [$fieldName, 'CONTAINS', $value];
    }
    elseif (!empty($field['options']) || in_array($dataType, ['Integer', 'Boolean', 'Date', 'Timestamp'])) {
      $clause[] = [$fieldName, '=', $value];
    }
    elseif ($prefixWithWildcard) {
      $clause[] = [$fieldName, 'CONTAINS', $value];
    }
    else {
      $clause[] = [$fieldName, 'LIKE', $value . '%'];
    }
  }

  /**
   * Transforms the SORT param (which is expected to be an array of arrays)
   * to the ORDER BY clause (which is an associative array of [field => DIR]
   *
   * @return array
   */
  private function getOrderByFromSort() {
    $defaultSort = $this->display['settings']['sort'] ?? [];
    $currentSort = $this->sort;

    // Validate that requested sort fields are part of the SELECT
    foreach ($this->sort as $item) {
      if (!in_array($item[0], $this->getSelectAliases())) {
        $currentSort = NULL;
      }
    }

    $orderBy = [];
    foreach ($currentSort ?: $defaultSort as $item) {
      $orderBy[$item[0]] = $item[1];
    }
    return $orderBy;
  }

  /**
   * Returns an array of field names or aliases + allowed suffixes from the SELECT clause
   * @return string[]
   */
  private function getSelectAliases() {
    $result = [];
    $selectAliases = array_map(function($select) {
      return array_slice(explode(' AS ', $select), -1)[0];
    }, $this->savedSearch['api_params']['select']);
    foreach ($selectAliases as $alias) {
      [$alias] = explode(':', $alias);
      $result[] = $alias;
      foreach (['name', 'label', 'abbr'] as $allowedSuffix) {
        $result[] = $alias . ':' . $allowedSuffix;
      }
    }
    return $result;
  }

  /**
   * Determines if a column is eligible to use an aggregate function
   * @param string $fieldName
   * @param string $prefix
   * @return bool
   */
  private function canAggregate($fieldName, $prefix = '') {
    $apiParams = $this->savedSearch['api_params'];

    // If the query does not use grouping, never
    if (empty($apiParams['groupBy'])) {
      return FALSE;
    }
    // If the column is used for a groupBy, no
    if (in_array($prefix . $fieldName, $apiParams['groupBy'])) {
      return FALSE;
    }
    // If the entity this column belongs to is being grouped by id, then also no
    return !in_array($prefix . 'id', $apiParams['groupBy']);
  }

  /**
   * Returns field definition for a given field or NULL if not found
   * @param $fieldName
   * @return array|null
   */
  private function getField($fieldName) {
    if (!$this->_selectQuery) {
      $api = \Civi\API\Request::create($this->savedSearch['api_entity'], 'get', $this->savedSearch['api_params']);
      $this->_selectQuery = new \Civi\Api4\Query\Api4SelectQuery($api);
    }
    return $this->_selectQuery->getField($fieldName, FALSE);
  }

  /**
   * @return array
   */
  private function getAfformFilters() {
    $afform = $this->loadAfform();
    if (!$afform) {
      return [];
    }
    // Get afform field filters
    $filters = array_column(\CRM_Utils_Array::findAll(
      $afform['layout'] ?? [],
      ['#tag' => 'af-field']
    ), 'name');
    // Get filters passed into search display directive
    $filterAttr = $afform['searchDisplay']['filters'] ?? NULL;
    if ($filterAttr && is_string($filterAttr) && $filterAttr[0] === '{') {
      $filters = array_unique(array_merge($filters, array_keys(\CRM_Utils_JS::getRawProps($filterAttr))));
    }
    return $filters;
  }

  /**
   * Return afform with name specified in api call.
   *
   * Verifies the searchDisplay is embedded in the afform and the user has permission to view it.
   *
   * @return array|false|null
   */
  private function loadAfform() {
    // Only attempt to load afform once.
    if ($this->afform && !isset($this->_afform)) {
      $this->_afform = FALSE;
      // Permission checks are enabled in this api call to ensure the user has permission to view the form
      $afform = \Civi\Api4\Afform::get()
        ->addWhere('name', '=', $this->afform)
        ->setLayoutFormat('shallow')
        ->execute()->first();
      // Validate that the afform contains this search display
      $afform['searchDisplay'] = \CRM_Utils_Array::findAll(
        $afform['layout'] ?? [],
        ['#tag' => "{$this->display['type:name']}", 'display-name' => $this->display['name']]
      )[0] ?? NULL;
      if ($afform['searchDisplay']) {
        $this->_afform = $afform;
      }
    }
    return $this->_afform;
  }

  /**
   * Adds additional useful fields to the select clause
   *
   * @param array $apiParams
   */
  private function augmentSelectClause(&$apiParams): void {
    foreach ($this->getExtraEntityFields($this->savedSearch['api_entity']) as $extraFieldName) {
      if (!in_array($extraFieldName, $apiParams['select']) && !$this->canAggregate($extraFieldName)) {
        $apiParams['select'][] = $extraFieldName;
      }
    }
    $joinAliases = [];
    // Select the ids, etc. of explicitly joined entities (helps with displaying links)
    foreach ($apiParams['join'] ?? [] as $join) {
      [$joinEntity, $joinAlias] = explode(' AS ', $join[0]);
      $joinAliases[] = $joinAlias;
      foreach ($this->getExtraEntityFields($joinEntity) as $extraField) {
        $extraFieldName = $joinAlias . '.' . $extraField;
        if (!in_array($extraFieldName, $apiParams['select']) && !$this->canAggregate($extraField, $joinAlias . '.')) {
          $apiParams['select'][] = $extraFieldName;
        }
      }
    }
    // Select the ids of implicitly joined entities (helps with displaying links)
    foreach ($apiParams['select'] as $fieldName) {
      if (strstr($fieldName, '.') && !strstr($fieldName, ' AS ') && !strstr($fieldName, ':')) {
        $idFieldName = $fieldNameWithoutPrefix = substr($fieldName, 0, strrpos($fieldName, '.'));
        $idField = $this->getField($idFieldName);
        $explicitJoin = '';
        if (strstr($idFieldName, '.')) {
          [$prefix, $fieldNameWithoutPrefix] = explode('.', $idFieldName, 2);
          if (in_array($prefix, $joinAliases, TRUE)) {
            $explicitJoin = $prefix . '.';
          }
        }
        if (!in_array($idFieldName, $apiParams['select']) && !empty($idField['fk_entity']) && !$this->canAggregate($fieldNameWithoutPrefix, $explicitJoin)) {
          $apiParams['select'][] = $idFieldName;
        }
      }
    }
    // Select value fields for in-place editing
    foreach ($this->display['settings']['columns'] ?? [] as $column) {
      if (isset($column['editable']['value']) && !in_array($column['editable']['value'], $apiParams['select'])) {
        $apiParams['select'][] = $column['editable']['value'];
      }
    }
  }

  /**
   * Get list of extra fields needed for displaying links for a given entity
   *
   * @param string $entityName
   * @return array
   */
  private function getExtraEntityFields(string $entityName): array {
    if (!isset($this->_extraEntityFields[$entityName])) {
      $info = CoreUtil::getApiClass($entityName)::getInfo();
      $this->_extraEntityFields[$entityName] = [$info['id_field']];
      foreach ($info['paths'] ?? [] as $path) {
        $matches = [];
        preg_match_all('#\[(\w+)]#', $path, $matches);
        $this->_extraEntityFields[$entityName] = array_unique(array_merge($this->_extraEntityFields[$entityName], $matches[1] ?? []));
      }
    }
    return $this->_extraEntityFields[$entityName];
  }

}
