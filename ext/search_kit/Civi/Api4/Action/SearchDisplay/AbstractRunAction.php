<?php

namespace Civi\Api4\Action\SearchDisplay;

use Civi\API\Exception\UnauthorizedException;
use Civi\Api4\Query\SqlExpression;
use Civi\Api4\SavedSearch;
use Civi\Api4\SearchDisplay;
use Civi\Api4\Utils\CoreUtil;

/**
 * Base class for running a search.
 *
 * @package Civi\Api4\Action\SearchDisplay
 */
abstract class AbstractRunAction extends \Civi\Api4\Generic\AbstractAction {

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
   * Search conditions that will be automatically added to the WHERE or HAVING clauses
   * @var array
   */
  protected $filters = [];

  /**
   * Integer used as a seed when ordering by RAND().
   * This keeps the order stable enough to use a pager with random sorting.
   *
   * @var int
   */
  protected $seed;

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
  private $_selectClause;

  /**
   * @param \Civi\Api4\Generic\Result $result
   * @throws UnauthorizedException
   * @throws \API_Exception
   */
  public function _run(\Civi\Api4\Generic\Result $result) {
    // Only administrators can use this in unsecured "preview mode"
    if (!(is_string($this->savedSearch) && is_string($this->display)) && $this->checkPermissions && !\CRM_Core_Permission::check('administer CiviCRM data')) {
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
    // Displays with acl_bypass must be embedded on an afform which the user has access to
    if (
      $this->checkPermissions && !empty($this->display['acl_bypass']) &&
      !\CRM_Core_Permission::check('all CiviCRM permissions and ACLs') && !$this->loadAfform()
    ) {
      throw new UnauthorizedException('Access denied');
    }

    $this->savedSearch['api_params'] += ['where' => []];
    $this->savedSearch['api_params']['checkPermissions'] = empty($this->display['acl_bypass']);
    $this->display['settings']['columns'] = $this->display['settings']['columns'] ?? [];

    $this->processResult($result);
  }

  abstract protected function processResult(\Civi\Api4\Generic\Result $result);

  /**
   * Transforms each row into an array of raw data and an array of formatted columns
   *
   * @param \Civi\Api4\Generic\Result $result
   * @return array{data: array, columns: array}[]
   */
  protected function formatResult(\Civi\Api4\Generic\Result $result): array {
    $rows = [];
    foreach ($result as $index => $row) {
      $data = $columns = [];
      foreach ($this->getSelectClause() as $key => $item) {
        $data[$key] = $this->getValue($key, $row, $index);
      }
      foreach ($this->display['settings']['columns'] as $column) {
        $columns[] = $this->formatColumn($column, $data);
      }
      $rows[] = [
        'data' => $data,
        'columns' => $columns,
      ];
    }
    return $rows;
  }

  /**
   * @param string $key
   * @param array $data
   * @param int $rowIndex
   * @return mixed
   */
  private function getValue($key, $data, $rowIndex) {
    // Get value from api result unless this is a pseudo-field which gets a calculated value
    switch ($key) {
      case 'result_row_num':
        return $rowIndex + 1 + ($this->savedSearch['api_params']['offset'] ?? 0);

      case 'user_contact_id':
        return \CRM_Core_Session::getLoggedInContactID();

      default:
        return $data[$key] ?? NULL;
    }
  }

  /**
   * @param $column
   * @param $data
   * @return array{val: mixed, links: array, edit: array, label: string, title: string, image: array, cssClass: string}
   */
  private function formatColumn($column, $data) {
    $column += ['rewrite' => NULL, 'label' => NULL];
    $out = $cssClass = [];
    switch ($column['type']) {
      case 'field':
        if (isset($column['image']) && is_array($column['image'])) {
          $out['img'] = $this->formatImage($column, $data);
          $out['val'] = $this->replaceTokens($column['image']['alt'] ?? NULL, $data, 'view');
        }
        elseif ($column['rewrite']) {
          $out['val'] = $this->replaceTokens($column['rewrite'], $data, 'view');
        }
        else {
          $out['val'] = $this->formatViewValue($column['key'], $data[$column['key']] ?? NULL);
        }
        if ($this->hasValue($column['label']) && (!empty($column['forceLabel']) || $this->hasValue($out['val']))) {
          $out['label'] = $this->replaceTokens($column['label'], $data, 'view');
        }
        if (isset($column['title']) && strlen($column['title'])) {
          $out['title'] = $this->replaceTokens($column['title'], $data, 'view');
        }
        if (!empty($column['link']['path'])) {
          $out['links'] = $this->formatFieldLinks($column, $data, $out['val']);
        }
        elseif (!empty($column['editable']) && !$column['rewrite']) {
          $out['edit'] = $this->formatEditableColumn($column, $data);
        }
        break;

      case 'links':
      case 'buttons':
      case 'menu':
        $out = $this->formatLinksColumn($column, $data);
        break;
    }
    if (!empty($column['alignment'])) {
      $cssClass[] = $column['alignment'];
    }
    if ($cssClass) {
      $out['cssClass'] = implode(' ', $cssClass);
    }
    return $out;
  }

  /**
   * Format a field value as links
   * @param $column
   * @param $data
   * @param $value
   * @return array{text: string, url: string, target: string}[]
   */
  private function formatFieldLinks($column, $data, $value): array {
    $links = [];
    if (!empty($column['image'])) {
      $value = [''];
    }
    foreach ((array) $value as $index => $val) {
      $path = $this->replaceTokens($column['link']['path'], $data, 'url', $index);
      if ($path) {
        $link = [
          'text' => $val,
          'url' => $this->getUrl($path),
        ];
        if (!empty($column['link']['target'])) {
          $link['target'] = $column['link']['target'];
        }
        $links[] = $link;
      }
    }
    return $links;
  }

  /**
   * Format links for a menu/buttons/links column
   * @param $column
   * @param $data
   * @return array{text: string, url: string, target: string, style: string, icon: string}[]
   */
  private function formatLinksColumn($column, $data): array {
    $out = ['links' => []];
    if (isset($column['text'])) {
      $out['text'] = $this->replaceTokens($column['text'], $data, 'view');
    }
    foreach ($column['links'] as $item) {
      $path = $this->replaceTokens($item['path'], $data, 'url');
      if ($path) {
        $link = [
          'text' => $this->replaceTokens($item['text'] ?? '', $data, 'view'),
          'url' => $this->getUrl($path),
        ];
        foreach (['target', 'style', 'icon'] as $prop) {
          if (!empty($item[$prop])) {
            $link[$prop] = $item[$prop];
          }
        }
        $out['links'][] = $link;
      }
    }
    return $out;
  }

  /**
   * @param string $path
   * @return string
   */
  private function getUrl(string $path) {
    if ($path[0] === '/' || strpos($path, 'http://') || strpos($path, 'https://')) {
      return $path;
    }
    // Use absolute urls when downloading spreadsheet
    $absolute = $this->getActionName() === 'download';
    return \CRM_Utils_System::url($path, NULL, $absolute, NULL, FALSE);
  }

  /**
   * @param $column
   * @param $data
   * @return array{entity: string, input_type: string, data_type: string, options: bool, serialize: bool, fk_entity: string, value_key: string, record: array, value: mixed}|null
   */
  private function formatEditableColumn($column, $data) {
    $editable = $this->getEditableInfo($column['key']);
    if (!empty($data[$editable['id_path']])) {
      $editable['record'] = [
        $editable['id_key'] => $data[$editable['id_path']],
      ];
      $editable['value'] = $data[$editable['value_path']];
      \CRM_Utils_Array::remove($editable, 'id_key', 'id_path', 'value_path');
      return $editable;
    }
    return NULL;
  }

  /**
   * @param $key
   * @return array{entity: string, input_type: string, data_type: string, options: bool, serialize: bool, fk_entity: string, value_key: string, value_path: string, id_key: string, id_path: string}|null
   */
  private function getEditableInfo($key) {
    [$key] = explode(':', $key);
    $field = $this->getField($key);
    // If field is an implicit join, use the original fk field
    if (!empty($field['implicit_join'])) {
      return $this->getEditableInfo(substr($key, 0, -1 - strlen($field['name'])));
    }
    if ($field) {
      $idKey = CoreUtil::getIdFieldName($field['entity']);
      $idPath = ($field['explicit_join'] ? $field['explicit_join'] . '.' : '') . $idKey;
      // Hack to support editing relationships
      if ($field['entity'] === 'RelationshipCache') {
        $field['entity'] = 'Relationship';
        $idPath = ($field['explicit_join'] ? $field['explicit_join'] . '.' : '') . 'relationship_id';
      }
      return [
        'entity' => $field['entity'],
        'input_type' => $field['input_type'],
        'data_type' => $field['data_type'],
        'options' => !empty($field['options']),
        'serialize' => !empty($field['serialize']),
        'fk_entity' => $field['fk_entity'],
        'value_key' => $field['name'],
        'value_path' => $key,
        'id_key' => $idKey,
        'id_path' => $idPath,
      ];
    }
    return NULL;
  }

  /**
   * @param $column
   * @param $data
   * @return array{url: string, width: int, height: int}
   */
  private function formatImage($column, $data) {
    $tokenExpr = $column['rewrite'] ?: '[' . $column['key'] . ']';
    return [
      'url' => $this->replaceTokens($tokenExpr, $data, 'url'),
      'height' => $column['image']['height'] ?? NULL,
      'width' => $column['image']['width'] ?? NULL,
    ];
  }

  /**
   * Returns field definition for a given field or NULL if not found
   * @param $fieldName
   * @return array|null
   */
  protected function getField($fieldName) {
    if (!$this->_selectQuery) {
      $api = \Civi\API\Request::create($this->savedSearch['api_entity'], 'get', $this->savedSearch['api_params']);
      $this->_selectQuery = new \Civi\Api4\Query\Api4SelectQuery($api);
    }
    return $this->_selectQuery->getField($fieldName, FALSE);
  }

  /**
   * Returns the select clause enhanced with metadata
   *
   * @return array
   */
  protected function getSelectClause() {
    if (!isset($this->_selectClause)) {
      $this->_selectClause = [];
      foreach ($this->savedSearch['api_params']['select'] as $selectExpr) {
        $expr = SqlExpression::convert($selectExpr, TRUE);
        $item = [
          'fields' => [],
          'type' => $expr->getType(),
          'dataType' => $expr->getDataType(),
        ];
        foreach ($expr->getFields() as $fieldName) {
          $fieldMeta = $this->getField($fieldName);
          if ($fieldMeta) {
            $item['fields'][] = $fieldMeta;
          }
        }
        if (!isset($item['dataType']) && $item['fields']) {
          $item['dataType'] = $item['fields'][0]['data_type'];
        }
        $this->_selectClause[$expr->getAlias()] = $item;
      }
    }
    return $this->_selectClause;
  }

  /**
   * @param string $key
   * @return array{fields: array, dataType: string}|NULL
   */
  protected function getSelectExpression($key) {
    return $this->getSelectClause()[$key] ?? NULL;
  }

  /**
   * @param string $tokenExpr
   * @param array $data
   * @param string $format view|raw|url
   * @param int $index
   * @return string
   */
  private function replaceTokens($tokenExpr, $data, $format, $index = 0) {
    foreach ($this->getTokens($tokenExpr) as $token) {
      $val = $data[$token] ?? NULL;
      if (isset($val) && $format === 'view') {
        $val = $this->formatViewValue($token, $val);
      }
      $replacement = is_array($val) ? $val[$index] ?? '' : $val;
      // A missing token value in a url invalidates it
      if ($format === 'url' && (!isset($replacement) || $replacement === '')) {
        return NULL;
      }
      $tokenExpr = str_replace('[' . $token . ']', $replacement, $tokenExpr);
    }
    return $tokenExpr;
  }

  /**
   * Format raw field value according to data type
   * @param string $key
   * @param mixed $rawValue
   * @return array|string
   */
  protected function formatViewValue($key, $rawValue) {
    if (is_array($rawValue)) {
      return array_map(function($val) use ($key) {
        return $this->formatViewValue($key, $val);
      }, $rawValue);
    }

    $dataType = $this->getSelectExpression($key)['dataType'] ?? NULL;

    $formatted = $rawValue;

    switch ($dataType) {
      case 'Boolean':
        if (is_bool($rawValue)) {
          $formatted = $rawValue ? ts('Yes') : ts('No');
        }
        break;

      case 'Money':
        $formatted = \CRM_Utils_Money::format($rawValue);
        break;

      case 'Date':
      case 'Timestamp':
        $formatted = \CRM_Utils_Date::customFormat($rawValue);
    }

    return $formatted;
  }

  /**
   * Applies supplied filters to the where clause
   */
  protected function applyFilters() {
    // Allow all filters that are included in SELECT clause or are fields on the Afform.
    $allowedFilters = array_merge($this->getSelectAliases(), $this->getAfformFilters());

    // Ignore empty strings
    $filters = array_filter($this->filters, [$this, 'hasValue']);
    if (!$filters) {
      return;
    }

    foreach ($filters as $key => $value) {
      $fieldNames = explode(',', $key);
      if (in_array($key, $allowedFilters, TRUE) || !array_diff($fieldNames, $allowedFilters)) {
        $this->applyFilter($fieldNames, $value);
      }
    }
  }

  /**
   * Returns an array of field names or aliases + allowed suffixes from the SELECT clause
   * @return string[]
   */
  protected function getSelectAliases() {
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
   * @param array $fieldNames
   *   If multiple field names are given they will be combined in an OR clause
   * @param mixed $value
   */
  private function applyFilter(array $fieldNames, $value) {
    // Global setting determines if % wildcard should be added to both sides (default) or only the end of a search string
    $prefixWithWildcard = \Civi::settings()->get('includeWildCardInName');

    // Based on the first field, decide which clause to add this condition to
    $fieldName = $fieldNames[0];
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

    $filterClauses = [];

    foreach ($fieldNames as $fieldName) {
      $field = $this->getField($fieldName);
      $dataType = $field['data_type'] ?? NULL;
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
      elseif (!empty($field['serialize'])) {
        $filterClauses[] = [$fieldName, 'CONTAINS', $value];
      }
      elseif (!empty($field['options']) || in_array($dataType, ['Integer', 'Boolean', 'Date', 'Timestamp'])) {
        $filterClauses[] = [$fieldName, '=', $value];
      }
      elseif ($prefixWithWildcard) {
        $filterClauses[] = [$fieldName, 'CONTAINS', $value];
      }
      else {
        $filterClauses[] = [$fieldName, 'LIKE', $value . '%'];
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
   * Transforms the SORT param (which is expected to be an array of arrays)
   * to the ORDER BY clause (which is an associative array of [field => DIR]
   *
   * @return array
   */
  protected function getOrderByFromSort() {
    $defaultSort = $this->display['settings']['sort'] ?? [];
    $currentSort = $this->sort;

    // Verify requested sort corresponds to sortable columns
    foreach ($this->sort as $item) {
      $column = array_column($this->display['settings']['columns'], NULL, 'key')[$item[0]] ?? NULL;
      if (!$column || (isset($column['sortable']) && !$column['sortable'])) {
        $currentSort = NULL;
      }
    }

    $orderBy = [];
    foreach ($currentSort ?: $defaultSort as $item) {
      // Apply seed to random sorting
      if ($item[0] === 'RAND()' && isset($this->seed)) {
        $item[0] = 'RAND(' . $this->seed . ')';
      }
      $orderBy[$item[0]] = $item[1];
    }
    return $orderBy;
  }

  /**
   * Adds additional fields to the select clause required to render the display
   *
   * @param array $apiParams
   */
  protected function augmentSelectClause(&$apiParams): void {
    $existing = array_map(function($item) {
      return explode(' AS ', $item)[1] ?? $item;
    }, $apiParams['select']);
    $additions = [];
    // Add primary key field if actions are enabled
    if (!empty($this->display['settings']['actions'])) {
      $additions = CoreUtil::getInfoItem($this->savedSearch['api_entity'], 'primary_key');
    }
    $possibleTokens = '';
    foreach ($this->display['settings']['columns'] as $column) {
      // Collect display values in which a token is allowed
      $possibleTokens .= ($column['rewrite'] ?? '') . ($column['link']['path'] ?? '');
      if (!empty($column['links'])) {
        $possibleTokens .= implode('', array_column($column['links'], 'path'));
        $possibleTokens .= implode('', array_column($column['links'], 'text'));
      }

      // Select id & value for in-place editing
      if (!empty($column['editable'])) {
        $editable = $this->getEditableInfo($column['key']);
        if ($editable) {
          $additions[] = $editable['value_path'];
          $additions[] = $editable['id_path'];
        }
      }
    }
    // Add fields referenced via token
    $tokens = $this->getTokens($possibleTokens);
    // Only add fields not already in SELECT clause
    $additions = array_diff(array_merge($additions, $tokens), $existing);
    // Tokens for aggregated columns start with 'GROUP_CONCAT_'
    foreach ($additions as $index => $alias) {
      if (strpos($alias, 'GROUP_CONCAT_') === 0) {
        $additions[$index] = 'GROUP_CONCAT(' . $this->getJoinFromAlias(explode('_', $alias, 3)[2]) . ') AS ' . $alias;
      }
    }
    $this->_selectClause = NULL;
    $apiParams['select'] = array_unique(array_merge($apiParams['select'], $additions));
  }

  /**
   * @param string $str
   */
  private function getTokens($str) {
    $tokens = [];
    preg_match_all('/\\[([^]]+)\\]/', $str, $tokens);
    return array_unique($tokens[1]);
  }

  /**
   * Given an alias like Contact_Email_01_location_type_id
   * this will return Contact_Email_01.location_type_id
   * @param string $alias
   * @return string
   */
  protected function getJoinFromAlias(string $alias) {
    $result = '';
    foreach ($this->savedSearch['api_params']['join'] ?? [] as $join) {
      $joinName = explode(' AS ', $join[0])[1];
      if (strpos($alias, $joinName) === 0) {
        $parsed = $joinName . '.' . substr($alias, strlen($joinName) + 1);
        // Ensure we are using the longest match
        if (strlen($parsed) > strlen($result)) {
          $result = $parsed;
        }
      }
    }
    return $result;
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
   * Returns a list of filter fields and directive filters
   *
   * Automatically applies directive filters
   *
   * @return array
   */
  private function getAfformFilters() {
    $afform = $this->loadAfform();
    if (!$afform) {
      return [];
    }
    // Get afform field filters
    $filterKeys = array_column(\CRM_Utils_Array::findAll(
      $afform['layout'] ?? [],
      ['#tag' => 'af-field']
    ), 'name');
    // Get filters passed into search display directive from Afform markup
    $filterAttr = $afform['searchDisplay']['filters'] ?? NULL;
    if ($filterAttr && is_string($filterAttr) && $filterAttr[0] === '{') {
      foreach (\CRM_Utils_JS::decode($filterAttr) as $filterKey => $filterVal) {
        // Automatically apply filters from the markup if they have a value
        if ($filterVal !== NULL) {
          unset($this->filters[$filterKey]);
          if ($this->hasValue($filterVal)) {
            $this->applyFilter(explode(',', $filterKey), $filterVal);
          }
        }
        // If it's a javascript variable it will have come back from decode() as NULL;
        // whitelist it to allow it to be passed to this api from javascript.
        else {
          $filterKeys[] = $filterKey;
        }
      }
    }
    return $filterKeys;
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
   * Extra calculated fields provided by SearchKit
   * @return array[]
   */
  public static function getPseudoFields(): array {
    return [
      [
        'name' => 'result_row_num',
        'fieldName' => 'result_row_num',
        'title' => ts('Row Number'),
        'label' => ts('Row Number'),
        'description' => ts('Index of each row, starting from 1 on the first page'),
        'type' => 'Pseudo',
        'data_type' => 'Integer',
        'readonly' => TRUE,
      ],
      [
        'name' => 'user_contact_id',
        'fieldName' => 'result_row_num',
        'title' => ts('Current User ID'),
        'label' => ts('Current User ID'),
        'description' => ts('Contact ID of the current user if logged in'),
        'type' => 'Pseudo',
        'data_type' => 'Integer',
        'readonly' => TRUE,
      ],
    ];
  }

}
