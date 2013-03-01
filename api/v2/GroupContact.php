<?php
// $Id: GroupContact.php 45502 2013-02-08 13:32:55Z kurund $


/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * File for the CiviCRM APIv2 group contact functions
 *
 * @package CiviCRM_APIv2
 * @subpackage API_Group
 *
 * @copyright CiviCRM LLC (c) 2004-2013
 * @version $Id: GroupContact.php 45502 2013-02-08 13:32:55Z kurund $
 *
 */

/**
 * Include utility functions
 */
require_once 'api/v2/utils.php';

/**
 * This API will give list of the groups for particular contact
 * Particualr status can be sent in params array
 * If no status mentioned in params, by default 'added' will be used
 * to fetch the records
 *
 * @param  array $params  name value pair of contact information
 *
 * @return  array  list of groups, given contact subsribed to
 */
function civicrm_group_contact_get(&$params) {
  if (!is_array($params)) {
    return civicrm_create_error(ts('input parameter should be an array'));
  }

  if (!array_key_exists('contact_id', $params)) {
    return civicrm_create_error(ts('contact_id is a required field'));
  }

  $status = CRM_Utils_Array::value('status', $params, 'Added');
  require_once 'CRM/Contact/BAO/GroupContact.php';
  $values = CRM_Contact_BAO_GroupContact::getContactGroup($params['contact_id'], $status, NULL, FALSE, TRUE);
  return $values;
}

/**
 *
 * @param <type> $params
 *
 * @return <type>
 */
function civicrm_group_contact_add(&$params) {
  return civicrm_group_contact_common($params, 'add');
}

/**
 *
 * @param <type> $params
 *
 * @return <type>
 */
function civicrm_group_contact_remove(&$params) {
  return civicrm_group_contact_common($params, 'remove');
}

/**
 *
 * @param <type> $params
 *
 * @return <type>
 */
function civicrm_group_contact_pending(&$params) {
  return civicrm_group_contact_common($params, 'pending');
}

/**
 *
 * @param <type> $params
 * @param <type> $op
 *
 * @return <type>
 */
function civicrm_group_contact_common(&$params, $op = 'add') {
  if (!is_array($params)) {
    return civicrm_create_error(ts('input parameter should be an array'));
  }

  $contactIDs = array();
  $groupIDs = array();
  foreach ($params as $n => $v) {
    if (substr($n, 0, 10) == 'contact_id') {
      $contactIDs[] = $v;
    }
    elseif (substr($n, 0, 8) == 'group_id') {
      $groupIDs[] = $v;
    }
  }

  if (empty($contactIDs)) {
    return civicrm_create_error(ts('contact_id is a required field'));
  }

  if (empty($groupIDs)) {
    return civicrm_create_error(ts('group_id is a required field'));
  }

  $method = CRM_Utils_Array::value('method', $params, 'API');
  if ($op == 'add') {
    $status = CRM_Utils_Array::value('status', $params, 'Added');
  }
  elseif ($op == 'pending') {
    $status = CRM_Utils_Array::value('status', $params, 'Pending');
  }
  else {
    $status = CRM_Utils_Array::value('status', $params, 'Removed');
  }
  $tracking = CRM_Utils_Array::value('tracking', $params);

  require_once 'CRM/Contact/BAO/GroupContact.php';
  $values = array('is_error' => 0);
  if ($op == 'add' || $op == 'pending') {
    $values['total_count'] = $values['added'] = $values['not_added'] = 0;
    foreach ($groupIDs as $groupID) {
      list($tc, $a, $na) = CRM_Contact_BAO_GroupContact::addContactsToGroup($contactIDs, $groupID,
        $method, $status, $tracking
      );
      $values['total_count'] += $tc;
      $values['added'] += $a;
      $values['not_added'] += $na;
    }
  }
  else {
    $values['total_count'] = $values['removed'] = $values['not_removed'] = 0;
    foreach ($groupIDs as $groupID) {
      list($tc, $r, $nr) = CRM_Contact_BAO_GroupContact::removeContactsFromGroup($contactIDs, $groupID,
        $method, $status, $tracking
      );
      $values['total_count'] += $tc;
      $values['removed'] += $r;
      $values['not_removed'] += $nr;
    }
  }
  return $values;
}

function civicrm_group_contact_update_status(&$params) {
  if (!is_array($params)) {
    return civicrm_create_error(ts('input parameter should be an array'));
  }

  if (empty($params['contact_id'])) {
    return civicrm_create_error(ts('contact_id is a required field'));
  }
  else {
    $contactID = $params['contact_id'];
  }

  if (empty($params['group_id'])) {
    return civicrm_create_error(ts('group_id is a required field'));
  }
  else {
    $groupID = $params['group_id'];
  }
  $method = CRM_Utils_Array::value('method', $params, 'API');
  $tracking = CRM_Utils_Array::value('tracking', $params);

  require_once 'CRM/Contact/BAO/GroupContact.php';

  CRM_Contact_BAO_GroupContact::updateGroupMembershipStatus($contactID, $groupID, $method, $tracking);

  return TRUE;
}

