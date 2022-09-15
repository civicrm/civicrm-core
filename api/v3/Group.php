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
 * This api exposes CiviCRM Groups.
 *
 * This api is for creating/deleting groups or fetching a list of existing groups.
 * To add/remove contacts to a group, use the GroupContact api instead.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Create/update group.
 *
 * @param array $params
 *   name/value pairs to insert in new 'Group'
 *
 * @return array
 *   API result array
 */
function civicrm_api3_group_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params, 'Group');
}

/**
 * Adjust Metadata for Create action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_group_create_spec(&$params) {
  $params['is_active']['api.default'] = 1;
  $params['title']['api.required'] = 1;
}

/**
 * Returns array of groups matching a set of one or more Group properties.
 *
 * @param array $params
 *   Array of properties. If empty, all records will be returned.
 *
 * @return array
 *   Array of matching groups
 */
function civicrm_api3_group_get($params) {
  $options = _civicrm_api3_get_options_from_params($params, TRUE, 'Group', 'get');

  if ($options['is_count']) {
    $params['options']['is_count'] = 0;
    $params['return'] = 'id';
  }

  $groups = _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params, FALSE, 'Group');
  foreach ($groups as $id => $group) {
    if (!empty($options['return']) && in_array('member_count', $options['return'])) {
      $groups[$id]['member_count'] = CRM_Contact_BAO_Group::memberCount($id);
    }
  }
  return civicrm_api3_create_success($groups, $params, 'Group', 'get');
}

/**
 * Delete an existing Group.
 *
 * @param array $params
 *   [id]
 * @return array API result array
 * @throws CRM_Core_Exception
 */
function civicrm_api3_group_delete($params) {
  $group = civicrm_api3_group_get(['id' => $params['id']]);
  if ($group['count'] == 0) {
    throw new CRM_Core_Exception('Could not delete group ' . $params['id']);
  }
  CRM_Contact_BAO_Group::discard($params['id']);
  return civicrm_api3_create_success(TRUE);
}
