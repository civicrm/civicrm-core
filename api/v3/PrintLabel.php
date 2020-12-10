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
 * This api exposes CiviCRM PrintLabel.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Save a PrintLabel.
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_api3_print_label_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params, 'PrintLabel');
}

/**
 * Adjust metadata for navigation create action.
 *
 * @param array $params
 */
function _civicrm_api3_print_label_create_spec(&$params) {
  $params['name']['api.required'] = 1;
}

/**
 * Get a PrintLabel.
 *
 * @param array $params
 *
 * @return array
 *   Array of retrieved PrintLabel property values.
 */
function civicrm_api3_print_label_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Delete a PrintLabel.
 *
 * @param array $params
 *
 * @return array
 *   Array of deleted values.
 */
function civicrm_api3_print_label_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}
