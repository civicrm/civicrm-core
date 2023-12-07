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
 * Handle a queue event.
 *
 * @param array $params
 *   Array of property.
 *
 * @throws Exception
 * @return array
 *   api result array
 */
function civicrm_api3_mailing_event_queue_create($params) {
  if (!array_key_exists('id', $params) && !array_key_exists('email_id', $params) && !array_key_exists('phone_id', $params)) {
    throw new CRM_Core_Exception("Mandatory key missing from params array: id, email_id, or phone_id field is required");
  }
  civicrm_api3_verify_mandatory($params,
    'CRM_Mailing_DAO_MailingJob',
    ['job_id', 'contact_id'],
    FALSE
  );
  return _civicrm_api3_basic_create('CRM_Mailing_Event_BAO_MailingEventQueue', $params, 'MailingEventQueue');
}

/**
 * Get mailing event queue record.
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_api3_mailing_event_queue_get($params) {
  return _civicrm_api3_basic_get('CRM_Mailing_Event_BAO_MailingEventQueue', $params);
}

/**
 * Delete mailing event queue record.
 *
 * @param array $params
 *
 * @return array
 * @throws \CRM_Core_Exception
 */
function civicrm_api3_mailing_event_queue_delete($params) {
  return _civicrm_api3_basic_delete('CRM_Mailing_Event_BAO_MailingEventQueue', $params);
}

/**
 * Adjust Metadata for Create action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_mailing_event_queue_create_spec(&$params) {
  $params['job_id']['api.required'] = 1;
  $params['contact_id']['api.required'] = 1;
}
