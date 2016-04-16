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
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2015
 * $Id$
 *
 */

require_once 'civicrm_rules_utils.inc';

/**
 * Participant role options
 */
function civicrm_rules_get_participant_role_options() {
  return civicrm_rules_get_options('participant_role');
}

function civicrm_rules_condition_participant_role($entityobj, $roleIDs) {
  if (!isset($roleIDs[$entityobj->role_id]) ||
    empty($roleIDs[$entityobj->role_id])
  ) {
    return FALSE;
  }

  return TRUE;
}

/*
 *get node related via cck  - this relies on there being a field called civicrm_event.
 *If you use this field on more than one event type it will load the last one (not something you should do)
 *I wanted to do it using a civicrm_event cck type but got a bit stuck.
 */
function civicrm_rules_events_argument_civicrm_eventnode($participantobj) {
  if (!module_exists('field') || !isset($participantobj['participant'])) {
    return NULL;
  }

  $participant = $participantobj['participant'];
  $eventID = $participant->event_id;
  $results = db_select('field_config_instance', 'i')
    ->fields('i', array('field_name', 'entity_type', 'bundle'))
    ->condition('field_name',  '%'.db_like('civicrm_event'), 'LIKE')
    ->execute()
    ->fetchAllAssoc('id');
  foreach($results as $result) {
    $nid = db_select('field_data_' . $result->field_name, 'fd')
      ->fields('fd', array('entity_id'))
      ->condition('bundle', $result->bundle, '=')
      ->condition($result->field_name . '_value', $eventID, '=')
      ->condition('entity_type',  $result->entity_type, '=')
      ->execute()
      ->fetchAssoc();
    return $nid['entity_id'];
  }
  return NULL;
}

/**
 * Handler to get the user. This will work if $entityobj->contact_id is defined
 * e.g. for participant object, contribution object
 */
function civicrm_rules_events_argument_civicrm_contactID_load_user($entityobj) {
  require_once 'api/api.php';
  $contact = civicrm_api('UFMatch', 'get', array('version' => 3, 'contact_id' => $entityobj['participant']->contact_id, 'sequential' => 1));
  if (!empty($contact) && is_numeric($contact['values'][0]['uf_id'])) {
    return $contact['values'][0]['uf_id'];
  }
  return NULL;
}

