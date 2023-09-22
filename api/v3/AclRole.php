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
 * This api exposes CiviCRM AclRole.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Save an AclRole.
 *
 * @param array $params
 *
 * @return array
 *   API result array
 */
function civicrm_api3_acl_role_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params, 'ACLEntityRole');
}

/**
 * AclRole create metadata.
 *
 * @param array $params
 */
function _civicrm_api3_acl_role_create_spec(&$params) {
  $params['is_active']['api.default'] = 1;
}

/**
 * Get an AclRole.
 *
 * @param array $params
 *
 * @return array
 *   API result array
 */
function civicrm_api3_acl_role_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Delete an AclRole.
 *
 * @param array $params
 *
 * @return array
 *   API result array
 */
function civicrm_api3_acl_role_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}
