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
 * This api exposes CiviCRM Dashboard.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Creates or updates an Dashlet.
 *
 * @param array $params
 *
 * @return array
 *   Array containing 'is_error' to denote success or failure and details of the created activity
 */
function civicrm_api3_dashboard_create($params) {
  civicrm_api3_verify_one_mandatory($params, NULL, [
    'name',
    'label',
    'url',
    'fullscreen_url',
  ]);
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params, 'Dashboard');
}

/**
 * Specify Meta data for create.
 *
 * Note that this data is retrievable via the getfields function
 * and is used for pre-filling defaults and ensuring mandatory requirements are met.
 *
 * @param array $params
 *   array of parameters determined by getfields.
 */
function _civicrm_api3_dashboard_create_spec(&$params) {
  $params['is_active']['api.default'] = 1;
  unset($params['version']);
}

/**
 * Gets a CiviCRM Dashlets according to parameters.
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_api3_dashboard_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Delete a specified Dashlet.
 *
 * @param array $params
 *   Array holding 'id' of dashlet to be deleted.
 * @return array
 * @throws CRM_Core_Exception
 * @throws CRM_Core_Exception
 */
function civicrm_api3_dashboard_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}
