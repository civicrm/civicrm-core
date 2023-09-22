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
 * Get a Dedupe Exception.
 *
 * @param array $params
 *   Array per getfields metadata.
 *
 * @return array
 *   Array of all found dedupe exception object property values.
 */
function civicrm_api3_exception_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Create or update an dedupe exception.
 *
 * @param array $params
 *          Array per getfields metadata.
 *
 * @return array api result array
 */
function civicrm_api3_exception_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params, 'DedupeException');
}

/**
 * Delete an existing Exception.
 *
 * This method is used to delete any existing Exception given its id.
 *
 * @param array $params
 *          [id]
 *
 * @return array api result array
 */
function civicrm_api3_exception_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}
