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
 * This api exposes CiviCRM Contribution records.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Add or update a Contribution.
 *
 * @param array $params
 *   Input parameters.
 *
 * @throws API_Exception
 * @return array
 *   Api result array
 */
function civicrm_api3_contribution_create($params) {
  $values = [];
  _civicrm_api3_custom_format_params($params, $values, 'Contribution');
  $params = array_merge($params, $values);
  // The BAO should not clean money - it should be done in the form layer & api wrapper
  // (although arguably the api should expect pre-cleaned it seems to do some cleaning.)
  if (empty($params['skipCleanMoney'])) {
    foreach (['total_amount', 'net_amount', 'fee_amount', 'non_deductible_amount'] as $field) {
      if (isset($params[$field])) {
        $params[$field] = CRM_Utils_Rule::cleanMoney($params[$field]);
      }
    }
  }
  $params['skipCleanMoney'] = TRUE;

  if (!empty($params['check_permissions']) && CRM_Financial_BAO_FinancialType::isACLFinancialTypeStatus()) {
    if (empty($params['id'])) {
      $op = CRM_Core_Action::ADD;
    }
    else {
      if (empty($params['financial_type_id'])) {
        $params['financial_type_id'] = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution', $params['id'], 'financial_type_id');
      }
      $op = CRM_Core_Action::UPDATE;
    }
    CRM_Financial_BAO_FinancialType::getAvailableFinancialTypes($types, $op);
    if (!in_array($params['financial_type_id'], array_keys($types))) {
      throw new API_Exception('You do not have permission to create this contribution');
    }
  }
  if (!empty($params['id']) && !empty($params['contribution_status_id'])) {
    $error = [];
    //throw error for invalid status change such as setting completed back to pending
    //@todo this sort of validation belongs in the BAO not the API - if it is not an OK
    // action it needs to be blocked there. If it is Ok through a form it needs to be OK through the api
    CRM_Contribute_BAO_Contribution::checkStatusValidation(NULL, $params, $error);
    if (array_key_exists('contribution_status_id', $error)) {
      throw new API_Exception($error['contribution_status_id']);
    }
  }
  if (!empty($params['id']) && !empty($params['financial_type_id'])) {
    $error = [];
    CRM_Contribute_BAO_Contribution::checkFinancialTypeChange($params['financial_type_id'], $params['id'], $error);
    if (array_key_exists('financial_type_id', $error)) {
      throw new API_Exception($error['financial_type_id']);
    }
  }
  _civicrm_api3_contribution_create_legacy_support_45($params);

  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params, 'Contribution');
}

/**
 * Adjust Metadata for Create action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_contribution_create_spec(&$params) {
  $params['contact_id']['api.required'] = 1;
  $params['total_amount']['api.required'] = 1;
  $params['payment_instrument_id']['api.aliases'] = ['payment_instrument'];
  $params['receive_date']['api.default'] = 'now';
  $params['receive_date']['api.required'] = TRUE;
  $params['payment_processor'] = [
    'name' => 'payment_processor',
    'title' => 'Payment Processor ID',
    'description' => 'ID of payment processor used for this contribution',
    // field is called payment processor - not payment processor id but can only be one id so
    // it seems likely someone will fix it up one day to be more consistent - lets alias it from the start
    'api.aliases' => ['payment_processor_id'],
    'type' => CRM_Utils_Type::T_INT,
  ];
  $params['financial_type_id']['api.aliases'] = ['contribution_type_id', 'contribution_type'];
  $params['financial_type_id']['api.required'] = 1;
  $params['note'] = [
    'name' => 'note',
    'uniqueName' => 'contribution_note',
    'title' => 'note',
    'type' => 2,
    'description' => 'Associated Note in the notes table',
  ];
  $params['soft_credit_to'] = [
    'name' => 'soft_credit_to',
    'title' => 'Soft Credit contact ID (legacy)',
    'type' => CRM_Utils_Type::T_INT,
    'description' => 'ID of Contact to be Soft credited to (deprecated - use contribution_soft api)',
    'FKClassName' => 'CRM_Contact_DAO_Contact',
  ];
  $params['honor_contact_id'] = [
    'name' => 'honor_contact_id',
    'title' => 'Honoree contact ID (legacy)',
    'type' => CRM_Utils_Type::T_INT,
    'description' => 'ID of honoree contact (deprecated - use contribution_soft api)',
    'FKClassName' => 'CRM_Contact_DAO_Contact',
  ];
  $params['honor_type_id'] = [
    'name' => 'honor_type_id',
    'title' => 'Honoree Type (legacy)',
    'type' => CRM_Utils_Type::T_INT,
    'description' => 'Type of honoree contact (deprecated - use contribution_soft api)',
    'pseudoconstant' => TRUE,
  ];
  // note this is a recommended option but not adding as a default to avoid
  // creating unnecessary changes for the dev
  $params['skipRecentView'] = [
    'name' => 'skipRecentView',
    'title' => 'Skip adding to recent view',
    'type' => CRM_Utils_Type::T_BOOLEAN,
    'description' => 'Do not add to recent view (setting this improves performance)',
  ];
  $params['skipLineItem'] = [
    'name' => 'skipLineItem',
    'title' => 'Skip adding line items',
    'type' => CRM_Utils_Type::T_BOOLEAN,
    'api.default' => 0,
    'description' => 'Do not add line items by default (if you wish to add your own)',
  ];
  $params['batch_id'] = [
    'title' => 'Batch',
    'type' => CRM_Utils_Type::T_INT,
    'description' => 'Batch which relevant transactions should be added to',
  ];
  $params['refund_trxn_id'] = [
    'title' => 'Refund Transaction ID',
    'type' => CRM_Utils_Type::T_STRING,
    'description' => 'Transaction ID specific to the refund taking place',
  ];
  $params['card_type_id'] = [
    'title' => 'Card Type ID',
    'description' => 'Providing Credit Card Type ID',
    'type' => CRM_Utils_Type::T_INT,
    'pseudoconstant' => [
      'optionGroupName' => 'accept_creditcard',
    ],
  ];
}

/**
 * Support for schema changes made in 4.5.
 *
 * The main purpose of the API is to provide integrators a level of stability not provided by
 * the core code or schema - this means we have to provide support for api calls (where possible)
 * across schema changes.
 *
 * @param array $params
 */
function _civicrm_api3_contribution_create_legacy_support_45(&$params) {
  //legacy soft credit handling - recommended approach is chaining
  if (!empty($params['soft_credit_to'])) {
    $params['soft_credit'][] = [
      'contact_id'          => $params['soft_credit_to'],
      'amount'              => $params['total_amount'],
      'soft_credit_type_id' => CRM_Core_OptionGroup::getDefaultValue("soft_credit_type"),
    ];
  }
  if (!empty($params['honor_contact_id'])) {
    $params['soft_credit'][] = [
      'contact_id'          => $params['honor_contact_id'],
      'amount'              => $params['total_amount'],
      'soft_credit_type_id' => CRM_Utils_Array::value('honor_type_id', $params, CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_ContributionSoft', 'soft_credit_type_id', 'in_honor_of')),
    ];
  }
}

/**
 * Delete a Contribution.
 *
 * @param array $params
 *   Input parameters.
 *
 * @return array
 * @throws \API_Exception
 */
function civicrm_api3_contribution_delete($params) {

  $contributionID = !empty($params['contribution_id']) ? $params['contribution_id'] : $params['id'];
  // First check contribution financial type
  $financialType = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution', $contributionID, 'financial_type_id');
  // Now check permissioned lineitems & permissioned contribution
  if (!empty($params['check_permissions']) && CRM_Financial_BAO_FinancialType::isACLFinancialTypeStatus() &&
    (
      !CRM_Core_Permission::check('delete contributions of type ' . CRM_Contribute_PseudoConstant::financialType($financialType))
      || !CRM_Financial_BAO_FinancialType::checkPermissionedLineItems($contributionID, 'delete', FALSE)
    )
  ) {
    throw new API_Exception('You do not have permission to delete this contribution');
  }
  if (CRM_Contribute_BAO_Contribution::deleteContribution($contributionID)) {
    return civicrm_api3_create_success([$contributionID => 1]);
  }
  else {
    throw new API_Exception('Could not delete contribution');
  }
}

/**
 * Modify metadata for delete action.
 *
 * Legacy support for contribution_id.
 *
 * @param array $params
 */
function _civicrm_api3_contribution_delete_spec(&$params) {
  $params['id']['api.aliases'] = ['contribution_id'];
}

/**
 * Retrieve a set of contributions.
 *
 * @param array $params
 *  Input parameters.
 *
 * @return array
 *   Array of contributions, if error an array with an error id and error message
 */
function civicrm_api3_contribution_get($params) {

  $mode = CRM_Contact_BAO_Query::MODE_CONTRIBUTE;
  $additionalOptions = _civicrm_api3_contribution_get_support_nonunique_returns($params);
  $returnProperties = CRM_Contribute_BAO_Query::defaultReturnProperties($mode);

  // Get the contributions based on parameters passed in
  $contributions = _civicrm_api3_get_using_query_object('Contribution', $params, $additionalOptions, NULL, $mode, $returnProperties);
  if (!empty($contributions)) {
    $softContributions = CRM_Contribute_BAO_ContributionSoft::getSoftCreditContributionFields(array_keys($contributions), TRUE);
    foreach ($contributions as $id => $contribution) {
      $contributions[$id] = isset($softContributions[$id]) ? array_merge($contribution, $softContributions[$id]) : $contribution;
      // format soft credit for backward compatibility
      _civicrm_api3_format_soft_credit($contributions[$id]);
      _civicrm_api3_contribution_add_supported_fields($contributions[$id]);
    }
  }
  return civicrm_api3_create_success($contributions, $params, 'Contribution', 'get');
}

/**
 * Fix the return values to reflect cases where the schema has been changed.
 *
 * At the query object level using uniquenames dismbiguates between tables.
 *
 * However, adding uniquename can change inputs accepted by the api, so we need
 * to ensure we are asking for the unique name return fields.
 *
 * @param array $params
 *
 * @return array
 * @throws \API_Exception
 */
function _civicrm_api3_contribution_get_support_nonunique_returns($params) {
  $additionalOptions = [];
  $options = _civicrm_api3_get_options_from_params($params, TRUE);
  foreach (['check_number', 'address_id', 'cancel_date'] as $changedVariable) {
    if (isset($options['return']) && !empty($options['return'][$changedVariable])) {
      $additionalOptions['return']['contribution_' . $changedVariable] = 1;
    }
  }
  return $additionalOptions;
}

/**
 * Support for supported output variables.
 *
 * @param $contribution
 */
function _civicrm_api3_contribution_add_supported_fields(&$contribution) {
  // These are output fields that are supported in our test contract.
  // Arguably we should also do the same with 'campaign_id' &
  // 'source' - which are also fields being rendered with unique names.
  // That seems more consistent with other api where we output the actual field names.
  $outputAliases = [
    'contribution_check_number' => 'check_number',
    'contribution_address_id' => 'address_id',
    'payment_instrument_id' => 'instrument_id',
    'contribution_cancel_date' => 'cancel_date',
  ];
  foreach ($outputAliases as $returnName => $copyTo) {
    if (array_key_exists($returnName, $contribution)) {
      $contribution[$copyTo] = $contribution[$returnName];
    }
  }

}

/**
 * Get number of contacts matching the supplied criteria.
 *
 * @param array $params
 *
 * @return int
 */
function civicrm_api3_contribution_getcount($params) {
  $count = _civicrm_api3_get_using_query_object('Contribution', $params, [], TRUE, CRM_Contact_BAO_Query::MODE_CONTRIBUTE);
  return (int) $count;
}

/**
 * This function is used to format the soft credit for backward compatibility.
 *
 * As of v4.4 we support multiple soft credit, so now contribution returns array with 'soft_credit' as key
 * but we still return first soft credit as a part of contribution array
 *
 * @param $contribution
 */
function _civicrm_api3_format_soft_credit(&$contribution) {
  if (!empty($contribution['soft_credit'])) {
    $contribution['soft_credit_to'] = $contribution['soft_credit'][1]['contact_id'];
    $contribution['soft_credit_id'] = $contribution['soft_credit'][1]['soft_credit_id'];
  }
}

/**
 * Adjust Metadata for Get action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_contribution_get_spec(&$params) {
  $params['contribution_test'] = [
    'api.default' => 0,
    'type' => CRM_Utils_Type::T_BOOLEAN,
    'title' => 'Get Test Contributions?',
    'api.aliases' => ['is_test'],
  ];

  $params['financial_type_id']['api.aliases'] = ['contribution_type_id'];
  $params['payment_instrument_id']['api.aliases'] = ['contribution_payment_instrument', 'payment_instrument'];
  $params['contact_id'] = $params['contribution_contact_id'] ?? NULL;
  $params['contact_id']['api.aliases'] = ['contribution_contact_id'];
  $params['is_template']['api.default'] = 0;
  unset($params['contribution_contact_id']);
}

/**
 * Legacy handling for contribution parameters.
 *
 * Take the input parameter list as specified in the data model and
 * convert it into the same format that we use in QF and BAO object.
 *
 * @param array $params
 *   property name/value  pairs to insert in new contact.
 * @param array $values
 *   The reformatted properties that we can use internally.
 *
 * @return array
 */
function _civicrm_api3_contribute_format_params($params, &$values) {
  //legacy way of formatting from v2 api - v3 way is to define metadata & do it in the api layer
  _civicrm_api3_filter_fields_for_bao('Contribution', $params, $values);
  return [];
}

/**
 * Send a contribution confirmation (receipt or invoice).
 *
 * The appropriate online template will be used (the existence of related objects
 * (e.g. memberships ) will affect this selection
 *
 * @param array $params
 *   Input parameters.
 *
 * @throws Exception
 */
function civicrm_api3_contribution_sendconfirmation($params) {
  $ids = [];
  $allowedParams = [
    'receipt_from_email',
    'receipt_from_name',
    'receipt_update',
    'cc_receipt',
    'bcc_receipt',
    'receipt_text',
    'payment_processor_id',
  ];
  $input = array_intersect_key($params, array_flip($allowedParams));
  CRM_Contribute_BAO_Contribution::sendMail($input, $ids, $params['id']);
}

/**
 * Adjust Metadata for sendconfirmation action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_contribution_sendconfirmation_spec(&$params) {
  $params['id'] = [
    'api.required' => 1,
    'title' => ts('Contribution ID'),
    'type' => CRM_Utils_Type::T_INT,
  ];
  $params['receipt_from_email'] = [
    'title' => ts('From Email address (string)'),
    'type' => CRM_Utils_Type::T_STRING,
  ];
  $params['receipt_from_name'] = [
    'title' => ts('From Name (string)'),
    'type' => CRM_Utils_Type::T_STRING,
  ];
  $params['cc_receipt'] = [
    'title' => ts('CC Email address (string)'),
    'type' => CRM_Utils_Type::T_STRING,
  ];
  $params['bcc_receipt'] = [
    'title' => ts('BCC Email address (string)'),
    'type' => CRM_Utils_Type::T_STRING,
  ];
  $params['receipt_text'] = [
    'title' => ts('Message (string)'),
    'type' => CRM_Utils_Type::T_STRING,
  ];
  $params['receipt_update'] = [
    'title' => ts('Update the Receipt Date'),
    'type' => CRM_Utils_Type::T_BOOLEAN,
    'api.default' => TRUE,
  ];
  $params['payment_processor_id'] = [
    'title' => ts('Payment processor Id (avoids mis-guesses)'),
    'type' => CRM_Utils_Type::T_INT,
  ];
}

/**
 * Complete an existing (pending) transaction.
 *
 * This will update related entities (participant, membership, pledge etc)
 * and take any complete actions from the contribution page (e.g. send receipt).
 *
 * @todo - most of this should live in the BAO layer but as we want it to be an addition
 * to 4.3 which is already stable we should add it to the api layer & re-factor into the BAO layer later
 *
 * @param array $params
 *   Input parameters.
 *
 * @return array
 *   API result array
 * @throws \API_Exception
 * @throws \CRM_Core_Exception
 * @throws \Exception
 */
function civicrm_api3_contribution_completetransaction($params) {
  $contribution = new CRM_Contribute_BAO_Contribution();
  $contribution->id = $params['id'];
  if (!$contribution->find(TRUE)) {
    throw new API_Exception('A valid contribution ID is required', 'invalid_data');
  }
  if ($contribution->contribution_status_id == CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Completed')) {
    throw new API_Exception(ts('Contribution already completed'), 'contribution_completed');
  }

  $params['trxn_id'] = $params['trxn_id'] ?? $contribution->trxn_id;

  $passThroughParams = [
    'fee_amount',
    'payment_processor_id',
    'trxn_id',
  ];
  $input = array_intersect_key($params, array_fill_keys($passThroughParams, NULL));

  $ids = [];
  if (!$contribution->loadRelatedObjects(['payment_processor_id' => $input['payment_processor_id'] ?? NULL], $ids, TRUE)) {
    throw new API_Exception('failed to load related objects');
  }

  return _ipn_process_transaction($params, $contribution, $input, $ids);
}

/**
 * Provide function metadata.
 *
 * @param array $params
 */
function _civicrm_api3_contribution_completetransaction_spec(&$params) {
  $params['id'] = [
    'title' => 'Contribution ID',
    'type' => CRM_Utils_Type::T_INT,
    'api.required' => TRUE,
  ];
  $params['trxn_id'] = [
    'title' => 'Transaction ID',
    'type' => CRM_Utils_Type::T_STRING,
  ];
  $params['is_email_receipt'] = [
    'title' => 'Send email Receipt?',
    'type' => CRM_Utils_Type::T_BOOLEAN,
  ];
  $params['receipt_from_email'] = [
    'title' => 'Email to send receipt from.',
    'description' => 'If not provided this will default to being based on domain mail or contribution page',
    'type' => CRM_Utils_Type::T_EMAIL,
  ];
  $params['receipt_from_name'] = [
    'title' => 'Name to send receipt from',
    'description' => '. If not provided this will default to domain mail or contribution page',
    'type' => CRM_Utils_Type::T_STRING,
  ];
  $params['payment_processor_id'] = [
    'title' => 'Payment processor ID',
    'description' => 'Providing this is strongly recommended, as not possible to calculate it accurately always',
    'type' => CRM_Utils_Type::T_INT,
  ];
  $params['fee_amount'] = [
    'title' => 'Fee charged on transaction',
    'description' => 'If a fee has been charged then the amount',
    'type' => CRM_Utils_Type::T_FLOAT,
  ];
  $params['trxn_date'] = [
    'title' => 'Transaction Date',
    'description' => 'Date this transaction occurred',
    'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
  ];
  $params['card_type_id'] = [
    'title' => 'Card Type ID',
    'description' => 'Providing Credit Card Type ID',
    'type' => CRM_Utils_Type::T_INT,
    'pseudoconstant' => [
      'optionGroupName' => 'accept_creditcard',
    ],
  ];
  // At some point we will deprecate this api in favour of calling payment create which will in turn call this
  // api if appropriate to transition related entities and send receipts - ie. financial responsibility should
  // not exist in completetransaction. For now we just need to allow payment.create to force a bypass on the
  // things it does itself.
  $params['is_post_payment_create'] = [
    'title' => 'Is this being called from payment create?',
    'type' => CRM_Utils_Type::T_BOOLEAN,
    'description' => 'The \'correct\' flow is to call payment.create for the financial side & for that to call completecontribution for the entity & receipt management. However, we need to still support completetransaction directly for legacy reasons',
  ];
}

/**
 * Complete an existing (pending) transaction.
 *
 * This will update related entities (participant, membership, pledge etc)
 * and take any complete actions from the contribution page (e.g. send receipt).
 *
 * @todo - most of this should live in the BAO layer but as we want it to be an addition
 * to 4.3 which is already stable we should add it to the api layer & re-factor into the BAO layer later
 *
 * @param array $params
 *   Input parameters.
 *
 * @return array
 *   Api result array.
 * @throws API_Exception
 */
function civicrm_api3_contribution_repeattransaction($params) {
  civicrm_api3_verify_one_mandatory($params, NULL, ['contribution_recur_id', 'original_contribution_id']);
  if (empty($params['original_contribution_id'])) {
    //  CRM-19873 call with test mode.
    $params['original_contribution_id'] = civicrm_api3('contribution', 'getvalue', [
      'return' => 'id',
      'contribution_status_id' => ['IN' => ['Completed']],
      'contribution_recur_id' => $params['contribution_recur_id'],
      'contribution_test' => CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_ContributionRecur', $params['contribution_recur_id'], 'is_test'),
      'options' => ['limit' => 1, 'sort' => 'id DESC'],
    ]);
  }
  $contribution = new CRM_Contribute_BAO_Contribution();
  $contribution->id = $params['original_contribution_id'];
  if (!$contribution->find(TRUE)) {
    throw new API_Exception(
      'A valid original contribution ID is required', 'invalid_data');
  }
  // We don't support repeattransaction without a related recurring contribution.
  if (empty($contribution->contribution_recur_id)) {
    throw new API_Exception(
      'Repeattransaction API can only be used in the context of contributions that have a contribution_recur_id.',
      'invalid_data'
    );
  }

  $params['payment_processor_id'] = civicrm_api3('contributionRecur', 'getvalue', [
    'return' => 'payment_processor_id',
    'id' => $contribution->contribution_recur_id,
  ]);

  $passThroughParams = [
    'trxn_id',
    'total_amount',
    'campaign_id',
    'fee_amount',
    'financial_type_id',
    'contribution_status_id',
    'membership_id',
    'payment_processor_id',
  ];
  $input = array_intersect_key($params, array_fill_keys($passThroughParams, NULL));

  $ids = [];
  if (!$contribution->loadRelatedObjects(['payment_processor_id' => $input['payment_processor_id']], $ids, TRUE)) {
    throw new API_Exception('failed to load related objects');
  }
  unset($contribution->id, $contribution->receive_date, $contribution->invoice_id);
  $contribution->receive_date = $params['receive_date'];

  return _ipn_process_transaction($params, $contribution, $input, $ids);
}

/**
 * Calls IPN complete transaction for completing or repeating a transaction.
 *
 * The IPN function is overloaded with two purposes - this is simply a wrapper for that
 * when separating them in the api layer.
 *
 * @param array $params
 * @param CRM_Contribute_BAO_Contribution $contribution
 * @param array $input
 *
 * @param array $ids
 *
 * @return mixed
 * @throws \CRM_Core_Exception
 * @throws \CiviCRM_API3_Exception
 */
function _ipn_process_transaction($params, $contribution, $input, $ids) {
  $objects = $contribution->_relatedObjects;
  $objects['contribution'] = &$contribution;
  $input['component'] = $contribution->_component;
  $input['is_test'] = $contribution->is_test;
  $input['amount'] = empty($input['total_amount']) ? $contribution->total_amount : $input['total_amount'];

  if (isset($params['is_email_receipt'])) {
    $input['is_email_receipt'] = $params['is_email_receipt'];
  }
  if (!empty($params['trxn_date'])) {
    $input['trxn_date'] = $params['trxn_date'];
  }
  if (!empty($params['receive_date'])) {
    $input['receive_date'] = $params['receive_date'];
  }
  if (empty($contribution->contribution_page_id)) {
    static $domainFromName;
    static $domainFromEmail;
    if (empty($domainFromEmail) && (empty($params['receipt_from_name']) || empty($params['receipt_from_email']))) {
      list($domainFromName, $domainFromEmail) = CRM_Core_BAO_Domain::getNameAndEmail(TRUE);
    }
    $input['receipt_from_name'] = CRM_Utils_Array::value('receipt_from_name', $params, $domainFromName);
    $input['receipt_from_email'] = CRM_Utils_Array::value('receipt_from_email', $params, $domainFromEmail);
  }
  $input['card_type_id'] = $params['card_type_id'] ?? NULL;
  $input['pan_truncation'] = $params['pan_truncation'] ?? NULL;
  if (!empty($params['payment_instrument_id'])) {
    $input['payment_instrument_id'] = $params['payment_instrument_id'];
  }
  return CRM_Contribute_BAO_Contribution::completeOrder($input, [
    'related_contact' => $ids['related_contact'] ?? NULL,
    'participant' => !empty($objects['participant']) ? $objects['participant']->id : NULL,
    'contributionRecur' => !empty($objects['contributionRecur']) ? $objects['contributionRecur']->id : NULL,
  ], $objects['contribution'],
    $params['is_post_payment_create'] ?? NULL);
}

/**
 * Provide function metadata.
 *
 * @param array $params
 */
function _civicrm_api3_contribution_repeattransaction_spec(&$params) {
  $params['original_contribution_id'] = [
    'title' => 'Original Contribution ID',
    'description' => 'Contribution ID to copy (will be calculated from recurring contribution if not provided)',
    'type' => CRM_Utils_Type::T_INT,
  ];
  $params['contribution_recur_id'] = [
    'title' => 'Recurring contribution ID',
    'type' => CRM_Utils_Type::T_INT,
  ];
  $params['trxn_id'] = [
    'title' => 'Transaction ID',
    'type' => CRM_Utils_Type::T_STRING,
  ];
  $params['is_email_receipt'] = [
    'title' => 'Send email Receipt?',
    'type' => CRM_Utils_Type::T_BOOLEAN,
  ];
  $params['contribution_status_id'] = [
    'title' => 'Contribution Status ID',
    'name' => 'contribution_status_id',
    'type' => CRM_Utils_Type::T_INT,
    'pseudoconstant' => [
      'optionGroupName' => 'contribution_status',
    ],
    'api.required' => TRUE,
    'api.default' => 'Pending',
  ];
  $params['receive_date'] = [
    'title' => 'Contribution Receive Date',
    'name' => 'receive_date',
    'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
    'api.default' => 'now',
  ];
  $params['trxn_id'] = [
    'title' => 'Transaction ID',
    'name' => 'trxn_id',
    'type' => CRM_Utils_Type::T_STRING,
  ];
  $params['campaign_id'] = [
    'title' => 'Campaign ID',
    'name' => 'campaign_id',
    'type' => CRM_Utils_Type::T_INT,
    'pseudoconstant' => [
      'table' => 'civicrm_campaign',
      'keyColumn' => 'id',
      'labelColumn' => 'title',
    ],
  ];
  $params['financial_type_id'] = [
    'title' => 'Financial ID (ignored if more than one line item)',
    'name' => 'financial_type_id',
    'type' => CRM_Utils_Type::T_INT,
    'pseudoconstant' => [
      'table' => 'civicrm_financial_type',
      'keyColumn' => 'id',
      'labelColumn' => 'name',
    ],
  ];
  $params['payment_processor_id'] = [
    'description' => ts('Payment processor ID, will be loaded from contribution_recur if not provided'),
    'title' => 'Payment processor ID',
    'name' => 'payment_processor_id',
    'type' => CRM_Utils_Type::T_INT,
  ];
}

/**
 * Declare deprecated functions.
 *
 * @return array
 *   Array of deprecated actions
 */
function _civicrm_api3_contribution_deprecation() {
  return ['transact' => 'Contribute.transact is ureliable & unsupported - see https://docs.civicrm.org/dev/en/latest/financial/OrderAPI/  for how to move on'];
}
