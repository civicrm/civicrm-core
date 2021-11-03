<?php

namespace Civi\Api4\Action\SearchDisplay;

use Civi\Api4\Utils\CoreUtil;

/**
 * Load the results for rendering a SearchDisplay.
 *
 * @method $this setReturn(string $return)
 * @method string getReturn()
 * @method $this setLimit(int $limit)
 * @method int getLimit()
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
    $apiParams =& $this->_apiParams;
    $settings = $this->display['settings'];
    $page = $index = NULL;
    $key = $this->return;

    switch ($this->return) {
      case 'id':
        $key = CoreUtil::getIdFieldName($entityName);
        $index = [$key];
      case 'row_count':
        if (empty($apiParams['having'])) {
          $apiParams['select'] = [];
        }
        if (!in_array($key, $apiParams['select'], TRUE)) {
          $apiParams['select'][] = $key;
        }
        unset($apiParams['orderBy'], $apiParams['limit']);
        break;

      default:
        if (($settings['pager'] ?? FALSE) !== FALSE && preg_match('/^page:\d+$/', $key)) {
          $page = explode(':', $key)[1];
        }
        $limit = !empty($settings['pager']['expose_limit']) && $this->limit ? $this->limit : NULL;
        $apiParams['debug'] = $this->debug;
        $apiParams['limit'] = $limit ?? $settings['limit'] ?? NULL;
        $apiParams['offset'] = $page ? $apiParams['limit'] * ($page - 1) : 0;
        $apiParams['orderBy'] = $this->getOrderByFromSort();
        $this->augmentSelectClause($apiParams);
    }

    $this->applyFilters();

    $apiResult = civicrm_api4($entityName, 'get', $apiParams, $index);
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
