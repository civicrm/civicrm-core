<?php
// $Id$

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
 * File for the CiviCRM APIv3 membership contact functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Membership
 *
 * @copyright CiviCRM LLC (c) 2004-2013
 * @version $Id: MembershipContact.php 30171 2010-10-14 09:11:27Z mover $
 */

/**
 * Files required for this package
 */

require_once 'CRM/Utils/Rule.php';
require_once 'CRM/Utils/Array.php';

/**
 * Deletes an existing contact membership
 *
 * This API is used for deleting a contact membership
 *
 * @param  $params array  array holding id - Id of the contact membership to be deleted
 *
 * @return array api result
 * {@getfields membership_delete}
 * @access public
 */
function civicrm_api3_membership_delete($params) {

  // membershipID should be numeric
  // this check should be done @ wrapper level
  if (!is_numeric($params['id'])) {
    return civicrm_api3_create_error('Input parameter should be numeric');
  }

  CRM_Member_BAO_Membership::deleteRelatedMemberships($params['id']);

  $membership = new CRM_Member_BAO_Membership();
  $result = $membership->deleteMembership($params['id']);

  return $result ? civicrm_api3_create_success() : civicrm_api3_create_error('Error while deleting Membership');

}

/**
 * modify metadata
 */
function _civicrm_api3_membership_delete_spec(&$params) {
  // set as not required as membership_id also acceptable & no either/or std yet
  $params['id']['api.required'] = 1;
  $params['id']['api.aliases'] = array('membership_id');
}

/**
 * Create a Contact Membership
 *
 * This API is used for creating a Membership for a contact.
 * Required parameters : membership_type_id and status_id.
 *
 * @param   array  $params     an associative array of name/value property values of civicrm_membership
 *
 * @return array of newly created membership property values.
 * {@getfields membership_create}
 * @access public
 */
function civicrm_api3_membership_create($params) {
// @todo shouldn't be required - should be handling by api.aliases & api.required in _spec
  civicrm_api3_verify_one_mandatory($params, NULL, array('membership_type_id', 'membership_type'));
  // check params for membership id during update
  if (CRM_Utils_Array::value('id', $params) && !isset($params['skipStatusCal'])) {
    //don't calculate dates on exisiting membership - expect API use to pass them in
    // or leave unchanged
    $params['skipStatusCal'] = 1;
  }
  else {
    // also check for status id if override is set (during add/update)
    if (isset($params['is_override']) &&
      !CRM_Utils_Array::value('status_id', $params)
    ) {
      return civicrm_api3_create_error('Status ID required');
    }
  }


  $values = array();
  $error = _civicrm_api3_membership_format_params($params, $values);

  if (civicrm_error($error)) {
    return $error;
  }
  _civicrm_api3_custom_format_params($params, $values, 'Membership');
  $params = array_merge($params, $values);


  $action = CRM_Core_Action::ADD;
  // we need user id during add mode
    $ids = array ();
    if(CRM_Utils_Array::value('contact_id',$params)){
      $ids['userId'] = $params['contact_id'];
    }
  //for edit membership id should be present
  if (CRM_Utils_Array::value('id', $params)) {
      $ids['membership'] = $params['id'];
    $action = CRM_Core_Action::UPDATE;
  }

  //need to pass action to handle related memberships.
  $params['action'] = $action;


  $membershipBAO = CRM_Member_BAO_Membership::create($params, $ids, TRUE);

  if (array_key_exists('is_error', $membershipBAO)) {
    // In case of no valid status for given dates, $membershipBAO
    // is going to contain 'is_error' => "Error Message"
    return civicrm_api3_create_error(ts('The membership can not be saved, no valid membership status for given dates'));
  }

  $membership = array();
  _civicrm_api3_object_to_array($membershipBAO, $membership[$membershipBAO->id]);

  return civicrm_api3_create_success($membership, $params, 'membership', 'create', $membershipBAO);

}

/**
 * Adjust Metadata for Create action
 *
 * The metadata is used for setting defaults, documentation & validation
 * @param array $params array or parameters determined by getfields
 */
function _civicrm_api3_membership_create_spec(&$params) {
  $params['contact_id']['api.required'] = 1;
  $params['skipStatusCal'] = array('title' => 'skip status calculation. By default this is 0 if id is not set and 1 if it is set');
}
/**
 * Get contact membership record.
 *
 * This api will return the membership records for the contacts
 * having membership based on the relationship with the direct members.
 *
 * @param  Array $params key/value pairs for contact_id and some
 *          options affecting the desired results; has legacy support
 *          for just passing the contact_id itself as the argument
 *
 * @return  Array of all found membership property values.
 * @access public
 * @todo needs some love - basically only a get for a given contact right now
 * {@getfields membership_get}
 */
function civicrm_api3_membership_get($params) {
  $contactID = $activeOnly = $membershipTypeId = $membershipType = NULL;

  $contactID = CRM_Utils_Array::value('contact_id', $params);
  if (is_array(CRM_Utils_Array::value('filters', $params)) && !empty($params['filters'])) {
    $activeOnly = CRM_Utils_Array::value('is_current', $params['filters'], FALSE);
  }
  $activeOnly = CRM_Utils_Array::value('active_only', $params, $activeOnly);
  //@todo replace this by handling in API layer - we should have enough info to do this
  // between pseudoconstant & fk - see comments in format_params
  $membershipTypeId = CRM_Utils_Array::value('membership_type_id', $params);
  if (!$membershipTypeId) {
    $membershipType = CRM_Utils_Array::value('membership_type', $params);
    if ($membershipType) {
      $membershipTypeId = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipType',
        $membershipType, 'id', 'name'
      );
    }
  }
    if(CRM_Utils_Array::value('contact_id',$params)){
      $membershipValues = _civicrm_api3_membership_get_customv2behaviour($params, $contactID, $membershipTypeId, $activeOnly );
  }
    else{
      //legacy behaviour only ever worked when contact_id passed in - use standard api function otherwise
    $membershipValues = _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params, FALSE);
  }


  if (empty($membershipValues)) {
    # No results is NOT an error!
    return civicrm_api3_create_success($membershipValues, $params);
  }

  $relationships = array();
  foreach ($membershipValues as $membershipId => $values) {
    // populate the membership type name for the membership type id
    $membershipType = CRM_Member_BAO_MembershipType::getMembershipTypeDetails($values['membership_type_id']);

    $membershipValues[$membershipId]['membership_name'] = $membershipType['name'];

    if (CRM_Utils_Array::value('relationship_type_id', $membershipType)) {
      $relationships[$membershipType['relationship_type_id']] = $membershipId;
    }

    // populating relationship type name.
    $relationshipType = new CRM_Contact_BAO_RelationshipType();
    $relationshipType->id = CRM_Utils_Array::value('relationship_type_id', $membershipType);
    if ($relationshipType->find(TRUE)) {
      $membershipValues[$membershipId]['relationship_name'] = $relationshipType->name_a_b;
    }

    _civicrm_api3_custom_data_get($membershipValues[$membershipId], 'Membership', $membershipId, NULL, $values['membership_type_id']);
  }

  $members = $membershipValues;

  // populating contacts in members array based on their relationship with direct members.
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
          $members[$membershipId]['related_contact_id'] = $relationship->contact_id_a;
        }
      }

    }
  }

  return civicrm_api3_create_success($members, $params, 'membership', 'get');

}


/**
 * @deprecated
 * Deprecated function to support membership create. Do not call this. It will be removed in favour of
 * wrapper layer formatting
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
function _civicrm_api3_membership_format_params($params, &$values, $create = FALSE) {

  $fields = CRM_Member_DAO_Membership::fields();
  _civicrm_api3_store_values($fields, $params, $values);

  foreach ($params as $key => $value) {
    // ignore empty values or empty arrays etc
    if (CRM_Utils_System::isNull($value)) {
      continue;
    }

    switch ($key) {

      case 'membership_type':
        // @todo we still need to adequately figure out how to handle this @ the API layer.
        // it is an FK & a pseudoconstant - we should probably alias it onto membership_type_id &
        // then in the validate_integer function do an if(!is_integer && $fieldInfo['pseudoconstant) look
        // up pseudoconstant & flip it over. By the time it hits api it will be a valid membership_type & handling @
        // api layer not required
        $membershipTypeId = CRM_Utils_Array::key(ucfirst($value),
          CRM_Member_PseudoConstant::membershipType()
        );
        if ($membershipTypeId) {
          if (CRM_Utils_Array::value('membership_type_id', $values) &&
            $membershipTypeId != $values['membership_type_id']
          ) {
            return civicrm_api3_create_error('Mismatched membership Type and Membership Type Id');
          }
        }
        else {
          return civicrm_api3_create_error('Invalid Membership Type');
        }
        $values['membership_type_id'] = $membershipTypeId;
        break;
      default:
        break;
    }
  }

  return NULL;
}

/**
 * When we copied apiv3 from api v2 we brought across some custom behaviours - in the case of
 * membership a complicated return array is constructed. The original
 * behaviour made contact_id a required field. We still need to keep this for v3 when contact_id
 * is passed in as part of the reasonable expectation developers have that we will keep the api
 * as stable as possible
 *
 * @param array $params parameters passed into get function
 * @return array result for calling function
 */
function _civicrm_api3_membership_get_customv2behaviour(&$params, $contactID, $membershipTypeId, $activeOnly ){
    // get the membership for the given contact ID
    require_once 'CRM/Member/BAO/Membership.php';
    $membershipParams = array( 'contact_id' => $contactID );
    if ( $membershipTypeId ) {
      $membershipParams['membership_type_id'] = $membershipTypeId;
    }
    $membershipValues = array();
    CRM_Member_BAO_Membership::getValues( $membershipParams, $membershipValues, $activeOnly );
    return $membershipValues;
}
