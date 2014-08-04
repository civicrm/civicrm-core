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
 * File for the CiviCRM APIv3 print_label functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_print_label
 *
 */

/**
 * Save a print_label
 *
 * Allowed @params array keys are:
 * {@getfields print_label_create}
 * @example print_labelCreate.php
 *
 * @param $params
 *
 * @return array of newly created print_label property values.
 * @access public
 */
function civicrm_api3_print_label_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Get a print_label
 *
 * Allowed @params array keys are:
 * {@getfields print_label_get}
 * @example print_labelCreate.php
 *
 * @param $params
 *
 * @return array of retrieved print_label property values.
 * @access public
 */
function civicrm_api3_print_label_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Delete a print_label
 *
 * Allowed @params array keys are:
 * {@getfields print_label_delete}
 * @example print_labelCreate.php
 *
 * @param $params
 *
 * @return array of deleted values.
 * @access public
 */
function civicrm_api3_print_label_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}
