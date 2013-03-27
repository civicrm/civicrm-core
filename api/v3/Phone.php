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
 * File for the CiviCRM APIv3 phone functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Phone
 *
 * @copyright CiviCRM LLC (c) 2004-2013
 * @version $Id: Phone.php 2011-03-16 ErikHommel $
 */

/**
 * Include utility functions
 */
require_once 'CRM/Core/BAO/Phone.php';

/**
 *  Add an Phone for a contact
 *
 * Allowed @params array keys are:
 * {@getfields phone_create}
 * @example PhoneCreate.php
 *
 * @return array of newly created phone property values.
 * @access public
 */
function civicrm_api3_phone_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Adjust Metadata for Create action
 * 
 * The metadata is used for setting defaults, documentation & validation
 * @param array $params array or parameters determined by getfields
 */
function _civicrm_api3_phone_create_spec(&$params) {
  $params['contact_id']['api.required'] = 1;
  $params['phone']['api.required'] = 1;
  // hopefully change to use handleprimary
  $params['is_primary']['api.default'] = 0;
}

/**
 * Deletes an existing Phone
 *
 * @param  array  $params
 *
 * @return array Api Result
 * {@getfields phone_delete}
 * @example PhoneDelete.php
 * @access public
 */
function civicrm_api3_phone_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 *  civicrm_api('Phone','Get') to retrieve one or more phones is implemented by
 *  function civicrm_api3_phone_get ($params) into the file Phone/Get.php
 *  Could have been implemented here in this file too, but we moved it to illustrate the feature with a real usage.
 *
 */

