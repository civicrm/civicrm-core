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
 * This api exposes the relationships between organizations and CiviCRM groups.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Get group organization record/s.
 *
 * @param array $params
 *   Name value pair of contact information.
 *
 * @return array
 *   list of groups, given contact subscribed to
 */
function civicrm_api3_group_organization_get($params) {
  return _civicrm_api3_basic_get('CRM_Contact_DAO_GroupOrganization', $params);
}

/**
 * Create group organization record.
 *
 * @param array $params
 *   Array.
 *
 * @return array
 */
function civicrm_api3_group_organization_create($params) {

  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params, 'GroupOrganization');
}

/**
 * Adjust Metadata for Create action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_group_organization_create_spec(&$params) {
  $params['organization_id']['api.required'] = 1;
  $params['group_id']['api.required'] = 1;
}

/**
 * Deletes an existing Group Organization.
 *
 * This API is used for deleting a Group Organization
 *
 * @param array $params
 *   With 'id' = ID of the Group Organization to be deleted.
 *
 * @return array
 *   API Result
 */
function civicrm_api3_group_organization_delete($params) {

  $result = CRM_Contact_BAO_GroupOrganization::deleteGroupOrganization($params['id']);
  return $result ? civicrm_api3_create_success('Deleted Group Organization successfully') : civicrm_api3_create_error('Could not delete Group Organization');
}
