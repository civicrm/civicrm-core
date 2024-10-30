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
   * Job ID
   *
   * @var int
   */
  public $id;

  /**
   * Which Domain is this scheduled job for
   *
   * @var int
   */
  public $domain_id;

  /**
   * Scheduled job run frequency.
   *
   * @var string
   */
  public $run_frequency;

  /**
   * When was this cron entry last run
   *
   * @var string
   */
  public $last_run;

  /**
   * When is this cron entry scheduled to run
   *
   * @var string
   */
  public $scheduled_run_date;

  /**
   * Title of the job
   *
   * @var string
   */
  public $name;

  /**
   * Description of the job
   *
   * @var string
   */
  public $description;

  /**
   * Entity of the job api call
   *
   * @var string
   */
  public $api_entity;

  /**
   * Action of the job api call
   *
   * @var string
   */
  public $api_action;

  /**
   * List of parameters to the command.
   *
   * @var string
   */
  public $parameters;

  /**
   * Is this job active?
   *
   * @var bool
   */
  public $is_active;

  /**
   * Class string
   *
   * Set as a URL, when the jobs template is rendered,
   * but not set in other contexts
   *
   * @var string|null
   */
  public $action = NULL;

  /**
   * Action
   *
   * @var string
   * @todo This seems to only ever be set to an empty string and passed through to job.tpl,
   *       where it is used a HTML `class`. Can this be removed?
   */
  public $class;

  /**
   * Result of parsing multi-line `$parameters` string into an array
   *
   * @var array
   */
  public $apiParams = [];

  /**
   * Container for error messages
   *
   * @var array
   */
  public $remarks = [];

  /**
   * @param array $params
   */
  public function __construct($params) {
    foreach ($params as $name => $param) {
      if (property_exists($this, $name)) {
        $this->$name = $param;
      }
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
    $dao->last_run_end = NULL;
    $dao->save();
  }

  /**
   * Update the last_run date of this job
   */
  public function saveLastRunEnd() {
    $dao = new CRM_Core_DAO_Job();
    $dao->id = $this->id;
    $dao->last_run_end = CRM_Utils_Date::currentDBDate();
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
