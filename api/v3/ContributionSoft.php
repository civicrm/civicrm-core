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
 * This api exposes CiviCRM soft credits.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Create or Update a Soft Credit.
 *
 * @param array $params
 *   Array per getfields metadata.
 *
 * @return array
 *   API result array
 */
function civicrm_api3_contribution_soft_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params, 'ContributionSoft');
}

/**
 * Adjust Metadata for Create action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_contribution_soft_create_spec(&$params) {
  $params['contribution_id']['api.required'] = 1;
  $params['contact_id']['api.required'] = 1;
  $params['amount']['api.required'] = 1;
}

/**
 * Deletes an existing Soft Credit.
 *
 * @param array $params
 *
 * @return array
 *   Api formatted result.
 *
 * @throws CRM_Core_Exception
 */
function civicrm_api3_contribution_soft_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Retrieve one or more Soft Credits.
 *
 * @param array $params
 *   Array per getfields metadata.
 *
 * @return array
 *   API result
 */
function civicrm_api3_contribution_soft_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}
