<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
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
 * File for the CiviCRM APIv3 activity contact functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_ActivityContact
 *
 * @copyright CiviCRM LLC (c) 2004-2014
 * @version $Id: ActivityContact.php 2014-04-01 elcapo $
 */

/**
 *  Add a record relating a contact with an activity
 *
 * Allowed @params array keys are:
 *
 * @example ActivityContact.php
 *
 * @param $params
 *
 * @return array of newly created activity contact records.
 * @access public
 */
function civicrm_api3_activity_contact_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Adjust Metadata for Create action
 *
 * The metadata is used for setting defaults, documentation & validation
 * @param array $params array or parameters determined by getfields
 */
function _civicrm_api3_activity_contact_create_spec(&$params) {
  $params['contact_id']['api.required'] = 1;
  $params['activity_id']['api.required'] = 1;
}

/**
 * Deletes an existing ActivityContact record
 *
 * @param  array  $params
 *
 * @return array Api Result
 *
 * @example ActivityContact.php
 * @access public
 */
function civicrm_api3_activity_contact_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Get a ActivityContact.
 *
 * @example ActivityContact.php
 *
 * @param  array $params  an associative array of name/value pairs.
 *
 * @return  array details of found tags else error
 *
 * @access public
 */
function civicrm_api3_activity_contact_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}
