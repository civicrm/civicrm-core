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
 * This api exposes CiviCRM MembershipBlock records.
 *
 * @package CiviCRM_APIv3
 */

/**
 * API to Create or update a MembershipBlock.
 *
 * @param array $params
 *   An associative array of name/value property values of MembershipBlock.
 *
 * @return array
 *   API result array
 */
function civicrm_api3_membership_block_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Adjust Metadata for Create action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_membership_block_create_spec(&$params) {
  $params['is_active']['api.default'] = TRUE;
  $params['entity_id']['api.required'] = TRUE;
  $params['entity_table']['api.default'] = 'civicrm_contribution_page';
}

/**
 * Get a Membership Block.
 *
 * This api is used for finding an existing membership block.
 *
 * @param array $params
 *   An associative array of name/value property values of civicrm_membership_block.
 * {getfields MembershipBlock_get}
 *
 * @return array
 *   API result array
 */
function civicrm_api3_membership_block_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Deletes an existing membership block.
 *
 * This API is used for deleting a membership block
 * Required parameters : id of a membership block
 *
 * @param array $params
 *
 * @return array
 *   API result array
 */
function civicrm_api3_membership_block_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}
