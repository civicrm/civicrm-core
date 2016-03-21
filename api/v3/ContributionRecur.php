<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
  _civicrm_api3_custom_format_params($params, $values, 'ContributionRecur');
  $params = array_merge($params, $values);
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params);
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
  civicrm_api3_verify_one_mandatory($params, NULL, array('id'));
  return CRM_Contribute_BAO_ContributionRecur::cancelRecurContribution($params['id'], CRM_Core_DAO::$_nullObject) ? civicrm_api3_create_success() : civicrm_api3_create_error(ts('Error while cancelling recurring contribution'));
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
