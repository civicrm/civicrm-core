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
 * This api exposes CiviCRM membership status.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Create a Membership Status.
 *
 * @param array $params
 *   Array of name/value property values of civicrm_membership_status.
 *
 * @return array
 */
function civicrm_api3_membership_status_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params, 'MembershipStatus');
}

/**
 * Adjust Metadata for Create action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_membership_status_create_spec(&$params) {
  $params['name']['api.required'] = 1;
}

/**
 * Get a membership status.
 *
 * This api is used for finding an existing membership status.
 *
 * @param array $params
 *   An associative array of name/value property values of civicrm_membership_status.
 *
 * @return array
 *   Array of all found membership status property values.
 */
function civicrm_api3_membership_status_get($params) {
  return _civicrm_api3_basic_get('CRM_Member_BAO_MembershipStatus', $params);
}

/**
 * Update an existing membership status.
 *
 * This api is used for updating an existing membership status.
 * Required parameters: id of a membership status
 *
 * @param array $params
 *   Array of name/value property values of civicrm_membership_status.
 *
 * @deprecated - should just use create
 *
 * @return array
 *   Array of updated membership status property values
 */
function civicrm_api3_membership_status_update($params) {

  civicrm_api3_verify_mandatory($params, NULL, ['id']);
  //don't allow duplicate names.
  $name = CRM_Utils_Array::value('name', $params);
  if ($name) {
    $status = new CRM_Member_DAO_MembershipStatus();
    $status->name = $params['name'];
    if ($status->find(TRUE) && $status->id != $params['id']) {
      return civicrm_api3_create_error(ts('A membership status with this name already exists.'));
    }
  }

  $membershipStatusBAO = new CRM_Member_BAO_MembershipStatus();
  $membershipStatusBAO->id = $params['id'];
  if ($membershipStatusBAO->find(TRUE)) {
    $fields = $membershipStatusBAO->fields();
    foreach ($fields as $name => $field) {
      if (array_key_exists($name, $params)) {
        $membershipStatusBAO->$name = $params[$name];
      }
    }
    $membershipStatusBAO->save();
  }
  $membershipStatus = [];
  $cloneBAO = clone($membershipStatusBAO);
  _civicrm_api3_object_to_array($cloneBAO, $membershipStatus);
  $membershipStatus['is_error'] = 0;
  return $membershipStatus;
}

/**
 * Deletes an existing membership status.
 *
 * This API is used for deleting a membership status
 *
 * @param array $params
 * @return array
 * @throws API_Exception
 * @throws CRM_Core_Exception
 */
function civicrm_api3_membership_status_delete($params) {

  $memberStatusDelete = CRM_Member_BAO_MembershipStatus::del($params['id'], TRUE);
  if ($memberStatusDelete) {
    throw new API_Exception($memberStatusDelete['error_message']);
  }
  return civicrm_api3_create_success();
}

/**
 * Derives the Membership Status of a given Membership Record.
 *
 * This API is used for deriving Membership Status of a given Membership
 * record using the rules encoded in the membership_status table.
 *
 * @param array $membershipParams
 *
 * @throws API_Exception
 *
 * @return array
 *   Array of status id and status name
 */
function civicrm_api3_membership_status_calc($membershipParams) {
  if (!($membershipID = CRM_Utils_Array::value('membership_id', $membershipParams))) {
    throw new API_Exception('membershipParams do not contain membership_id');
  }

  if (empty($membershipParams['id'])) {
    //for consistency lets make sure id is set as this will get passed to hooks downstream
    $membershipParams['id'] = $membershipID;
  }
  $query = "
SELECT start_date, end_date, join_date, membership_type_id
  FROM civicrm_membership
 WHERE id = %1
";

  $params = [1 => [$membershipID, 'Integer']];
  $dao = CRM_Core_DAO::executeQuery($query, $params);
  if ($dao->fetch()) {
    $membershipTypeID = empty($membershipParams['membership_type_id']) ? $dao->membership_type_id : $membershipParams['membership_type_id'];
    $result = CRM_Member_BAO_MembershipStatus::getMembershipStatusByDate($dao->start_date, $dao->end_date, $dao->join_date, 'today', CRM_Utils_Array::value('ignore_admin_only', $membershipParams), $membershipTypeID, $membershipParams);
    //make is error zero only when valid status found.
    if (!empty($result['id'])) {
      $result['is_error'] = 0;
    }
  }
  else {
    throw new API_Exception('did not find a membership record');
  }
  return $result;
}

/**
 * Adjust Metadata for Calc action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_membership_status_calc_spec(&$params) {
  $params['membership_id']['api.required'] = 1;
  $params['membership_id']['title'] = 'Membership ID';
}
