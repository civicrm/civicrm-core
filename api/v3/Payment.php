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
 * This api exposes CiviCRM Contribution Payment records.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Retrieve a set of financial transactions which are payments.
 *
 * @param array $params
 *  Input parameters.
 *
 * @return array
 *   Array of financial transactions which are payments, if error an array with an error id and error message
 *
 * @throws \CRM_Core_Exception
 */
function civicrm_api3_payment_get($params) {
  $params['is_payment'] = TRUE;
  $contributionID = $params['entity_id'] ?? NULL;

  // In order to support contribution id we need to do an extra lookup.
  if ($contributionID) {
    $eftParams = [
      'entity_id' => $contributionID,
      'entity_table' => 'civicrm_contribution',
      'options' => ['limit' => 0],
      'financial_trxn_id.is_payment' => 1,
    ];
    $eft = civicrm_api3('EntityFinancialTrxn', 'get', $eftParams)['values'];
    if (empty($eft)) {
      return civicrm_api3_create_success([], $params, 'Payment', 'get');
    }
    $ftIds = array_column($eft, 'financial_trxn_id');
    $params['financial_trxn_id'] = ['IN' => $ftIds];
  }

  $financialTrxn = civicrm_api3('FinancialTrxn', 'get', array_merge($params, ['sequential' => FALSE]))['values'];
  if ($contributionID) {
    foreach ($financialTrxn as &$values) {
      $values['contribution_id'] = $contributionID;
    }
  }
  elseif (!empty($financialTrxn)) {
    $entityFinancialTrxns = civicrm_api3('EntityFinancialTrxn', 'get', ['financial_trxn_id' => ['IN' => array_keys($financialTrxn)], 'entity_table' => 'civicrm_contribution', 'options' => ['limit' => 0]])['values'];
    foreach ($entityFinancialTrxns as $entityFinancialTrxn) {
      $financialTrxn[$entityFinancialTrxn['financial_trxn_id']]['contribution_id'] = $entityFinancialTrxn['entity_id'];
    }
  }

  return civicrm_api3_create_success($financialTrxn, $params, 'Payment', 'get');
}

/**
 * Delete a payment.
 *
 * @param array $params
 *   Input parameters.
 *
 * @return array
 *   Api result array
 *
 * @throws \CRM_Core_Exception
 */
function civicrm_api3_payment_delete($params) {
  return civicrm_api3('FinancialTrxn', 'delete', $params);
}

/**
 * Cancel/Refund a payment for a Contribution.
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
function civicrm_api3_payment_cancel($params) {
  $eftParams = [
    'entity_table' => 'civicrm_contribution',
    'financial_trxn_id' => $params['id'],
    'return' => ['entity', 'amount', 'entity_id', 'financial_trxn_id.check_number'],
  ];
  $entity = civicrm_api3('EntityFinancialTrxn', 'getsingle', $eftParams);

  $paymentParams = [
    'total_amount' => -$entity['amount'],
    'contribution_id' => $entity['entity_id'],
    'trxn_date' => $params['trxn_date'] ?? 'now',
    'cancelled_payment_id' => $params['id'],
    'check_number' => $entity['financial_trxn_id.check_number'] ?? NULL,
  ];

  foreach (['trxn_id', 'payment_instrument_id'] as $permittedParam) {
    if (isset($params[$permittedParam])) {
      $paymentParams[$permittedParam] = $params[$permittedParam];
    }
  }
  $result = civicrm_api3('Payment', 'create', $paymentParams);
  return civicrm_api3_create_success($result['values'], $params, 'Payment', 'cancel');
}

/**
 * Add a payment for a Contribution.
 *
 * @param array $params
 *   Input parameters.
 *
 * @return array
 *   Api result array
 *
 * @throws \CRM_Core_Exception
 */
function civicrm_api3_payment_create($params) {
  if (empty($params['skipCleanMoney'])) {
    foreach (['total_amount', 'net_amount', 'fee_amount'] as $field) {
      if (isset($params[$field])) {
        $params[$field] = CRM_Utils_Rule::cleanMoney($params[$field]);
      }
    }
  }
  _civicrm_api3_format_params_for_create($params, 'FinancialTrxn');
  // Check if it is an update
  if (!empty($params['id'])) {
    $amount = $params['total_amount'];
    civicrm_api3('Payment', 'cancel', $params);
    $params['total_amount'] = $amount;
  }
  $trxn = CRM_Financial_BAO_Payment::create($params);

  $values = [];
  _civicrm_api3_object_to_array_unique_fields($trxn, $values[$trxn->id]);
  return civicrm_api3_create_success($values, $params, 'Payment', 'create', $trxn);
}

/**
 * Adjust Metadata for Create action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters.
 */
function _civicrm_api3_payment_create_spec(&$params) {
  $params = [
    'contribution_id' => [
      'api.required' => 1,
      'title' => ts('Contribution ID'),
      'type' => CRM_Utils_Type::T_INT,
      // We accept order_id as an alias so that we can chain like
      // civicrm_api3('Order', 'create', ['blah' => 'blah', 'contribution_status_id' => 'Pending', 'api.Payment.create => ['total_amount' => 5]]
      'api.aliases' => ['order_id'],
    ],
    'total_amount' => [
      'api.required' => 1,
      'title' => ts('Total Payment Amount'),
      'type' => CRM_Utils_Type::T_FLOAT,
    ],
    'fee_amount' => [
      'title' => ts('Fee Amount'),
      'type' => CRM_Utils_Type::T_FLOAT,
    ],
    'payment_processor_id' => [
      'name' => 'payment_processor_id',
      'type' => CRM_Utils_Type::T_INT,
      'title' => ts('Payment Processor'),
      'description' => ts('Payment Processor for this payment'),
      'where' => 'civicrm_financial_trxn.payment_processor_id',
      'table_name' => 'civicrm_financial_trxn',
      'entity' => 'FinancialTrxn',
      'bao' => 'CRM_Financial_DAO_FinancialTrxn',
      'localizable' => 0,
      'FKClassName' => 'CRM_Financial_DAO_PaymentProcessor',
    ],
    'id' => [
      'title' => ts('Payment ID'),
      'type' => CRM_Utils_Type::T_INT,
      'api.aliases' => ['payment_id'],
    ],
    'trxn_date' => [
      'title' => ts('Payment Date'),
      'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
      'api.default' => 'now',
      'api.required' => TRUE,
    ],
    'is_send_contribution_notification' => [
      'title' => ts('Send out notifications based on contribution status change?'),
      'description' => ts('Most commonly this equates to emails relating to the contribution, event, etcwhen a payment completes a contribution'),
      'type' => CRM_Utils_Type::T_BOOLEAN,
      'api.default' => TRUE,
    ],
    'payment_instrument_id' => [
      'name' => 'payment_instrument_id',
      'type' => CRM_Utils_Type::T_INT,
      'title' => ts('Payment Method'),
      'description' => ts('FK to payment_instrument option group values'),
      'where' => 'civicrm_financial_trxn.payment_instrument_id',
      'table_name' => 'civicrm_financial_trxn',
      'entity' => 'FinancialTrxn',
      'bao' => 'CRM_Financial_DAO_FinancialTrxn',
      'localizable' => 0,
      'html' => [
        'type' => 'Select',
      ],
      'pseudoconstant' => [
        'optionGroupName' => 'payment_instrument',
        'optionEditPath' => 'civicrm/admin/options/payment_instrument',
      ],
    ],
    'card_type_id' => [
      'name' => 'card_type_id',
      'type' => CRM_Utils_Type::T_INT,
      'title' => ts('Card Type ID'),
      'description' => ts('FK to accept_creditcard option group values'),
      'where' => 'civicrm_financial_trxn.card_type_id',
      'table_name' => 'civicrm_financial_trxn',
      'entity' => 'FinancialTrxn',
      'bao' => 'CRM_Financial_DAO_FinancialTrxn',
      'localizable' => 0,
      'html' => [
        'type' => 'Select',
      ],
      'pseudoconstant' => [
        'optionGroupName' => 'accept_creditcard',
        'optionEditPath' => 'civicrm/admin/options/accept_creditcard',
      ],
    ],
    'trxn_result_code' => [
      'name' => 'trxn_result_code',
      'type' => CRM_Utils_Type::T_STRING,
      'title' => ts('Transaction Result Code'),
      'description' => ts('processor result code'),
      'maxlength' => 255,
      'size' => CRM_Utils_Type::HUGE,
      'where' => 'civicrm_financial_trxn.trxn_result_code',
      'table_name' => 'civicrm_financial_trxn',
      'entity' => 'FinancialTrxn',
      'bao' => 'CRM_Financial_DAO_FinancialTrxn',
      'localizable' => 0,
    ],
    'trxn_id' => [
      'name' => 'trxn_id',
      'type' => CRM_Utils_Type::T_STRING,
      'title' => ts('Transaction ID'),
      'description' => ts('Transaction id supplied by external processor. This may not be unique.'),
      'maxlength' => 255,
      'size' => 10,
      'where' => 'civicrm_financial_trxn.trxn_id',
      'table_name' => 'civicrm_financial_trxn',
      'entity' => 'FinancialTrxn',
      'bao' => 'CRM_Financial_DAO_FinancialTrxn',
      'localizable' => 0,
      'html' => [
        'type' => 'Text',
      ],
    ],
    'order_reference' => [
      'name' => 'order_reference',
      'type' => CRM_Utils_Type::T_STRING,
      'title' => 'Order Reference',
      'description' => 'Payment Processor external order reference',
      'maxlength' => 255,
      'size' => 25,
      'where' => 'civicrm_financial_trxn.order_reference',
      'table_name' => 'civicrm_financial_trxn',
      'entity' => 'FinancialTrxn',
      'bao' => 'CRM_Financial_DAO_FinancialTrxn',
      'localizable' => 0,
      'html' => [
        'type' => 'Text',
      ],
    ],
    'check_number' => [
      'name' => 'check_number',
      'type' => CRM_Utils_Type::T_STRING,
      'title' => ts('Check Number'),
      'description' => ts('Check number'),
      'maxlength' => 255,
      'size' => 6,
      'where' => 'civicrm_financial_trxn.check_number',
      'table_name' => 'civicrm_financial_trxn',
      'entity' => 'FinancialTrxn',
      'bao' => 'CRM_Financial_DAO_FinancialTrxn',
      'localizable' => 0,
      'html' => [
        'type' => 'Text',
      ],
    ],
    'pan_truncation' => [
      'name' => 'pan_truncation',
      'type' => CRM_Utils_Type::T_STRING,
      'title' => ts('PAN Truncation'),
      'description' => ts('Last 4 digits of credit card'),
      'maxlength' => 4,
      'size' => 4,
      'where' => 'civicrm_financial_trxn.pan_truncation',
      'table_name' => 'civicrm_financial_trxn',
      'entity' => 'FinancialTrxn',
      'bao' => 'CRM_Financial_DAO_FinancialTrxn',
      'localizable' => 0,
      'html' => [
        'type' => 'Text',
      ],
    ],
  ];
}

/**
 * Adjust Metadata for Get action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_payment_get_spec(&$params) {
  $params = [
    'contribution_id' => [
      'title' => ts('Contribution ID'),
      'type' => CRM_Utils_Type::T_INT,
    ],
    'entity_id' => [
      'title' => ts('Entity ID'),
      'type' => CRM_Utils_Type::T_INT,
      'api.aliases' => ['contribution_id'],
    ],
    'trxn_id' => [
      'title' => ts('Transaction ID'),
      'description' => ts('Transaction id supplied by external processor. This may not be unique.'),
      'type' => CRM_Utils_Type::T_STRING,
    ],
    'order_reference' => [
      'title' => ts('Order Reference'),
      'description' => ts('Payment Processor external order reference'),
      'type' => CRM_Utils_Type::T_STRING,
    ],
    'trxn_date' => [
      'title' => ts('Payment Date'),
      'type' => CRM_Utils_Type::T_TIMESTAMP,
    ],
    'financial_trxn_id' => [
      'title' => ts('Payment ID'),
      'description' => ts('The ID of the record in civicrm_financial_trxn'),
      'type' => CRM_Utils_Type::T_INT,
      'api.aliases' => ['payment_id', 'id'],
    ],
  ];
}

/**
 * Adjust Metadata for Delete action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters.
 */
function _civicrm_api3_payment_delete_spec(&$params) {
  $params = [
    'id' => [
      'api.required' => 1,
      'title' => 'Payment ID',
      'type' => CRM_Utils_Type::T_INT,
      'api.aliases' => ['payment_id'],
    ],
  ];
}

/**
 * Adjust Metadata for Cancel action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters.
 */
function _civicrm_api3_payment_cancel_spec(&$params) {
  $params = [
    'id' => [
      'api.required' => 1,
      'title' => 'Payment ID',
      'type' => CRM_Utils_Type::T_INT,
      'api.aliases' => ['payment_id'],
    ],
    'trxn_date' => [
      'title' => 'Cancel Date',
      'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
    ],
  ];
}

/**
 * Send a payment confirmation.
 *
 * @param array $params
 *   Input parameters.
 *
 * @return array
 * @throws Exception
 */
function civicrm_api3_payment_sendconfirmation($params) {
  $allowedParams = [
    'from',
    'id',
    'check_permissions',
  ];
  $input = array_intersect_key($params, array_flip($allowedParams));
  // use either the contribution or membership receipt, based on whether it’s a membership-related contrib or not
  $result = CRM_Financial_BAO_Payment::sendConfirmation($input);
  return civicrm_api3_create_success([
    $params['id'] => [
      'is_sent' => $result[0],
      'subject' => $result[1],
      'message_txt' => $result[2],
      'message_html' => $result[3],
    ],
  ]);
}

/**
 * Adjust Metadata for sendconfirmation action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_payment_sendconfirmation_spec(&$params) {
  $params['id'] = [
    'api.required' => 1,
    'title' => ts('Payment ID'),
    'type' => CRM_Utils_Type::T_INT,
  ];
  $params['from_email_address'] = [
    'title' => ts('From email; an email string or the id of a valid email'),
    'type' => CRM_Utils_Type::T_STRING,
  ];
  $params['is_send_contribution_notification'] = [
    'title' => ts('Send any event or contribution confirmations triggered by this payment'),
    'description' => ts('If this payment completes a contribution it may mean receipts will go out according to busines logic if thie is set to TRUE'),
    'type' => CRM_Utils_Type::T_BOOLEAN,
    'api.default' => 0,
  ];
}
