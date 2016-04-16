<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 * This api exposes CiviCRM premiums.
 *
 * Premiums are used as incentive gifts on contribution pages.
 * Premiums contain "Products" which has a separate api.
 * Use chaining to create a premium and related products in one api call.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Save a premium.
 *
 * @param array $params
 *
 * @throws API_Exception
 * @return array
 */
function civicrm_api3_premium_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Get a premium.
 *
 * @param array $params
 *
 * @return array
 *   Array of retrieved premium property values.
 */
function civicrm_api3_premium_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Delete a premium.
 *
 * @param array $params
 *
 * @throws API_Exception
 * @return array
 *   Array of deleted values.
 */
function civicrm_api3_premium_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Return field specification specific to get requests.
 *
 * @param array $params
 */
function _civicrm_api3_premium_get_spec(&$params) {
  $params['premiums_active']['api.aliases'] = array('is_active');
}

/**
 * Return field specification specific to create requests.
 *
 * @param array $params
 */
function _civicrm_api3_premium_create_spec(&$params) {
  $params['premiums_active']['api.aliases'] = array('is_active');
}
