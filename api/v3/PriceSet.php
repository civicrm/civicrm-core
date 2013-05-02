<?php
/*
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * File for the CiviCRM APIv3 group functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_PriceSet
 * @copyright CiviCRM LLC (c) 20042012
 */

/**
 * Create or update a price_set
 *
 * @param array $params  Associative array of property
 *                       name/value pairs to insert in new 'price_set'
 * @example PriceSetCreate.php Std Create example
 *
 * @return array api result array
 * {@getfields price_set_create}
 * @access public
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
        CRM_Price_BAO_Set::addTo($params['entity_table'], $eid, $result['id']);
      }
    }
  }
  return $result;
}

/**
 * Adjust Metadata for Create action
 *
 * The metadata is used for setting defaults, documentation & validation
 * @param array $params array or parameters determined by getfields
 */
function _civicrm_api3_price_set_create_spec(&$params) {
}

/**
 * Returns array of price_sets  matching a set of one or more group properties
 *
 * @param array $params Array of one or more valid property_name=>value pairs. If $params is set
 *  as null, all price_sets will be returned (default limit is 25)
 *
 * @return array  Array of matching price_sets
 * {@getfields price_set_get}
 * @access public
 */
function civicrm_api3_price_set_get($params) {
  $result = _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params, FALSE);
  // Fetch associated entities
  foreach ($result as &$item) {
    $item['entity'] = CRM_Price_BAO_Set::getUsedBy($item['id'], 'entity');
  }
  return civicrm_api3_create_success($result, $params);
}

/**
 * delete an existing price_set
 *
 * This method is used to delete any existing price_set. id of the group
 * to be deleted is required field in $params array
 *
 * @param array $params array containing id of the group
 *  to be deleted
 *
 * @return array  returns flag true if successfull, error message otherwise
 * {@getfields price_set_delete}
 * @access public
 */
function civicrm_api3_price_set_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}
