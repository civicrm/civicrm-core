<?php
// $Id$

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
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
 * File for the CiviCRM APIv3 Pledge functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Pledge_Payment
 *
 * @copyright CiviCRM LLC (c) 2004-2013
 * @version $Id: PledgePayment.php
 *
 */

/**
 * Include utility functions
 */
require_once 'CRM/Pledge/BAO/PledgePayment.php';

/**
 * Add or update a plege payment. Pledge Payment API doesn't actually add a pledge
 *  if the request is to 'create' and 'id' is not passed in
 * the oldest pledge with no associated contribution is updated
 *
 * @todo possibly add ability to add payment if there are less payments than pledge installments
 * @todo possibily add ability to recalc dates if the schedule is changed
 *
 * @param  array   $params    input parameters
 * {@getfields PledgePayment_create}
 * @example PledgePaymentCreate.php
 *
 * @return array API Result
 * @static void
 * @access public
 */
function civicrm_api3_pledge_payment_create($params) {

  $paymentParams = $params;
  if (empty($params['id']) && !CRM_Utils_Array::value('option.create_new', $params)) {
    $paymentDetails = CRM_Pledge_BAO_PledgePayment::getOldestPledgePayment($params['pledge_id']);
    if (empty($paymentDetails)) {
      return civicrm_api3_create_error("There are no unmatched payment on this pledge. Pass in the pledge_payment id to specify one or 'option.create_new' to create one");
    }
    elseif (is_array($paymentDetails)) {
      $paymentParams = array_merge($params, $paymentDetails);
    }
  }

  $dao = CRM_Pledge_BAO_PledgePayment::add($paymentParams);
  if(empty($dao->pledge_id)){
    $dao->find(True);
  }
  _civicrm_api3_object_to_array($dao, $result[$dao->id]);


  //update pledge status
  CRM_Pledge_BAO_PledgePayment::updatePledgePaymentStatus($dao->pledge_id);

  return civicrm_api3_create_success($result, $params, 'pledge_payment', 'create', $dao);
}

/**
 * Adjust Metadata for Create action
 *
 * The metadata is used for setting defaults, documentation & validation
 * @param array $params array or parameters determined by getfields
 */
function _civicrm_api3_pledge_payment_create_spec(&$params) {
  $params['pledge_id']['api.required'] = 1;
  $params['status_id']['api.required'] = 1;
}

/**
 * Delete a pledge Payment - Note this deletes the contribution not just the link
 *
 * @param  array   $params     input parameters
 * {@getfields PledgePayment_delete}
 * @example PledgePaymentDelete.php
 *
 * @return array API result
 * @static void
 * @access public
 */
function civicrm_api3_pledge_payment_delete($params) {

  if (CRM_Pledge_BAO_PledgePayment::del($params['id'])) {
    return civicrm_api3_create_success(array('id' => $params['id']), $params,'pledge_payment','delete');
  }
  else {
    return civicrm_api3_create_error('Could not delete payment');
  }
}

/**
 * Retrieve a set of pledges, given a set of input params
 *
 * @param  array   $params     input parameters
 * {@getfields PledgePayment_get}
 * @example PledgePaymentGet.php *
 *
 * @return array (reference )        array of pledges, if error an array with an error id and error message
 * @static void
 * @access public
 */
function civicrm_api3_pledge_payment_get($params) {


  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

function updatePledgePayments($pledgeId, $paymentStatusId, $paymentIds) {

  $result = updatePledgePayments($pledgeId, $paymentStatusId, $paymentIds = NULL);
  return $result;
}

/**
 * Gets field for civicrm_pledge_payment functions
 *
 * @return array fields valid for other functions
 */
function civicrm_api3_pledge_payment_get_spec(&$params) {
  $params['option.create_new'] = array('title' => "Create new field rather than update an unpaid payment");
}

