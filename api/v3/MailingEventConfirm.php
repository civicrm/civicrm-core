<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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

  $confirm = CRM_Mailing_Event_BAO_Confirm::confirm($contact_id, $subscribe_id, $hash) !== FALSE;

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
