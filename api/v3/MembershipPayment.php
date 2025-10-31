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
 * This api exposes CiviCRM membership contribution link.
 *
 * @package CiviCRM_APIv3
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
