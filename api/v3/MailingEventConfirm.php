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
 * Get mailing event confirm record.
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_api3_mailing_event_confirm_get($params) {
  return _civicrm_api3_basic_get('CRM_Mailing_Event_BAO_MailingEventConfirm', $params);
}

/**
 * Handle a confirm event.
 *
 * @param array $params
 *   name/value pairs to insert in new 'survey'
 *
 * @throws Exception
 * @return array
 *   api result array
 */
function civicrm_api3_mailing_event_confirm_create($params) {

  $contact_id   = $params['contact_id'];
  $subscribe_id = $params['subscribe_id'];
  $hash         = $params['hash'];

  $confirm = CRM_Mailing_Event_BAO_MailingEventConfirm::confirm($contact_id, $subscribe_id, $hash) !== FALSE;

  if (!$confirm) {
    throw new Exception('Confirmation failed');
  }
  return civicrm_api3_create_success($params);
}

/**
 * Adjust Metadata for Create action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_mailing_event_confirm_create_spec(&$params) {
  $params['contact_id'] = [
    'api.required' => 1,
    'title' => 'Contact ID',
    'type' => CRM_Utils_Type::T_INT,
  ];
  $params['subscribe_id'] = [
    'api.required' => 1,
    'title' => 'Subscribe Event ID',
    'type' => CRM_Utils_Type::T_INT,
  ];
  $params['hash'] = [
    'api.required' => 1,
    'title' => 'Hash',
    'type' => CRM_Utils_Type::T_STRING,
  ];
}
