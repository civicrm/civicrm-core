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
 * This api exposes CiviCRM email records.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Add an Email for a contact.
 *
 * @param array $params
 *   Array per getfields metadata.
 *
 * @return array
 *   API result array
 *
 * @throws \CRM_Core_Exception
 * @throws \Civi\API\Exception\UnauthorizedException
 */
function civicrm_api3_email_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params, 'Email');
}

/**
 * Adjust Metadata for Create action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_email_create_spec(&$params) {
  $params['is_primary']['api.default'] = 0;
  $params['email']['api.required'] = 1;
  $params['contact_id']['api.required'] = 1;
  $defaultLocation = CRM_Core_BAO_LocationType::getDefault();
  if ($defaultLocation) {
    $params['location_type_id']['api.default'] = $defaultLocation->id;
  }
}

/**
 * Deletes an existing Email.
 *
 * @param array $params
 *   Array per getfields metadata.
 *
 * @return array
 *   API result array.
 *
 * @throws \CRM_Core_Exception
 * @throws \Civi\API\Exception\UnauthorizedException
 */
function civicrm_api3_email_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Retrieve one or more emails.
 *
 * @param array $params
 *   Array per getfields metadata.
 *
 * @return array
 *   api result array
 */
function civicrm_api3_email_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Set default getlist parameters.
 *
 * @see _civicrm_api3_generic_getlist_defaults
 *
 * @param array $request
 *
 * @return array
 */
function _civicrm_api3_email_getlist_defaults(&$request) {
  return [
    'description_field' => [
      'email',
    ],
    'params' => [
      'on_hold' => 0,
      'contact_id.is_deleted' => 0,
      'contact_id.is_deceased' => 0,
      'contact_id.do_not_email' => 0,
    ],
    // Note that changing this to display name affects query performance. The label field is used
    // for sorting & mysql will prefer to use the index on the ORDER BY field. So if this is changed
    // to display name then the filtering will bypass the index. In testing this took around 30 times
    // as long.
    'label_field' => 'contact_id.sort_name',
    // If no results from sort_name try email.
    'search_field' => 'contact_id.sort_name',
    'search_field_fallback' => 'email',
  ];

}
