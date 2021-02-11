<?php

namespace Civi\Api4\Action\SearchDisplay;

use Civi\API\Exception\UnauthorizedException;
use Civi\Api4\SavedSearch;
use Civi\Api4\SearchDisplay;

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
  protected $sort;

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

  public function _run(\Civi\Api4\Generic\Result $result) {
    // Only administrators can use this in the unsecured "preview mode"
    if (!(is_string($this->savedSearch) && is_string($this->display)) && $this->checkPermissions && !\CRM_Core_Permission::check('administer CiviCRM')) {
      throw new UnauthorizedException('Access denied');
    }
    if (is_string($this->savedSearch)) {
      $this->savedSearch = SavedSearch::get(FALSE)
        ->addWhere('name', '=', $this->savedSearch)
        ->execute()->first();
    }
    if (is_string($this->display)) {
      $this->display = SearchDisplay::get(FALSE)
        ->addWhere('name', '=', $this->display)
        ->addWhere('saved_search_id', '=', $this->savedSearch['id'])
        ->execute()->first();
    }
    $entityName = $this->savedSearch['api_entity'];
    $apiParams =& $this->savedSearch['api_params'];
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

        // Select the ids of joined entities (helps with displaying links)
        foreach ($apiParams['join'] ?? [] as $join) {
          $joinEntity = explode(' AS ', $join[0])[1];
          $idField = $joinEntity . '.id';
          if (!in_array($idField, $apiParams['select']) && !$this->canAggregate('id', $joinEntity . '.')) {
            $apiParams['select'][] = $idField;
          }
        }

    }

    $apiResult = civicrm_api4($entityName, 'get', $apiParams);

    $result->rowCount = $apiResult->rowCount;
    $result->exchangeArray($apiResult->getArrayCopy());
  }

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

  private function getSelectAliases() {
    return array_map(function($select) {
      return array_slice(explode(' AS ', $select), -1)[0];
    }, $this->savedSearch['api_params']['select']);
  }

  private function canAggregate($fieldName, $prefix) {
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

}
