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

/**
 * This interface defines methods that need to be implemented
 * by every scheduled job (cron task) in CiviCRM.
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Core_ScheduledJob {

  /**
   * @var int
   * @deprecated
   */
  public $version = 3;

  /**
   * @var int
   */
  public $id;

  public $name = NULL;

  /**
   * @var string
   */
  public $parameters = '';

  public $apiParams = [];

  public $remarks = [];

  /**
   * @param array $params
   */
  public function __construct($params) {
    // Fixme - setting undeclared class properties!
    foreach ($params as $name => $param) {
      $this->$name = $param;
    }

    try {
      $this->apiParams = CRM_Core_BAO_Job::parseParameters($this->parameters);
    }
    catch (CRM_Core_Exception $e) {
      $this->remarks[] = $e->getMessage();
    }
  }

  /**
   * Update the last_run date of this job
   */
  public function saveLastRun() {
    $dao = new CRM_Core_DAO_Job();
    $dao->id = $this->id;
    $dao->last_run = CRM_Utils_Date::currentDBDate();
    $dao->save();
  }

  /**
   * Delete the scheduled_run_date from this job
   */
  public function clearScheduledRunDate() {
    CRM_Core_DAO::executeQuery('UPDATE civicrm_job SET scheduled_run_date = NULL WHERE id = %1', [
      '1' => [$this->id, 'Integer'],
    ]);
  }

  /**
   * @return bool
   */
  public function needsRunning() {

    // CRM-17686
    // check if the job has a specific scheduled date/time
    if (!empty($this->scheduled_run_date)) {
      if (strtotime($this->scheduled_run_date) <= time()) {
        $this->clearScheduledRunDate();
        return TRUE;
      }
      else {
        return FALSE;
      }
    }

    // run if it was never run
    if (empty($this->last_run)) {
      return TRUE;
    }

    // run_frequency check
    switch ($this->run_frequency) {
      case 'Always':
        return TRUE;

      // CRM-17669
      case 'Yearly':
        $offset = '+1 year';
        break;

      case 'Quarter':
        $offset = '+3 months';
        break;

      case 'Monthly':
        $offset = '+1 month';
        break;

      case 'Weekly':
        $offset = '+1 week';
        break;

      case 'Daily':
        $offset = '+1 day';
        break;

      case 'Hourly':
        $offset = '+1 hour';
        break;
    }

    $now = strtotime(CRM_Utils_Date::currentDBDate());
    $lastTime = strtotime($this->last_run);
    $nextTime = strtotime($offset, $lastTime);

    return ($now >= $nextTime);
  }

}
