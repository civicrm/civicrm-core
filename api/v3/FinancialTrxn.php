<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * File for the CiviCRM APIv3 financial_transaction functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_FinancialTransation
 *
 * @copyright CiviCRM LLC (c) 2004-2013
 * @version $Id: FinancialTransation.php 2011-02-16 ErikHommel $
 */

/**
 * Add an FinancialTransation for a contact
 *
 * Allowed @params array keys are:
 *
 * @example FinancialTransationCreate.php Standard Create Example
 *
 * @return array API result array
 * {@getfields financial_transaction_create}
 * @access public
 */
function civicrm_api3_financial_transaction_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}


/**
 * @param $params
 */
function _civicrm_api3_financial_transaction_create_spec(&$params) {
  $params['trxn_date']['api.default'] = 'now';
  $params['payment_instrument_id']['api.required'] = TRUE;
  $params['total_amount']['api.required'] = TRUE;
  $params['contribution_id']['api.required'] = TRUE;
  $params['created_id']['api.default'] = 'user_contact_id';
  $params['contribution_status_id'] = array('title' => 'Optional over-ride for status id');
  $params['participant_status_id'] = array('title' => 'Optional Over-ride for participant id');
  $params['send_receipt'] = array('title' =>  'Send a receipt using built in template (possible future would be to specify the template)');
}


/**
 * Deletes an existing FinancialTransation
 *
 * @param  array  $params
 *
 * @example FinancialTransationDelete.php Standard Delete Example
 *
 * @return boolean | error  true if successfull, error otherwise
 * {@getfields financial_transaction_delete}
 * @access public
 */
function civicrm_api3_financial_transaction_delete($params) {
  //return 'go away - you can't delete transactions';
}

/**
 * Retrieve one or more financial_transactions
 *
 * @param  array input parameters
 *
 *
 * @example FinancialTransationGet.php Standard Get Example
 *
 * @param  array $params  an associative array of name/value pairs.
 *
 * @return  array api result array
 * {@getfields financial_transaction_get}
 * @access public
 */
function civicrm_api3_financial_transaction_get($params) {

  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

