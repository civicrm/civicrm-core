<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * This api exposes CiviCRM membership contribution link.
 *
 * @package CiviCRM_APIv3
 * @todo delete function doesn't exist
 */

/**
 * Add or update a link between contribution and membership.
 *
 * @param array $params
 *   Input parameters.
 *
 * @return array
 *   API result array.
 */
function civicrm_api3_membership_payment_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params, 'MembershipPayment');
}

/**
 * Adjust Metadata for Create action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_membership_payment_create_spec(&$params) {
  $params['membership_id']['api.required'] = 1;
  $params['contribution_id']['api.required'] = 1;
  $params['membership_type_id'] = [
    'title' => 'Membership type id',
    'description' => 'The id of the membership type',
    'type' => CRM_Utils_Type::T_INT,
  ];
}

/**
 * Retrieve one or more membership payment records.
 *
 * @param array $params
 *   Input parameters.
 *
 * @return array
 *   API result array.
 */
function civicrm_api3_membership_payment_get($params) {
  return _civicrm_api3_basic_get('CRM_Member_DAO_MembershipPayment', $params);
}
