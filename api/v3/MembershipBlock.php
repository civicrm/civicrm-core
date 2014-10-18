<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * File for the CiviCRM APIv3 membership type functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Membership
 *
 * @copyright CiviCRM LLC (c) 2004-2014
 * @version $Id: MembershipBlock.php 30171 2010-10-14 09:11:27Z mover $
 *
 */

/**
 * API to Create or update a Membership Type
 *
 * @param array$params  an associative array of name/value property values of civicrm_membership_block
 *
 * @return array $result newly created or updated membership type property values.
 * @access public
 * {getfields MembershipBlock_get}
 */
function civicrm_api3_membership_block_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Adjust Metadata for Create action
 *
 * The metadata is used for setting defaults, documentation & validation
 * @param array $params array or parameters determined by getfields
 */
function _civicrm_api3_membership_block_create_spec(&$params) {
  $params['is_active']['api.default'] = TRUE;
  $params['entity_id']['api.required'] = TRUE;
  $params['entity_table']['api.default'] = 'civicrm_contribution_page';
}

/**
 * Get a Membership Type.
 *
 * This api is used for finding an existing membership type.
 *
 * @param array $params  an associative array of name/value property values of civicrm_membership_block
 * {getfields MembershipBlock_get}
 *
 * @return array api result array of all found membership block property values.
 * @access public
 */
function civicrm_api3_membership_block_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Deletes an existing membership type
 *
 * This API is used for deleting a membership type
 * Required parameters : id of a membership type
 *
 * @param  array $params
 *
 * @return array api result array
 * @access public
 * {getfields MembershipBlock_delete}
 */
function civicrm_api3_membership_block_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

