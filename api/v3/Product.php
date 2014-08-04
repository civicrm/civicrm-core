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
 * File for the CiviCRM APIv3 product functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_product
 *
 */

/**
 * Save a product
 *
 * Allowed @params array keys are:
 * {@getfields product_create}
 * @example productCreate.php
 *
 * @param $params
 *
 * @throws API_Exception
 * @return array of newly created product property values.
 * @access public
 */
function civicrm_api3_product_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Get a product
 *
 * Allowed @params array keys are:
 * {@getfields product_get}
 * @example productCreate.php
 *
 * @param $params
 *
 * @return array of retrieved product property values.
 * @access public
 */
function civicrm_api3_product_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Delete a product
 *
 * Allowed @params array keys are:
 * {@getfields product_delete}
 * @example productCreate.php
 *
 * @param $params
 *
 * @throws API_Exception
 * @return array of deleted values.
 * @access public
 */
function civicrm_api3_product_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}
