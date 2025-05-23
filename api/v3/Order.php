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

use Civi\Api4\Membership;

/**
 * Retrieve a set of Order.
 *
 * @param array $params
 *  Input parameters.
 *
 * @return array
 *   Array of Order, if error an array with an error id and error message
 * @throws \CRM_Core_Exception
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
 * @throws \CRM_Core_Exception
 * @throws CRM_Core_Exception
 */
function civicrm_api3_order_create(array $params): array {
  civicrm_api3_verify_one_mandatory($params, NULL, ['line_items', 'total_amount']);
  if (empty($params['skipCleanMoney'])) {
    // We have to do this for v3 api - sadly. For v4 it will be no more.
    foreach (['total_amount', 'net_amount', 'fee_amount', 'non_deductible_amount'] as $field) {
      if (isset($params[$field])) {
        $params[$field] = CRM_Utils_Rule::cleanMoney($params[$field]);
      }
    }
    $params['skipCleanMoney'] = TRUE;
  }
  $params['contribution_status_id'] = 'Pending';
  $order = new CRM_Financial_BAO_Order();
  $order->setDefaultFinancialTypeID($params['financial_type_id'] ?? NULL);

  if (!empty($params['line_items']) && is_array($params['line_items'])) {
    foreach ($params['line_items'] as $index => $lineItems) {
      if (!empty($lineItems['params'])) {
        $order->setEntityParameters($lineItems['params'], $index);
      }
      foreach ($lineItems['line_item'] as $innerIndex => $lineItem) {
        // For historical reasons it might be name.
        if (!empty($lineItem['membership_type_id']) && !is_numeric($lineItem['membership_type_id'])) {
          $lineItem['membership_type_id'] = CRM_Core_PseudoConstant::getKey('CRM_Member_BAO_Membership', 'membership_type_id', $lineItems['params']['membership_type_id']);
        }
        $lineIndex = $index . '+' . $innerIndex;
        if (!empty($lineItem['financial_type_id']) && !is_numeric($lineItem['financial_type_id'])) {
          $lineItem['financial_type_id'] = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'financial_type_id', $lineItem['financial_type_id']);
        }
        $order->setLineItem($lineItem, $lineIndex);
        $order->addLineItemToEntityParameters($lineIndex, $index);
      }
    }
  }
  else {
    $order->setPriceSetToDefault('contribution');
    $order->setOverrideTotalAmount((float) $params['total_amount']);
    $order->setLineItem([], 0);
  }
  // Only check the amount if line items are set because that is what we have historically
  // done and total amount is historically only inclusive of tax_amount IF
  // tax amount is also passed in it seems
  if (isset($params['total_amount']) && !empty($params['line_items'])) {
    $currency = $params['currency'] ?? CRM_Core_Config::singleton()->defaultCurrency;
    if (!CRM_Utils_Money::equals($params['total_amount'], $order->getTotalAmount(), $currency)) {
      throw new CRM_Contribute_Exception_CheckLineItemsException();
    }
  }
  $params['total_amount'] = $order->getTotalAmount();
  if (!isset($params['tax_amount'])) {
    // @todo always calculate tax amount - left for now
    // for webform
    $params['tax_amount'] = $order->getTotalTaxAmount();
  }

  foreach ($order->getEntitiesToCreate() as $entityParams) {
    if ($entityParams['entity'] === 'participant') {
      if (isset($entityParams['participant_status_id'])
        && (!CRM_Event_BAO_ParticipantStatusType::getIsValidStatusForClass($entityParams['participant_status_id'], 'Pending'))) {
        throw new CRM_Core_Exception('Creating a participant via the Order API with a non "pending" status is not supported');
      }
      $entityParams['participant_status_id'] ??= 'Pending from incomplete transaction';
      $entityParams['status_id'] = $entityParams['participant_status_id'];
      $entityParams['skipLineItem'] = TRUE;
      $entityResult = civicrm_api3('Participant', 'create', $entityParams);
      // @todo - once membership is cleaned up & financial validation tests are extended
      // we can look at removing this - some weird handling in removeFinancialAccounts
      $params['contribution_mode'] = 'participant';
      $params['participant_id'] = $entityResult['id'];
      foreach ($entityParams['line_references'] as $lineIndex) {
        $order->setLineItemValue('entity_id', $entityResult['id'], $lineIndex);
      }
    }

    if ($entityParams['entity'] === 'membership') {
      if (empty($entityParams['id'])) {
        $entityParams['status_id:name'] = 'Pending';
      }
      if (!empty($params['contribution_recur_id'])) {
        $entityParams['contribution_recur_id'] = $params['contribution_recur_id'];
      }
      // At this stage we need to get this passed through.
      $entityParams['version'] = 4;
      _order_create_wrangle_membership_params($entityParams);

      $membershipID = Membership::save($params['check_permissions'] ?? FALSE)->setRecords([$entityParams])->execute()->first()['id'];
      foreach ($entityParams['line_references'] as $lineIndex) {
        $order->setLineItemValue('entity_id', $membershipID, $lineIndex);
      }
    }
  }

  $params['line_item'][$order->getPriceSetID()] = $order->getLineItems();

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
 * @throws CRM_Core_Exception
 * @throws CRM_Core_Exception
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
    throw new CRM_Core_Exception('Only test orders can be deleted.');
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
 * @throws \CRM_Core_Exception
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

/**
 * Handle possibility of v3 style params.
 *
 * We used to call v3 Membership.create. Now we call v4.
 * This converts membership input parameters.
 *
 * @param array $membershipParams
 *
 * @throws \CRM_Core_Exception
 */
function _order_create_wrangle_membership_params(array &$membershipParams) {
  $fields = Membership::getFields(FALSE)->execute()->indexBy('name');
  // Ensure this legacy parameter is not true.
  $membershipParams['skipStatusCal'] = FALSE;
  foreach ($fields as $fieldName => $field) {
    $customFieldName = 'custom_' . ($field['custom_field_id'] ?? NULL);
    if ($field['type'] === ['Custom'] && isset($membershipParams[$customFieldName])) {
      $membershipParams[$field['custom_group'] . '.' . $field['custom_field']] = $membershipParams[$customFieldName];
      unset($membershipParams[$customFieldName]);
    }

    if (!empty($membershipParams[$fieldName]) && $field['data_type'] === 'Integer' && !is_numeric($membershipParams[$fieldName])) {
      $membershipParams[$field['name'] . ':name'] = $membershipParams[$fieldName];
      unset($membershipParams[$field['name']]);
    }
  }
}
