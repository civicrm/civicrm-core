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
 * Retrieve one or more job log record.
 *
 * @param array $params
 *   input parameters
 *
 * @return array
 */
function civicrm_api3_job_log_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Create one or more job log record.
 *
 * @param array $params
 *   input parameters
 *
 * @return array
 */
function civicrm_api3_job_log_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params, 'JobLog');
}

/**
 * Delete one or more job log record.
 *
 * @param array $params
 *   input parameters
 *
 * @return array
 */
function civicrm_api3_job_log_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params, 'JobLog');
}
