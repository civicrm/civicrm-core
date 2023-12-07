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
 * This api exposes CiviCRM Pledge payment.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Add or update a pledge payment.
 *
 * Pledge Payment API doesn't actually add a pledge.
 * If the request is to 'create' and 'id' is not passed in
 * the oldest pledge with no associated contribution is updated.
 *
 * @todo possibly add ability to add payment if there are less payments than pledge installments
 * @todo possibly add ability to recalculate dates if the schedule is changed
 *
 * @param array $params
 *   Input parameters.
 *
 * @return array
 *   API Result
 */
function civicrm_api3_pledge_payment_create($params) {

  $paymentParams = $params;
  if (empty($params['id']) && empty($params['option.create_new'])) {
    $paymentDetails = CRM_Pledge_BAO_PledgePayment::getOldestPledgePayment($params['pledge_id']);
    if (empty($paymentDetails)) {
      return civicrm_api3_create_error("There are no unmatched payment on this pledge. Pass in the pledge_payment id to specify one or 'option.create_new' to create one");
    }
    elseif (is_array($paymentDetails)) {
      $paymentParams = array_merge($params, $paymentDetails);
    }
  }

  $dao = CRM_Pledge_BAO_PledgePayment::add($paymentParams);
  $result = [];
  if (empty($dao->pledge_id)) {
    $dao->find(TRUE);
  }
  _civicrm_api3_object_to_array($dao, $result[$dao->id]);

  //update pledge status
  CRM_Pledge_BAO_PledgePayment::updatePledgePaymentStatus($dao->pledge_id);

  return civicrm_api3_create_success($result, $params, 'PledgePayment', 'create', $dao);
}

/**
 * Adjust Metadata for Create action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_pledge_payment_create_spec(&$params) {
  $params['pledge_id']['api.required'] = 1;
  $params['status_id']['api.required'] = 1;
}

/**
 * Delete a pledge Payment - Note this deletes the contribution not just the
 * link.
 *
 * @param array $params
 *   Input parameters.
 *
 * @return array
 *   API result
 * @throws \CRM_Core_Exception
 * @noinspection PhpUnused
 */
function civicrm_api3_pledge_payment_delete(array $params): array {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Retrieve a set of pledges, given a set of input params.
 *
 * @param array $params
 *   Input parameters.
 *
 * @return array
 *   array of pledges, if error an array with an error id and error message
 */
function civicrm_api3_pledge_payment_get($params) {

  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}
