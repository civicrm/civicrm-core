<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
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
 * This api exposes CiviCRM relationship types.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Create relationship type.
 *
 * @param array $params
 *   Array per getfields metadata.
 *
 * @return array
 */
function civicrm_api3_relationship_type_create($params) {

  if (!isset($params['label_a_b'])) {

    $params['label_a_b'] = $params['name_a_b'];
  }

  if (!isset($params['label_b_a'])) {

    $params['label_b_a'] = $params['name_b_a'];
  }

  $ids = array();
  if (isset($params['id']) && !CRM_Utils_Rule::integer($params['id'])) {
    return civicrm_api3_create_error('Invalid value for relationship type ID');
  }
  else {
    $ids['relationshipType'] = CRM_Utils_Array::value('id', $params);
  }

  $relationType = CRM_Contact_BAO_RelationshipType::add($params, $ids);

  $relType = array();

  _civicrm_api3_object_to_array($relationType, $relType[$relationType->id]);

  return civicrm_api3_create_success($relType, $params, 'RelationshipType', 'create', $relationType);
}

/**
 * Adjust Metadata for Create action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_relationship_type_create_spec(&$params) {
  $params['contact_type_a']['api.required'] = 1;
  $params['contact_type_b']['api.required'] = 1;
  $params['name_a_b']['api.required'] = 1;
  $params['name_b_a']['api.required'] = 1;
  $params['is_active']['api.default'] = 1;
}

/**
 * Get all relationship types.
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_api3_relationship_type_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Delete a relationship type.
 *
 * @param array $params
 *
 * @return array
 *   API Result Array
 */
function civicrm_api3_relationship_type_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Get list defaults for relationship types.
 *
 * @see _civicrm_api3_generic_getlist_defaults
 *
 * @param array $request
 *
 * @return array
 */
function _civicrm_api3_relationship_type_getlist_defaults($request) {
  return array(
    'label_field' => 'label_a_b',
    'search_field' => 'label_a_b',
  );
}
