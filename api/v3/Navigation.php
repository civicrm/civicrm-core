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
 * This api exposes CiviCRM Navigation BAO.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Adjust metadata for navigation reset action.
 *
 * @param array $params
 */
function _civicrm_api3_navigation_reset_spec(&$params) {
  $params['for']['api.required'] = TRUE;
  $params['for']['title'] = "Is this reset for all navigation or reports";
  $params['for']['type'] = CRM_Utils_Type::T_STRING;
  $params['for']['options'] = [
    'all' => 'General Navigation rebuild from xml',
    'report' => 'Reset report menu to default structure',
  ];
  $params['domain_id']['api.default'] = CRM_Core_Config::domainID();
  $params['domain_id']['type'] = CRM_Utils_Type::T_INT;
  $params['domain_id']['title'] = 'Domain ID';
}

/**
 * Reset navigation.
 *
 * @param array $params
 *   Array of name/value pairs.
 *
 * @return array
 *   API result array.
 */
function civicrm_api3_navigation_reset($params) {
  if ($params['for'] == 'report') {
    CRM_Core_BAO_Navigation::rebuildReportsNavigation($params['domain_id']);
  }
  CRM_Core_BAO_Navigation::resetNavigation();
  return civicrm_api3_create_success(1, $params, 'navigation', 'reset');
}

/**
 * Adjust metadata for navigation get action.
 *
 * @param array $params
 */
function _civicrm_api3_navigation_get_spec(&$params) {
}

/**
 * Reset navigation.
 *
 * @param array $params
 *   Array of name/value pairs.
 *
 * @return array
 *   API result array.
 */
function civicrm_api3_navigation_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Create navigation item.
 *
 * @param array $params
 *   Array of name/value pairs.
 *
 * @return array
 *   API result array.
 */
function civicrm_api3_navigation_create($params) {
  civicrm_api3_verify_one_mandatory($params, NULL, ['name', 'label']);
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params, 'Navigation');
}

/**
 * Adjust metadata for navigation create action.
 *
 * @param array[] $fields
 */
function _civicrm_api3_navigation_create_spec(&$fields) {
  $fields['is_active']['api.default'] = TRUE;
  $fields['domain_id']['api.default'] = CRM_Core_Config::domainID();
}

/**
 * Delete navigation item.
 *
 * @param array $params
 *   Array of name/value pairs.
 *
 * @return array
 *   API result array.
 */
function civicrm_api3_navigation_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}
