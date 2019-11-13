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
 * This api exposes CiviCRM report instances.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Retrieve a report instance.
 *
 * @param array $params
 *   Input parameters.
 *
 * @return array
 *   API result array
 */
function civicrm_api3_report_instance_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Add or update a report instance.
 *
 * @param array $params
 *
 * @return array
 *   API result array
 */
function civicrm_api3_report_instance_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params, 'ReportInstance');
}

/**
 * Adjust Metadata for Create action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_report_instance_create_spec(&$params) {
  $params['report_id']['api.required'] = 1;
  $params['title']['api.required'] = 1;
  $params['domain_id']['api.default'] = CRM_Core_Config::domainID();
  $params['view_mode']['api.default'] = 'view';
  $params['view_mode']['title'] = ts('View Mode for Navigation URL');
  $params['view_mode']['type'] = CRM_Utils_Type::T_STRING;
  $params['view_mode']['options'] = [
    'view' => ts('View'),
    'criteria' => ts('Show Criteria'),
  ];
}

/**
 * Deletes an existing ReportInstance.
 *
 * @param array $params
 *
 * @return array
 *   API result array
 */
function civicrm_api3_report_instance_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}
