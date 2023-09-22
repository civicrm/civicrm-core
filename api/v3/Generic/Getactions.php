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
 * @package CiviCRM_APIv3
 */

/**
 * Get available api actions.
 *
 * @param array $apiRequest
 *
 * @return array
 * @throws CRM_Core_Exception
 */
function civicrm_api3_generic_getActions($apiRequest) {
  civicrm_api3_verify_mandatory($apiRequest, NULL, ['entity']);
  $mfp = \Civi::service('magic_function_provider');
  $actions = $mfp->getActionNames($apiRequest['version'], $apiRequest['entity']);
  return civicrm_api3_create_success($actions, $apiRequest['params'], $apiRequest['entity'], 'getactions');
}
