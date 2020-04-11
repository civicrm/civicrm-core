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
 * This api exposes CiviCRM Batch records.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Save a Batch.
 *
 * @param array $params
 *
 * @return array
 * @throws \API_Exception
 * @throws \Civi\API\Exception\UnauthorizedException
 */
function civicrm_api3_batch_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params, 'Batch');
}

/**
 * Adjust Metadata for Create action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_batch_create_spec(&$params) {
  //@todo - the entity table field looks like it is not actually required & should probably be removed (or a description added if
  // it is actually required)
  $params['entity_table']['api.default'] = "civicrm_batch";
  $params['entity_table']['type'] = CRM_Utils_Type::T_STRING;
  $params['entity_table']['title'] = 'Batch Entity Table - remove?';
  $params['created_id']['api.default'] = 'user_contact_id';
  $params['created_date']['api.default'] = 'now';
  $params['modified_id']['api.default'] = 'user_contact_id';
  $params['modified_date']['api.default'] = 'now';
  $params['status_id']['api.required'] = 1;
  $params['title']['api.required'] = 1;
  $params['status_id']['api.required'] = 1;
}

/**
 * Get a Batch.
 *
 * @param array $params
 *
 * @return array
 *   Array of retrieved batch property values.
 */
function civicrm_api3_batch_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Delete a Batch.
 *
 * @param array $params
 *
 * @return array
 *   Array of deleted values.
 * @throws \API_Exception
 * @throws \Civi\API\Exception\UnauthorizedException
 */
function civicrm_api3_batch_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}
