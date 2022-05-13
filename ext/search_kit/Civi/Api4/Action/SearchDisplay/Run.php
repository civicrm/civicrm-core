<?php

namespace Civi\Api4\Action\SearchDisplay;

use Civi\API\Request;
use Civi\Api4\Query\Api4SelectQuery;
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
   * @param \Civi\Api4\Result\SearchDisplayRunResult $result
   * @throws \API_Exception
   */
  protected function processResult(\Civi\Api4\Result\SearchDisplayRunResult $result) {
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

      case 'tally':
        $this->applyFilters();
        unset($apiParams['orderBy'], $apiParams['limit']);
        $api = Request::create($entityName, 'get', $apiParams);
        $query = new Api4SelectQuery($api);
        $query->forceSelectId = FALSE;
        $sql = $query->getSql();
        $select = [];
        foreach ($settings['columns'] as $col) {
          if (!empty($col['tally']['fn']) && !empty($col['key'])) {
            $fn = \CRM_Core_DAO::escapeString($col['tally']['fn']);
            $key = \CRM_Core_DAO::escapeString($col['key']);
            $select[] = $fn . '(`' . $key . '`) `' . $key . '`';
          }
        }
        $query = 'SELECT ' . implode(', ', $select) . ' FROM (' . $sql . ') `api_query`';
        $dao = \CRM_Core_DAO::executeQuery($query);
        $dao->fetch();
        $tally = [];
        foreach ($settings['columns'] as $col) {
          if (!empty($col['tally']['fn']) && !empty($col['key'])) {
            $alias = str_replace('.', '_', $col['key']);
            $tally[$col['key']] = $dao->$alias ?? NULL;
          }
        }
        $result[] = $tally;
        return;

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
      $result->labels = $this->filterLabels;
    }

  }

}
