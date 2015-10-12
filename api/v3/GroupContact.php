<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.6                                                |
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
 * This api exposes CiviCRM GroupContact records.
 *
 * This api is for adding/removing contacts from a group,
 * or fetching a list of groups for a contact.
 *
 * Important note: This api does not fetch smart groups for a contact.
 * To fetch all contacts in a smart group, use the Contact api
 * passing a contact_id and group_id.
 *
 * To create/delete groups, use the group api instead.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Fetch a list of groups for a contact, or contacts for a group.
 *
 * @Note: this only applies to static groups, not smart groups.
 * To fetch all contacts in a smart group, use the Contact api
 * passing a contact_id and group_id.
 *
 * If no status mentioned in params, by default 'added' will be used
 * to fetch the records
 *
 * @param array $params
 *   Name value pair of contact information.
 *
 * @return array
 *   list of groups, given contact subsribed to
 */
function civicrm_api3_group_contact_get($params) {

  if (empty($params['contact_id'])) {
    if (empty($params['status'])) {
      //default to 'Added'
      $params['status'] = 'Added';
    }
    //ie. id passed in so we have to return something
    return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
  }
  $status = CRM_Utils_Array::value('status', $params, 'Added');

  $groupId = CRM_Utils_Array::value('group_id', $params);
  $values = CRM_Contact_BAO_GroupContact::getContactGroup($params['contact_id'], $status, NULL, FALSE, TRUE, FALSE, TRUE, $groupId);
  return civicrm_api3_create_success($values, $params, 'GroupContact');
}

/**
 * Add contact(s) to group(s).
 *
 * This api has a legacy/nonstandard signature.
 * On success, the return array will be structured as follows:
 * @code
 * array(
 *   "is_error" => 0,
 *   "version"  => 3,
 *   "count"    => 3,
 *   "values" => array(
 *     "not_added"   => integer,
 *     "added"       => integer,
 *     "total_count" => integer
 *   )
 * )
 * @endcode
 *
 * On failure, the return array will be structured as follows:
 * @code
 * array(
 *   'is_error' => 1,
 *   'error_message' = string,
 *   'error_data' = mixed or undefined
 * )
 * @endcode
 *
 * @param array $params
 *   Input parameters.
 *   - "contact_id" (required) : first contact to add
 *   - "group_id" (required): first group to add contact(s) to
 *   - "contact_id.1" etc. (optional) : another contact to add
 *   - "group_id.1" etc. (optional) : additional group to add contact(s) to
 *   - "status" (optional) : one of "Added", "Pending" or "Removed" (default is "Added")
 *
 * @return array
 *   Information about operation results
 */
function civicrm_api3_group_contact_create($params) {
  // Nonstandard bao - doesn't accept ID as a param, so convert id to group_id + contact_id
  if (!empty($params['id'])) {
    $getParams = array('id' => $params['id']);
    $info = _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $getParams);
    if (!empty($info['values'][$params['id']])) {
      $params['group_id'] = $info['values'][$params['id']]['group_id'];
      $params['contact_id'] = $info['values'][$params['id']]['contact_id'];
    }
  }
  civicrm_api3_verify_mandatory($params, NULL, array('group_id', 'contact_id'));
  $action = CRM_Utils_Array::value('status', $params, 'Added');
  return _civicrm_api3_group_contact_common($params, $action);
}

/**
 * Delete group contact record.
 *
 * @param array $params
 *
 * @return array
 *
 * @deprecated
 */
function civicrm_api3_group_contact_delete($params) {
  $params['status'] = CRM_Utils_Array::value('status', $params, empty($params['skip_undelete']) ? 'Removed' : 'Deleted');
  // "Deleted" isn't a real option so skip the api wrapper to avoid pseudoconstant validation
  return civicrm_api3_group_contact_create($params);
}

/**
 * Adjust metadata.
 *
 * @param array $params
 */
function _civicrm_api3_group_contact_delete_spec(&$params) {
  // set as not required no either/or std yet
  $params['id']['api.required'] = 0;
}

/**
 * Get pending group contacts.
 *
 * @param array $params
 *
 * @return array|int
 * @deprecated
 */
function civicrm_api3_group_contact_pending($params) {
  $params['status'] = 'Pending';
  return civicrm_api('GroupContact', 'Create', $params);
}

/**
 * Group contact helper function.
 *
 * @todo behaviour is highly non-standard - need to figure out how to make this 'behave'
 *   & at the very least return IDs & details of the groups created / changed
 *
 * @param array $params
 * @param string $op
 *
 * @return array
 */
function _civicrm_api3_group_contact_common($params, $op = 'Added') {

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
    return civicrm_api3_create_error('contact_id is a required field');
  }

  if (empty($groupIDs)) {
    return civicrm_api3_create_error('group_id is a required field');
  }

  $method = CRM_Utils_Array::value('method', $params, 'API');
  $status = CRM_Utils_Array::value('status', $params, $op);
  $tracking = CRM_Utils_Array::value('tracking', $params);

  if ($op == 'Added' || $op == 'Pending') {
    $extraReturnValues = array(
      'total_count' => 0,
      'added' => 0,
      'not_added' => 0,
    );
    foreach ($groupIDs as $groupID) {
      list($tc, $a, $na) = CRM_Contact_BAO_GroupContact::addContactsToGroup($contactIDs,
        $groupID,
        $method,
        $status,
        $tracking
      );
      $extraReturnValues['total_count'] += $tc;
      $extraReturnValues['added'] += $a;
      $extraReturnValues['not_added'] += $na;
    }
  }
  else {
    $extraReturnValues = array(
      'total_count' => 0,
      'removed' => 0,
      'not_removed' => 0,
    );
    foreach ($groupIDs as $groupID) {
      list($tc, $r, $nr) = CRM_Contact_BAO_GroupContact::removeContactsFromGroup($contactIDs, $groupID, $method, $status, $tracking);
      $extraReturnValues['total_count'] += $tc;
      $extraReturnValues['removed'] += $r;
      $extraReturnValues['not_removed'] += $nr;
    }
  }
  // can't pass this by reference
  $dao = NULL;
  return civicrm_api3_create_success(1, $params, 'GroupContact', 'create', $dao, $extraReturnValues);
}

/**
 * Update group contact status.
 *
 * @deprecated - this should be part of create but need to know we aren't missing something
 *
 * @param array $params
 *
 * @return bool
 * @throws \API_Exception
 */
function civicrm_api3_group_contact_update_status($params) {

  civicrm_api3_verify_mandatory($params, NULL, array('contact_id', 'group_id'));

  CRM_Contact_BAO_GroupContact::addContactsToGroup(
    array($params['contact_id']),
    $params['group_id'],
    CRM_Utils_Array::value('method', $params, 'API'),
    'Added',
    CRM_Utils_Array::value('tracking', $params)
  );

  return TRUE;
}

/**
 * Deprecated function notices.
 *
 * @deprecated api notice
 * @return array
 *   Array of deprecated actions
 */
function _civicrm_api3_group_contact_deprecation() {
  return array(
    'delete' => 'GroupContact "delete" action is deprecated in favor of "create".',
    'pending' => 'GroupContact "pending" action is deprecated in favor of "create".',
    'update_status' => 'GroupContact "update_status" action is deprecated in favor of "create".',
  );
}
