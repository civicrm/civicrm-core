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
 * Provide meta-data for this api.
 *
 * @param array $params
 */
function _civicrm_api3_generic_validate_spec(&$params) {
  $params['action']['api.required'] = TRUE;
  $params['action']['title'] = ts('API Action');
}

/**
 * Generic api wrapper used for validation of entity-action pair.
 *
 * @param array $apiRequest
 *
 * @return mixed
 */
function civicrm_api3_generic_validate($apiRequest) {
  $errors = _civicrm_api3_validate($apiRequest['entity'], $apiRequest['params']['action'], $apiRequest['params']);

  return civicrm_api3_create_success($errors, $apiRequest['params'], $apiRequest['entity'], 'validate');
}
