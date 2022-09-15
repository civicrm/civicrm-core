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
 * This api exposes CiviCRM Pledge.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Create or updates an Pledge.
 *
 * @param array $params
 *
 * @return array
 *   Array containing 'is_error' to denote success or failure and details of the created pledge
 * @throws \CRM_Core_Exception
 */
function civicrm_api3_pledge_create($params) {
  _civicrm_api3_pledge_format_params($params, TRUE);
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params, 'Pledge');
}

/**
 * Delete a pledge.
 *
 * @param array $params
 *   Array included 'pledge_id' of pledge to delete.
 *
 * @return array
 */
function civicrm_api3_pledge_delete($params) {
  if (CRM_Pledge_BAO_Pledge::deletePledge($params['id'])) {
    return civicrm_api3_create_success([
      'id' => $params['id'],
    ], $params, 'Pledge', 'delete');
  }
  else {
    return civicrm_api3_create_error('Could not delete pledge');
  }
}

/**
 * Adjust metadata for pledge delete action.
 *
 * @param array $params
 */
function _civicrm_api3_pledge_delete_spec(&$params) {
  // set as not required as pledge_id also acceptable & no either/or std yet
  $params['id']['api.aliases'] = ['pledge_id'];
}

/**
 * Adjust field specification specific to get requests.
 *
 * @param array $params
 */
function _civicrm_api3_pledge_get_spec(&$params) {
  $params['next_pay_date'] = [
    'name' => 'next_pay_date',
    'type' => 12,
    'title' => 'Pledge Made',
    'api.filter' => 0,
    'api.return' => 1,
  ];
  $params['pledge_is_test']['api.default'] = 0;
  $params['pledge_financial_type_id']['api.aliases'] = ['contribution_type_id', 'contribution_type'];

}

/**
 * Adjust field specification specific to get requests.
 *
 * @param array $params
 */
function _civicrm_api3_pledge_create_spec(&$params) {

  $required = ['contact_id', 'amount', 'installments', 'start_date', 'financial_type_id'];
  foreach ($required as $required_field) {
    $params[$required_field]['api.required'] = 1;
  }
  // @todo this can come from xml
  $params['amount']['api.aliases'] = ['pledge_amount'];
  $params['financial_type_id']['api.aliases'] = ['contribution_type_id', 'contribution_type'];
}

/**
 * Retrieve a set of pledges, given a set of input params.
 *
 * @param array $params
 *   Input parameters. Use interrogate for possible fields.
 *
 * @return array
 *   array of pledges, if error an array with an error id and error message
 */
function civicrm_api3_pledge_get($params) {
  $mode = CRM_Contact_BAO_Query::MODE_PLEDGE;

  list($dao, $query) = _civicrm_api3_get_query_object($params, $mode, 'Pledge');

  $pledge = [];
  while ($dao->fetch()) {
    $pledge[$dao->pledge_id] = $query->store($dao);
  }

  return civicrm_api3_create_success($pledge, $params, 'Pledge', 'get', $dao);
}

/**
 * Set default to not return test params.
 */
function _civicrm_api3_pledge_get_defaults() {
  return ['pledge_test' => 0];
}

/**
 * Legacy function to format pledge parameters.
 *
 * I removed a bunch of stuff no longer required from here but it still needs
 * more culling
 * take the input parameter list as specified in the data model and
 * convert it into the same format that we use in QF and BAO object
 *
 * @param array $values
 *   The reformatted properties that we can use internally.
 */
function _civicrm_api3_pledge_format_params(&$values) {

  // probably most of the below can be removed.... just needs a little more review
  if (array_key_exists('original_installment_amount', $values)) {
    $values['installment_amount'] = $values['original_installment_amount'];
    //it seems it will only create correctly with BOTH installment amount AND pledge_installment_amount set
    //pledge installment amount required for pledge payments
    $values['pledge_original_installment_amount'] = $values['original_installment_amount'];
  }

  if (array_key_exists('pledge_original_installment_amount', $values)) {
    $values['installment_amount'] = $values['pledge_original_installment_amount'];
  }

  if (empty($values['id'])) {
    //at this point both should be the same so unset both if not set - passing in empty
    //value causes crash rather creating new - do it before next section as null values ignored in 'switch'
    unset($values['id']);

    //if you have a single installment when creating & you don't set the pledge status (not a required field) then
    //status id is left null for pledge payments in BAO
    // so we are hacking in the addition of the pledge_status_id to pending here
    if (empty($values['status_id']) && $values['installments'] == 1) {
      $contributionStatus = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');
      $values['status_id'] = array_search('Pending', $contributionStatus);
    }
  }
  if (empty($values['scheduled_date']) && array_key_exists('start_date', $values)) {
    $values['scheduled_date'] = $values['start_date'];
  }
}
