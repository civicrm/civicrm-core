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
 * This api exposes CiviCRM GroupNesting.
 *
 * This defines parent/child relationships between nested groups.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Provides group nesting record(s) given parent and/or child id.
 *
 * @param array $params
 *   An array containing at least child_group_id or parent_group_id.
 *
 * @return array
 *   list of group nesting records
 */
function civicrm_api3_group_nesting_get($params) {
  return _civicrm_api3_basic_get('CRM_Contact_DAO_GroupNesting', $params);
}

/**
 * Creates group nesting record for given parent and child id.
 *
 * Parent and child groups need to exist.
 *
 * @param array $params
 *   Parameters array - allowed array keys include:.
 *
 * @return array
 *   API success array
 */
function civicrm_api3_group_nesting_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params, 'GroupNesting');
}

/**
 * Adjust Metadata for Create action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_group_nesting_create_spec(&$params) {
  $params['child_group_id']['api.required'] = 1;
  $params['parent_group_id']['api.required'] = 1;
}

/**
 * Removes specific nesting records.
 *
 * @param array $params
 *
 * @return array
 *   API Success or fail array
 *
 * @todo Work out the return value.
 */
function civicrm_api3_group_nesting_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}
