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
 * This api exposes CiviCRM sms_provider records.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Save an sms_provider.
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_api3_sms_provider_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params, 'SmsProvider');
}

/**
 * Adjust Metadata for Create action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_sms_provider_create_spec(&$params) {
  $params['domain_id']['api.default'] = CRM_Core_Config::domainID();
}

/**
 * Get an sms_provider.
 *
 * @param array $params
 *
 * @return array
 *   Array of retrieved sms_provider property values.
 */
function civicrm_api3_sms_provider_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Delete an sms_provider.
 *
 * @param array $params
 *
 * @return array
 *   Array of deleted values.
 */
function civicrm_api3_sms_provider_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}
