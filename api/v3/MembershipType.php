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
 * This api exposes CiviCRM membership type.
 *
 * @package CiviCRM_APIv3
 */

/**
 * API to Create or update a Membership Type.
 *
 * @param array $params
 *   Array of name/value property values of civicrm_membership_type.
 *
 * @return array
 *   API result array.
 */
function civicrm_api3_membership_type_create($params) {
  // Workaround for fields using nonstandard serialization
  foreach (['relationship_type_id', 'relationship_direction'] as $field) {
    if (isset($params[$field]) && is_array($params[$field])) {
      $params[$field] = implode(CRM_Core_DAO::VALUE_SEPARATOR, $params[$field]);
    }
  }
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params, 'MembershipType');
}

/**
 * Adjust Metadata for Create action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_membership_type_create_spec(&$params) {
  $params['domain_id']['api.default'] = CRM_Core_Config::domainID();
  $params['member_of_contact_id']['api.required'] = 1;
  $params['financial_type_id']['api.required'] = 1;
  $params['name']['api.required'] = 1;
  $params['duration_unit']['api.required'] = 1;
  $params['duration_interval']['api.required'] = 1;
  $params['period_type']['api.required'] = 1;
  $params['is_active']['api.default'] = 1;
}

/**
 * Get a Membership Type.
 *
 * This api is used for finding an existing membership type.
 *
 * @param array $params
 *   Array of name/value property values of civicrm_membership_type.
 *
 * @return array
 *   API result array.
 */
function civicrm_api3_membership_type_get($params) {
  $results = _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
  if (!empty($results['values']) && is_array($results['values'])) {
    foreach ($results['values'] as &$item) {
      // Workaround for fields using nonstandard serialization
      foreach (['relationship_type_id', 'relationship_direction'] as $field) {
        if (isset($item[$field]) && !is_array($item[$field])) {
          // @todo - this should be handled by the serialization now...
          $item[$field] = (array) $item[$field];
        }
      }
    }
  }
  return $results;
}

/**
 * Adjust Metadata for Get action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_membership_type_get_spec(&$params) {
  $params['domain_id']['api.default'] = CRM_Core_Config::domainID();
}

/**
 * Adjust input for getlist action.
 *
 * We want to only return active membership types for getlist. It's a bit
 * arguable whether this should be applied at the 'get' level but, since it's hard
 * to unset we'll just do it here.
 *
 * The usage of getlist is entity-reference fields & the like
 * so using only active ones makes sense.
 *
 * @param array $request
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_membership_type_getlist_params(&$request) {
  _civicrm_api3_generic_getlist_params($request);
  if (!isset($request['params']['is_active']) && empty($request['params']['id'])) {
    $request['params']['is_active'] = 1;
  }
}

/**
 * Deletes an existing membership type.
 *
 * @param array $params
 *
 * @return array
 *   API result array.
 */
function civicrm_api3_membership_type_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}
