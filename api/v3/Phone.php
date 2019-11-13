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
 * This api exposes CiviCRM phone records.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Add an Phone for a contact.
 *
 * @param array $params
 *   Array per getfields metadata.
 *
 * @return array
 */
function civicrm_api3_phone_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params, 'Phone');
}

/**
 * Adjust Metadata for Create action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_phone_create_spec(&$params) {
  $params['contact_id']['api.required'] = 1;
  $params['phone']['api.required'] = 1;
  // hopefully change to use handleprimary
  $params['is_primary']['api.default'] = 0;
  $defaultLocation = CRM_Core_BAO_LocationType::getDefault();
  if ($defaultLocation) {
    $params['location_type_id']['api.default'] = $defaultLocation->id;
  }
}

/**
 * Delete an existing Phone.
 *
 * @param array $params
 *   Array per getfields metadata.
 *
 * @return array
 *   Api Result
 */
function civicrm_api3_phone_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 *  civicrm_api('Phone','Get') to retrieve one or more phones is implemented by
 *  function civicrm_api3_phone_get ($params) into the file Phone/Get.php
 *  Could have been implemented here in this file too, but we moved it to illustrate the feature with a real usage.
 */
