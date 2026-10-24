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

namespace Civi\Api4\Action\SearchSchedule;

use Civi\Api4\Generic\AbstractAction;
use Civi\Api4\Generic\Result;
use Civi\Api4\SearchDisplay;
use Civi\Api4\SearchSchedule;
use CRM_Search_BAO_SearchSchedule as BAO;

/**
 * Sends scheduled SearchKit reports that are currently due.
 */
class SendDue extends AbstractAction {

  /**
   * Optional ID of a specific schedule to send.
   *
   * @var int
   */
  protected $scheduleId;

  /**
   * If TRUE, process the schedule even if next_run is in the future.
   *
   * @var bool
   */
  protected $force = FALSE;

  /**
   * Criteria to filter schedules.
   *
   * @var array
   */
  protected $where = [];

  public function _run(Result $result) {
    $now = new \DateTime();
    $nowStr = $now->format('Y-m-d H:i:s');

    $query = SearchSchedule::get($this->checkPermissions);
    foreach ($this->where as $clause) {
      $query->addClause('where', $clause);
    }

    if ($this->scheduleId) {
      $query->addWhere('id', '=', $this->scheduleId);
      if (!$this->force) {
        $query->addWhere('is_active', '=', TRUE);
        $query->addWhere('next_run', '<=', $nowStr);
      }
    }
    else {
      $query->addWhere('is_active', '=', TRUE);
      $query->addWhere('next_run', '<=', $nowStr);
      $query->addWhere('next_run', 'IS NOT NULL');
    }

    $schedules = $query->execute();

    foreach ($schedules as $schedule) {
      try {
        $this->sendSchedule($schedule);
        $nextRun = BAO::calculateNextRun($schedule, $now);
        SearchSchedule::update(FALSE)
          ->addWhere('id', '=', $schedule['id'])
          ->addValue('last_run', $nowStr)
          ->addValue('next_run', $nextRun ?? '')
          ->execute();

        $result[] = [
          'id' => $schedule['id'],
          'label' => $schedule['label'],
          'status' => 'sent',
          'next_run' => $nextRun,
        ];
      }
      catch (\Throwable $e) {
        \Civi::log()->error('Error sending SearchSchedule #' . $schedule['id'] . ': ' . $e->getMessage(), ['exception' => $e]);

        // Advance next_run so a single failing schedule does not block the queue
        $nextRun = BAO::calculateNextRun($schedule, $now);
        SearchSchedule::update(FALSE)
          ->addWhere('id', '=', $schedule['id'])
          ->addValue('last_run', $nowStr)
          ->addValue('next_run', $nextRun ?? '')
          ->execute();

        $result[] = [
          'id' => $schedule['id'],
          'label' => $schedule['label'],
          'status' => 'error',
          'error' => $e->getMessage(),
          'next_run' => $nextRun,
        ];
      }
    }
  }

  /**
   * Sends a single schedule via SearchDisplay::emailReport.
   *
   * @param array $schedule
   * @throws \CRM_Core_Exception
   */
  protected function sendSchedule(array $schedule) {
    $display = SearchDisplay::get(FALSE)
      ->addSelect('name', 'label', 'saved_search_id', 'saved_search_id.name')
      ->addWhere('id', '=', $schedule['search_display_id'])
      ->execute()
      ->first();

    if (!$display) {
      throw new \CRM_Core_Exception("SearchDisplay #{$schedule['search_display_id']} not found.");
    }

    $recipients = (array) ($schedule['recipients'] ?? []);
    if (empty($recipients)) {
      throw new \CRM_Core_Exception("No recipients specified for SearchSchedule #{$schedule['id']}.");
    }

    $savedSearchName = $display['saved_search_id.name'];
    $displayName = $display['name'];
    $filters = !empty($schedule['filters']) ? (array) $schedule['filters'] : [];
    $format = !empty($schedule['format']) ? $schedule['format'] : 'csv';
    $fileName = \CRM_Utils_String::munge($schedule['label']);

    $action = SearchDisplay::emailReport(FALSE)
      ->setSavedSearch($savedSearchName)
      ->setDisplay($displayName)
      ->setContactID(implode(',', $recipients))
      ->setSubject($schedule['subject'])
      ->setReportName($schedule['label'])
      ->setFileName($fileName)
      ->setFormat($format)
      ->setFilters($filters);

    if (!empty($schedule['msg_template_id'])) {
      $action->setTemplateID($schedule['msg_template_id']);
    }

    $action->execute();
  }

}
