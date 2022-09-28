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

namespace Civi\Api4\Generic;

use Civi\Api4\Utils\CoreUtil;

/**
 * Retrieve $ENTITIES for an autocomplete form field.
 *
 * @method $this setInput(string $input) Set input term.
 * @method string getInput()
 * @method $this setIds(array $ids) Set array of ids.
 * @method array getIds()
 * @method $this setPage(int $page) Set current page.
 * @method array getPage()
 * @method $this setFormName(string $formName) Set formName.
 * @method string getFormName()
 * @method $this setFieldName(string $fieldName) Set fieldName.
 * @method string getFieldName()
 * @method $this setClientFilters(array $clientFilters) Set array of untrusted filter values.
 * @method array getClientFilters()
 */
class AutocompleteAction extends AbstractAction {
  use Traits\SavedSearchInspectorTrait;

  /**
   * Autocomplete search input for search mode
   *
   * @var string
   */
  protected $input = '';

  /**
   * Array of ids for render mode
   *
   * @var array
   */
  protected $ids;

  /**
   * @var int
   */
  protected $page = 1;

  /**
   * Name of SavedSearch to use for filtering.
   * @var string
   */
  protected $savedSearch;

  /**
   * @var string
   */
  protected $formName;

  /**
   * @var string
   */
  protected $fieldName;

  /**
   * Filters requested by untrusted client, permissions will be checked before applying (even if this request has checkPermissions = FALSE).
   *
   * Format: [fieldName => value][]
   * @var array
   */
  protected $clientFilters = [];

  /**
   * Filters set programmatically by `civi.api.prepare` listener. Automatically trusted.
   *
   * Format: [fieldName => value][]
   * @var array
   */
  private $trustedFilters = [];

  /**
   * Fetch results.
   *
   * @param \Civi\Api4\Generic\Result $result
   */
  public function _run(Result $result) {
    $entityName = $this->getEntityName();
    $fields = CoreUtil::getApiClass($entityName)::get()->entityFields();
    $idField = CoreUtil::getIdFieldName($entityName);
    $labelField = CoreUtil::getInfoItem($entityName, 'label_field');
    $iconFields = CoreUtil::getInfoItem($entityName, 'icon_field') ?? [];
    $map = [
      'id' => $idField,
      'label' => $labelField,
    ];
    // FIXME: Use metadata
    if (isset($fields['description'])) {
      $map['description'] = 'description';
    }
    if (isset($fields['color'])) {
      $map['color'] = 'color';
    }
    $select = array_merge(array_values($map), $iconFields);

    if (!$this->savedSearch) {
      $this->savedSearch = ['api_entity' => $entityName];
    }
    $this->loadSavedSearch();
    // Pass-through this parameter
    $this->_apiParams['checkPermissions'] = $this->savedSearch['api_params']['checkPermissions'] = $this->getCheckPermissions();
    // Render mode: fetch by id
    if ($this->ids) {
      $this->_apiParams['where'][] = [$idField, 'IN', $this->ids];
      $resultsPerPage = NULL;
    }
    // Search mode: fetch a page of results based on input
    else {
      $resultsPerPage = \Civi::settings()->get('search_autocomplete_count') ?: 10;
      // Adding one extra result allows us to see if there are any more
      $this->_apiParams['limit'] = $resultsPerPage + 1;
      $this->_apiParams['offset'] = ($this->page - 1) * $resultsPerPage;

      $orderBy = CoreUtil::getInfoItem($this->getEntityName(), 'order_by') ?: $labelField;
      $this->_apiParams['orderBy'] = [$orderBy => 'ASC'];
      if (strlen($this->input)) {
        $prefix = \Civi::settings()->get('includeWildCardInName') ? '%' : '';
        $this->_apiParams['where'][] = [$labelField, 'LIKE', $prefix . $this->input . '%'];
      }
    }
    if (empty($this->_apiParams['having'])) {
      $this->_apiParams['select'] = $select;
    }
    // A HAVING clause depends on the SELECT clause so don't overwrite it.
    else {
      $this->_apiParams['select'] = array_unique(array_merge($this->_apiParams['select'], $select));
    }
    $this->applyFilters();
    $apiResult = civicrm_api4($entityName, 'get', $this->_apiParams);
    $rawResults = array_slice((array) $apiResult, 0, $resultsPerPage);
    foreach ($rawResults as $row) {
      $mapped = [];
      foreach ($map as $key => $fieldName) {
        $mapped[$key] = $row[$fieldName];
      }
      // Get icon in order of priority
      foreach ($iconFields as $fieldName) {
        if (!empty($row[$fieldName])) {
          // Icon field may be multivalued e.g. contact_sub_type
          $icon = \CRM_Utils_Array::first(array_filter((array) $row[$fieldName]));
          if ($icon) {
            $mapped['icon'] = $icon;
          }
          break;
        }
      }
      $result[] = $mapped;
    }
    $result->setCountMatched($apiResult->countFetched());
  }

  /**
   * Method for `civi.api.prepare` listener to add a trusted filter.
   *
   * @param string $fieldName
   * @param mixed $value
   */
  public function addFilter(string $fieldName, $value) {
    $this->trustedFilters[$fieldName] = $value;
  }

  /**
   * Applies trusted filters. Checks access before applying client filters.
   */
  private function applyFilters() {
    foreach ($this->trustedFilters as $field => $val) {
      if ($this->hasValue($val)) {
        $this->applyFilter($field, $val);
      }
    }
    foreach ($this->clientFilters as $field => $val) {
      if ($this->hasValue($val) && $this->checkFieldAccess($field)) {
        $this->applyFilter($field, $val);
      }
    }
  }

  /**
   * @param $fieldNameWithSuffix
   * @return bool
   */
  private function checkFieldAccess($fieldNameWithSuffix) {
    [$fieldName] = explode(':', $fieldNameWithSuffix);
    if (
      in_array($fieldName, $this->_apiParams['select'], TRUE) ||
      in_array($fieldNameWithSuffix, $this->_apiParams['select'], TRUE) ||
      in_array($fieldName, $this->savedSearch['api_params']['select'], TRUE) ||
      in_array($fieldNameWithSuffix, $this->savedSearch['api_params']['select'], TRUE)
    ) {
      return TRUE;
    }
    // Proceed only if permissions are being enforced.'
    // Anonymous users in permission-bypass mode should not be allowed to set arbitrary filters.
    if ($this->getCheckPermissions()) {
      // This function checks field permissions
      return (bool) $this->getField($fieldName);
    }
    return FALSE;
  }

  /**
   * @return array
   */
  public function getPermissions() {
    // Permissions for this action are checked internally
    return [];
  }

}
