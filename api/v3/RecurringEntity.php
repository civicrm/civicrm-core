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
 * This api exposes CiviCRM recurring entity records.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Retrieve a recurring entity.
 *
 * @param array $params
 *   Input parameters.
 *
 * @return array
 */
function civicrm_api3_recurring_entity_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Adjust Metadata for Get action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_recurring_entity_get_spec(&$params) {
  $params['entity_table']['options'] = [
    'civicrm_event' => 'civicrm_event',
    'civicrm_activity' => 'civicrm_activity',
  ];
}

/**
 * Add or update a recurring entity.
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_api3_recurring_entity_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params, 'RecurringEntity');
}

/**
 * Adjust Metadata for Create action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_recurring_entity_create_spec(&$params) {
  $params['entity_table']['options'] = [
    'civicrm_event' => 'civicrm_event',
    'civicrm_activity' => 'civicrm_activity',
  ];
  $params['entity_table']['api.required'] = 1;
}

/**
 * Deletes an existing ReportInstance.
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_api3_recurring_entity_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}
