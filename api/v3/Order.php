<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2017                                |
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
 * This api exposes CiviCRM Order objects, an abstract entity
 * comprised of contributions and related line items.
 *
 * @package CiviCRM_APIv3
 */

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
  $isSequential = FALSE;
  if (CRM_Utils_Array::value('sequential', $params)) {
    $params['sequential'] = 0;
    $isSequential = TRUE;
  }
  $result = civicrm_api3('Contribution', 'get', $params);
  if (!empty($result['values'])) {
    foreach ($result['values'] as $key => $contribution) {
      $contributions[$key] = $contribution;
      $contributions[$key]['line_items'] = $contribution['api.line_item.get']['values'];
      unset($contributions[$key]['api.line_item.get']);
    }
  }
  $params['sequential'] = $isSequential;
  return civicrm_api3_create_success($contributions, $params, 'Order', 'get');
}

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
  $entityIds = array();
  if (CRM_Utils_Array::value('line_items', $params) && is_array($params['line_items'])) {
    $priceSetID = NULL;
    CRM_Contribute_BAO_Contribution::checkLineItems($params);
    foreach ($params['line_items'] as $lineItems) {
      $entityParams = CRM_Utils_Array::value('params', $lineItems, array());
      if (!empty($entityParams) && !empty($lineItems['line_item'])) {
        $item = reset($lineItems['line_item']);
        $entity = str_replace('civicrm_', '', $item['entity_table']);
      }
      if ($entityParams) {
        if (in_array($entity, array('participant', 'membership'))) {
          $entityParams['skipLineItem'] = TRUE;
          $entityResult = civicrm_api3($entity, 'create', $entityParams);
          $params['contribution_mode'] = $entity;
          $entityIds[] = $params[$entity . '_id'] = $entityResult['id'];
          foreach ($lineItems['line_item'] as &$items) {
            $items['entity_id'] = $entityResult['id'];
          }
        }
        else {
          // pledge payment
        }
      }
      if (empty($priceSetID)) {
        $item = reset($lineItems['line_item']);
        $priceSetID = civicrm_api3('PriceField', 'getvalue', array(
          'return' => 'price_set_id',
          'id' => $item['price_field_id'],
        ));
        $params['line_item'][$priceSetID] = array();
      }
      $params['line_item'][$priceSetID] = array_merge($params['line_item'][$priceSetID], $lineItems['line_item']);
    }
  }
  $contribution = civicrm_api3('Contribution', 'create', $params);
  // add payments
  if ($entity && CRM_Utils_Array::value('id', $contribution)) {
    foreach ($entityIds as $entityId) {
      $paymentParams = array(
        'contribution_id' => $contribution['id'],
        $entity . '_id' => $entityId,
      );
      // if entity is pledge then build pledge param
      if ($entity == 'pledge') {
        $paymentParams += $entityParams;
      }
      $payments = civicrm_api3($entity . '_payment', 'create', $paymentParams);
    }
  }
  return civicrm_api3_create_success(CRM_Utils_Array::value('values', $contribution), $params, 'Order', 'create');
}

/**
 * Delete a Order.
 *
 * @param array $params
 *   Input parameters.
 * @return array
 * @throws API_Exception
 * @throws CiviCRM_API3_Exception
 */
function civicrm_api3_order_delete($params) {
  $contribution = civicrm_api3('Contribution', 'get', array(
    'return' => array('is_test'),
    'id' => $params['id'],
  ));
  if ($contribution['id'] && $contribution['values'][$contribution['id']]['is_test'] == TRUE) {
    $result = civicrm_api3('Contribution', 'delete', $params);
  }
  else {
    throw new API_Exception('Only test orders can be deleted.');
  }
  return civicrm_api3_create_success($result['values'], $params, 'Order', 'delete');
}

/**
 * Cancel an Order.
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
  CRM_Contribute_BAO_Contribution::transitionComponents($params);
  return civicrm_api3_create_success($result['values'], $params, 'Order', 'cancel');
}

/**
 * Adjust Metadata for Cancel action.
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

/**
 * Adjust Metadata for Create action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_order_create_spec(&$params) {
  $params['contact_id'] = array(
    'name' => 'contact_id',
    'title' => 'Contact ID',
    'type' => CRM_Utils_Type::T_INT,
    'api.required' => TRUE,
  );
  $params['total_amount'] = array(
    'name' => 'total_amount',
    'title' => 'Total Amount',
    'api.required' => TRUE,
  );
  $params['financial_type_id'] = array(
    'name' => 'financial_type_id',
    'title' => 'Financial Type',
    'type' => CRM_Utils_Type::T_INT,
    'api.required' => TRUE,
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
    'api.required' => TRUE,
    'title' => 'Contribution ID',
    'type' => CRM_Utils_Type::T_INT,
  );
  $params['id']['api.aliases'] = array('contribution_id');
}
