<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
    $groups = CRM_Mailing_Event_BAO_Unsubscribe::unsub_from_mailing($job, $queue, $hash);
    if (count($groups)) {
      CRM_Mailing_Event_BAO_Unsubscribe::send_unsub_response($queue, $groups, FALSE, $job);
      return civicrm_api3_create_success($params);
    }
  }
  else {
    $unsubs = CRM_Mailing_Event_BAO_Unsubscribe::unsub_from_domain($job, $queue, $hash);
    if (!$unsubs) {
      return civicrm_api3_create_error('Domain Queue event could not be found');
    }

    CRM_Mailing_Event_BAO_Unsubscribe::send_unsub_response($queue, NULL, TRUE, $job);
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
  $params['job_id'] = array(
    'api.required' => 1,
    'title' => 'Mailing Job ID',
    'type' => CRM_Utils_Type::T_INT,
  );
  $params['hash'] = array(
    'api.required' => 1,
    'title' => 'Mailing Hash',
    'type' => CRM_Utils_Type::T_STRING,
  );
  $params['event_queue_id'] = array(
    'api.required' => 1,
    'title' => 'Mailing Queue ID',
    'type' => CRM_Utils_Type::T_INT,
  );
}
