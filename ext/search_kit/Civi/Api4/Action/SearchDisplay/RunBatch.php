<?php

namespace Civi\Api4\Action\SearchDisplay;

use Civi\Api4\Result\SearchDisplayRunResult;
use Civi\Api4\UserJob;
use Civi\Api4\Utils\CoreUtil;

/**
 * Specialized run action for batch displays
 *
 * @package Civi\Api4\Action\SearchDisplay
 * @since 6.3
 */
class RunBatch extends Run {

  /**
   * @var int
   * @required
   */
  protected $userJobId;

  /**
   * @param \Civi\Api4\Result\SearchDisplayRunResult $result
   * @throws \CRM_Core_Exception
   */
  protected function processResult(SearchDisplayRunResult $result) {
    $userJob = UserJob::get(FALSE)
      ->addWhere('id', '=', $this->userJobId)
      ->addWhere('job_type', '=', 'search_batch_import')
      ->execute()->single();

    // TODO: Validate permission to access this userJob

    $entityName = "Import_{$this->userJobId}";
    $apiParams = [
      'select' => '*',
      'orderBy' => ['_id' => 'ASC'],
    ];
    $settings = $this->display['settings'];
    $page = $index = NULL;
    $key = $this->return;

    $this->applyFilters();

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
        $result[] = $this->getTally();
        return;

      default:
        // Pager mode: `page:n`
        // Or NULL for unlimited results
        if (($settings['pager'] ?? FALSE) !== FALSE && $key && preg_match('/^page:\d+$/', $key)) {
          [$pagerMode, $page] = explode(':', $key);
          $limit = !empty($settings['pager']['expose_limit']) && $this->limit ? $this->limit : NULL;
        }
        $apiParams['debug'] = $this->debug;
        $apiParams['limit'] = $limit ?? $settings['limit'] ?? NULL;
        $apiParams['offset'] = $page ? $apiParams['limit'] * ($page - 1) : 0;

        // Add metadata needed for inline-editing
        if ($this->getActionName() === 'run') {
          $this->addEditableInfo($result);
        }
    }

    $apiResult = civicrm_api4($entityName, 'get', $apiParams, $index);
    // Copy over meta properties to this result
    $result->rowCount = $apiResult->rowCount;
    $result->debug = $apiResult->debug;

    if ($this->return === 'row_count' || $this->return === 'id') {
      $result->exchangeArray($apiResult->getArrayCopy());
    }
    else {
      $result->toolbar = $this->formatToolbar();
      $result->exchangeArray($this->formatResult($apiResult));
      $result->labels = $this->filterLabels;
    }
  }

}
