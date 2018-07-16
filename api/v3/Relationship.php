<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2017                                |
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
 * This api exposes CiviCRM relationships.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Add or update a relationship.
 *
 * @param array $params
 *   Input parameters.
 *
 * @throws API_Exception
 *
 * @return array
 *   API Result Array
 */
function civicrm_api3_relationship_create($params) {
  _civicrm_api3_handle_relationship_type($params);
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params, 'Relationship');
}

/**
 * Adjust Metadata for Create action.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_relationship_create_spec(&$params) {
  $params['contact_id_a']['api.required'] = 1;
  $params['contact_id_b']['api.required'] = 1;
  $params['relationship_type_id']['api.required'] = 1;
  $params['is_active']['api.default'] = 1;
}

/**
 * Delete a relationship.
 *
 * @param array $params
 *
 * @return array
 *   API Result Array
 */
function civicrm_api3_relationship_delete($params) {

  if (!CRM_Utils_Rule::integer($params['id'])) {
    return civicrm_api3_create_error('Invalid value for relationship ID');
  }

  $relationBAO = new CRM_Contact_BAO_Relationship();
  $relationBAO->id = $params['id'];
  if (!$relationBAO->find(TRUE)) {
    return civicrm_api3_create_error('Relationship id is not valid');
  }
  else {
    $relationBAO->del($params['id']);
    return civicrm_api3_create_success('Deleted relationship successfully');
  }
}

/**
 * Get one or more relationship/s.
 *
 * @param array $params
 *   Input parameters.
 *
 * @todo  Result is inconsistent depending on whether contact_id is passed in :
 * -  if you pass in contact_id - it just returns all relationships for 'contact_id'
 * -  if you don't pass in contact_id then it does a filter on the relationship table (DAO based search)
 *
 * @return array
 *   API Result Array
 */
function civicrm_api3_relationship_get($params) {
  $options = _civicrm_api3_get_options_from_params($params);

  if (empty($params['contact_id'])) {
    if (!empty($params['membership_type_id']) && empty($params['relationship_type_id'])) {
      CRM_Contact_BAO_Relationship::membershipTypeToRelationshipTypes($params);
    }
    $relationships = _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params, FALSE, 'Relationship');
  }
  else {
    $relationships = CRM_Contact_BAO_Relationship::getRelationship($params['contact_id'],
      CRM_Utils_Array::value('status_id', $params),
      0,
      CRM_Utils_Array::value('is_count', $options),
      CRM_Utils_Array::value('id', $params),
      NULL,
      NULL,
      FALSE,
      $params
    );
  }
  //perhaps we should add a 'getcount' but at this stage lets just handle getcount output
  if ($options['is_count']) {
    return array('count' => $relationships);
  }
  foreach ($relationships as $relationshipId => $values) {
    _civicrm_api3_custom_data_get($relationships[$relationshipId], CRM_Utils_Array::value('check_permissions', $params), 'Relationship', $relationshipId, NULL, CRM_Utils_Array::value('relationship_type_id', $values));
  }
  return civicrm_api3_create_success($relationships, $params);
}

/**
 * Legacy handling for relationship_type parameter.
 *
 * @param array $params
 *   Associative array of property name/value.
 *   pairs to insert in new contact.
 */
function _civicrm_api3_handle_relationship_type(&$params) {
  if (empty($params['relationship_type_id']) && !empty($params['relationship_type'])) {
    $relationTypes = CRM_Core_PseudoConstant::relationshipType('name');
    foreach ($relationTypes as $relationshipTypeId => $relationshipValue) {
      if (CRM_Utils_Array::key(ucfirst($params['relationship_type']), $relationshipValue)) {
        $params['relationship_type_id'] = $relationshipTypeId;
      }
    }
  }
}

/**
 * Hack to ensure inherited membership got created/deleted on
 * relationship add/delete respectively.
 *
 * @param array $params
 *   Array per getfields metadata.
 *
 * @return array
 */
function civicrm_api3_relationship_setvalue($params) {
  require_once 'api/v3/Generic/Setvalue.php';
  $result = civicrm_api3_generic_setValue(array("entity" => 'Relationship', 'params' => $params));

  if (empty($result['is_error']) && CRM_Utils_String::munge($params['field']) == 'is_active') {
    $action = CRM_Core_Action::DISABLE;
    if ($params['value'] == TRUE) {
      $action = CRM_Core_Action::ENABLE;
    }
    CRM_Contact_BAO_Relationship::disableEnableRelationship($params['id'], $action);
  }
  return $result;
}

function _civicrm_api3_relationship_getoptions_spec(&$params) {
  $params['field']['options']['relationship_type_id'] = ts('Relationship Type ID');

  // Add parameters for limiting relationship type ID
  $relationshipTypePrefix = ts('(For relationship_type_id only) ');
  $params['contact_id'] = [
    'title' => ts('Contact ID'),
    'description' => $relationshipTypePrefix . ts('Limits options to those'
      . ' available to give contact'),
    'type' => CRM_Utils_Type::T_INT,
    'FKClassName' => 'CRM_Contact_DAO_Contact',
    'FKApiName' => 'Contact',
  ];
  $params['relationship_direction'] = [
    'title' => ts('Relationship Direction'),
    'description' => $relationshipTypePrefix . ts('For relationships where the '
      . 'name is the same for both sides (i.e. "Spouse Of") show the option '
      . 'from "A" (origin) side or "B" (target) side of the relationship?'),
    'type' => CRM_Utils_Type::T_STRING,
    'options' => ['a_b' => 'a_b', 'b_a' => 'b_a'],
    'api.default' => 'a_b',
  ];
  $params['relationship_id'] = [
    'title' => ts('Reference Relationship ID'),
    'description' => $relationshipTypePrefix . ts('If provided alongside '
      . 'contact ID it will be used to establish the contact type of the "B" '
      . 'side of the relationship and limit options based on it. If the '
      . 'provided contact ID does not match the "A" side of this relationship '
      . 'then the "A" side of this relationship will be used to limit options'),
    'type' => CRM_Utils_Type::T_INT,
    'FKClassName' => 'CRM_Contact_DAO_Relationship',
    'FKApiName' => 'Relationship',
  ];
  $contactTypes = CRM_Contact_BAO_ContactType::contactTypes();
  $params['contact_type'] = [
    'title' => ts('Contact Type'),
    'description' => $relationshipTypePrefix . ts('Limits options to those '
    . 'available to this contact type. Overridden by the contact type of '
    . 'contact ID (if provided)'),
    'type' => CRM_Utils_Type::T_STRING,
    'options' => array_combine($contactTypes, $contactTypes),
  ];
  $params['is_form'] = [
    'title' => ts('Is Form?'),
    'description' => $relationshipTypePrefix . ts('Formats the options for use'
      . ' in a form if true. The format is &lt;id&gt;_a_b => &lt;label&gt;'),
    'type' => CRM_Utils_Type::T_BOOLEAN
  ];
}
