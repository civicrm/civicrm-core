<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2018
 * $Id$
 *
 */

require_once 'civicrm_rules_utils.inc';
function civicrm_rules_condition_event_node(&$entityobj, $roleID, $field) {
  //just a hack for our site
  //SELECT * FROM `content_type_og_node` WHERE `field_civicrm_event_value` =993
  $sql = "SELECT * FROM `content_type_og_node` WHERE `field_civicrm_event_value` = $entityobj->id";
  $nid = db_result(db_query($sql));
  return $entityobj->id == $nid ? TRUE : FALSE;
}

/**
 * Condition civicrm_event type
 */
function civicrm_rules_condition_event_type($entityobj, $eventTypes) {
  if (!isset($eventTypes[$entityobj->event_type_id]) ||
    empty($eventTypes[$entityobj->event_type_id])
  ) {
    return FALSE;
  }
  return TRUE;
}

/**
 * Handler to get the user.
 * @todo this should either move into an event specific file or be more generic
 */
function civicrm_rules_events_argument_civicrm_event($eventobj) {
  if (!isset($eventobj['event'])) {
    return;
  }
  static $result;
  static $contact;

  if (empty($result) || empty($contact)) {
    $event = $eventobj['event'];
    //todo BAO POST hook leaves created_id blank, the api call is a work-around for now
    if (empty($event->created_id)) {
      require_once 'api/api.php';
      $result = civicrm_api('Event', 'get', array('version' => 3, 'id' => $event->id, 'sequential' => 1));
      if (!CRM_Utils_Array::value('is_error', $result) && CRM_Utils_Array::value('created_id', $result['values'][0])) {
        $contact = civicrm_api('UFMatch', 'get', array('version' => 3, 'contact_id' => $result['values'][0]['created_id'], 'sequential' => 1));
      }
    }
    else {
      $contact = civicrm_api('UFMatch', 'get', array('version' => 3, 'contact_id' => $event->created_id, 'sequential' => 1));
    }
  }

  if (!empty($contact) && !CRM_Utils_Array::value('is_error', $result) && is_numeric($contact['values'][0]['uf_id'])) {
    return $contact['values'][0]['uf_id'];
  }
  return NULL;
}

/**
 * Event type options
 */
function civicrm_rules_get_event_type_options() {
  return civicrm_rules_get_options('event_type');
}
