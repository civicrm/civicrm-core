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
 *   TBD
 * @todo Work out the return value.
 */
function civicrm_api3_group_nesting_create($params) {
  CRM_Contact_BAO_GroupNesting::add($params['parent_group_id'], $params['child_group_id']);

  // FIXME: CRM_Contact_BAO_GroupNesting requires some work
  $result = array('is_error' => 0);
  return civicrm_api3_create_success($result, $params, 'GroupNesting');
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
