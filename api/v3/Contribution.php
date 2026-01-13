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

use Civi\Api4\Contribution;

/**
 * Add or update a Contribution.
 *
 * @param array $params
 *   Input parameters.
 *
 * @throws CRM_Core_Exception
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

  if (!empty($params['check_permissions'])) {
    // Check acls on this entity. Note that we pass in financial type id, if we have it
    // since we know this is checked by acls. In v4 we do something more generic.
    if (!Contribution::checkAccess()
      ->setAction(empty($params['id']) ? 'create' : 'update')
      ->addValue('id', $params['id'] ?? NULL)
      ->addValue('financial_type_id', $params['financial_type_id'] ?? NULL)
      ->execute()->first()['access']) {
      throw new CRM_Core_Exception('You do not have permission to create this contribution');
    }
  }
  if (!empty($params['id']) && !empty($params['financial_type_id'])) {
    $error = [];
    CRM_Contribute_BAO_Contribution::checkFinancialTypeChange($params['financial_type_id'], $params['id'], $error);
    if (array_key_exists('financial_type_id', $error)) {
      throw new CRM_Core_Exception($error['financial_type_id']);
    }
  }
  if (!isset($params['tax_amount']) && empty($params['line_item'])
    && empty($params['skipLineItem'])
    && empty($params['id'])
  ) {
    $taxRates = CRM_Core_PseudoConstant::getTaxRates();
    $taxRate = $taxRates[$params['financial_type_id']] ?? 0;
    if ($taxRate) {
      // Be afraid - historically if a contribution was tax then the passed in amount is EXCLUSIVE
      $params['tax_amount'] = $params['total_amount'] * ($taxRate / 100);
      $params['total_amount'] += $params['tax_amount'];
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
      'soft_credit_type_id' => $params['honor_type_id'] ?? CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_ContributionSoft', 'soft_credit_type_id', 'in_honor_of'),
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
 * @throws \CRM_Core_Exception
 */
function civicrm_api3_contribution_delete($params) {

  $contributionID = !empty($params['contribution_id']) ? $params['contribution_id'] : $params['id'];
  if (!empty($params['check_permissions']) && !\Civi\Api4\Utils\CoreUtil::checkAccessDelegated('Contribution', 'delete', ['id' => $contributionID], CRM_Core_Session::getLoggedInContactID() ?: 0)) {
    throw new CRM_Core_Exception('You do not have permission to delete this contribution');
  }
  if (CRM_Contribute_BAO_Contribution::deleteContribution($contributionID)) {
    return civicrm_api3_create_success([$contributionID => 1]);
  }
  throw new CRM_Core_Exception('Could not delete contribution');
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
 * @throws \CRM_Core_Exception
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
 * @param array $contribution
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
 * @param array $contribution
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
  $allowedParams = [
    'receipt_from_email',
    'receipt_from_name',
    'receipt_update',
    'cc_receipt',
    'bcc_receipt',
    'receipt_text',
    'pay_later_receipt',
    'payment_processor_id',
  ];
  $input = array_intersect_key($params, array_flip($allowedParams));
  $input['modelProps'] = [
    // Pass through legacy receipt_text.
    'userEnteredText' => $params['receipt_text'] ?? NULL,
  ];
  CRM_Contribute_BAO_Contribution::sendMail($input, [], $params['id']);
  return [];
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
  $params['pay_later_receipt'] = [
    'title' => ts('Pay Later Message (string)'),
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
 * @throws \CRM_Core_Exception
 * @throws \Exception
 */
function civicrm_api3_contribution_completetransaction($params): array {
  $contribution = new CRM_Contribute_BAO_Contribution();
  $contribution->id = $params['id'];
  if (!$contribution->find(TRUE)) {
    throw new CRM_Core_Exception('A valid contribution ID is required', 'invalid_data');
  }
  if ($contribution->contribution_status_id == CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Completed')) {
    throw new CRM_Core_Exception(ts('Contribution already completed'), 'contribution_completed');
  }

  $params['trxn_id'] ??= $contribution->trxn_id;

  $passThroughParams = [
    'fee_amount',
    'payment_processor_id',
    'trxn_id',
  ];
  $input = array_intersect_key($params, array_fill_keys($passThroughParams, NULL));

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
    if (empty($params['receipt_from_name']) || empty($params['receipt_from_email'])) {
      [$domainFromName, $domainFromEmail] = CRM_Core_BAO_Domain::getNameAndEmail(TRUE);
    }
    $input['receipt_from_name'] = ($input['receipt_from_name'] ?? FALSE) ?: $domainFromName;
    $input['receipt_from_email'] = ($input['receipt_from_email'] ?? FALSE) ?: $domainFromEmail;
  }
  $input['card_type_id'] = $params['card_type_id'] ?? NULL;
  $input['pan_truncation'] = $params['pan_truncation'] ?? NULL;
  if (!empty($params['payment_instrument_id'])) {
    $input['payment_instrument_id'] = $params['payment_instrument_id'];
  }
  return CRM_Contribute_BAO_Contribution::completeOrder($input,
    $contribution->contribution_recur_id,
    $params['id'],
    $params['is_post_payment_create'] ?? NULL);
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
 * @todo this needs a big refactor to use the
 * CRM_Contribute_BAO_Contribution::repeatTransaction and Payment.create where
 * currently it uses CRM_Contribute_BAO_Contribution::completeOrder and repeats
 * a lot of work. See comments in https://github.com/civicrm/civicrm-core/pull/23928
 *
 * @param array $params
 *   Input parameters.
 *
 * @return array
 *   Api result array.
 * @throws CRM_Core_Exception
 */
function civicrm_api3_contribution_repeattransaction($params) {

  civicrm_api3_verify_one_mandatory($params, NULL, ['contribution_recur_id', 'original_contribution_id']);

  // We need a contribution to copy.
  if (empty($params['original_contribution_id'])) {
    // Find one from the given recur. A template contribution is preferred, otherwise use the latest one added.
    // @todo this duplicates work done by CRM_Contribute_BAO_Contribution::repeatTransaction & should be removed.
    $templateContribution = CRM_Contribute_BAO_ContributionRecur::getTemplateContribution($params['contribution_recur_id']);
    if (empty($templateContribution)) {
      throw new CRM_Core_Exception('Contribution.repeattransaction failed to get original_contribution_id for recur with ID: ' . $params['contribution_recur_id']);
    }
  }
  else {
    // A template/original contribution was specified by the params. Load it.
    // @todo this duplicates work done by CRM_Contribute_BAO_Contribution::repeatTransaction & should be removed.
    $templateContribution = Contribution::get(FALSE)
      ->addWhere('id', '=', $params['original_contribution_id'])
      ->addWhere('is_test', 'IN', [0, 1])
      ->addWhere('contribution_recur_id', 'IS NOT EMPTY')
      ->execute()->first();
    if (empty($templateContribution)) {
      throw new CRM_Core_Exception("Contribution.repeattransaction failed to load the given original_contribution_id ($params[original_contribution_id]) because it does not exist, or because it does not belong to a recurring contribution");
    }
  }

  // Collect inputs for CRM_Contribute_BAO_Contribution::completeOrder in $input.
  $paramsToCopy = [
    'trxn_id',
    'campaign_id',
    'fee_amount',
    'financial_type_id',
    'contribution_status_id',
    'payment_processor_id',
    'is_email_receipt',
    'trxn_date',
    'receive_date',
    'card_type_id',
    'pan_truncation',
    'payment_instrument_id',
    'total_amount',
  ];
  $input = array_intersect_key($params, array_fill_keys($paramsToCopy, NULL));
  // Ensure certain keys exist with NULL values if they don't already (not sure if this is ACTUALLY necessary?)
  $input += array_fill_keys(['card_type_id', 'pan_truncation'], NULL);

  $input['payment_processor_id'] = civicrm_api3('contributionRecur', 'getvalue', [
    'return' => 'payment_processor_id',
    'id' => $templateContribution['contribution_recur_id'],
  ]);
  // @todo this duplicates work done by CRM_Contribute_BAO_Contribution::repeatTransaction & should be removed.
  $input['is_test'] = $templateContribution['is_test'];

  // @todo this duplicates work done by CRM_Contribute_BAO_Contribution::repeatTransaction & should be removed.
  if (empty($templateContribution['contribution_page_id'])) {
    if (empty($domainFromEmail) && (empty($params['receipt_from_name']) || empty($params['receipt_from_email']))) {
      [$domainFromName, $domainFromEmail] = CRM_Core_BAO_Domain::getNameAndEmail(TRUE);
    }
    $input['receipt_from_name'] = ($params['receipt_from_name'] ?? NULL) ?: $domainFromName;
    $input['receipt_from_email'] = ($params['receipt_from_email'] ?? NULL) ?: $domainFromEmail;
  }

  return CRM_Contribute_BAO_Contribution::repeatTransaction($input,
    $templateContribution['contribution_recur_id']
  );
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
  $params['total_amount'] = [
    'description' => ts('Optional override amount, will be ignored if more than one line item exists'),
    'title' => ts('Total amount of the contribution'),
    'name' => 'total_amount',
    'type' => CRM_Utils_Type::T_MONEY,
    // Map 'amount' to total_amount - historically both have been used at times.
    'api.aliases' => ['amount'],
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
