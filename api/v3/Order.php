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
 * @throws \CiviCRM_API3_Exception
 */
function civicrm_api3_order_get(array $params): array {
  $contributions = [];
  $params['api.line_item.get'] = ['qty' => ['<>' => 0]];
  $isSequential = FALSE;
  if (!empty($params['sequential'])) {
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
 * Adjust Metadata for Get action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_order_get_spec(array &$params) {
  $params['id']['api.aliases'] = ['order_id'];
  $params['id']['title'] = ts('Contribution / Order ID');
}

/**
 * Add or update a Order.
 *
 * @param array $params
 *   Input parameters.
 *
 * @return array
 *   Api result array
 *
 * @throws \CiviCRM_API3_Exception
 * @throws API_Exception
 */
function civicrm_api3_order_create(array $params): array {
  civicrm_api3_verify_one_mandatory($params, NULL, ['line_items', 'total_amount']);

  $values = CRM_Financial_BAO_Order::create($params);

  return civicrm_api3_create_success($values, $params, 'Order', 'create');
}

/**
 * Delete a Order.
 *
 * @param array $params
 *   Input parameters.
 *
 * @return array
 * @throws API_Exception
 * @throws CiviCRM_API3_Exception
 */
function civicrm_api3_order_delete(array $params): array {
  $contribution = civicrm_api3('Contribution', 'get', [
    'return' => ['is_test'],
    'id' => $params['id'],
  ]);
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
 * @throws \CiviCRM_API3_Exception
 */
function civicrm_api3_order_cancel(array $params) {
  $contributionStatuses = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');
  $params['contribution_status_id'] = array_search('Cancelled', $contributionStatuses);
  $result = civicrm_api3('Contribution', 'create', $params);
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
function _civicrm_api3_order_cancel_spec(array &$params) {
  $params['contribution_id'] = [
    'api.required' => 1,
    'title' => 'Contribution ID',
    'type' => CRM_Utils_Type::T_INT,
  ];
}

/**
 * Adjust Metadata for Create action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_order_create_spec(array &$params) {
  $params = array_merge($params, CRM_Financial_BAO_Order::fields());
}

/**
 * Adjust Metadata for Delete action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_order_delete_spec(array &$params) {
  $params['contribution_id'] = [
    'api.required' => TRUE,
    'title' => 'Contribution ID',
    'type' => CRM_Utils_Type::T_INT,
  ];
  $params['id']['api.aliases'] = ['contribution_id'];
}
