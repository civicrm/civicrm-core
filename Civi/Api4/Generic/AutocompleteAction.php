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
 */
class AutocompleteAction extends AbstractAction {
  use Traits\SavedSearchInspectorTrait;

  /**
   * Autocomplete search input
   *
   * @var string
   */
  protected $input = '';

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
   * Fetch results.
   *
   * @param \Civi\Api4\Generic\Result $result
   */
  public function _run(Result $result) {
    $entityName = $this->getEntityName();
    $fields = CoreUtil::getApiClass($entityName)::get()->entityFields();
    $idField = CoreUtil::getIdFieldName($entityName);
    $labelField = CoreUtil::getInfoItem($entityName, 'label_field');
    $map = [
      'id' => $idField,
      'text' => $labelField,
    ];
    // FIXME: Use metadata
    if (isset($fields['description'])) {
      $map['description'] = 'description';
    }
    if (isset($fields['color'])) {
      $map['color'] = 'color';
    }
    if (isset($fields['icon'])) {
      $map['icon'] = 'icon';
    }

    if (!$this->savedSearch) {
      $this->savedSearch = ['api_entity' => $entityName];
    }
    $this->loadSavedSearch();
    $resultsPerPage = \Civi::settings()->get('search_autocomplete_count');
    // Adding one extra result allows us to see if there are any more
    $this->_apiParams['limit'] = $resultsPerPage + 1;
    $this->_apiParams['offset'] = ($this->page - 1) * $resultsPerPage;
    if (strlen($this->input)) {
      $prefix = \Civi::settings()->get('includeWildCardInName') ? '%' : '';
      $this->_apiParams['where'][] = [$labelField, 'LIKE', $prefix . $this->input . '%'];
    }
    if (empty($this->_apiParams['having'])) {
      $this->_apiParams['select'] = array_values($map);
    }
    else {
      $this->_apiParams['select'] = array_merge($this->_apiParams['select'], array_values($map));
    }
    $this->_apiParams['checkPermissions'] = $this->getCheckPermissions();
    $apiResult = civicrm_api4($entityName, 'get', $this->_apiParams);
    $rawResults = array_slice((array) $apiResult, 0, $resultsPerPage);
    foreach ($rawResults as $row) {
      $mapped = [];
      foreach ($map as $key => $fieldName) {
        $mapped[$key] = $row[$fieldName];
      }
      $result[] = $mapped;
    }
    $result->setCountMatched($apiResult->countFetched());
  }

}
