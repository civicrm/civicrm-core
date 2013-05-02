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
 * File for the CiviCRM APIv3 group nesting functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Group
 *
 * @copyright CiviCRM LLC (c) 2004-2013
 * @version $Id: GroupNesting.php 21624 2009-08-07 22:02:55Z wmorgan $
 *
 */

require_once 'CRM/Contact/BAO/GroupNesting.php';

/**
 * Provides group nesting record(s) given parent and/or child id.
 *
 * @param array $params  an array containing at least child_group_id or parent_group_id
 * {@getfields GroupNesting_get}
 *
 * @return  array  list of group nesting records
 */
function civicrm_api3_group_nesting_get($params) {

  return _civicrm_api3_basic_get('CRM_Contact_DAO_GroupNesting', $params);
}

/**
 * Creates group nesting record for given parent and child id.
 * Parent and child groups need to exist.
 *
 * @param array $params parameters array - allowed array keys include:
 *
 * @return array TBD
 * {@getfields GroupNesting_create
 * @todo Work out the return value.
 */
function civicrm_api3_group_nesting_create($params) {

  CRM_Contact_BAO_GroupNesting::add($params['parent_group_id'], $params['child_group_id']);

  // FIXME: CRM_Contact_BAO_GroupNesting requires some work
  $result = array('is_error' => 0);
  return civicrm_api3_create_success($result, $params);
}

/**
 * Adjust Metadata for Create action
 * 
 * The metadata is used for setting defaults, documentation & validation
 * @param array $params array or parameters determined by getfields
 */
function _civicrm_api3_group_nesting_create_spec(&$params) {
  $params['child_group_id']['api.required'] = 1;
  $params['parent_group_id']['api.required'] = 1;
}

/**
 * Removes specific nesting records.
 *
 * @param array $params parameters array - allowed array keys include:
 * {@getfields GroupNesting_delete}
 *
 * @return array API Success or fail array
 *
 * @todo Work out the return value.
 */
function civicrm_api3_group_nesting_delete($params) {

  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

