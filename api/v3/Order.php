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

  $params['contribution_status_id'] = 'Pending';
  $order = new CRM_Financial_BAO_Order();
  $order->setDefaultFinancialTypeID($params['financial_type_id'] ?? NULL);

  if (!empty($params['line_items']) && is_array($params['line_items'])) {
    CRM_Contribute_BAO_Contribution::checkLineItems($params);
    foreach ($params['line_items'] as $index => $lineItems) {
      foreach ($lineItems['line_item'] as $innerIndex => $lineItem) {
        $lineIndex = $index . '+' . $innerIndex;
        $order->setLineItem($lineItem, $lineIndex);
      }

      $entityParams = $lineItems['params'] ?? NULL;

      if ($entityParams && $order->getLineItemEntity($lineIndex) !== 'contribution') {
        switch ($order->getLineItemEntity($lineIndex)) {
          case 'participant':
            if (isset($entityParams['participant_status_id'])
              && (!CRM_Event_BAO_ParticipantStatusType::getIsValidStatusForClass($entityParams['participant_status_id'], 'Pending'))) {
              throw new CiviCRM_API3_Exception('Creating a participant via the Order API with a non "pending" status is not supported');
            }
            $entityParams['participant_status_id'] = $entityParams['participant_status_id'] ?? 'Pending from incomplete transaction';
            $entityParams['status_id'] = $entityParams['participant_status_id'];
            $entityParams['skipLineItem'] = TRUE;
            $entityResult = civicrm_api3('Participant', 'create', $entityParams);
            // @todo - once membership is cleaned up & financial validation tests are extended
            // we can look at removing this - some weird handling in removeFinancialAccounts
            $params['contribution_mode'] = 'participant';
            $params['participant_id'] = $entityResult['id'];
            break;

          case 'membership':
            $entityParams['status_id'] = 'Pending';
            if (!empty($params['contribution_recur_id'])) {
              $entityParams['contribution_recur_id'] = $params['contribution_recur_id'];
            }
            $entityParams['skipLineItem'] = TRUE;
            $entityResult = civicrm_api3('Membership', 'create', $entityParams);
            break;

        }

        foreach ($lineItems['line_item'] as $innerIndex => $lineItem) {
          $lineIndex = $index . '+' . $innerIndex;
          $order->setLineItemValue('entity_id', $entityResult['id'], $lineIndex);
        }
      }
    }
    $priceSetID = $order->getPriceSetID();
    $params['line_item'][$priceSetID] = $order->getLineItems();
  }
  else {
    $order->setPriceSetToDefault('contribution');
  }

  $contributionParams = $params;
  // If this is nested we need to set sequential to 0 as sequential handling is done
  // in create_success & id will be miscalculated...
  $contributionParams['sequential'] = 0;
  foreach ($contributionParams as $key => $value) {
    // Unset chained keys so the code does not attempt to do this chaining twice.
    // e.g if calling 'api.Payment.create' We want to finish creating the order first.
    // it would probably be better to have a full whitelist of contributionParams
    if (substr($key, 0, 3) === 'api') {
      unset($contributionParams[$key]);
    }
  }

  $contribution = civicrm_api3('Contribution', 'create', $contributionParams);
  $contribution['values'][$contribution['id']]['line_item'] = array_values($order->getLineItems());

  return civicrm_api3_create_success($contribution['values'] ?? [], $params, 'Order', 'create');
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
  $params['contact_id'] = [
    'name' => 'contact_id',
    'title' => 'Contact ID',
    'type' => CRM_Utils_Type::T_INT,
    'api.required' => TRUE,
  ];
  $params['total_amount'] = [
    'name' => 'total_amount',
    'title' => 'Total Amount',
  ];
  $params['skipCleanMoney'] = [
    'api.default' => TRUE,
    'title' => 'Do not attempt to convert money values',
    'type' => CRM_Utils_Type::T_BOOLEAN,
  ];
  $params['financial_type_id'] = [
    'name' => 'financial_type_id',
    'title' => 'Financial Type',
    'type' => CRM_Utils_Type::T_INT,
    'api.required' => TRUE,
    'table_name' => 'civicrm_contribution',
    'entity' => 'Contribution',
    'bao' => 'CRM_Contribute_BAO_Contribution',
    'pseudoconstant' => [
      'table' => 'civicrm_financial_type',
      'keyColumn' => 'id',
      'labelColumn' => 'name',
    ],
  ];
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
