<?php

namespace Civi\Api4\Action\SearchDisplay;

/**
 * Load the results for rendering a SearchDisplay.
 *
 * @package Civi\Api4\Action\SearchDisplay
 */
class Run extends AbstractRunAction {

  /**
   * Should this api call return a page of results or the row_count or the ids
   * E.g. "page:1" or "row_count" or "id"
   * @var string
   */
  protected $return;

  /**
   * Number of results to return
   * @var int
   */
  protected $limit;

  /**
   * @param \Civi\Api4\Generic\Result $result
   * @throws \API_Exception
   */
  protected function processResult(\Civi\Api4\Generic\Result $result) {
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
        if (!in_array($this->return, $apiParams['select'], TRUE)) {
          $apiParams['select'][] = $this->return;
        }
        unset($apiParams['orderBy'], $apiParams['limit']);
        break;

      default:
        if (($settings['pager'] ?? FALSE) !== FALSE && preg_match('/^page:\d+$/', $this->return)) {
          $page = explode(':', $this->return)[1];
        }
        $limit = !empty($settings['pager']['expose_limit']) && $this->limit ? $this->limit : NULL;
        $apiParams['debug'] = $this->debug;
        $apiParams['limit'] = $limit ?? $settings['limit'] ?? NULL;
        $apiParams['offset'] = $page ? $apiParams['limit'] * ($page - 1) : 0;
        $apiParams['orderBy'] = $this->getOrderByFromSort();
        $this->augmentSelectClause($apiParams);
    }

    $this->applyFilters();

    $apiResult = civicrm_api4($entityName, 'get', $apiParams);
    // Copy over meta properties to this result
    $result->rowCount = $apiResult->rowCount;
    $result->debug = $apiResult->debug;

    if ($this->return === 'row_count' || $this->return === 'id') {
      $result->exchangeArray($apiResult->getArrayCopy());
    }
    else {
      $result->exchangeArray($this->formatResult($apiResult));
    }

  }

}
