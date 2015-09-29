<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
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
    throw new API_Exception("Mandatory key missing from params array: id, email_id, or phone_id field is required");
  }
  civicrm_api3_verify_mandatory($params,
    'CRM_Mailing_DAO_MailingJob',
    array('job_id', 'contact_id'),
    FALSE
  );
  return _civicrm_api3_basic_create('CRM_Mailing_Event_BAO_Queue', $params);
}

/**
 * Get mailing event queue record.
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_api3_mailing_event_queue_get($params) {
  return _civicrm_api3_basic_get('CRM_Mailing_Event_BAO_Queue', $params);
}

/**
 * Delete mailing event queue record.
 *
 * @param array $params
 *
 * @return array
 * @throws \API_Exception
 */
function civicrm_api3_mailing_event_queue_delete($params) {
  return _civicrm_api3_basic_delete('CRM_Mailing_Event_BAO_Queue', $params);
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
