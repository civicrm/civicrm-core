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
 * This api exposes CiviCRM MailingComponent (header and footer).
 *
 * @package CiviCRM_APIv3
 */

/**
 * Save a MailingComponent.
 *
 * @param array $params
 *
 * @throws CRM_Core_Exception
 * @return array
 *   API result array.
 */
function civicrm_api3_mailing_component_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params, 'MailingComponent');
}

/**
 * Adjust Metadata for Create action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $spec
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_mailing_component_create_spec(&$spec) {
  $spec['is_active']['api.default'] = 1;
  $spec['name']['api.required'] = 1;
  $spec['component_type']['api.required'] = 1;
}

/**
 * Get a MailingComponent.
 *
 * @param array $params
 *
 * @return array
 *   API result array.
 */
function civicrm_api3_mailing_component_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Adjust metadata for get.
 *
 * @param array $params
 */
function _civicrm_api3_mailing_component_get_spec(&$params) {
  // fetch active records by default
  $params['is_active']['api.default'] = 1;
}

/**
 * Delete a MailingComponent.
 *
 * @param array $params
 *
 * @throws CRM_Core_Exception
 * @return array
 *   API result array.
 */
function civicrm_api3_mailing_component_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}
