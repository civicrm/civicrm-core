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
 * This api exposes CiviCRM dashboard contacts.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Creates/Updates a new Dashboard Contact Entry.
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_api3_dashboard_contact_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params, 'DashboardContact');
}

/**
 * Gets a CiviCRM Dashlets of Contacts according to parameters.
 *
 * @param array $params
 *   Array per getfields metadata.
 *
 * @return array
 */
function civicrm_api3_dashboard_contact_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Adjust Metadata for Create action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $fields
 *   Array of fields determined by getfields.
 */
function _civicrm_api3_dashboard_contact_create_spec(&$fields) {
  $fields['dashboard_id']['api.required'] = TRUE;
}

/**
 * Delete an existing dashboard-contact.
 *
 * @param array $params
 *
 * @return array
 * @throws \CRM_Core_Exception
 */
function civicrm_api3_dashboard_contact_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}
