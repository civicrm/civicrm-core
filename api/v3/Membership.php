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
 *
 * File for the CiviCRM APIv3 membership contact functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Membership
 *
 * @copyright CiviCRM LLC (c) 2004-2014
 * @version $Id: MembershipContact.php 30171 2010-10-14 09:11:27Z mover $
 */

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
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
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
  // check params for membership id during update
  if (!empty($params['id']) && !isset($params['skipStatusCal'])) {
    //don't calculate status on exisiting membership - expect API use to pass them in
    // or leave unchanged
    $params['skipStatusCal'] = 1;
  }
  else {
    // also check for status id if override is set (during add/update)
    if (!empty($params['is_override']) && empty($params['status_id'])) {
      return civicrm_api3_create_error('Status ID required');
    }
  }

  $values = array();
  _civicrm_api3_custom_format_params($params, $values, 'Membership');
  $params = array_merge($params, $values);

  // Fixme: This code belongs in the BAO
  if (empty($params['id']) || !empty($params['num_terms'])) {
    if (empty($params['id'])) {
      $calcDates = CRM_Member_BAO_MembershipType::getDatesForMembershipType(
        $params['membership_type_id'],
        CRM_Utils_Array::value('join_date', $params),
        CRM_Utils_Array::value('start_date', $params),
        CRM_Utils_Array::value('end_date', $params),
        CRM_Utils_Array::value('num_terms', $params, 1)
      );
    }
    else {
      $calcDates = CRM_Member_BAO_MembershipType::getRenewalDatesForMembershipType(
        $params['id'],
        NULL,
        CRM_Utils_Array::value('membership_type_id', $params),
        $params['num_terms']
      );
    }
    foreach (array('join_date', 'start_date', 'end_date') as $date) {
      if (empty($params[$date]) && isset($calcDates[$date])) {
        $params[$date] = $calcDates[$date];
      }
    }
  }

  // Fixme: This code belongs in the BAO
  $action = CRM_Core_Action::ADD;
  // we need user id during add mode
    $ids = array ();
    if (!empty($params['contact_id'])) {
      $ids['userId'] = $params['contact_id'];
    }
  //for edit membership id should be present
  if (!empty($params['id'])) {
    $ids['membership'] = $params['id'];
    $action = CRM_Core_Action::UPDATE;
  }
  //need to pass action to handle related memberships.
  $params['action'] = $action;

  if (empty($params['line_item']) && !empty($params['membership_type_id'])) {
    CRM_Price_BAO_LineItem::getLineItemArray($params, NULL, 'membership', $params['membership_type_id']);
  }

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
  $params['membership_type_id']['api.required'] = 1;
  $params['is_test']['api.default'] = 0;
  $params['membership_type_id']['api.aliases'] = array('membership_type');
  $params['status_id']['api.aliases'] = array('membership_status');
  $params['skipStatusCal'] = array(
    'title' => 'Skip status calculation. By default this is 0 if id is not set and 1 if it is set.'
  );
  $params['num_terms'] = array(
    'title' => 'Number of terms to add/renew. If this parameter is passed, dates will be calculated automatically. If no id is passed (new membership) and no dates are given, num_terms will be assumed to be 1.',
    'type' => CRM_Utils_Type::T_INT,
  );
}
/**
 * Adjust Metadata for Get action
 *
 * The metadata is used for setting defaults, documentation & validation
 * @param array $params array or parameters determined by getfields
 */
function _civicrm_api3_membership_get_spec(&$params) {
  $params['membership_type_id']['api.aliases'] = array('membership_type');
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
  $activeOnly = $membershipTypeId = $membershipType = NULL;

  $contactID = CRM_Utils_Array::value('contact_id', $params);
  if (!empty($params['filters']) && is_array($params['filters']) && isset($params['filters']['is_current'])) {
    $activeOnly = $params['filters']['is_current'];
    unset($params['filters']['is_current']);
  }
  $activeOnly = CRM_Utils_Array::value('active_only', $params, $activeOnly);
  if ($activeOnly && empty($params['status_id'])) {
    $params['status_id'] = array('IN' => CRM_Member_BAO_MembershipStatus::getMembershipStatusCurrent());
  }

  $options = _civicrm_api3_get_options_from_params($params, TRUE,'membership', 'get');
  if ($options['is_count']) {
    return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
  }
  $membershipValues = _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params, FALSE, 'Membership');

  $return = $options['return'];
  if(empty($membershipValues) ||
    (!empty($return)
      && !array_key_exists('related_contact_id', $return)
      && !array_key_exists('relationship_name', $return)
    )
    ) {
    return civicrm_api3_create_success($membershipValues, $params, 'membership', 'get');
  }

  $members =  _civicrm_api3_membership_relationsship_get_customv2behaviour($params, $membershipValues, $contactID );
  return civicrm_api3_create_success($members, $params, 'membership', 'get');
}

/**
 * When we copied apiv3 from api v2 we brought across some custom behaviours - in the case of
 * membership a complicated return array is constructed. The original
 * behaviour made contact_id a required field. We still need to keep this for v3 when contact_id
 * is passed in as part of the reasonable expectation developers have that we will keep the api
 * as stable as possible
 *
 * @param array $params parameters passed into get function
 * @param $membershipTypeId
 * @param $activeOnly
 *
 * @return array result for calling function
 */
function _civicrm_api3_membership_get_customv2behaviour(&$params, $membershipTypeId, $activeOnly) {
  // get the membership for the given contact ID
  $membershipParams = array('contact_id' => $params['contact_id']);
  if ($membershipTypeId) {
    $membershipParams['membership_type_id'] = $membershipTypeId;
  }
  $membershipValues = array();
  CRM_Member_BAO_Membership::getValues($membershipParams, $membershipValues, $activeOnly);
  return $membershipValues;
}


/**
 * non-standard behaviour inherited from v2
 *
 * @param array $params parameters passed into get function
 * @param $membershipValues
 * @param $contactID
 *
 * @return array result for calling function
 */
function _civicrm_api3_membership_relationsship_get_customv2behaviour(&$params, $membershipValues, $contactID) {
  $relationships = array();
  foreach ($membershipValues as $membershipId => $values) {
    // populate the membership type name for the membership type id
    $membershipType = CRM_Member_BAO_MembershipType::getMembershipTypeDetails($values['membership_type_id']);

    $membershipValues[$membershipId]['membership_name'] = $membershipType['name'];

    if (!empty($membershipType['relationship_type_id'])) {
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
  return $members;
}
