<?php

namespace Civi\Api4\Action\SearchDisplay;

use CRM_Search_ExtensionUtil as E;

/**
 * @method $this setUserJobId(int $userJobId)
 * @method int getUserJobId()
 * @package Civi\Api4\Action\SearchDisplay
 * @since 6.3
 */
class ImportBatch extends \Civi\Api4\Generic\AbstractAction {
  use \Civi\Api4\Generic\Traits\SavedSearchInspectorTrait;

  /**
   * @var int
   * @required
   */
  protected $userJobId;

  /**
   * An array containing the searchDisplay definition (passed to the api as a string)
   * @var string
   * @required
   */
  protected $display;

  /**
   * @param \Civi\Api4\Generic\Result $result
   * @throws \CRM_Core_Exception
   */
  public function _run(\Civi\Api4\Generic\Result $result) {
    $this->loadSavedSearch();
    $this->loadSearchDisplay();
    // TODO: Validate permission to access this userJob

    $parser = new \CRM_Search_Import_Parser();
    $parser->setUserJobID($this->userJobId);
    $parser->queue();
    $queue = \Civi::queue('user_job_' . $this->userJobId);
    $runner = new \CRM_Queue_Runner([
      'queue' => $queue,
      'errorMode' => \CRM_Queue_Runner::ERROR_ABORT,
      'onEndUrl' => \CRM_Utils_System::url('civicrm/import/contact/summary', [
        'user_job_id' => $this->userJobId,
        'reset' => 1,
      ], FALSE, NULL, FALSE),
    ]);
    $url = $runner->runAllInteractive(FALSE);
    $result[] = [
      'url' => $url,
    ];
  }

}
