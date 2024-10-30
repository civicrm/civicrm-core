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
  $status = $params['status'] ?? 'Added';

  $groupId = $params['group_id'] ?? NULL;
  $values = CRM_Contact_BAO_GroupContact::getContactGroup($params['contact_id'], $status, NULL, FALSE, TRUE, FALSE, TRUE, $groupId);
  return civicrm_api3_create_success($values, $params, 'GroupContact');
}

/**
 * Adjust metadata for Create action.
 *
 * @param array $params
 */
function _civicrm_api3_group_contact_create_spec(&$params) {
  $params['contact_id']['api.required'] = 1;
  $params['group_id']['api.required'] = 1;
}

/**
 * Add contact(s) to group(s).
 *
 * This api has a legacy/nonstandard signature.
 * On success, the return array will be structured as follows:
 * ```
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
 * ```
 *
 * On failure, the return array will be structured as follows:
 * ```
 * array(
 *   'is_error' => 1,
 *   'error_message' = string,
 *   'error_data' = mixed or undefined
 * )
 * ```
 *
 * @param array $params
 *   Input parameters:
 *   - "contact_id" (required): First contact to add, or array of Contact IDs
 *   - "group_id" (required): First group to add contact(s) to, or array of Group IDs
 *   - "status" (optional): "Added" (default), "Pending" or "Removed"
 *   Legacy input parameters (will be deprecated):
 *   - "contact_id.1" etc. (optional): Additional contact_id to add to group(s)
 *   - "group_id.1" etc. (optional): Additional groups to add contact(s) to
 *
 * @return array
 *   Information about operation results
 */
function civicrm_api3_group_contact_create($params) {
  // Nonstandard bao - doesn't accept ID as a param, so convert id to group_id + contact_id
  if (!empty($params['id'])) {
    $getParams = ['id' => $params['id']];
    $info = _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $getParams);
    if (!empty($info['values'][$params['id']])) {
      $params['group_id'] = $info['values'][$params['id']]['group_id'];
      $params['contact_id'] = $info['values'][$params['id']]['contact_id'];
    }
  }
  $action = $params['status'] ?? 'Added';
  return _civicrm_api3_group_contact_common($params, $action);
}

/**
 * Delete group contact record.
 *
 * @param array $params
 * @return array
 * @throws CRM_Core_Exception
 * @throws CRM_Core_Exception
 * @deprecated
 */
function civicrm_api3_group_contact_delete($params) {
  $checkParams = $params;
  if (!empty($checkParams['status']) && in_array($checkParams['status'], ['Removed', 'Deleted'])) {
    $checkParams['status'] = ['IN' => ['Added', 'Pending']];
  }
  elseif (!empty($checkParams['status']) && $checkParams['status'] == 'Added') {
    $checkParams['status'] = ['IN' => ['Pending', 'Removed']];
  }
  elseif (!empty($checkParams['status'])) {
    unset($checkParams['status']);
  }
  $groupContact = civicrm_api3('GroupContact', 'get', $checkParams);
  if ($groupContact['count'] == 0 && !empty($params['skip_undelete'])) {
    $checkParams['status'] = ['IN' => ['Removed', 'Pending']];
  }
  $groupContact2 = civicrm_api3('GroupContact', 'get', $checkParams);
  if ($groupContact['count'] == 0 && $groupContact2['count'] == 0) {
    throw new CRM_Core_Exception('Cannot Delete GroupContact');
  }
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

  $contactIDs = [];
  $groupIDs = [];

  // CRM-16959: Handle multiple Contact IDs and Group IDs in legacy format
  // (contact_id.1, contact_id.2) or as an array
  foreach ($params as $n => $v) {
    if (substr($n, 0, 10) == 'contact_id') {
      if (is_array($v)) {
        foreach ($v as $arr_v) {
          $contactIDs[] = $arr_v;
        }
      }
      else {
        $contactIDs[] = $v;
      }
    }
    elseif (substr($n, 0, 8) == 'group_id') {
      if (is_array($v)) {
        foreach ($v as $arr_v) {
          $groupIDs[] = $arr_v;
        }
      }
      else {
        $groupIDs[] = $v;
      }
    }
  }

  $method = $params['method'] ?? 'API';
  $status = $params['status'] ?? $op;
  $tracking = $params['tracking'] ?? NULL;

  if ($op == 'Added' || $op == 'Pending') {
    $extraReturnValues = [
      'total_count' => 0,
      'added' => 0,
      'not_added' => 0,
    ];
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
    $extraReturnValues = [
      'total_count' => 0,
      'removed' => 0,
      'not_removed' => 0,
    ];
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
 * @throws \CRM_Core_Exception
 */
function civicrm_api3_group_contact_update_status($params) {

  civicrm_api3_verify_mandatory($params, NULL, ['contact_id', 'group_id']);

  CRM_Contact_BAO_GroupContact::addContactsToGroup(
    [$params['contact_id']],
    $params['group_id'],
    $params['method'] ?? 'API',
    'Added',
    $params['tracking'] ?? NULL
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
  return [
    'delete' => 'GroupContact "delete" action is deprecated in favor of "create".',
    'pending' => 'GroupContact "pending" action is deprecated in favor of "create".',
    'update_status' => 'GroupContact "update_status" action is deprecated in favor of "create".',
  ];
}
