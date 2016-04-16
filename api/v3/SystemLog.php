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
 * This api exposes CiviCRM SystemLog.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Delete system log record.
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_api3_system_log_delete($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params, TRUE, 'SystemLog');
}

/**
 * Create system log record.
 *
 * It's arguable whether this function should exist as it fits our crud pattern and adding it meets our SyntaxConformance test requirements
 * but it just wraps system.log which is more consistent with the PSR3 implemented.
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_api3_system_log_create($params) {
  return civicrm_api3('system', 'log', $params);
}

/**
 * Adjust system log create metadata.
 *
 * @param array $params
 */
function _civicrm_api3_system_log_create_spec(&$params) {
  require_once 'api/v3/System.php';
  _civicrm_api3_system_log_spec($params);
}

/**
 * Get system log record.
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_api3_system_log_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params, TRUE, 'SystemLog');
}
