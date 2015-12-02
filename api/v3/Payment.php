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
 * This api exposes CiviCRM Contribution Payment records.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Retrieve a set of contributions which are payments.
 *
 * @param array $params
 *  Input parameters.
 *
 * @return array
 *   Array of contributions which are payments, if error an array with an error id and error message
 */
function civicrm_api3_payment_get($params) {
  
  require_once 'api/v3/Contribution.php';

  $mode = CRM_Contact_BAO_Query::MODE_CONTRIBUTE;
  $params['is_payment'] = 1;
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
 * Add or update a Contribution which is a payment.
 *
 * @param array $params
 *   Input parameters.
 *
 * @throws API_Exception
 * @return array
 *   Api result array
 */
function civicrm_api3_payment_create(&$params) {
  $values = array();
  _civicrm_api3_custom_format_params($params, $values, 'Contribution');
  $params = array_merge($params, $values);
  if (empty($params['contribution_id']) || 
      (isset($params['contribution_id']) && !CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution', $params['contribution_id'], 'id'))) {
    return civicrm_api3_create_error(ts('You need to supply a valid contribution ID to create a payment'));
  }
  // Get paid lineitems
  $lineItems = CRM_Contribute_BAO_Contribution::getPaidLineItems($params);
  $params['id'] = $params['contribution_id'];

  if (!empty($params['contribution_id']) && !empty($params['contribution_status_id'])) {
    $error = array();
    //throw error for invalid status change such as setting completed back to pending
    //@todo this sort of validation belongs in the BAO not the API - if it is not an OK
    // action it needs to be blocked there. If it is Ok through a form it needs to be OK through the api
    CRM_Contribute_BAO_Contribution::checkStatusValidation(NULL, $params, $error);
    if (array_key_exists('contribution_status_id', $error)) {
      throw new API_Exception($error['contribution_status_id']);
    }
  }
  if (!empty($params['contribution_id']) && !empty($params['financial_type_id'])) {
    $error = array();
    CRM_Contribute_BAO_Contribution::checkFinancialTypeChange($params['financial_type_id'], $params['id'], $error);
    if (array_key_exists('financial_type_id', $error)) {
      throw new API_Exception($error['financial_type_id']);
    }
  }
  _civicrm_api3_contribution_create_legacy_support_45($params);

  // Make sure tax calculation is handled via api.
  $params = CRM_Contribute_BAO_Contribution::checkTaxAmount($params);
  CRM_Core_Error::debug( '$params', $params );
  
  $contribution = civicrm_api3('Contribution', 'get', $params);

  return _civicrm_api3_basic_create('CRM_Contribute_BAO_Contribution', $params, 'Contribution');
}

/**
 * Adjust Metadata for Create action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_payment_create_spec(&$params) {
  $params['contribution_id']['api.required'] = 1;   
  $params['total_amount']['api.required'] = 1;  
  $params['payment_processor_id']['description'] = 'Payment processor ID - required for payment processor payments'; 
}