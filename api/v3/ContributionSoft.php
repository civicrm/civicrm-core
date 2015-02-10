<?php
// $Id$

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
 * File for the CiviCRM APIv3 soft credit functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_ContributionSoft
 *
 * @copyright CiviCRM LLC (c) 2004-2014
 * @version $Id: ContributionSoft.php 2013-05-01 Jon Goldberg $
 */

/**
 * Create or Update a Soft Credit
 *
 * @param array $params  Associative array of property
 *                       name/value pairs to insert in new 'contribution_soft'
 *
 * @example ContributionSoftCreate.php Standard Create Example //FIXME
 *
 * @return array API result array
 * {@getfields contribution_soft_create}
 * @access public
 */
function civicrm_api3_contribution_soft_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Adjust Metadata for Create action
 *
 * The metadata is used for setting defaults, documentation & validation
 * @param array $params array or parameters determined by getfields
 */
function _civicrm_api3_contribution_soft_create_spec(&$params) {
  $params['contribution_id']['api.required'] = 1;
  $params['contact_id']['api.required'] = 1;
  $params['amount']['api.required'] = 1;
}

/**
 * Deletes an existing Soft Credit
 *
 * @param  array  $params
 *
 * @example ContributionSoftDelete.php Standard Delete Example
 *
 * @return boolean | error  true if successfull, error otherwise
 * {@getfields contribution_soft_delete}
 * @access public
 */
function civicrm_api3_contribution_soft_delete($params) {
  // non standard BAO - we have to write custom code to cope
  CRM_Contribute_BAO_ContributionSoft::del(array('id' => $params['id']));

}

/**
 * Retrieve one or more Soft Credits
 *
 * @param  array input parameters
 *
 *
 * @example ContributionSoftGet.php Standard Get Example
 *
 * @param  array $params  an associative array of name/value pairs.
 *
 * @return  array api result
 * {@getfields contribution_soft_get}
 * @access public
 */
function civicrm_api3_contribution_soft_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

