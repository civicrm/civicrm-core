<?php
// $Id: MembershipStatus.php 45502 2013-02-08 13:32:55Z kurund $


/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * File for the CiviCRM APIv2 membership status functions
 *
 * @package CiviCRM_APIv2
 * @subpackage API_Membership
 *
 * @copyright CiviCRM LLC (c) 2004-2013
 * @version $Id: MembershipStatus.php 45502 2013-02-08 13:32:55Z kurund $
 *
 */

/**
 * Files required for this package
 */
require_once 'api/v2/utils.php';

/**
 * Create a Membership Status
 *
 * This API is used for creating a Membership Status
 *
 * @param   array  $params  an associative array of name/value property values of civicrm_membership_status
 *
 * @return array of newly created membership status property values.
 * @access public
 */
function civicrm_membership_status_create(&$params) {
  _civicrm_initialize();
  if (!is_array($params)) {
    return civicrm_create_error('Params is not an array.');
  }

  if (empty($params)) {
    return civicrm_create_error('Params can not be empty.');
  }

  $name = CRM_Utils_Array::value('name', $params);
  if (!$name) {
    $name = CRM_Utils_Array::value('label', $params);
  }
  if (!$name) {
    return civicrm_create_error('Missing required fields');
  }

  //don't allow duplicate names.
  require_once 'CRM/Member/DAO/MembershipStatus.php';
  $status = new CRM_Member_DAO_MembershipStatus();
  $status->name = $name;
  if ($status->find(TRUE)) {
    return civicrm_create_error(ts('A membership status with this name already exists.'));
  }

  require_once 'CRM/Member/BAO/MembershipStatus.php';
  $ids = array();
  $membershipStatusBAO = CRM_Member_BAO_MembershipStatus::add($params, $ids);
  if (is_a($membershipStatusBAO, 'CRM_Core_Error')) {
    return civicrm_create_error("Membership is not created");
  }
  else {
    $values             = array();
    $values['id']       = $membershipStatusBAO->id;
    $values['is_error'] = 0;
    return $values;
  }
}

/**
 * Get a membership status.
 *
 * This api is used for finding an existing membership status.
 *
 * @param  array $params  an associative array of name/value property values of civicrm_membership_status
 *
 * @return  Array of all found membership status property values.
 * @access public
 */
function civicrm_membership_status_get(&$params) {
  _civicrm_initialize();
  if (!is_array($params)) {
    return civicrm_create_error('Params is not an array.');
  }

  require_once 'CRM/Member/BAO/MembershipStatus.php';
  $membershipStatusBAO = new CRM_Member_BAO_MembershipStatus();

  $properties = array_keys($membershipStatusBAO->fields());

  foreach ($properties as $name) {
    if (array_key_exists($name, $params)) {
      $membershipStatusBAO->$name = $params[$name];
    }
  }

  if ($membershipStatusBAO->find()) {
    $membershipStatus = array();
    while ($membershipStatusBAO->fetch()) {
      _civicrm_object_to_array(clone($membershipStatusBAO), $membershipStatus);
      $membershipStatuses[$membershipStatusBAO->id] = $membershipStatus;
    }
  }
  else {
    return civicrm_create_error('Exact match not found');
  }
  return $membershipStatuses;
}

/**
 * Update an existing membership status
 *
 * This api is used for updating an existing membership status.
 * Required parrmeters : id of a membership status
 *
 * @param  Array   $params  an associative array of name/value property values of civicrm_membership_status
 *
 * @return array of updated membership status property values
 * @access public
 */
function &civicrm_membership_status_update(&$params) {
  _civicrm_initialize();
  if (!is_array($params)) {
    return civicrm_create_error('Params is not an array');
  }

  if (!isset($params['id'])) {
    return civicrm_create_error('Required parameter missing');
  }

  //don't allow duplicate names.
  $name = CRM_Utils_Array::value('name', $params);
  if ($name) {
    require_once 'CRM/Member/DAO/MembershipStatus.php';
    $status = new CRM_Member_DAO_MembershipStatus();
    $status->name = $params['name'];
    if ($status->find(TRUE) && $status->id != $params['id']) {
      return civicrm_create_error(ts('A membership status with this name already exists.'));
    }
  }

  require_once 'CRM/Member/BAO/MembershipStatus.php';
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
  $membershipStatus = array();
  _civicrm_object_to_array(clone($membershipStatusBAO), $membershipStatus);
  $membershipStatus['is_error'] = 0;
  return $membershipStatus;
}

/**
 * Deletes an existing membership status
 *
 * This API is used for deleting a membership status
 *
 * @param  Int  $membershipStatusID   Id of the membership status to be deleted
 *
 * @return null if successfull, object of CRM_Core_Error otherwise
 * @access public
 */
function civicrm_membership_status_delete(&$params) {
  if (!is_array($params)) {
    return civicrm_create_error('Params is not an array');
  }

  if (!CRM_Utils_Array::value('id', $params)) {
    return civicrm_create_error('Invalid or no value for membershipStatusID');
  }

  require_once 'CRM/Member/BAO/MembershipStatus.php';
  $memberStatusDelete = CRM_Member_BAO_MembershipStatus::del($params['id']);
  return $memberStatusDelete ? civicrm_create_error('Error while deleting membership type Status') : civicrm_create_success();
}

/**
 * Derives the Membership Status of a given Membership Reocrd
 *
 * This API is used for deriving Membership Status of a given Membership
 * record using the rules encoded in the membership_status table.
 *
 * @param  Int     $membershipID  Id of a membership
 * @param  String  $statusDate
 *
 * @return Array  Array of status id and status name
 * @public
 */
function civicrm_membership_status_calc($membershipParams, $excludeIsAdmin = FALSE) {
  if (!is_array($membershipParams)) {
    return civicrm_create_error(ts('membershipParams is not an array'));
  }

  if (!($membershipID = CRM_Utils_Array::value('membership_id', $membershipParams))) {
    return civicrm_create_error('membershipParams do not contain membership_id');
  }

  $query = "
SELECT start_date, end_date, join_date
  FROM civicrm_membership
 WHERE id = %1
";
  $params = array(1 => array($membershipID, 'Integer'));
  $dao = CRM_Core_DAO::executeQuery($query, $params);
  if ($dao->fetch()) {
    require_once 'CRM/Member/BAO/MembershipStatus.php';
    // CRM-7248 added $excludeIsAdmin to this function, also 'today' param
    $result = &CRM_Member_BAO_MembershipStatus::getMembershipStatusByDate($dao->start_date,
      $dao->end_date,
      $dao->join_date,
      'today',
      $excludeIsAdmin
    );

    //make is error zero only when valid status found.
    if (CRM_Utils_Array::value('id', $result)) {
      $result['is_error'] = 0;
    }
  }
  else {
    $result = civicrm_create_error('did not find a membership record');
  }
  $dao->free();
  return $result;
}

