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
 * Subscribe from mailing group.
 *
 * @param array $params
 *
 * @return array
 *   api result array
 */
function civicrm_api3_mailing_event_resubscribe_create($params) {

  $groups = CRM_Mailing_Event_BAO_MailingEventResubscribe::resub_to_mailing(
    $params['job_id'],
    $params['event_queue_id'],
    $params['hash']
  );

  if (!empty($groups)) {
    CRM_Mailing_Event_BAO_MailingEventResubscribe::send_resub_response(
      $params['event_queue_id'],
      $groups,
      $params['job_id']
    );
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
function _civicrm_api3_mailing_event_resubscribe_create_spec(&$params) {
  $params['event_queue_id'] = [
    'api.required' => 1,
    'title' => 'Event Queue ID',
    'type' => CRM_Utils_Type::T_INT,
  ];
  $params['job_id'] = [
    'api.required' => 1,
    'title' => 'Job ID',
    'type' => CRM_Utils_Type::T_INT,
  ];
  $params['hash'] = [
    'api.required' => 1,
    'title' => 'Hash',
    'type' => CRM_Utils_Type::T_STRING,
  ];
}
