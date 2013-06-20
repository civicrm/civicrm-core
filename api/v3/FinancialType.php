<?php
// $Id$

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * File for the CiviCRM APIv3 Financial Type functions
 *
 * @package CiviCRM_APIv3
 *
 * @copyright CiviCRM LLC (c) 2004-2013
 * @version $Id: FinancialType.php 2013-06-20 Lola Slade $
 */

require_once 'CRM/Financial/BAO/FinancialType.php';

/**
 * Add a new Financial Type
 *
 * Allowed @params array keys are: name, description, is_active, is_deductible, is_reserved
 *
 * @return array API result array
 * @access public
 */
function civicrm_api3_financial_type_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Adjust Metadata for Create action
 * 
 * The metadata is used for setting defaults, documentation & validation
 * @param array $params array or parameters determined by getfields
 */
function _civicrm_api3_financial_type_create_spec(&$params) {
  $params['name']['api.required'] = 1;
  $params['is_deductible']['api.default'] = 0;
  $params['is_reserved']['api.default'] = 0;
  $params['is_active']['api.default'] = 1;
}

/**
 * Deletes an existing Financial Type
 *
 * @param  array  $params
 *
 *
 * @return boolean | error  true if successfull, error otherwise
 * @access public
 */
function civicrm_api3_financial_type_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Retrieve one or more Financial Types
 *
 * @param  array $params  an associative array of name/value pairs.
 *
 * @return  array api result array
 * @access public
 */
function civicrm_api3_financial_type_get($params) {

  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

