<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+ */

/**
 * This api exposes CiviCRM EntityBatch records.
 *
 * Use this api to add/remove entities from a batch.
 * To create/update/delete the batches themselves, use the Batch api.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Get entity batches.
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_api3_entity_batch_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Adjust Metadata for Create action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_entity_batch_create_spec(&$params) {
  $params['entity_id']['api.required'] = 1;
  $params['batch_id']['api.required'] = 1;
}

/**
 * Create an entity batch.
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_api3_entity_batch_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params, 'EntityBatch');
}

/**
 * Mark entity batch as removed.
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_api3_entity_batch_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}
