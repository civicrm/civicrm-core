<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.6                                                |
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
function civicrm_api3_contribution_create(&$params) {
  $values = array();
  _civicrm_api3_custom_format_params($params, $values, 'Contribution');
  $params = array_merge($params, $values);

  if (!empty($params['id']) && !empty($params['contribution_status_id'])) {
    $error = array();
    //throw error for invalid status change such as setting completed back to pending
    //@todo this sort of validation belongs in the BAO not the API - if it is not an OK
    // action it needs to be blocked there. If it is Ok through a form it needs to be OK through the api
    CRM_Contribute_BAO_Contribution::checkStatusValidation(NULL, $params, $error);
    if (array_key_exists('contribution_status_id', $error)) {
      throw new API_Exception($error['contribution_status_id']);
    }
  }
  if (!empty($params['id']) && !empty($params['financial_type_id'])) {
    $error = array();
    CRM_Contribute_BAO_Contribution::checkFinancialTypeChange($params['financial_type_id'], $params['id'], $error);
    if (array_key_exists('financial_type_id', $error)) {
      throw new API_Exception($error['financial_type_id']);
    }
  }
  _civicrm_api3_contribution_create_legacy_support_45($params);

  // Make sure tax calculation is handled via api.
  $params = CRM_Contribute_BAO_Contribution::checkTaxAmount($params);

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
  $params['payment_instrument_id']['api.aliases'] = array('payment_instrument');
  $params['receive_date']['api.default'] = 'now';
  $params['payment_processor'] = array(
    'name' => 'payment_processor',
    'title' => 'Payment Processor ID',
    'description' => 'ID of payment processor used for this contribution',
    // field is called payment processor - not payment processor id but can only be one id so
    // it seems likely someone will fix it up one day to be more consistent - lets alias it from the start
    'api.aliases' => array('payment_processor_id'),
    'type' => CRM_Utils_Type::T_INT,
  );
  $params['financial_type_id']['api.aliases'] = array('contribution_type_id', 'contribution_type');
  $params['financial_type_id']['api.required'] = 1;
  $params['note'] = array(
    'name' => 'note',
    'uniqueName' => 'contribution_note',
    'title' => 'note',
    'type' => 2,
    'description' => 'Associated Note in the notes table',
  );
  $params['soft_credit_to'] = array(
    'name' => 'soft_credit_to',
    'title' => 'Soft Credit contact ID (legacy)',
    'type' => 1,
    'description' => 'ID of Contact to be Soft credited to (deprecated - use contribution_soft api)',
    'FKClassName' => 'CRM_Contact_DAO_Contact',
  );
  $params['honor_contact_id'] = array(
    'name' => 'honor_contact_id',
    'title' => 'Honoree contact ID (legacy)',
    'type' => 1,
    'description' => 'ID of honoree contact (deprecated - use contribution_soft api)',
    'FKClassName' => 'CRM_Contact_DAO_Contact',
  );
  $params['honor_type_id'] = array(
    'name' => 'honor_type_id',
    'title' => 'Honoree Type (legacy)',
    'type' => 1,
    'description' => 'Type of honoree contact (deprecated - use contribution_soft api)',
    'pseudoconstant' => TRUE,
  );
  // note this is a recommended option but not adding as a default to avoid
  // creating unnecessary changes for the dev
  $params['skipRecentView'] = array(
    'name' => 'skipRecentView',
    'title' => 'Skip adding to recent view',
    'type' => CRM_Utils_Type::T_BOOLEAN,
    'description' => 'Do not add to recent view (setting this improves performance)',
  );
  $params['skipLineItem'] = array(
    'name' => 'skipLineItem',
    'title' => 'Skip adding line items',
    'type' => 1,
    'api.default' => 0,
    'description' => 'Do not add line items by default (if you wish to add your own)',
  );
  $params['batch_id'] = array(
    'title' => 'Batch',
    'type' => 1,
    'description' => 'Batch which relevant transactions should be added to',
  );
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
    $params['soft_credit'][] = array(
      'contact_id'          => $params['soft_credit_to'],
      'amount'              => $params['total_amount'],
      'soft_credit_type_id' => CRM_Core_OptionGroup::getDefaultValue("soft_credit_type"),
    );
  }
  if (!empty($params['honor_contact_id'])) {
    $params['soft_credit'][] = array(
      'contact_id'          => $params['honor_contact_id'],
      'amount'              => $params['total_amount'],
      'soft_credit_type_id' => CRM_Utils_Array::value('honor_type_id', $params, CRM_Core_OptionGroup::getValue('soft_credit_type', 'in_honor_of', 'name')),
    );
  }
}

/**
 * Delete a Contribution.
 *
 * @param array $params
 *   Input parameters.
 *
 * @return array
 */
function civicrm_api3_contribution_delete($params) {

  $contributionID = !empty($params['contribution_id']) ? $params['contribution_id'] : $params['id'];
  if (CRM_Contribute_BAO_Contribution::deleteContribution($contributionID)) {
    return civicrm_api3_create_success(array($contributionID => 1));
  }
  else {
    return civicrm_api3_create_error('Could not delete contribution');
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
  $params['id']['api.aliases'] = array('contribution_id');
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
  list($dao, $query) = _civicrm_api3_get_query_object($params, $mode, 'Contribution');

  $contribution = array();
  while ($dao->fetch()) {
    //CRM-8662
    $contribution_details = $query->store($dao);
    $softContribution = CRM_Contribute_BAO_ContributionSoft::getSoftContribution($dao->contribution_id, TRUE);
    $contribution[$dao->contribution_id] = array_merge($contribution_details, $softContribution);
    // format soft credit for backward compatibility
    _civicrm_api3_format_soft_credit($contribution[$dao->contribution_id]);
  }
  return civicrm_api3_create_success($contribution, $params, 'Contribution', 'get', $dao);
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
  $params['contribution_test'] = array(
    'api.default' => 0,
    'type' => CRM_Utils_Type::T_BOOLEAN,
    'title' => 'Get Test Contributions?',
    'api.aliases' => array('is_test'),
  );

  $params['financial_type_id']['api.aliases'] = array('contribution_type_id');
  $params['payment_instrument_id']['api.aliases'] = array('contribution_payment_instrument', 'payment_instrument');
  $params['contact_id'] = $params['contribution_contact_id'];
  $params['contact_id']['api.aliases'] = array('contribution_contact_id');
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
  return array();
}

/**
 * Adjust Metadata for Transact action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_contribution_transact_spec(&$params) {
  $fields = civicrm_api3('Contribution', 'getfields', array('action' => 'create'));
  $params = array_merge($params, $fields['values']);
  $params['receive_date']['api.default'] = 'now';
}

/**
 * Process a transaction and record it against the contact.
 *
 * @param array $params
 *   Input parameters.
 *
 * @return array
 *   contribution of created or updated record (or a civicrm error)
 */
function civicrm_api3_contribution_transact($params) {
  // Set some params specific to payment processing
  // @todo - fix this function - none of the results checked by civicrm_error would ever be an array with
  // 'is_error' set
  // also trxn_id is not saved.
  // but since there is no test it's not desirable to jump in & make the obvious changes.
  $params['payment_processor_mode'] = empty($params['is_test']) ? 'live' : 'test';
  $params['amount'] = $params['total_amount'];
  if (!isset($params['net_amount'])) {
    $params['net_amount'] = $params['amount'];
  }
  if (!isset($params['invoiceID']) && isset($params['invoice_id'])) {
    $params['invoiceID'] = $params['invoice_id'];
  }

  // Some payment processors expect a unique invoice_id - generate one if not supplied
  $params['invoice_id'] = CRM_Utils_Array::value('invoice_id', $params, md5(uniqid(rand(), TRUE)));

  $paymentProcessor = CRM_Financial_BAO_PaymentProcessor::getPayment($params['payment_processor'], $params['payment_processor_mode']);
  if (civicrm_error($paymentProcessor)) {
    return $paymentProcessor;
  }

  $payment = CRM_Core_Payment::singleton($params['payment_processor_mode'], $paymentProcessor);
  if (civicrm_error($payment)) {
    return $payment;
  }

  $transaction = $payment->doPayment($params);

  $params['payment_instrument_id'] = CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_PaymentProcessorType', $paymentProcessor['payment_processor_type_id'], 'payment_type') == 1 ? 'Credit Card' : 'Debit Card';
  return civicrm_api('Contribution', 'create', $params);
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
  $contribution = new CRM_Contribute_BAO_Contribution();
  $contribution->id = $params['id'];
  if (!$contribution->find(TRUE)) {
    throw new Exception('Contribution does not exist');
  }
  $input = $ids = $cvalues = array('receipt_from_email' => $params['receipt_from_email']);
  $contribution->loadRelatedObjects($input, $ids, FALSE, TRUE);
  $contribution->composeMessageArray($input, $ids, $cvalues, FALSE, FALSE);
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
  $params['id'] = array(
    'api.required' => 1,
    'title' => 'Contribution ID',
    'type' => CRM_Utils_Type::T_INT,
  );
  $params['receipt_from_email'] = array(
    'api.required' => 1,
    'title' => 'From Email address (string) required until someone provides a patch :-)',
    'type' => CRM_Utils_Type::T_STRING,
  );
  $params['receipt_from_name'] = array(
    'title' => 'From Name (string)',
    'type' => CRM_Utils_Type::T_STRING,
  );
  $params['cc_receipt'] = array(
    'title' => 'CC Email address (string)',
    'type' => CRM_Utils_Type::T_STRING,
  );
  $params['bcc_receipt'] = array(
    'title' => 'BCC Email address (string)',
    'type' => CRM_Utils_Type::T_STRING,
  );
  $params['receipt_text'] = array(
    'title' => 'Message (string)',
    'type' => CRM_Utils_Type::T_STRING,
  );
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
 * @throws API_Exception
 *   Api result array.
 */
function civicrm_api3_contribution_completetransaction(&$params) {

  $input = $ids = array();
  $contribution = new CRM_Contribute_BAO_Contribution();
  $contribution->id = $params['id'];
  $contribution->find(TRUE);
  if (!$contribution->id == $params['id']) {
    throw new API_Exception('A valid contribution ID is required', 'invalid_data');
  }
  try {
    if (!$contribution->loadRelatedObjects($input, $ids, FALSE, TRUE)) {
      throw new API_Exception('failed to load related objects');
    }
    elseif ($contribution->contribution_status_id == CRM_Core_OptionGroup::getValue('contribution_status', 'Completed', 'name')) {
      throw new API_Exception(ts('Contribution already completed'));
    }
    $input['trxn_id'] = !empty($params['trxn_id']) ? $params['trxn_id'] : $contribution->trxn_id;
    $params = _ipn_process_transaction($params, $contribution, $input, $ids);
  }
  catch(Exception $e) {
    throw new API_Exception('failed to load related objects' . $e->getMessage() . "\n" . $e->getTraceAsString());
  }
}

/**
 * Provide function metadata.
 *
 * @param array $params
 */
function _civicrm_api3_contribution_completetransaction_spec(&$params) {
  $params['id'] = array(
    'title' => 'Contribution ID',
    'type' => CRM_Utils_Type::T_INT,
    'api.required' => TRUE,
  );
  $params['trxn_id'] = array(
    'title' => 'Transaction ID',
    'type' => CRM_Utils_Type::T_STRING,
  );
  $params['is_email_receipt'] = array(
    'title' => 'Send email Receipt?',
    'type' => CRM_Utils_Type::T_BOOLEAN,
  );
  $params['receipt_from_email'] = array(
    'title' => 'Email to send receipt from.',
    'description' => 'If not provided this will default to being based on domain mail or contribution page',
    'type' => CRM_Utils_Type::T_EMAIL,
  );
  $params['receipt_from_name'] = array(
    'title' => 'Name to send receipt from',
    'description' => '. If not provided this will default to domain mail or contribution page',
    'type' => CRM_Utils_Type::T_STRING,
  );
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
 * @throws API_Exception
 *   Api result array.
 */
function civicrm_api3_contribution_repeattransaction(&$params) {
  $input = $ids = array();
  $contribution = new CRM_Contribute_BAO_Contribution();
  $contribution->id = $params['original_contribution_id'];
  if (!$contribution->find(TRUE)) {
    throw new API_Exception(
      'A valid original contribution ID is required', 'invalid_data');
  }
  $original_contribution = clone $contribution;
  try {
    if (!$contribution->loadRelatedObjects($input, $ids, FALSE, TRUE)) {
      throw new API_Exception('failed to load related objects');
    }

    unset($contribution->id, $contribution->receive_date, $contribution->invoice_id);
    $contribution->contribution_status_id = $params['contribution_status_id'];
    $contribution->receive_date = $params['receive_date'];

    $passThroughParams = array('trxn_id', 'total_amount', 'campaign_id', 'fee_amount');
    $input = array_intersect_key($params, array_fill_keys($passThroughParams, NULL));

    $params = _ipn_process_transaction($params, $contribution, $input, $ids, $original_contribution);
  }
  catch(Exception $e) {
    throw new API_Exception('failed to load related objects' . $e->getMessage() . "\n" . $e->getTraceAsString());
  }
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
 * @param CRM_Contribute_BAO_Contribution $firstContribution
 *
 * @return mixed
 */
function _ipn_process_transaction(&$params, $contribution, $input, $ids, $firstContribution = NULL) {
  $objects = $contribution->_relatedObjects;
  $objects['contribution'] = &$contribution;

  if ($firstContribution) {
    $objects['first_contribution'] = $firstContribution;
  }
  $input['component'] = $contribution->_component;
  $input['is_test'] = $contribution->is_test;
  $input['amount'] = empty($input['total_amount']) ? $contribution->total_amount : $input['total_amount'];

  if (isset($params['is_email_receipt'])) {
    $input['is_email_receipt'] = $params['is_email_receipt'];
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
  // @todo required for base ipn but problematic as api layer handles this
  $transaction = new CRM_Core_Transaction();
  $ipn = new CRM_Core_Payment_BaseIPN();
  $ipn->completeTransaction($input, $ids, $objects, $transaction, !empty($contribution->contribution_recur_id));
  return $params;
}

/**
 * Provide function metadata.
 *
 * @param array $params
 */
function _civicrm_api3_contribution_repeattransaction_spec(&$params) {
  $params['original_contribution_id'] = array(
    'title' => 'Original Contribution ID',
    'type' => CRM_Utils_Type::T_INT,
    'api.required' => TRUE,
  );
  $params['trxn_id'] = array(
    'title' => 'Transaction ID',
    'type' => CRM_Utils_Type::T_STRING,
  );
  $params['is_email_receipt'] = array(
    'title' => 'Send email Receipt?',
    'type' => CRM_Utils_Type::T_BOOLEAN,
  );
  $params['contribution_status_id'] = array(
    'title' => 'Contribution Status ID',
    'name' => 'contribution_status_id',
    'type' => CRM_Utils_Type::T_INT,
    'pseudoconstant' => array(
      'optionGroupName' => 'contribution_status',
    ),
    'api.required' => TRUE,
  );
  $params['receive_date'] = array(
    'title' => 'Contribution Receive Date',
    'name' => 'receive_date',
    'type' => CRM_Utils_Type::T_DATE,
    'api.default' => 'now',
  );
  $params['trxn_id'] = array(
    'title' => 'Transaction ID',
    'name' => 'trxn_id',
    'type' => CRM_Utils_Type::T_STRING,
  );
  $params['payment_processor_id'] = array(
    'description' => ts('Payment processor ID, will be loaded from contribution_recur if not provided'),
    'title' => 'Payment processor ID',
    'name' => 'payment_processor_id',
    'type' => CRM_Utils_Type::T_INT,
  );
}
