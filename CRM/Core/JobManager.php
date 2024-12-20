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

use Civi\Api4\Job;

/**
 * This interface defines methods that need to be implemented
 * by every scheduled job (cron task) in CiviCRM.
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Core_JobManager {

  /**
   * Jobs.
   *
   * Format is ($id => CRM_Core_ScheduledJob).
   *
   * @var CRM_Core_ScheduledJob[]
   * @deprecated
   */
  public $jobs = NULL;

  /**
   * @var CRM_Core_ScheduledJob
   */
  public $currentJob = NULL;

  /**
   * @var array
   *
   * @fixme How are these set? What do they do?
   */
  public $singleRunParams = [];

  /**
   * @var string|null
   *
   * @fixme Looks like this is only used by "singleRun"
   */
  public $_source = NULL;

  /**
   * @param bool $auth
   */
  public function execute($auth = TRUE) {

    $this->logEntry('Starting scheduled jobs execution');

    if ($auth && !CRM_Utils_System::authenticateKey(TRUE)) {
      $this->logEntry('Could not authenticate the site key.');
    }
    require_once 'api/api.php';

    // it's not asynchronous at this stage
    CRM_Utils_Hook::cron($this);

    // Get a list of the jobs that have completed previously
    $successfulJobs = Job::get(FALSE)
      ->addWhere('is_active', '=', TRUE)
      ->addClause('OR', ['last_run', 'IS NULL'], ['last_run', '<=', 'last_run_end', TRUE])
      ->addOrderBy('name', 'ASC')
      ->execute()
      ->indexBy('id')
      ->getArrayCopy();

    // Get a list of jobs that have not completed previously.
    // This could be because they are a new job that has not yet run or a job that is fatally crashing (eg. OOM).
    // If last_run is NULL the job has never run and will be selected above so exclude it here
    // If last_run_end is NULL the job has never completed successfully.
    // If last_run_end is < last_run job has completed successfully in the past but is now failing to complete.
    $maybeUnsuccessfulJobs = Job::get(FALSE)
      ->addWhere('is_active', '=', TRUE)
      ->addWhere('last_run', 'IS NOT NULL')
      ->addClause('OR', ['last_run_end', 'IS NULL'], ['last_run', '>', 'last_run_end', TRUE])
      ->addOrderBy('name', 'ASC')
      ->execute()
      ->indexBy('id')
      ->getArrayCopy();

    $jobs = array_merge($successfulJobs, $maybeUnsuccessfulJobs);
    foreach ($jobs as $job) {
      $temp = ['class' => NULL, 'parameters' => NULL, 'last_run' => NULL];
      $scheduledJobParams = array_merge($temp, $job);
      $jobDAO = new CRM_Core_ScheduledJob($scheduledJobParams);

      if ($jobDAO->needsRunning()) {
        $this->executeJob($jobDAO);
      }
    }

    $this->logEntry('Finishing scheduled jobs execution.');

    // Set last cron date for the status check
    $statusPref = [
      'name' => 'checkLastCron',
      'check_info' => gmdate('U'),
      'prefs' => '',
    ];
    CRM_Core_BAO_StatusPreference::create($statusPref);
  }

  /**
   * @param $entity
   * @param $action
   */
  public function executeJobByAction($entity, $action) {
    $job = $this->getJob(NULL, $entity, $action);
    $this->executeJob($job);
  }

  /**
   * @param int $id
   */
  public function executeJobById($id) {
    $job = $this->getJob($id);
    $this->executeJob($job);
  }

  /**
   * @param CRM_Core_ScheduledJob $job
   */
  public function executeJob($job) {
    $this->currentJob = $job;

    // CRM-18231 check if non-production environment.
    try {
      CRM_Core_BAO_Setting::isAPIJobAllowedToRun($job->apiParams);
    }
    catch (Exception $e) {
      $this->logEntry('Error while executing ' . $job->name . ': ' . $e->getMessage());
      $this->currentJob = FALSE;
      return FALSE;
    }

    $this->logEntry('Starting execution of ' . $job->name);
    $job->saveLastRun();

    $singleRunParamsKey = strtolower($job->api_entity . '_' . $job->api_action);

    if (array_key_exists($singleRunParamsKey, $this->singleRunParams)) {
      $params = $this->singleRunParams[$singleRunParamsKey];
    }
    else {
      $params = $job->apiParams;
    }

    CRM_Utils_Hook::preJob($job, $params);
    try {
      $result = civicrm_api($job->api_entity, $job->api_action, $params);
    }
    catch (Exception $e) {
      $this->logEntry('Error while executing ' . $job->name . ': ' . $e->getMessage());
      $result = $e;
    }
    CRM_Utils_Hook::postJob($job, $params, $result);
    $this->logEntry('Finished execution of ' . $job->name . ' with result: ' . $this->apiResultToMessage($result));
    $this->currentJob = FALSE;

    // Save the job last run end date (if this doesn't get written we know the job crashed and was not caught (eg. OOM).
    $job->saveLastRunEnd();
  }

  /**
   * Retrieves specific job from the database by id.
   * and creates ScheduledJob object.
   *
   * @param int $id
   * @param null $entity
   * @param null $action
   *
   * @return CRM_Core_ScheduledJob
   * @throws Exception
   */
  private function getJob($id = NULL, $entity = NULL, $action = NULL) {
    if (is_null($id) && is_null($action)) {
      throw new CRM_Core_Exception('You need to provide either id or name to use this method');
    }
    $dao = new CRM_Core_DAO_Job();
    $dao->id = $id;
    $dao->api_entity = $entity;
    $dao->api_action = $action;
    $dao->find();
    while ($dao->fetch()) {
      CRM_Core_DAO::storeValues($dao, $temp);
      $job = new CRM_Core_ScheduledJob($temp);
    }
    return $job;
  }

  /**
   * @param $entity
   * @param $job
   * @param array $params
   * @param string|null $source
   */
  public function setSingleRunParams($entity, $job, $params, $source = NULL) {
    $this->_source = $source;
    $key = strtolower($entity . '_' . $job);
    $this->singleRunParams[$key] = $params;
    $this->singleRunParams[$key]['version'] = 3;
  }

  /**
   * @param string $message
   */
  public function logEntry($message) {
    $domainID = CRM_Core_Config::domainID();
    $dao = new CRM_Core_DAO_JobLog();

    $dao->domain_id = $domainID;

    /*
     * The description is a summary of the message.
     * HTML tags are stripped from the message.
     * The description is limited to 240 characters
     * and has an ellipsis added if it is truncated.
     */
    $maxDescription = 240;
    $ellipsis = " (...)";
    $description = strip_tags($message);
    if (strlen($description) > $maxDescription) {
      $description = substr($description, 0, $maxDescription - strlen($ellipsis)) . $ellipsis;
    }
    $dao->description = $description;

    if ($this->currentJob) {
      $dao->job_id = $this->currentJob->id;
      $dao->name = $this->currentJob->name;
      $dao->command = ts("Entity:") . " " . $this->currentJob->api_entity . " " . ts("Action:") . " " . $this->currentJob->api_action;
      $data = "";
      if (!empty($this->currentJob->parameters)) {
        $data .= "\n\nParameters raw (from db settings): \n" . $this->currentJob->parameters;
      }
      $singleRunParamsKey = strtolower($this->currentJob->api_entity . '_' . $this->currentJob->api_action);
      if (array_key_exists($singleRunParamsKey, $this->singleRunParams)) {
        $data .= "\n\nParameters raw (" . $this->_source . "): \n" . serialize($this->singleRunParams[$singleRunParamsKey]);
        $data .= "\n\nParameters parsed (and passed to API method): \n" . serialize($this->singleRunParams[$singleRunParamsKey]);
      }
      else {
        $data .= "\n\nParameters parsed (and passed to API method): \n" . serialize($this->currentJob->apiParams);
      }

      $data .= "\n\nFull message: \n" . $message;

      $dao->data = $data;
    }
    $dao->save();
  }

  /**
   * @param $apiResult
   *
   * @return string
   */
  private function apiResultToMessage($apiResult) {
    $status = ($apiResult['is_error'] ?? FALSE) ? ts('Failure') : ts('Success');
    $msg = CRM_Utils_Array::value('error_message', $apiResult, 'empty error_message!');
    $vals = CRM_Utils_Array::value('values', $apiResult, 'empty values!');
    if (is_array($msg)) {
      $msg = serialize($msg);
    }
    if (is_array($vals)) {
      $vals = serialize($vals);
    }
    $message = ($apiResult['is_error'] ?? FALSE) ? ', Error message: ' . $msg : " (" . $vals . ")";
    return $status . $message;
  }

}
