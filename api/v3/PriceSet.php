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
 * This api exposes CiviCRM Price Sets.
 *
 * PriceSets contain PriceFields (which have their own api).
 * Use chaining to create a PriceSet and associated PriceFields in one api call.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Create or update a PriceSet.
 *
 * @param array $params
 *   name/value pairs to insert in new 'PriceSet'
 *
 * @return array
 *   api result array
 */
function civicrm_api3_price_set_create($params) {
  $result = _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params, 'PriceSet');
  // Handle price_set_entity
  if (!empty($result['id']) && !empty($params['entity_table']) && !empty($params['entity_id'])) {
    $entityId = $params['entity_id'];
    if (!is_array($params['entity_id'])) {
      $entityId = explode(',', $entityId);
    }
    foreach ($entityId as $eid) {
      $eid = (int) trim($eid);
      if ($eid) {
        CRM_Price_BAO_PriceSet::addTo($params['entity_table'], $eid, $result['id']);
      }
    }
  }
  return $result;
}

/**
 * Adjust Metadata for Create action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_price_set_create_spec(&$params) {
  $params['title']['api.required'] = TRUE;
}

/**
 * Returns array of price_sets matching a set of one or more group properties.
 *
 * @param array $params
 *   Array of one or more valid property_name=>value pairs. If $params is set.
 *   as null, all price_sets will be returned (default limit is 25)
 *
 * @return array
 *   Array of matching price_sets
 */
function civicrm_api3_price_set_get($params) {
  // hack to make getcount work. - not sure the best approach here
  // as creating an alternate getcount function also feels a bit hacky
  if (isset($params['options'])  && isset($params['options']['is_count'])) {
    return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
  }
  $result = _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params, FALSE);
  // Fetch associated entities if the return has not been previously limited.
  if (!isset($params['return'])) {
    foreach ($result as &$item) {
      $item['entity'] = CRM_Price_BAO_PriceSet::getUsedBy($item['id'], 'entity');
    }
  }
  return civicrm_api3_create_success($result, $params);
}

/**
 * Delete an existing PriceSet.
 *
 * This method is used to delete any existing PriceSet given its id.
 *
 * @param array $params
 *   Array containing id of the group to be deleted.
 *
 * @return array
 *   API result array
 */
function civicrm_api3_price_set_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}
