<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

/**
 * This interface defines methods that need to be implemented
 * by every scheduled job (cron task) in CiviCRM.
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */
class CRM_Core_JobManager {

  /**
   * @var array ($id => CRM_Core_ScheduledJob)
   */
  var $jobs = NULL;

  /**
   * @var CRM_Core_ScheduledJob
   */
  var $currentJob = NULL;

  var $singleRunParams = array();

  var $_source = NULL;


  /*
   * Class constructor
   * 
   * @param void
   * @access public
   * 
   */
  public function __construct() {
    $config = CRM_Core_Config::singleton();
    $config->fatalErrorHandler = 'CRM_Core_JobManager_scheduledJobFatalErrorHandler';

    $this->jobs = $this->_getJobs();
  }

  /*
   * 
   * @param void
   * @access private
   * 
   */
  public function execute($auth = TRUE) {

    $this->logEntry('Starting scheduled jobs execution');

    if ($auth && !CRM_Utils_System::authenticateKey(TRUE)) {
      $this->logEntry('Could not authenticate the site key.');
    }
    require_once 'api/api.php';

    // it's not asynchronous at this stage
    CRM_Utils_Hook::cron($this);
    foreach ($this->jobs as $job) {
      if ($job->is_active) {
        if ($job->needsRunning()) {
          $this->executeJob($job);
        }
      }
    }
    $this->logEntry('Finishing scheduled jobs execution.');
  }

  /*
   * Class destructor
   * 
   * @param void
   * @access public
   * 
   */
  public function __destruct() {}

  public function executeJobByAction($entity, $action) {
    $job = $this->_getJob(NULL, $entity, $action);
    $this->executeJob($job);
  }

  public function executeJobById($id) {
    $job = $this->_getJob($id);
    $this->executeJob($job);
  }

  /**
   * @param CRM_Core_ScheduledJob $job
   */
  public function executeJob($job) {
    $this->currentJob = $job;
    $this->logEntry('Starting execution of ' . $job->name);
    $job->saveLastRun();

    $singleRunParamsKey = strtolower($job->api_entity . '_' . $job->api_action);

    if (array_key_exists($singleRunParamsKey, $this->singleRunParams)) {
      $params = $this->singleRunParams[$singleRunParamsKey];
    }
    else {
      $params = $job->apiParams;
    }

    try {
      $result = civicrm_api($job->api_entity, $job->api_action, $params);
    }
    catch(Exception$e) {
      $this->logEntry('Error while executing ' . $job->name . ': ' . $e->getMessage());
    }
    $this->logEntry('Finished execution of ' . $job->name . ' with result: ' . $this->_apiResultToMessage($result));
    $this->currentJob = FALSE;
  }

  /*
   * Retrieves the list of jobs from the database,
   * populates class param.
   * 
   * @param void
   * @return array ($id => CRM_Core_ScheduledJob)
   * @access private
   * 
   */
  private function _getJobs() {
    $jobs = array();
    $dao = new CRM_Core_DAO_Job();
    $dao->orderBy('name');
    $dao->find();
    while ($dao->fetch()) {
      $temp = array();
      CRM_Core_DAO::storeValues($dao, $temp);
      $jobs[$dao->id] = new CRM_Core_ScheduledJob($temp);
    }
    return $jobs;
  }

  /*
   * Retrieves specific job from the database by id
   * and creates ScheduledJob object.
   * 
   * @param void
   * @access private
   * 
   */
  private function _getJob($id = NULL, $entity = NULL, $action = NULL) {
    if (is_null($id) && is_null($action)) {
      CRM_Core_Error::fatal('You need to provide either id or name to use this method');
    }
    $dao             = new CRM_Core_DAO_Job();
    $dao->id         = $id;
    $dao->api_entity = $entity;
    $dao->api_action = $action;
    $dao->find();
    while ($dao->fetch()) {
      CRM_Core_DAO::storeValues($dao, $temp);
      $job = new CRM_Core_ScheduledJob($temp);
    }
    return $job;
  }

  public function setSingleRunParams($entity, $job, $params, $source = NULL) {
    $this->_source = $source;
    $key = strtolower($entity . '_' . $job);
    $this->singleRunParams[$key] = $params;
    $this->singleRunParams[$key]['version'] = 3;
  }
  
  /*
   *
   * @return array|null collection of permissions, null if none
   * @access public
   *
   */
  public function logEntry($message) {
    $domainID = CRM_Core_Config::domainID();
    $dao = new CRM_Core_DAO_JobLog();

    $dao->domain_id = $domainID;
    $dao->description = substr($message, 0, 235);
    if (strlen($message) > 235) {
      $dao->description .= " (...)";
    }
    if ($this->currentJob) {
      $dao->job_id  = $this->currentJob->id;
      $dao->name    = $this->currentJob->name;
      $dao->command = ts("Entity:") . " " + $this->currentJob->api_entity + " " . ts("Action:") . " " + $this->currentJob->api_action;
      $data         = "";
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

  private function _apiResultToMessage($apiResult) {
    $status = $apiResult['is_error'] ? ts('Failure') : ts('Success');
    $msg    = CRM_Utils_Array::value('error_message', $apiResult, 'empty error_message!');
    $vals   = CRM_Utils_Array::value('values', $apiResult, 'empty values!');
    if (is_array($msg)) {
      $msg = serialize($msg);
    }
    if (is_array($vals)) {
      $vals = serialize($vals);
    }
    $message = $apiResult['is_error'] ? ', Error message: ' . $msg : " (" . $vals . ")";
    return $status . $message;
  }
}

function CRM_Core_JobManager_scheduledJobFatalErrorHandler($message) {
  throw new Exception("{$message['message']}: {$message['code']}");
}

