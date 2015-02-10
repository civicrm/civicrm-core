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
 * @version $Id: MembershipType.php 30171 2010-10-14 09:11:27Z mover $
 *
 */

/**
 * API to Create or update a Membership Type
 *
 * @param   array  $params  an associative array of name/value property values of civicrm_membership_type
 *
 * @return array $result newly created or updated membership type property values.
 * @access public
 * {getfields MembershipType_get}
 */
function civicrm_api3_membership_type_create($params) {
  $ids['membershipType'] = CRM_Utils_Array::value('id', $params);
  $ids['memberOfContact'] = CRM_Utils_Array::value('member_of_contact_id', $params);
  $ids['contributionType'] = CRM_Utils_Array::value('financial_type_id', $params);

  $membershipTypeBAO = CRM_Member_BAO_MembershipType::add($params, $ids);
  $membershipType = array();
  _civicrm_api3_object_to_array($membershipTypeBAO, $membershipType[$membershipTypeBAO->id]);
  CRM_Member_PseudoConstant::membershipType(NULL, TRUE);
  civicrm_api3('membership', 'getfields', array('cache_clear' => 1, 'fieldname' => 'membership_type_id'));
  civicrm_api3('profile', 'getfields', array('action' => 'submit', 'cache_clear' => 1));
  return civicrm_api3_create_success($membershipType, $params, 'membership_type', 'create', $membershipTypeBAO);
}

/**
 * Adjust Metadata for Create action
 *
 * The metadata is used for setting defaults, documentation & validation
 * @param array $params array or parameters determined by getfields
 */
function _civicrm_api3_membership_type_create_spec(&$params) {
  // todo could set default here probably
  $params['domain_id']['api.required'] = 1;
  $params['member_of_contact_id']['api.required'] = 1;
  $params['financial_type_id']['api.required'] =1;
  $params['name']['api.required'] = 1;
  $params['duration_unit']['api.required'] = 1;
  $params['duration_interval']['api.required'] = 1;
}

/**
 * Get a Membership Type.
 *
 * This api is used for finding an existing membership type.
 *
 * @param  array $params  an associative array of name/value property values of civicrm_membership_type
 * {getfields MembershipType_get}
 *
 * @return  Array of all found membership type property values.
 * @access public
 */
function civicrm_api3_membership_type_get($params) {
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
 * @return boolean        true if success, else false
 * @access public
 * {getfields MembershipType_delete}
 */
function civicrm_api3_membership_type_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

