<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 */
function civicrm_api3_payment_get($params) {
  $financialTrxn = array();
  $limit = '';
  if (isset($params['options']) && CRM_Utils_Array::value('limit', $params['options'])) {
    $limit = CRM_Utils_Array::value('limit', $params['options']);
  }
  $params['options']['limit'] = 0;
  $eft = civicrm_api3('EntityFinancialTrxn', 'get', $params);
  if (!empty($eft['values'])) {
    $eftIds = array();
    foreach ($eft['values'] as $efts) {
      if (empty($efts['financial_trxn_id'])) {
        continue;
      }
      $eftIds[] = $efts['financial_trxn_id'];
      $map[$efts['financial_trxn_id']] = $efts['entity_id'];
    }
    if (!empty($eftIds)) {
      $ftParams = array(
        'id' => array('IN' => $eftIds),
        'is_payment' => 1,
      );
      if ($limit) {
        $ftParams['options']['limit'] = $limit;
      }
      $financialTrxn = civicrm_api3('FinancialTrxn', 'get', $ftParams);
      foreach ($financialTrxn['values'] as &$values) {
        $values['contribution_id'] = $map[$values['id']];
      }
    }
  }
  return civicrm_api3_create_success(CRM_Utils_Array::value('values', $financialTrxn, array()), $params, 'Payment', 'get');
}

/**
 * Delete a payment.
 *
 * @param array $params
 *   Input parameters.
 *
 * @throws API_Exception
 * @return array
 *   Api result array
 */
function civicrm_api3_payment_delete(&$params) {
  return civicrm_api3('FinancialTrxn', 'delete', $params);
}

/**
 * Cancel/Refund a payment for a Contribution.
 *
 * @param array $params
 *   Input parameters.
 *
 * @throws API_Exception
 * @return array
 *   Api result array
 */
function civicrm_api3_payment_cancel(&$params) {
  $eftParams = array(
    'entity_table' => 'civicrm_contribution',
    'financial_trxn_id' => $params['id'],
  );
  $entity = civicrm_api3('EntityFinancialTrxn', 'getsingle', $eftParams);
  $contributionId = $entity['entity_id'];
  $params['total_amount'] = $entity['amount'];
  unset($params['id']);

  $trxn = CRM_Contribute_BAO_Contribution::recordAdditionalPayment($contributionId, $params, 'refund', NULL, FALSE);

  $values = array();
  _civicrm_api3_object_to_array_unique_fields($trxn, $values[$trxn->id]);
  return civicrm_api3_create_success($values, $params, 'Payment', 'cancel', $trxn);
}

/**
 * Add a payment for a Contribution.
 *
 * @param array $params
 *   Input parameters.
 *
 * @throws API_Exception
 * @return array
 *   Api result array
 */
function civicrm_api3_payment_create(&$params) {
  // Check if it is an update
  if (CRM_Utils_Array::value('id', $params)) {
    $amount = $params['total_amount'];
    civicrm_api3('Payment', 'cancel', $params);
    $params['total_amount'] = $amount;
  }
  $trxn = CRM_Contribute_BAO_Contribution::addPayment($params);

  $values = array();
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
  $params = array(
    'contribution_id' => array(
      'api.required' => 1 ,
      'title' => 'Contribution ID',
      'type' => CRM_Utils_Type::T_INT,
    ),
    'total_amount' => array(
      'api.required' => 1 ,
      'title' => 'Total Payment Amount',
      'type' => CRM_Utils_Type::T_FLOAT,
    ),
    'payment_processor_id' => array(
      'title' => 'Payment Processor ID',
      'type' => CRM_Utils_Type::T_INT,
      'description' => ts('Payment processor ID - required for payment processor payments'),
    ),
    'id' => array(
      'title' => 'Payment ID',
      'type' => CRM_Utils_Type::T_INT,
      'api.aliases' => array('payment_id'),
    ),
  );
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
  $params = array(
    'contribution_id' => array(
      'title' => 'Contribution ID',
      'type' => CRM_Utils_Type::T_INT,
    ),
    'entity_table' => array(
      'title' => 'Entity Table',
      'api.default' => 'civicrm_contribution',
    ),
    'entity_id' => array(
      'title' => 'Entity ID',
      'type' => CRM_Utils_Type::T_INT,
      'api.aliases' => array('contribution_id'),
    ),
  );
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
  $params = array(
    'id' => array(
      'api.required' => 1 ,
      'title' => 'Payment ID',
      'type' => CRM_Utils_Type::T_INT,
      'api.aliases' => array('payment_id'),
    ),
  );
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
  $params = array(
    'id' => array(
      'api.required' => 1 ,
      'title' => 'Payment ID',
      'type' => CRM_Utils_Type::T_INT,
      'api.aliases' => array('payment_id'),
    ),
  );
}
