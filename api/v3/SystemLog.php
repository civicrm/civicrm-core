<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
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
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
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
