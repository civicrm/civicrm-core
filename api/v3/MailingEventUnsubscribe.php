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
 *
 * APIv3 functions for registering/processing mailing group events.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Get mailing event unsubscribe record.
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_api3_mailing_event_unsubscribe_get($params) {
  return _civicrm_api3_basic_get('CRM_Mailing_Event_BAO_MailingEventUnsubscribe', $params);
}

/**
 * Unsubscribe from mailing group.
 *
 * @param array $params
 *   Array per getfields metadata.
 *
 * @return array
 *   Api result array
 */
function civicrm_api3_mailing_event_unsubscribe_create($params) {

  $job = $params['job_id'];
  $queue = $params['event_queue_id'];
  $hash = $params['hash'];
  if (empty($params['org_unsubscribe'])) {
    $groups = CRM_Mailing_Event_BAO_MailingEventUnsubscribe::unsub_from_mailing($job, $queue, $hash);
    if (!empty($groups)) {
      CRM_Mailing_Event_BAO_MailingEventUnsubscribe::send_unsub_response($queue, $groups, FALSE, $job);
      return civicrm_api3_create_success($params);
    }
  }
  else {
    $unsubs = CRM_Mailing_Event_BAO_MailingEventUnsubscribe::unsub_from_domain($job, $queue, $hash);
    if (!$unsubs) {
      return civicrm_api3_create_error('Domain Queue event could not be found');
    }

    CRM_Mailing_Event_BAO_MailingEventUnsubscribe::send_unsub_response($queue, NULL, TRUE, $job);
    return civicrm_api3_create_success($params);
  }

  return civicrm_api3_create_error('Queue event could not be found');
}

/**
 * Adjust Metadata for Create action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_mailing_event_unsubscribe_create_spec(&$params) {
  $params['job_id'] = [
    'api.required' => 1,
    'title' => 'Mailing Job ID',
    'type' => CRM_Utils_Type::T_INT,
  ];
  $params['hash'] = [
    'api.required' => 1,
    'title' => 'Mailing Hash',
    'type' => CRM_Utils_Type::T_STRING,
  ];
  $params['event_queue_id'] = [
    'api.required' => 1,
    'title' => 'Mailing Queue ID',
    'type' => CRM_Utils_Type::T_INT,
  ];
}
