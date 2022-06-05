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
 * This api exposes CiviCRM Price Set Entity.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Save a Price Set Entity.
 *
 * @param array $params
 *
 * @return array
 * @throws \API_Exception
 * @throws \Civi\API\Exception\UnauthorizedException
 */
function civicrm_api3_price_set_entity_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params, 'PriceSetEntity');
}

/**
 * Get a Price Set Entity.
 *
 * @param array $params
 *
 * @return array
 *   Array of retrieved Price Set Entity property values.
 */
function civicrm_api3_price_set_entity_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Delete a Price Set Entity.
 *
 * @param array $params
 *
 * @return array
 *   Array of deleted values.
 * @throws \API_Exception
 * @throws \Civi\API\Exception\UnauthorizedException
 */
function civicrm_api3_price_set_entity_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}
