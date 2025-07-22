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
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

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
   * @param \Psr\Log\LoggerInterface|null $logger
   */
  public function __construct($logger = NULL) {
    $this->logger = $logger ?: new CRM_Core_JobLogger();
  }

  /**
   * @param bool $auth
   */
  public function execute($auth = TRUE) {

    $this->logger->info('Starting scheduled jobs execution', $this->createLogContext());

    if ($auth && !CRM_Utils_System::authenticateKey(TRUE)) {
      $this->logger->error('Could not authenticate the site key.', $this->createLogContext());
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

    $this->logger->info('Finishing scheduled jobs execution.', $this->createLogContext());

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
      $this->logger->error('Error while executing ' . $job->name . ': ' . $e->getMessage(), $this->createLogContext());
      $this->currentJob = FALSE;
      return FALSE;
    }

    $this->logger->info('Starting execution of ' . $job->name, $this->createLogContext());
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
    catch (\Throwable $e) {
      $this->logger->error('Error while executing ' . $job->name . ': ' . $e->getMessage(), $this->createLogContext());
      $result = $e;
    }
    CRM_Utils_Hook::postJob($job, $params, $result);
    $logLevel = ($result instanceof \Throwable || !empty($result['is_error'])) ? \Psr\Log\LogLevel::ERROR : \Psr\Log\LogLevel::INFO;
    $this->logger->log($logLevel, 'Finished execution of ' . $job->name . ' with result: ' . $this->apiResultToMessage($result), $this->createLogContext());
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
   * Add a log entry.
   *
   * NOTE: This signature has been around forever, and it's used a little bit in contrib.
   * However, you will likely find it more meaningful to call the $logger, as in:
   *
   *   $this->logger->warning("Careful!", $this->createLogContext());
   *   $this->logger->error("Uh oh!", $this->createLogContext());
   *
   * @param string $message
   * @deprecated
   */
  public function logEntry($message) {
    $this->logger->log(Psr\Log\LogLevel::INFO, $message, $this->createLogContext());
  }

  private function createLogContext($init = []): array {
    $context = $init;
    if ($this->currentJob) {
      $context['job'] = $this->currentJob;
      $singleRunParamsKey = strtolower($this->currentJob->api_entity . '_' . $this->currentJob->api_action);
      if (array_key_exists($singleRunParamsKey, $this->singleRunParams)) {
        $context['singleRun']['parameters'] = $this->singleRunParams[$singleRunParamsKey];
        $context['effective']['parameters'] = $this->singleRunParams[$singleRunParamsKey];
      }
      else {
        $context['effective']['parameters'] = $this->currentJob->apiParams;
      }
    }
    if ($this->_source) {
      $context['source'] = $this->_source;
    }
    return $context;
  }

  /**
   * @param $apiResult
   *
   * @return string
   */
  private function apiResultToMessage($apiResult) {
    $status = ($apiResult['is_error'] ?? FALSE) ? ts('Failure') : ts('Success');
    $msg = $apiResult['error_message'] ?? 'empty error_message!';
    $vals = $apiResult['values'] ?? 'empty values!';
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
