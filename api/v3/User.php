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
 * This api exposes CiviCRM the user framework user account.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Get details about the CMS User entity.
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_api3_user_get($params) {
  if (empty($params['contact_id'])) {
    $params['contact_id'] = civicrm_api3('UFMatch', 'getvalue', [
      'uf_id' => $params['id'],
      'domain_id' => CRM_Core_Config::domainID(),
      'return' => 'contact_id',
    ]);
  }
  $result = CRM_Core_Config::singleton()->userSystem->getUser($params['contact_id']);
  $result['contact_id'] = $params['contact_id'];
  return civicrm_api3_create_success(
    [$result['id'] => $result],
    $params,
    'user',
    'get'
  );

}

/**
 * Adjust Metadata for Get action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_user_get_spec(&$params) {
  // At this stage contact-id is required - we may be able to loosen this.
  $params['contact_id'] = [
    'title' => 'Contact ID',
    'type' => CRM_Utils_Type::T_INT,
    'api.required' => 1,
  ];
  $params['id'] = [
    'title' => 'CMS User ID',
    'type' => CRM_Utils_Type::T_INT,
  ];
  $params['name'] = [
    'title' => 'Username',
    'type' => CRM_Utils_Type::T_STRING,
  ];
}
