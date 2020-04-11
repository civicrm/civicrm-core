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
 * This api exposes CiviCRM IM records.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Add an IM for a contact.
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_api3_im_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params, 'Im');
}

/**
 * Adjust Metadata for Create action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_im_create_spec(&$params) {
  $params['contact_id']['api.required'] = 1;
  $defaultLocation = CRM_Core_BAO_LocationType::getDefault();
  if ($defaultLocation) {
    $params['location_type_id']['api.default'] = $defaultLocation->id;
  }
}

/**
 * Deletes an existing IM.
 *
 * @param array $params
 *
 * @return array
 *   API result Array
 */
function civicrm_api3_im_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Retrieve one or more IM.
 *
 * @param array $params
 *   An associative array of name/value pairs.
 *
 * @return array
 *   details of found IM
 */
function civicrm_api3_im_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}
