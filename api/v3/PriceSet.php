<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
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
  $result = _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params);
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
  // Fetch associated entities
  foreach ($result as &$item) {
    $item['entity'] = CRM_Price_BAO_PriceSet::getUsedBy($item['id'], 'entity');
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
