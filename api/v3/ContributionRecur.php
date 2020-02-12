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
 * This api exposes CiviCRM recurring contributions.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Create or update a ContributionRecur.
 *
 * @param array $params
 *   Array per getfields metadata.
 *
 * @return array
 *   api result array
 */
function civicrm_api3_contribution_recur_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params, 'ContributionRecur');
}

/**
 * Adjust Metadata for Create action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_contribution_recur_create_spec(&$params) {
  $params['contact_id']['api.required'] = 1;
  $params['create_date']['api.default'] = 'now';
  $params['frequency_interval']['api.required'] = 1;
  $params['amount']['api.required'] = 1;
  $params['start_date']['api.default'] = 'now';
  $params['modified_date']['api.default'] = 'now';
}

/**
 * Returns array of contribution_recurs matching a set of one or more group properties.
 *
 * @param array $params
 *   Array of properties. If empty, all records will be returned.
 *
 * @return array
 *   API result Array of matching contribution_recurs
 */
function civicrm_api3_contribution_recur_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Cancel a recurring contribution of existing ContributionRecur given its id.
 *
 * @param array $params
 *   Array containing id of the recurring contribution.
 *
 * @return bool
 *   returns true is successfully cancelled
 */
function civicrm_api3_contribution_recur_cancel($params) {
  return CRM_Contribute_BAO_ContributionRecur::cancelRecurContribution($params) ? civicrm_api3_create_success() : civicrm_api3_create_error(ts('Error while cancelling recurring contribution'));
}

/**
 * Adjust Metadata for Cancel action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_contribution_recur_cancel_spec(&$params) {
  $params['id'] = [
    'title' => ts('Contribution Recur ID'),
    'api.required' => TRUE,
    'type' => CRM_Utils_Type::T_INT,
  ];
}

/**
 * Delete an existing ContributionRecur.
 *
 * This method is used to delete an existing ContributionRecur given its id.
 *
 * @param array $params
 *   [id]
 *
 * @return array
 *   API result array
 */
function civicrm_api3_contribution_recur_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}
