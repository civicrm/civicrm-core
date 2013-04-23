<?php
// $Id$

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
 * new version of civicrm apis. See blog post at
 * http://civicrm.org/node/131
 * @todo Write sth
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Job
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id: Contact.php 30879 2010-11-22 15:45:55Z shot $
 *
 */

/**
 * Include common API util functions
 */
require_once 'api/v3/utils.php';

/**
 * Adjust metadata for "Create" action
 *
 * The metadata is used for setting defaults, documentation & validation
 * @param array $params array or parameters determined by getfields
 */
function _civicrm_api3_job_create_spec(&$params) {
  $params['run_frequency']['api.required'] = 1;
  $params['name']['api.required'] = 1;
  $params['api_entity']['api.required'] = 1;
  $params['api_action']['api.required'] = 1;

  $params['domain_id']['api.default'] = CRM_Core_Config::domainID();
  $params['is_active']['api.default'] = 1;
}

/**
 * Function to create scheduled job
 *
 * @param  array $params   Associative array of property name/value pairs to insert in new job.
 *
 * @return success or error
 * {@getfields Job_create}
 * @access public
 * {@schema Core/Job.xml}
 */
function civicrm_api3_job_create($params) {
  require_once 'CRM/Utils/Rule.php';

  if (isset($params['id']) && !CRM_Utils_Rule::integer($params['id'])) {
    return civicrm_api3_create_error('Invalid value for job ID');
  }

  $dao = CRM_Core_BAO_Job::create($params);

  $result = array();
  _civicrm_api3_object_to_array($dao, $result[$dao->id]);
  return civicrm_api3_create_success($result, $params, 'job', 'create', $dao);
}

/**
 * Retrieve one or more job
 * @param  array input parameters
 * @return  array api result array
 * {@getfields email_get}
 * @access public
 */
function civicrm_api3_job_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Delete a job
 *
 * @param int $id
 *
 * @return array API Result Array
 * {@getfields Job_delete}
 * @static void
 * @access public
 */
function civicrm_api3_job_delete($params) {
  require_once 'CRM/Utils/Rule.php';
  if ($params['id'] != NULL && !CRM_Utils_Rule::integer($params['id'])) {
    return civicrm_api3_create_error('Invalid value for job ID');
  }

  $result = CRM_Core_BAO_Job::del($params['id']);
  if (!$result) {
    return civicrm_api3_create_error('Could not delete job');
  }
  return civicrm_api3_create_success($result, $params, 'job', 'delete');
}

/**
 * Dumb wrapper to execute scheduled jobs. Always creates success - errors
 * and results are handled in the job log.
 *
 * @param  array       $params (reference ) input parameters
 *
 * @return array API Result Array
 *
 * @static void
 * @access public
 *
 */
function civicrm_api3_job_execute($params) {
  require_once 'CRM/Core/JobManager.php';
  $facility = new CRM_Core_JobManager();
  $facility->execute(FALSE);

  // always creates success - results are handled elsewhere
  return civicrm_api3_create_success();
}

/**
 * Adjust Metadata for Execute action
 *
 * @param array $params array or parameters determined by getfields
 */
function _civicrm_api3_job_execute_spec(&$params) {
}

/**
 * Geocode group of contacts based on given params
 *
 * @param  array       $params (reference ) input parameters
 *
 * @return array API Result Array
 * {@getfields contact_geocode}
 *
 * @static void
 * @access public
 *
 *
 */
function civicrm_api3_job_geocode($params) {

  require_once 'CRM/Utils/Address/BatchUpdate.php';
  $gc = new CRM_Utils_Address_BatchUpdate($params);


  $result = $gc->run();

  if ($result['is_error'] == 0) {
    return civicrm_api3_create_success($result['messages']);
  }
  else {
    return civicrm_api3_create_error($result['messages']);
  }
}
/**
 * First check on Code documentation
 */
function _civicrm_api3_job_geocode_spec(&$params) {
  $params['start'] = array('title' => 'Start Date');
  $params['end'] = array('title' => 'End Date');
  $params['geocoding'] = array('title' => 'Is this for GeoCoding? (I think this is a 1,0 field?)');
  $params['parse'] = array('title' => 'Is this for parsing? (I think this is a 1,0 field?)');
  $params['throttle'] = array('title' => 'Throttle? (no idea what you enter in this field)');
}

/**
 * Send the scheduled reminders for all contacts (either for activities or events)
 *
 * @param  array       $params (reference ) input parameters
 *                        now - the time to use, in YmdHis format
 *                            - makes testing a bit simpler since we can simulate past/future time
 *
 * @return boolean        true if success, else false
 * @static void
 * @access public
 *
 */
function civicrm_api3_job_send_reminder($params) {
  require_once 'CRM/Core/Lock.php';
  $lock = new CRM_Core_Lock('civimail.job.EmailProcessor');
  if (!$lock->isAcquired()) {
    return civicrm_api3_create_error('Could not acquire lock, another EmailProcessor process is running');
  }

  require_once 'CRM/Core/BAO/ActionSchedule.php';
  $result = CRM_Core_BAO_ActionSchedule::processQueue(CRM_Utils_Array::value('now', $params));
  $lock->release();

  if ($result['is_error'] == 0) {
    return civicrm_api3_create_success();
  }
  else {
    return civicrm_api3_create_error($result['messages']);
  }
}

/**
 * Execute a specific report instance and send the output via email
 *
 * @param  array       $params (reference ) input parameters
 *                        sendmail - Boolean - should email be sent?, required
 *                        instanceId - Integer - the report instance ID
 *                        resetVal - Integer - should we reset form state (always true)?
 *
 * @return boolean        true if success, else false
 * @static void
 * @access public
 *
 */
function civicrm_api3_job_mail_report($params) {
  $result = CRM_Report_Utils_Report::processReport($params);

  if ($result['is_error'] == 0) {
    // this should be handling by throwing exceptions but can't remove until we can test that.
    return civicrm_api3_create_success();
  }
  else {
    return civicrm_api3_create_error($result['messages']);
  }
}

/**
 *
 * This method allows to update Email Greetings, Postal Greetings and Addressee for a specific contact type.
 * IMPORTANT: You must first create valid option value before using via admin interface.
 * Check option lists for Email Greetings, Postal Greetings and Addressee
 *
 *                        id - Integer - greetings option group
 *
 * @return boolean        true if success, else false
 * @static
 * @access public
 *
 */
function civicrm_api3_job_update_greeting($params) {

  if (isset($params['ct']) && isset($params['gt'])) {
    $ct = $gt = array();
    $ct = explode(',', $params['ct']);
    $gt = explode(',', $params['gt']);
    foreach ($ct as $ctKey => $ctValue) {
      foreach ($gt as $gtKey => $gtValue) {
        $params['ct'] = trim($ctValue);
        $params['gt'] = trim($gtValue);
        $result[] = CRM_Contact_BAO_Contact_Utils::updateGreeting($params);
      }
    }
  }
  else {
    $result = CRM_Contact_BAO_Contact_Utils::updateGreeting($params);
  }

  foreach ($result as $resultKey => $resultValue) {
    if ($resultValue['is_error'] == 0) {
      //really we should rely on the exception mechanism here - but we need to test that before removing this line
      return civicrm_api3_create_success();
    }
    else {
      return civicrm_api3_create_error($resultValue['messages']);
    }
  }
}

/**
 * Adjust Metadata for Get action
*
* The metadata is used for setting defaults, documentation & validation
* @param array $params array or parameters determined by getfields
*/
function _civicrm_api3_job_update_greeting_spec(&$params) {
  $params['ct'] = array(
    'api.required' => 1,
    'title' => 'Contact Type',
    'type' => CRM_Utils_Type::T_STRING,
  );
  $params['gt'] = array(
      'api.required' => 1,
      'title' => 'Greeting Type',
      'type' => CRM_Utils_Type::T_STRING,
  );
}

/**
 * Mass update pledge statuses
 *
 * @param  array       $params (reference ) input parameters
 *
 * @return boolean        true if success, else false
 * @static
 * @access public
 *
 */
function civicrm_api3_job_process_pledge($params) {
  // *** Uncomment the next line if you want automated reminders to be sent
  // $params['send_reminders'] = true;
  $result = CRM_Pledge_BAO_Pledge::updatePledgeStatus($params);

  if ($result['is_error'] == 0) {
    // experiment: detailed execution log is a result here
    return civicrm_api3_create_success($result['messages']);
  }
  else {
    return civicrm_api3_create_error($result['error_message']);
  }
}

/**
 * Process mail queue
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_api3_job_process_mailing($params) {

  if (!CRM_Mailing_BAO_Mailing::processQueue()) {
    return civicrm_api3_create_error('Process Queue failed');
  }
  else {
    $values = array();
    return civicrm_api3_create_success($values, $params, 'mailing', 'process');
  }
}

/**
 * Process sms queue
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_api3_job_process_sms($params) {
  if (!CRM_Mailing_BAO_Mailing::processQueue('sms')) {
    return civicrm_api3_create_error('Process Queue failed');
  }
  else {
    $values = array();
    return civicrm_api3_create_success($values, $params, 'mailing', 'process');
  }
}

/**
 * Job to get mail responses from civimailing
 */
function civicrm_api3_job_fetch_bounces($params) {
  require_once 'CRM/Utils/Mail/EmailProcessor.php';
  require_once 'CRM/Core/Lock.php';
  $lock = new CRM_Core_Lock('civimail.job.EmailProcessor');
  if (!$lock->isAcquired()) {
    return civicrm_api3_create_error('Could not acquire lock, another EmailProcessor process is running');
  }
  if (!CRM_Utils_Mail_EmailProcessor::processBounces()) {
    $lock->release();
    return civicrm_api3_create_error('Process Bounces failed');
  }
  $lock->release();

  // FIXME: processBounces doesn't return true/false on success/failure
  $values = array();
  return civicrm_api3_create_success($values, $params, 'mailing', 'bounces');
}

/**
 * Job to get mail and create activities
 */
function civicrm_api3_job_fetch_activities($params) {
  require_once 'CRM/Utils/Mail/EmailProcessor.php';
  require_once 'CRM/Core/Lock.php';
  $lock = new CRM_Core_Lock('civimail.job.EmailProcessor');
  if (!$lock->isAcquired()) {
    return civicrm_api3_create_error('Could not acquire lock, another EmailProcessor process is running');
  }

  try {
    CRM_Utils_Mail_EmailProcessor::processActivities();
    $values = array( );
    $lock->release();
    return civicrm_api3_create_success($values, $params,'mailing','activities');
  } catch (Exception $e) {
    $lock->release();
    return civicrm_api3_create_error('Process Activities failed');
  }
}

/**
 * Process participant statuses
 *
 * @param  array   $params           (reference ) input parameters
 *
 * @return array (reference )        array of properties, if error an array with an error id and error message
 * @access public
 */
function civicrm_api3_job_process_participant($params) {
  require_once 'CRM/Event/BAO/ParticipantStatusType.php';
  $result = CRM_Event_BAO_ParticipantStatusType::process($params);

  if (!$result['is_error']) {
    return civicrm_api3_create_success(implode("\r\r", $result['messages']));
  }
  else {
    return civicrm_api3_create_error('Error while processing participant statuses');
  }
}


/**
 * This api checks and updates the status of all membership records for a given domain using the calc_membership_status and
 * update_contact_membership APIs.
 *
 * IMPORTANT:
 * Sending renewal reminders has been migrated from this job to the Scheduled Reminders function as of 4.3.
 *
 * @param  array $params input parameters NOT USED
 *
 * @return boolean true if success, else false
 * @static void
 * @access public
 */
function civicrm_api3_job_process_membership($params) {
  require_once 'CRM/Core/Lock.php';
  $lock = new CRM_Core_Lock('civimail.job.updateMembership');
  if (!$lock->isAcquired()) {
    return civicrm_api3_create_error('Could not acquire lock, another EmailProcessor process is running');
  }

  require_once 'CRM/Member/BAO/Membership.php';
  $result = CRM_Member_BAO_Membership::updateAllMembershipStatus();
  $lock->release();

  if ($result['is_error'] == 0) {
    return civicrm_api3_create_success($result['messages']);
  }
  else {
    return civicrm_api3_create_error($result['messages']);
  }
}

/**
 * This api checks and updates the status of all survey respondants.
 *
 * @param  array       $params (reference ) input parameters
 *
 * @return boolean        true if success, else false
 * @static void
 * @access public
 */
function civicrm_api3_job_process_respondent($params) {
  require_once 'CRM/Campaign/BAO/Survey.php';
  $result = CRM_Campaign_BAO_Survey::releaseRespondent($params);

  if ($result['is_error'] == 0) {
    return civicrm_api3_create_success();
  }
  else {
    return civicrm_api3_create_error($result['messages']);
  }
}

/**
 * Merges given pair of duplicate contacts.
 *
 * @param  array   $params   input parameters
 *
 * Allowed @params array keys are:
 * {int     $rgid        rule group id}
 * {int     $gid         group id}
 * {string  mode        helps decide how to behave when there are conflicts.
 *                      A 'safe' value skips the merge if there are no conflicts. Does a force merge otherwise.}
 * {boolean auto_flip   wether to let api decide which contact to retain and which to delete.}
 *
 * @return array  API Result Array
 *
 * @static void
 * @access public
 */
function civicrm_api3_job_process_batch_merge($params) {
  $rgid = CRM_Utils_Array::value('rgid', $params);
  $gid = CRM_Utils_Array::value('gid', $params);

  $mode = CRM_Utils_Array::value('mode', $params, 'safe');
  $autoFlip = CRM_Utils_Array::value('auto_flip', $params, TRUE);

  require_once 'CRM/Dedupe/Merger.php';
  $result = CRM_Dedupe_Merger::batchMerge($rgid, $gid, $mode, $autoFlip);

  if ($result['is_error'] == 0) {
    return civicrm_api3_create_success();
  }
  else {
    return civicrm_api3_create_error($result['messages']);
  }
}

/**
 * Runs handlePaymentCron method in the specified payment processor
 *
 * @param  array   $params   input parameters
 *
 * Expected @params array keys are:
 * {string  'processor_name' - the name of the payment processor, eg: Sagepay}
 *
 * @access public
 */
function civicrm_api3_job_run_payment_cron($params) {

  require_once 'CRM/Core/Payment.php';

  // live mode
  CRM_Core_Payment::handlePaymentMethod(
    'PaymentCron',
    array_merge(
      $params,
      array(
        'caller' => 'api',
      )
    )
  );

  // test mode
  CRM_Core_Payment::handlePaymentMethod(
    'PaymentCron',
    array_merge(
      $params,
      array(
        'mode' => 'test',
      )
    )
  );
}

/**
 * This api cleans up all the old session entries and temp tables. We recommend that sites run this on an hourly basis
 *
 * @param  array    $params (reference ) - sends in various config parameters to decide what needs to be cleaned
 *
 * @return boolean  true if success, else false
 * @static void
 * @access public
 */
function civicrm_api3_job_cleanup( $params ) {
  require_once 'CRM/Utils/Array.php';

  $session   = CRM_Utils_Array::value( 'session'   , $params, true  );
  $tempTable = CRM_Utils_Array::value( 'tempTables', $params, true  );
  $jobLog    = CRM_Utils_Array::value( 'jobLog'    , $params, true  );
  $prevNext  = CRM_Utils_Array::value( 'prevNext'  , $params, true  );
  $dbCache   = CRM_Utils_Array::value( 'dbCache'   , $params, false );
  $memCache  = CRM_Utils_Array::value( 'memCache'  , $params, false );

  if ( $session || $tempTable || $prevNext ) {
    require_once 'CRM/Core/BAO/Cache.php';
    CRM_Core_BAO_Cache::cleanup( $session, $tempTable, $prevNext );
  }

  if ( $jobLog ) {
    CRM_Core_BAO_Job::cleanup( );
  }

  if ( $dbCache ) {
    CRM_Core_Config::clearDBCache( );
  }

  if ( $memCache ) {
    CRM_Utils_System::flushCache( );
  }
}

/**
 * Set expired relationships to disabled.
 *
 */
function civicrm_api3_job_disable_expired_relationships($params) {
  $result = CRM_Contact_BAO_Relationship::disableExpiredRelationships();
  if ($result) {
    return civicrm_api3_create_success();
  }
  else {
    return civicrm_api3_create_error('Failed to disable all expired relationships.');
  }
}

/**
 * This api reloads all the smart groups. If the org has a large number of smart groups
 * it is recommended that they use the limit clause to limit the number of smart groups
 * evaluated on a per job basis. Might also help to increase the smartGroupCacheTimeout
 * and use the cache
 */
function civicrm_api3_job_group_rebuild( $params ) {
  require_once 'CRM/Core/Lock.php';
  $lock = new CRM_Core_Lock('civimail.job.groupRebuild');
  if (!$lock->isAcquired()) {
    return civicrm_api3_create_error('Could not acquire lock, another EmailProcessor process is running');
  }

  $limit = CRM_Utils_Array::value( 'limit', $params, 0 );

  CRM_Contact_BAO_GroupContactCache::loadAll(null, $limit);
  $lock->release();

  return civicrm_api3_create_success();
}
