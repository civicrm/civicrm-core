<?php
// $Id: MembershipType.php 45502 2013-02-08 13:32:55Z kurund $


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
 * File for the CiviCRM APIv2 membership type functions
 *
 * @package CiviCRM_APIv2
 * @subpackage API_Membership
 *
 * @copyright CiviCRM LLC (c) 2004-2013
 * @version $Id: MembershipType.php 45502 2013-02-08 13:32:55Z kurund $
 *
 */

/**
 * Files required for this package
 */
require_once 'api/v2/utils.php';

/**
 * Create a Membership Type
 *
 * This API is used for creating a Membership Type
 *
 * @param   array  $params  an associative array of name/value property values of civicrm_membership_type
 *
 * @return array of newly created membership type property values.
 * @access public
 */
function civicrm_membership_type_create(&$params) {
  _civicrm_initialize();
  if (!is_array($params)) {
    return civicrm_create_error('Params need to be of type array!');
  }
  if (empty($params)) {
    return civicrm_create_error('No input parameters present');
  }

  if (!isset($params['name']) ||
    !isset($params['duration_unit']) ||
    !isset($params['duration_interval'])
  ) {
    return civicrm_create_error('Missing require fileds ( name, duration unit,duration interval)');
  }

  $error = _civicrm_check_required_fields($params, 'CRM_Member_DAO_MembershipType');
  if ($error['is_error']) {
    return civicrm_create_error($error['error_message']);
  }

  $ids['membershipType'] = CRM_Utils_Array::value('id', $params);
  $ids['memberOfContact'] = CRM_Utils_Array::value('member_of_contact_id', $params);
  $ids['contributionType'] = CRM_Utils_Array::value('financial_type_id', $params);

  require_once 'CRM/Member/BAO/MembershipType.php';
  $membershipTypeBAO = CRM_Member_BAO_MembershipType::add($params, $ids);

  if (is_a($membershipTypeBAO, 'CRM_Core_Error')) {
    return civicrm_create_error("Membership is not created");
  }
  else {
    $membershipType = array();
    _civicrm_object_to_array($membershipTypeBAO, $membershipType);
    $values             = array();
    $values['id']       = $membershipType['id'];
    $values['is_error'] = 0;
  }

  return $values;
}

/**
 * Get a Membership Type.
 *
 * This api is used for finding an existing membership type.
 *
 * @param  array $params  an associative array of name/value property values of civicrm_membership_type
 *
 * @return  Array of all found membership type property values.
 * @access public
 */
function civicrm_membership_type_get(&$params) {
  _civicrm_initialize();

  if (!is_array($params)) {
    return civicrm_create_error('Params need to be of type array!');
  }
  if (empty($params)) {
    return civicrm_create_error('No input parameters present');
  }
  require_once 'CRM/Member/BAO/MembershipType.php';
  $membershipTypeBAO = new CRM_Member_BAO_MembershipType();

  $properties = array_keys($membershipTypeBAO->fields());

  foreach ($properties as $name) {
    if (array_key_exists($name, $params)) {
      $membershipTypeBAO->$name = $params[$name];
    }
  }

  if ($membershipTypeBAO->find()) {
    $membershipType = array();
    while ($membershipTypeBAO->fetch()) {
      _civicrm_object_to_array(clone($membershipTypeBAO), $membershipType);
      $membershipTypes[$membershipTypeBAO->id] = $membershipType;
    }
  }
  else {
    return civicrm_create_error('Exact match not found');
  }
  return $membershipTypes;
}

/**
 * Update an existing membership type
 *
 * This api is used for updating an existing membership type.
 * Required parrmeters : id of a membership type
 *
 * @param  Array   $params  an associative array of name/value property values of civicrm_membership_type
 *
 * @return array of updated membership type property values
 * @access public
 */
function &civicrm_membership_type_update(&$params) {
  if (!is_array($params)) {
    return civicrm_create_error('Params need to be of type array!');
  }
  if (empty($params)) {
    return civicrm_create_error('No input parameters present');
  }
  if (!isset($params['id'])) {
    return civicrm_create_error('Required parameter missing');
  }

  require_once 'CRM/Member/BAO/MembershipType.php';
  $membershipTypeBAO = new CRM_Member_BAO_MembershipType();
  $membershipTypeBAO->id = $params['id'];
  if ($membershipTypeBAO->find(TRUE)) {
    $fields = $membershipTypeBAO->fields();

    foreach ($fields as $name => $field) {
      if (array_key_exists($field['name'], $params)) {
        $membershipTypeBAO->$field['name'] = $params[$field['name']];
      }
    }
    $membershipTypeBAO->save();
  }

  $membershipType = array();
  _civicrm_object_to_array($membershipTypeBAO, $membershipType);
  $membershipTypeBAO->free();
  return $membershipType;
}

/**
 * Deletes an existing membership type
 *
 * This API is used for deleting a membership type
 * Required parrmeters : id of a membership type
 *
 * @param  Array   $params  an associative array of name/value property values of civicrm_membership_type
 *
 * @return boolean        true if success, else false
 * @access public
 */
function civicrm_membership_type_delete(&$params) {
  if (!is_array($params)) {
    return civicrm_create_error('Params need to be of type array!');
  }
  if (empty($params)) {
    return civicrm_create_error('No input parameters present');
  }
  if (!CRM_Utils_Array::value('id', $params)) {
    return civicrm_create_error('Invalid or no value for membershipTypeID');
  }

  require_once 'CRM/Member/BAO/MembershipType.php';
  $memberDelete = CRM_Member_BAO_MembershipType::del($params['id']);

  return $memberDelete ? civicrm_create_success("Given Membership Type have been deleted") : civicrm_create_error('Error while deleting membership type');
}

