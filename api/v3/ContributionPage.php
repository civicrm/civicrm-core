<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.6                                                |
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
 * @subpackage API_ContributionPage
 * @copyright CiviCRM LLC (c) 20042012
 */

/**
 * Create or update a contribution_page.
 *
 * @param array $params
 *   Array per getfields metadata.
 *
 * @return array
 *   api result array
 */
function civicrm_api3_contribution_page_create($params) {
  $result = _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params);
  CRM_Contribute_PseudoConstant::flush('contributionPageAll');
  CRM_Contribute_PseudoConstant::flush('contributionPageActive');
  return $result;
}

/**
 * Adjust Metadata for Create action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array per getfields metadata.
 */
function _civicrm_api3_contribution_page_create_spec(&$params) {
  $params['financial_type_id']['api.required'] = 1;
  $params['payment_processor']['api.aliases'] = array('payment_processor_id');
  $params['is_active']['api.default'] = 1;
}

/**
 * Returns array of contribution_pages matching a set of one or more group properties.
 *
 * @param array $params
 *   Array per getfields metadata.
 *
 * @return array
 *   API Result array Array of matching contribution_pages
 */
function civicrm_api3_contribution_page_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Delete an existing contribution_page.
 *
 * This method is used to delete any existing contribution_page. id of the group
 * to be deleted is required field in $params array
 *
 * @param array $params
 *   Array per getfields metadata.
 *
 * @return array
 *   API result Array
 */
function civicrm_api3_contribution_page_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Submit a contribution_page.
 *
 * @param array $params
 *   Array per getfields metadata.
 *
 * @return array
 *   API result array
 */
function civicrm_api3_contribution_page_submit($params) {
  $result = CRM_Contribute_Form_Contribution_Confirm::submit($params);
  return civicrm_api3_create_success($result, $params, 'contribution_page', 'submit');
}
