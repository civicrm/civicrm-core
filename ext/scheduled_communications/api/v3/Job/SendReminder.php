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
 * Send the scheduled reminders as configured.
 *
 * @param array $params
 *  - now - the time to use, in YmdHis format
 *  - makes testing a bit simpler since we can simulate past/future time
 *
 * @return array
 * @throws \CRM_Core_Exception
 */
function civicrm_api3_job_send_reminder($params) {
  //note that $params['rowCount' can be overridden by one of the preferred syntaxes ($options['limit'] = x
  //It's not clear whether than syntax can be passed in via the UI config - but this keeps the pre 4.4.4 behaviour
  // in that case (ie. makes it non-configurable via the UI). Another approach would be to set a default of 0
  // in the _spec function - but since that is a deprecated value it seems more contentious than this approach
  $params['rowCount'] = 0;
  $lock = Civi::lockManager()->acquire('worker.core.ActionSchedule');
  if (!$lock->isAcquired()) {
    throw new CRM_Core_Exception('Could not acquire lock, another ActionSchedule process is running');
  }

  CRM_Core_BAO_ActionSchedule::processQueue($params['now'] ?? NULL, $params);
  $lock->release();
  return civicrm_api3_create_success(1, $params, 'ActionSchedule', 'send_reminder');
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
  $params['id'] = [
    'type' => CRM_Utils_Type::T_INT,
    'title' => 'Action Schedule ID',
  ];
}
