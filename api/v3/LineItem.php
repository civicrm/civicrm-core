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
 * This api exposes CiviCRM LineItem records.
 *
 * Line items are sub-components of a complete financial transaction record.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Create or update a line_item.
 *
 * @param array $params
 *   Array of property name/value pairs to insert in new 'line_item'
 *
 * @return array
 *   api result array
 */
function civicrm_api3_line_item_create($params) {
  $params = CRM_Contribute_BAO_Contribution::checkTaxAmount($params, TRUE);
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Adjust Metadata for Create action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_line_item_create_spec(&$params) {
  $params['entity_id']['api.required'] = 1;
  $params['qty']['api.required'] = 1;
  $params['unit_price']['api.required'] = 1;
  $params['line_total']['api.required'] = 1;
  $params['label']['api.default'] = 'line item';
}

/**
 * Returns array of line_items  matching a set of one or more group properties.
 *
 * @param array $params
 *   Array of one or more valid property_name=>value pairs. If $params is set.
 *   as null, all line_items will be returned (default limit is 25)
 *
 * @return array
 *   Array of matching line_items
 */
function civicrm_api3_line_item_get($params) {
  if (CRM_Financial_BAO_FinancialType::isACLFinancialTypeStatus() && CRM_Utils_Array::value('check_permissions', $params)) {
    CRM_Price_BAO_LineItem::getAPILineItemParams($params);
  }
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Delete an existing LineItem.
 *
 * This method is used to delete any existing LineItem given its id.
 *
 * @param array $params
 *   Array containing id of the group to be deleted.
 *
 * @return array
 *   API result array
 */
function civicrm_api3_line_item_delete($params) {
  if (CRM_Financial_BAO_FinancialType::isACLFinancialTypeStatus() && CRM_Utils_Array::value('check_permissions', $params)) {
    CRM_Financial_BAO_FinancialType::getAvailableFinancialTypes($types, CRM_Core_Action::DELETE);
    if (empty($params['financial_type_id'])) {
      $params['financial_type_id'] = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_LineItem', $params['id'], 'financial_type_id');
    }
    if (!in_array($params['financial_type_id'], array_keys($types))) {
      throw new API_Exception('You do not have permission to delete this line item');
    }
  }
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}
