<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.6                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * This api is used for working with scheduled "cron" jobs.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Adjust metadata for "Create" action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
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
 * Create scheduled job.
 *
 * @param array $params
 *   Associative array of property name/value pairs to insert in new job.
 *
 * @return array
 */
function civicrm_api3_job_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Retrieve one or more job.
 *
 * @param array $params
 *   input parameters
 *
 * @return array
 */
function civicrm_api3_job_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Delete a job.
 *
 * @param array $params
 */
function civicrm_api3_job_delete($params) {
  _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Dumb wrapper to execute scheduled jobs.
 *
 * Always creates success - errors and results are handled in the job log.
 *
 * @param array $params
 *   input parameters (unused).
 *
 * @return array
 *   API Result Array
 */
function civicrm_api3_job_execute($params) {

  $facility = new CRM_Core_JobManager();
  $facility->execute(FALSE);

  // Always creates success - results are handled elsewhere.
  return civicrm_api3_create_success(1, $params, 'Job');
}

/**
 * Adjust Metadata for Execute action.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_job_execute_spec(&$params) {
}

/**
 * Geocode group of contacts based on given params.
 *
 * @param array $params
 *   input parameters.
 *
 * @return array
 *   API Result Array
 */
function civicrm_api3_job_geocode($params) {
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
 * First check on Code documentation.
 *
 * @param array $params
 */
function _civicrm_api3_job_geocode_spec(&$params) {
  $params['start'] = array(
    'title' => 'Starting Contact ID',
    'type' => CRM_Utils_Type::T_INT,
  );
  $params['end'] = array(
    'title' => 'Ending Contact ID',
    'type' => CRM_Utils_Type::T_INT,
  );
  $params['geocoding'] = array(
    'title' => 'Geocode address?',
    'type' => CRM_Utils_Type::T_BOOLEAN,
  );
  $params['parse'] = array(
    'title' => 'Parse street address?',
    'type' => CRM_Utils_Type::T_BOOLEAN,
  );
  $params['throttle'] = array(
    'title' => 'Throttle?',
    'description' => 'If enabled, geo-codes at a slow rate',
    'type' => CRM_Utils_Type::T_BOOLEAN,
  );
}

/**
 * Send the scheduled reminders for all contacts (either for activities or events).
 *
 * @param array $params
 *   (reference ) input parameters.
 *                        now - the time to use, in YmdHis format
 *                            - makes testing a bit simpler since we can simulate past/future time
 *
 * @return array
 */
function civicrm_api3_job_send_reminder($params) {
  //note that $params['rowCount' can be overridden by one of the preferred syntaxes ($options['limit'] = x
  //It's not clear whether than syntax can be passed in via the UI config - but this keeps the pre 4.4.4 behaviour
  // in that case (ie. makes it non-configurable via the UI). Another approach would be to set a default of 0
  // in the _spec function - but since that is a deprecated value it seems more contentious than this approach
  $params['rowCount'] = 0;
  $lock = new CRM_Core_Lock('civimail.job.EmailProcessor');
  if (!$lock->isAcquired()) {
    return civicrm_api3_create_error('Could not acquire lock, another EmailProcessor process is running');
  }

  $result = CRM_Core_BAO_ActionSchedule::processQueue(CRM_Utils_Array::value('now', $params), $params);
  $lock->release();

  if ($result['is_error'] == 0) {
    return civicrm_api3_create_success();
  }
  else {
    return civicrm_api3_create_error($result['messages']);
  }
}
/**
 * Adjust metadata for "send_reminder" action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_job_send_reminder(&$params) {
  //@todo this function will now take all fields in action_schedule as params
  // as it is calling the api fn to set the filters - update getfields to reflect
  $params['id'] = array(
    'type' => CRM_Utils_Type::T_INT,
    'title' => 'Action Schedule ID',
  );
}
/**
 * Execute a specific report instance and send the output via email.
 *
 * @param array $params
 *   (reference ) input parameters.
 *                        sendmail - Boolean - should email be sent?, required
 *                        instanceId - Integer - the report instance ID
 *                        resetVal - Integer - should we reset form state (always true)?
 *
 * @return array
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
 * This method allows to update Email Greetings, Postal Greetings and Addressee for a specific contact type.
 *
 * IMPORTANT: You must first create valid option value before using via admin interface.
 * Check option lists for Email Greetings, Postal Greetings and Addressee
 *
 * @todo - is this here by mistake or should it be added to _spec function :id - Integer - greetings option group.
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_api3_job_update_greeting($params) {
  if (isset($params['ct']) && isset($params['gt'])) {
    $ct = explode(',', $params['ct']);
    $gt = explode(',', $params['gt']);
    foreach ($ct as $ctKey => $ctValue) {
      foreach ($gt as $gtKey => $gtValue) {
        $params['ct'] = trim($ctValue);
        $params['gt'] = trim($gtValue);
        CRM_Contact_BAO_Contact_Utils::updateGreeting($params);
      }
    }
  }
  else {
    CRM_Contact_BAO_Contact_Utils::updateGreeting($params);
  }
  return civicrm_api3_create_success();
}

/**
 * Adjust Metadata for Get action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
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
 * Mass update pledge statuses.
 *
 * @param array $params
 *
 * @return array
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
 * Process mail queue.
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
    return civicrm_api3_create_success($values, $params, 'Job', 'process_mailing');
  }
}

/**
 * Process sms queue.
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
    return civicrm_api3_create_success($values, $params, 'Job', 'process_sms');
  }
}

/**
 * Job to get mail responses from civiMailing.
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_api3_job_fetch_bounces($params) {
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
  return civicrm_api3_create_success($values, $params, 'Job', 'fetch_bounces');
}

/**
 * Job to get mail and create activities.
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_api3_job_fetch_activities($params) {
  $lock = new CRM_Core_Lock('civimail.job.EmailProcessor');
  if (!$lock->isAcquired()) {
    return civicrm_api3_create_error('Could not acquire lock, another EmailProcessor process is running');
  }

  try {
    CRM_Utils_Mail_EmailProcessor::processActivities();
    $values = array();
    $lock->release();
    return civicrm_api3_create_success($values, $params, 'Job', 'fetch_activities');
  }
  catch (Exception $e) {
    $lock->release();
    return civicrm_api3_create_error('Process Activities failed');
  }
}

/**
 * Process participant statuses.
 *
 * @param array $params
 *  Input parameters.
 *
 * @return array
 *   array of properties, if error an array with an error id and error message
 */
function civicrm_api3_job_process_participant($params) {
  $result = CRM_Event_BAO_ParticipantStatusType::process($params);

  if (!$result['is_error']) {
    return civicrm_api3_create_success(implode("\r\r", $result['messages']));
  }
  else {
    return civicrm_api3_create_error('Error while processing participant statuses');
  }
}


/**
 * This api checks and updates the status of all membership records for a given domain.
 *
 * The function uses the calc_membership_status and update_contact_membership APIs.
 *
 * IMPORTANT:
 * Sending renewal reminders has been migrated from this job to the Scheduled Reminders function as of 4.3.
 *
 * @param array $params
 *   Input parameters NOT USED.
 *
 * @return bool
 *   true if success, else false
 */
function civicrm_api3_job_process_membership($params) {
  $lock = new CRM_Core_Lock('civimail.job.updateMembership');
  if (!$lock->isAcquired()) {
    return civicrm_api3_create_error('Could not acquire lock, another Membership Processing process is running');
  }

  $result = CRM_Member_BAO_Membership::updateAllMembershipStatus();
  $lock->release();

  if ($result['is_error'] == 0) {
    return civicrm_api3_create_success($result['messages'], $params, 'Job', 'process_membership');
  }
  else {
    return civicrm_api3_create_error($result['messages']);
  }
}

/**
 * This api checks and updates the status of all survey respondents.
 *
 * @param array $params
 *   (reference ) input parameters.
 *
 * @return bool
 *   true if success, else false
 */
function civicrm_api3_job_process_respondent($params) {
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
 * @param array $params
 *   Input parameters.
 *
 * @return array
 *   API Result Array
 */
function civicrm_api3_job_process_batch_merge($params) {
  $rgid = CRM_Utils_Array::value('rgid', $params);
  $gid = CRM_Utils_Array::value('gid', $params);

  $mode = CRM_Utils_Array::value('mode', $params, 'safe');
  $autoFlip = CRM_Utils_Array::value('auto_flip', $params, TRUE);

  $result = CRM_Dedupe_Merger::batchMerge($rgid, $gid, $mode, $autoFlip);

  if ($result['is_error'] == 0) {
    return civicrm_api3_create_success();
  }
  else {
    return civicrm_api3_create_error($result['messages']);
  }
}

/**
 * Metadata for batch merge function.
 *
 * @param $params
 */
function _civicrm_api3_job_process_batch_merge_spec(&$params) {
  $params['rgid'] = array(
    'title' => 'rule group id',
    'type' => CRM_Utils_Type::T_INT,
  );
  $params['gid'] = array(
    'title' => 'group id',
    'type' => CRM_Utils_Type::T_INT,
  );
  $params['mode'] = array(
    'title' => 'Mode',
    'description' => 'helps decide how to behave when there are conflicts. A \'safe\' value skips the merge if there are no conflicts. Does a force merge otherwise.',
    'type' => CRM_Utils_Type::T_STRING,
  );
  $params['auto_flip'] = array(
    'title' => 'Auto Flip',
    'description' => 'let the api decide which contact to retain and which to delete?',
    'type' => CRM_Utils_Type::T_BOOLEAN,
  );
}

/**
 * Runs handlePaymentCron method in the specified payment processor.
 *
 * @param array $params
 *   Input parameters.
 *
 * Expected @params array keys are: INCORRECTLY DOCUMENTED AND SHOULD BE IN THE _spec function
 * for retrieval via getfields.
 * {string  'processor_name' - the name of the payment processor, eg: Sagepay}
 */
function civicrm_api3_job_run_payment_cron($params) {

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
 * This api cleans up all the old session entries and temp tables.
 *
 * We recommend that sites run this on an hourly basis.
 *
 * @param array $params
 *   Sends in various config parameters to decide what needs to be cleaned.
 */
function civicrm_api3_job_cleanup($params) {
  $session   = CRM_Utils_Array::value('session', $params, TRUE);
  $tempTable = CRM_Utils_Array::value('tempTables', $params, TRUE);
  $jobLog    = CRM_Utils_Array::value('jobLog', $params, TRUE);
  $prevNext  = CRM_Utils_Array::value('prevNext', $params, TRUE);
  $dbCache   = CRM_Utils_Array::value('dbCache', $params, FALSE);
  $memCache  = CRM_Utils_Array::value('memCache', $params, FALSE);

  if ($session || $tempTable || $prevNext) {
    CRM_Core_BAO_Cache::cleanup($session, $tempTable, $prevNext);
  }

  if ($jobLog) {
    CRM_Core_BAO_Job::cleanup();
  }

  if ($dbCache) {
    CRM_Core_Config::clearDBCache();
  }

  if ($memCache) {
    CRM_Utils_System::flushCache();
  }
}

/**
 * Set expired relationships to disabled.
 *
 * @param array $params
 *
 * @return array
 * @throws \API_Exception
 */
function civicrm_api3_job_disable_expired_relationships($params) {
  $result = CRM_Contact_BAO_Relationship::disableExpiredRelationships();
  if (!$result) {
    throw new API_Exception('Failed to disable all expired relationships.');
  }
  return civicrm_api3_create_success(1, $params, 'Job', 'disable_expired_relationships');
}

/**
 * This api reloads all the smart groups.
 *
 * If the org has a large number of smart groups it is recommended that they use the limit clause
 * to limit the number of smart groups evaluated on a per job basis.
 *
 * Might also help to increase the smartGroupCacheTimeout and use the cache.
 *
 * @param array $params
 *
 * @return array
 * @throws \API_Exception
 */
function civicrm_api3_job_group_rebuild($params) {
  $lock = new CRM_Core_Lock('civimail.job.groupRebuild');
  if (!$lock->isAcquired()) {
    throw new API_Exception('Could not acquire lock, another EmailProcessor process is running');
  }

  $limit = CRM_Utils_Array::value('limit', $params, 0);

  CRM_Contact_BAO_GroupContactCache::loadAll(NULL, $limit);
  $lock->release();

  return civicrm_api3_create_success();
}
