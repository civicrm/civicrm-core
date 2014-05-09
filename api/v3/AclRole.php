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
 * File for the CiviCRM APIv3 acl_role functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_acl_role
 *
 */

/**
 * Save an acl_role
 *
 * Allowed @params array keys are:
 * {@getfields acl_role_create}
 * @example acl_roleCreate.php
 *
 * @param $params
 *
 * @return array of newly created acl_role property values.
 * @access public
 */
function civicrm_api3_acl_role_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Get an acl_role
 *
 * Allowed @params array keys are:
 * {@getfields acl_role_get}
 * @example acl_roleCreate.php
 *
 * @param $params
 *
 * @return array of retrieved acl_role property values.
 * @access public
 */
function civicrm_api3_acl_role_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Delete an acl_role
 *
 * Allowed @params array keys are:
 * {@getfields acl_role_delete}
 * @example acl_roleCreate.php
 *
 * @param $params
 *
 * @return array of deleted values.
 * @access public
 */
function civicrm_api3_acl_role_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}
