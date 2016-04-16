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
 * Subscribe from mailing group.
 *
 * @param array $params
 *   Array per getfields metadata.
 *
 * @throws API_Exception
 * @return array
 *   api result array
 */
function civicrm_api3_mailing_event_subscribe_create($params) {
  $email      = $params['email'];
  $group_id   = $params['group_id'];
  $contact_id = CRM_Utils_Array::value('contact_id', $params);

  $group            = new CRM_Contact_DAO_Group();
  $group->is_active = 1;
  $group->id        = (int) $group_id;
  if (!$group->find(TRUE)) {
    throw new API_Exception('Invalid Group id');
  }

  $subscribe = CRM_Mailing_Event_BAO_Subscribe::subscribe($group_id, $email, $contact_id);

  if ($subscribe !== NULL) {
    /* Ask the contact for confirmation */

    $subscribe->send_confirm_request($email);

    $values = array();
    $values[$subscribe->id]['contact_id'] = $subscribe->contact_id;
    $values[$subscribe->id]['subscribe_id'] = $subscribe->id;
    $values[$subscribe->id]['hash'] = $subscribe->hash;

    return civicrm_api3_create_success($values);
  }
  return civicrm_api3_create_error('Subscription failed');
}

/**
 * Adjust Metadata for Create action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_mailing_event_subscribe_create_spec(&$params) {
  $params['email'] = array(
    'api.required' => 1,
    'title' => 'Unsubscribe Email',
    'type' => CRM_Utils_Type::T_STRING,
  );
  $params['group_id'] = array(
    'api.required' => 1,
    'title' => 'Unsubscribe From Group',
    'type' => CRM_Utils_Type::T_INT,
  );
}
