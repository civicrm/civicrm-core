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
 * This api exposes CiviCRM mail settings.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Create or update a MailSettings.
 *
 * @param array $params
 *   name/value pairs to insert in new 'MailSettings'
 *
 * @return array
 *   API result array.
 */
function civicrm_api3_mail_settings_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params, 'MailSettings');
}

/**
 * Adjust Metadata for Create action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_mail_settings_create_spec(&$params) {
}

/**
 * Returns array of MailSettings matching a set of one or more properties.
 *
 * @param array $params
 *   Array of one or more property_name=>value pairs.
 *   If $params is set as null, all MailSettings will be returned.
 *
 * @return array
 *   API result array.
 */
function civicrm_api3_mail_settings_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Delete an existing MailSettings.
 *
 * @param array $params
 *   [id]
 *
 * @return array
 *   API result array.
 */
function civicrm_api3_mail_settings_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}
