<?php
// $Id: MembershipContact.php 45502 2013-02-08 13:32:55Z kurund $


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
 *
 * File for the CiviCRM APIv2 membership contact functions
 *
 * @package CiviCRM_APIv2
 * @subpackage API_Membership
 *
 * @copyright CiviCRM LLC (c) 2004-2013
 * @version $Id: MembershipContact.php 45502 2013-02-08 13:32:55Z kurund $
 */

/**
 * Files required for this package
 */
require_once 'api/v2/utils.php';
require_once 'CRM/Utils/Rule.php';
require_once 'CRM/Utils/Array.php';

/**
 * Create a Contact Membership
 *
 * This API is used for creating a Membership for a contact.
 * Required parameters : membership_type_id and status_id.
 *
 * @param   array  $params     an associative array of name/value property values of civicrm_membership
 *
 * @return array of newly created membership property values.
 * @access public
 */
function civicrm_membership_contact_create(&$params) {
  _civicrm_initialize();

  $error = _civicrm_membership_check_params($params);
  if (civicrm_error($error)) {
    return $error;
  }

  $values = array();
  $error = _civicrm_membership_format_params($params, $values);
  if (civicrm_error($error)) {
    return $error;
  }

  $params = array_merge($params, $values);

  require_once 'CRM/Core/Action.php';
  $action = CRM_Core_Action::ADD;
  // we need user id during add mode
  $ids = array('userId' => $params['contact_id']);

  //for edit membership id should be present
  if (CRM_Utils_Array::value('id', $params)) {
    $ids = array(
      'membership' => $params['id'],
      'userId' => $params['contact_id'],
    );
    $action = CRM_Core_Action::UPDATE;
  }

  //need to pass action to handle related memberships.
  $params['action'] = $action;

  require_once 'CRM/Member/BAO/Membership.php';
  $membershipBAO = CRM_Member_BAO_Membership::create($params, $ids, TRUE);

  if (array_key_exists('is_error', $membershipBAO)) {
    // In case of no valid status for given dates, $membershipBAO
    // is going to contain 'is_error' => "Error Message"
    return civicrm_create_error(ts('The membership can not be saved, no valid membership status for given dates'));
  }

  $membership = array();
  _civicrm_object_to_array($membershipBAO, $membership);
  $values             = array();
  $values['id']       = $membership['id'];
  $values['is_error'] = 0;

  return $values;
}

/**
 * Get contact membership record.
 *
 * This api is used for finding an existing membership record.
 * This api will also return the mebership records for the contacts
 * having mebership based on the relationship with the direct members.
 *
 * @param  Array $params key/value pairs for contact_id and some
 *          options affecting the desired results; has legacy support
 *          for just passing the contact_id itself as the argument
 *
 * @return  Array of all found membership property values.
 * @access public
 */
function civicrm_membership_contact_get(&$params) {
  _civicrm_initialize();

  $contactID = $activeOnly = $membershipTypeId = $membershipType = NULL;
  if (is_array($params)) {
    $contactID        = CRM_Utils_Array::value('contact_id', $params);
    $activeOnly       = CRM_Utils_Array::value('active_only', $params, FALSE);
    $membershipTypeId = CRM_Utils_Array::value('membership_type_id', $params);
    if (!$membershipTypeId) {
      $membershipType = CRM_Utils_Array::value('membership_type', $params);
      if ($membershipType) {
        require_once 'CRM/Member/DAO/MembershipType.php';
        $membershipTypeId = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipType',
          $membershipType, 'id', 'name'
        );
      }
    }
  }
  elseif (CRM_Utils_Rule::integer($params)) {
    $contactID = $params;
  }
  else {
    return civicrm_create_error('Parameters can be only of type array or integer');
  }

  if (empty($contactID)) {
    return civicrm_create_error('Invalid value for ContactID.');
  }

  // get the membership for the given contact ID
  require_once 'CRM/Member/BAO/Membership.php';
  $membershipParams = array('contact_id' => $contactID);
  if ($membershipTypeId) {
    $membershipParams['membership_type_id'] = $membershipTypeId;
  }
  $membershipValues = array();
  CRM_Member_BAO_Membership::getValues($membershipParams, $membershipValues, $activeOnly);

  $recordCount = 0;

  if (empty($membershipValues)) {
    # No results is NOT an error!
    # return civicrm_create_error('No memberships for this contact.');
    $membershipValues['record_count'] = $recordCount;
    return $membershipValues;
  }

  $members[$contactID] = array();
  $relationships = array();;
  foreach ($membershipValues as $membershipId => $values) {
    // populate the membership type name for the membership type id
    require_once 'CRM/Member/BAO/MembershipType.php';
    $membershipType = CRM_Member_BAO_MembershipType::getMembershipTypeDetails($values['membership_type_id']);

    $membershipValues[$membershipId]['membership_name'] = $membershipType['name'];

    if (CRM_Utils_Array::value('relationship_type_id', $membershipType)) {
      $relationships[$membershipType['relationship_type_id']] = $membershipId;
    }

    // populating relationship type name.
    require_once 'CRM/Contact/BAO/RelationshipType.php';
    $relationshipType = new CRM_Contact_BAO_RelationshipType();
    $relationshipType->id = CRM_Utils_Array::value('relationship_type_id', $membershipType);
    if ($relationshipType->find(TRUE)) {
      $membershipValues[$membershipId]['relationship_name'] = $relationshipType->name_a_b;
    }
    require_once 'CRM/Core/BAO/CustomGroup.php';
    $groupTree = &CRM_Core_BAO_CustomGroup::getTree('Membership', CRM_Core_DAO::$_nullObject, $membershipId, FALSE,
      $values['membership_type_id']
    );
    $groupTree = CRM_Core_BAO_CustomGroup::formatGroupTree($groupTree, 1, CRM_Core_DAO::$_nullObject);

    $defaults = array();
    CRM_Core_BAO_CustomGroup::setDefaults($groupTree, $defaults);

    if (!empty($defaults)) {
      foreach ($defaults as $key => $val) {
        $membershipValues[$membershipId][$key] = $val;
      }
    }

    $recordCount++;
  }

  $members[$contactID] = $membershipValues;

  // populating contacts in members array based on their relationship with direct members.
  require_once 'CRM/Contact/BAO/Relationship.php';
  if (!empty($relationships)) {
    foreach ($relationships as $relTypeId => $membershipId) {
      // As members are not direct members, there should not be
      // membership id in the result array.
      unset($membershipValues[$membershipId]['id']);
      $relationship = new CRM_Contact_BAO_Relationship();
      $relationship->contact_id_b = $contactID;
      $relationship->relationship_type_id = $relTypeId;
      if ($relationship->find()) {
        while ($relationship->fetch()) {
          clone($relationship);
          $membershipValues[$membershipId]['contact_id'] = $relationship->contact_id_a;
          $members[$contactID][$relationship->contact_id_a] = $membershipValues[$membershipId];
        }
      }
      $recordCount++;
    }
  }
  $members['record_count'] = $recordCount;
  return $members;
}

/**
 * take the input parameter list as specified in the data model and
 * convert it into the same format that we use in QF and BAO object
 *
 * @param array  $params       Associative array of property name/value
 *                             pairs to insert in new contact.
 * @param array  $values       The reformatted properties that we can use internally
 *
 * @param array  $create       Is the formatted Values array going to
 *                             be used for CRM_Member_BAO_Membership:create()
 *
 * @return array|error
 * @access public
 */
function _civicrm_membership_format_params(&$params, &$values, $create = FALSE) {
  require_once "CRM/Member/DAO/Membership.php";
  require_once "CRM/Member/PseudoConstant.php";
  $fields = CRM_Member_DAO_Membership::fields();
  _civicrm_store_values($fields, $params, $values);

  foreach ($params as $key => $value) {
    // ignore empty values or empty arrays etc
    if (CRM_Utils_System::isNull($value)) {
      continue;
    }

    switch ($key) {
      case 'membership_contact_id':
        if (!CRM_Utils_Rule::integer($value)) {
          return civicrm_create_error("contact_id not valid: $value");
        }
        $dao     = new CRM_Core_DAO();
        $qParams = array();
        $svq     = $dao->singleValueQuery("SELECT id FROM civicrm_contact WHERE id = $value",
          $qParams
        );
        if (!$svq) {
          return civicrm_create_error("Invalid Contact ID: There is no contact record with contact_id = $value.");
        }
        $values['contact_id'] = $values['membership_contact_id'];
        unset($values['membership_contact_id']);
        break;

      case 'join_date':
      case 'start_date':
      case 'end_date':
      case 'reminder_date':
      case 'membership_start_date':
      case 'membership_end_date':
        if (!CRM_Utils_Rule::date($value)) {
          return civicrm_create_error("$key not a valid date: $value");
        }

        // make sure we format dates to mysql friendly format
        $values[$key] = CRM_Utils_Date::processDate($value, NULL, FALSE, 'Ymd');
        break;

      case 'membership_type_id':
        if (!CRM_Utils_Array::value($value, CRM_Member_PseudoConstant::membershipType())) {
          return civicrm_create_error('Invalid Membership Type Id');
        }
        $values[$key] = $value;
        break;

      case 'membership_type':
        $membershipTypeId = CRM_Utils_Array::key(ucfirst($value),
          CRM_Member_PseudoConstant::membershipType()
        );
        if ($membershipTypeId) {
          if (CRM_Utils_Array::value('membership_type_id', $values) &&
            $membershipTypeId != $values['membership_type_id']
          ) {
            return civicrm_create_error('Mismatched membership Type and Membership Type Id');
          }
        }
        else {
          return civicrm_create_error('Invalid Membership Type');
        }
        $values['membership_type_id'] = $membershipTypeId;
        break;

      case 'status_id':
        if (!CRM_Utils_Array::value($value, CRM_Member_PseudoConstant::membershipStatus())) {
          return civicrm_create_error('Invalid Membership Status Id');
        }
        $values[$key] = $value;
        break;

      default:
        break;
    }
  }

  _civicrm_custom_format_params($params, $values, 'Membership');


  if ($create) {
    // CRM_Member_BAO_Membership::create() handles membership_start_date,
    // membership_end_date and membership_source. So, if $values contains
    // membership_start_date, membership_end_date  or membership_source,
    // convert it to start_date, end_date or source
    $changes = array(
      'membership_start_date' => 'start_date',
      'membership_end_date' => 'end_date',
      'membership_source' => 'source',
    );

    foreach ($changes as $orgVal => $changeVal) {
      if (isset($values[$orgVal])) {
        $values[$changeVal] = $values[$orgVal];
        unset($values[$orgVal]);
      }
    }
  }

  return NULL;
}

/**
 * This function ensures that we have the right input membership parameters
 *
 *
 * @param array  $params       Associative array of property name/value
 *                             pairs to insert in new membership.
 *
 * @return bool|CRM_Utils_Error
 * @access private
 */
function _civicrm_membership_check_params(&$params) {

  // params should be an array
  if (!is_array($params)) {
    return civicrm_create_error('Params is not an array');
  }

  // cannot create a membership with empty params
  if (empty($params)) {
    return civicrm_create_error('Input Parameters empty');
  }

  $valid = TRUE;
  $error = '';

  // contact id is required for both add and update
  if (!CRM_Utils_Array::value('contact_id', $params)) {
    $valid = FALSE;
    $error .= ' contact_id';
  }

  // check params for membership id during update
  if (CRM_Utils_Array::value('id', $params)) {
    require_once 'CRM/Member/BAO/Membership.php';
    $membership = new CRM_Member_BAO_Membership();
    $membership->id = $params['id'];
    if (!$membership->find(TRUE)) {
      return civicrm_create_error(ts('Membership id is not valid'));
    }
  }
  else {
    // membership type id Or membership type is required during add
    if (!CRM_Utils_Array::value('membership_type_id', $params) &&
      !CRM_Utils_Array::value('membership_type', $params)
    ) {
      $valid = FALSE;
      $error .= ' membership_type_id Or membership_type';
    }
  }

  // also check for status id if override is set (during add/update)
  if (isset($params['is_override']) &&
    !CRM_Utils_Array::value('status_id', $params)
  ) {
    $valid = FALSE;
    $error .= ' status_id';
  }

  if (!$valid) {
    return civicrm_create_error("Required fields not found for membership $error");
  }

  return array();
}

