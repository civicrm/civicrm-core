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
 * File for the CiviCRM APIv3 country functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Country
 *
 * @copyright CiviCRM LLC (c) 2004-2013
 * @version $Id: Country.php 2011-02-16 ErikHommel $
 */

require_once 'CRM/Core/DAO/Country.php';

/**
 * Add an Country for a contact
 *
 * Allowed @params array keys are:
 *
 * @example CountryCreate.php Standard Create Example
 *
 * @return array API result array
 * {@getfields country_create}
 * @access public
 */
function civicrm_api3_country_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_DAO(__FUNCTION__), $params);
}

/**
 * Adjust Metadata for Create action
 * 
 * The metadata is used for setting defaults, documentation & validation
 * @param array $params array or parameters determined by getfields
 */
function _civicrm_api3_country_create_spec(&$params) {
  $params['name']['api.required'] = 1;
}

/**
 * Deletes an existing Country
 *
 * @param  array  $params
 *
 * @example CountryDelete.php Standard Delete Example
 *
 * @return boolean | error  true if successfull, error otherwise
 * {@getfields country_delete}
 * @access public
 */
function civicrm_api3_country_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_DAO(__FUNCTION__), $params);
}

/**
 * Retrieve one or more countrys
 *
 * @param  array input parameters
 *
 *
 * @example CountryGet.php Standard Get Example
 *
 * @param  array $params  an associative array of name/value pairs.
 *
 * @return  array api result array
 * {@getfields country_get}
 * @access public
 */
function civicrm_api3_country_get($params) {

  return _civicrm_api3_basic_get(_civicrm_api3_get_DAO(__FUNCTION__), $params);
}

