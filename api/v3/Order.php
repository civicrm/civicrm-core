<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * TODO's
 * 1. Add failure function in each api call
 * 2. Add Validation mentioned in wiki for create/update call
 * 3. Add code for Update call
 */
/**
 * This api exposes CiviCRM Order records.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Add or update a Order.
 *
 * @param array $params
 *   Input parameters.
 *
 * @throws API_Exception
 * @return array
 *   Api result array
 */
function civicrm_api3_order_create(&$params) {
  $contribution = array();
  $entity = NULL;
  if (CRM_Utils_Array::value('line_items', $params)) {
    $entityParams = CRM_Utils_Array::value('params', $params['line_items'], array());
    if (CRM_Utils_Array::value('line_item', $params['line_items'])) {
      $params['line_item'] = $params['line_items']['line_item'];
      if (!empty($params['line_item'])) {
        $entity = str_replace('civicrm_', '', $params['line_item']['entity_table']);
      }
    }
    if ($entityParams) {
    }
    else {
      if (in_array($entity, array('participant', 'membership'))) {
        $entityResult = civicrm_api3($entity, 'create', $entityParams);
        $params['contribution_mode'] = $entity;
        $params[$entity . '_id'] = $entityResult['id'];
      }
      else {
        // pledge payment
      }
    }
  }  
  $contribution = civicrm_api3('Contribution', 'create', $params);
  // add payments
  if ($entity && CRM_Utils_Array::value('id', $contribution)) {
    $paymentParams = array(
      'contribution_id' => $contribution['id'],
      $entity . '_id' => $entityResult['id'],
    );
    // if entity is pledge then build pledge param
    if ($entity == 'pledge') {
      $paymentParams += $entityParams;
    }
    $payments = civicrm_api3($entity . '_payment', 'create', $paymentParams);
  }
  return civicrm_api3_create_success($result['values'], $params, 'Order', 'create');
}

/**
 * Delete a Order.
 *
 * @param array $params
 *   Input parameters.
 *
 * @return array
 */
function civicrm_api3_order_delete($params) {
  $result = civicrm_api3('Contribution', 'delete', $params);
  return civicrm_api3_create_success($result['values'], $params, 'Order', 'delete'); 
}

/**
 * Cancel a Order.
 *
 * @param array $params
 *   Input parameters.
 *
 * @return array
 */
function civicrm_api3_order_cancel($params) {
  $contributionStatuses = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');
  $params['contribution_status_id'] = array_search('Cancelled', $contributionStatuses);
  $result = civicrm_api3('Contribution', 'create', $params);
  if (!$result['is_error']) {
    CRM_Contribute_BAO_Contribution::transitionComponents($params);
  }
  return civicrm_api3_create_success($result['values'], $params, 'Order', 'cancel'); 
}

/**
 * Retrieve a set of Order.
 *
 * @param array $params
 *  Input parameters.
 *
 * @return array
 *   Array of Order, if error an array with an error id and error message
 */
function civicrm_api3_order_get($params) {
  $contributions = array();
  $params['api.line_item.get'] = array('qty' => array('<>' => 0));
  $result = civicrm_api3('Contribution', 'get', $params);
  if (!empty($result['values'])) {
    foreach ($result['values'] as $key => $contribution) {
      $contributions[$key] = $contribution;
      $contributions[$key]['line_items'] = $contribution['api.line_item.get']['values'];
      unset($contributions[$key]['api.line_item.get']);
    }
  }
  return civicrm_api3_create_success($contributions, $params, 'Order', 'get');  
}

/**
 * Adjust Metadata for Get action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_order_get_spec(&$params) {
  $params['contribution_id'] = array(
    'api.required' => 1 ,
    'title' => 'Contribution ID',
    'type' => CRM_Utils_Type::T_INT,
  );
}

/**
 * Adjust Metadata for Delete action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_order_delete_spec(&$params) {
  $params['contribution_id'] = array(
    'api.required' => 1 ,
    'title' => 'Contribution ID',
    'type' => CRM_Utils_Type::T_INT,
  );
  $params['id']['api.aliases'] = array('contribution_id');
}

/**
 * Adjust Metadata for Delete action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_order_cancel_spec(&$params) {
  $params['contribution_id'] = array(
    'api.required' => 1 ,
    'title' => 'Contribution ID',
    'type' => CRM_Utils_Type::T_INT,
  );
}