<?php

namespace Civi\Api4\Action\SearchDisplay;

use Civi\Api4\Result\SearchDisplayRunResult;
use Civi\Api4\UserJob;

/**
 * Specialized run action for batch displays
 *
 * @method $this setUserJobId(int $userJobId)
 * @method int getUserJobId()
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
   * @var array
   */
  private $userJob;

  /**
   * Unlike the base Run action, this does not support pager, count, or tally
   * as those are all handled client-side.
   *
   * @param \Civi\Api4\Result\SearchDisplayRunResult $result
   * @throws \CRM_Core_Exception
   */
  protected function processResult(SearchDisplayRunResult $result) {
    $this->userJob = UserJob::get(FALSE)
      ->addWhere('id', '=', $this->userJobId)
      ->addWhere('job_type', '=', 'search_batch_import')
      ->execute()->single();

    // TODO: Validate permission to access this userJob

    $entityName = "Import_{$this->userJobId}";
    $apiParams = [
      'select' => ['*'],
      'orderBy' => ['_id' => 'ASC'],
      'debug' => $this->debug,
    ];

    $this->applyFilters();

    $this->addEditableInfo($result);

    $apiResult = civicrm_api4($entityName, 'get', $apiParams);
    // Copy over meta properties to this result
    $result->rowCount = $apiResult->rowCount;
    $result->debug = $apiResult->debug;

    $result->exchangeArray($this->formatResult($apiResult));
    $result->labels = $this->filterLabels;
  }

  /**
   * Add editable information to the SearchDisplayRunResult object.
   *
   * @param \Civi\Api4\Result\SearchDisplayRunResult $result
   *   The SearchDisplayRunResult object to add editable info to.
   */
  private function addEditableInfo(SearchDisplayRunResult $result): void {
    foreach ($this->display['settings']['columns'] as $column) {
      if (!empty($column['key'])) {
        $key = $column['key'];
        $result->editable[$key] = $this->getEditableInfo($column);
        // Set `required` field status based on search display settings
        $result->editable[$key]['required'] = !empty($column['required']);
        // Instead of using nullable from field defn, defer to `required` display setting
        $result->editable[$key]['nullable'] = empty($column['required']);
        if (!empty($column['tally']['target'])) {
          $result->editable[$key]['target'] = $this->userJob['metadata']['DataSource']['targets'][$key] ?? NULL;
        }
      }
    }
  }

  /**
   * Override base method to skip row formatting
   *
   * @param iterable $result
   * @return array{data: array}[]
   */
  protected function formatResult(iterable $result): array {
    $rows = [];
    foreach ($result as $record) {
      $rows[] = [
        'data' => $record,
      ];
    }
    return $rows;
  }

}
