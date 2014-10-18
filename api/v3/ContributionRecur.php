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
 * File for the CiviCRM APIv3 group functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_ContributionRecur
 * @copyright CiviCRM LLC (c) 2004-2014
 */

/**
 * Create or update a contribution_recur
 *
 * @param array $params  Associative array of property
 *                       name/value pairs to insert in new 'contribution_recur'
 * @example ContributionRecurCreate.php Std Create example
 *
 * @return array api result array
 * {@getfields contribution_recur_create}
 * @access public
 */
function civicrm_api3_contribution_recur_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Adjust Metadata for Create action
 *
 * The metadata is used for setting defaults, documentation & validation
 * @param array $params array or parameters determined by getfields
 */
function _civicrm_api3_contribution_recur_create_spec(&$params) {
  $params['contact_id']['api.required'] = 1;
  $params['create_date']['api.default'] = 'now';
  $params['frequency_interval']['api.required'] = 1;
  $params['start_date']['api.default'] = 'now';
}

/**
 * Returns array of contribution_recurs  matching a set of one or more group properties
 *
 * @param array $params  Array of one or more valid
 *                       property_name=>value pairs. If $params is set
 *                       as null, all contribution_recurs will be returned
 *
 * @return array  API result Array of matching contribution_recurs
 * {@getfields contribution_recur_get}
 * @access public
 */
function civicrm_api3_contribution_recur_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Cancel a recurring contribution of existing contribution_recur.id
 *
 * @param array    $params (reference) array containing id of the recurring contribution
 *
 * @return boolean  returns true is successfully cancelled
 */

function civicrm_api3_contribution_recur_cancel($params) {
  civicrm_api3_verify_one_mandatory($params, NULL, array('id'));
  return CRM_Contribute_BAO_ContributionRecur::cancelRecurContribution($params['id'], CRM_Core_DAO::$_nullObject) ? civicrm_api3_create_success() : civicrm_api3_create_error(ts('Error while cancelling recurring contribution'));
}

/**
 * delete an existing contribution_recur
 *
 * This method is used to delete any existing contribution_recur. id of the group
 * to be deleted is required field in $params array
 *
 * @param array $params array containing id of the group
 *                       to be deleted
 *
 * @return array API result array
 *                message otherwise
 * {@getfields contribution_recur_delete}
 * @access public
 */
function civicrm_api3_contribution_recur_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}
