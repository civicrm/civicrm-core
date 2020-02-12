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
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
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
