<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * @subpackage API_Pledge
 *
 * @copyright CiviCRM LLC (c) 2004-2014
 * @version $Id: Pledge.php
 *
 */

/**
 * Include utility functions
 */

/**
 * Creates or updates an Activity. See the example for usage
 *
 * @param array  $params       Associative array of property name/value
 *                             pairs for the activity.
 * {@getfields pledge_create}
 *
 * @return array Array containing 'is_error' to denote success or failure and details of the created pledge
 *
 * @example PledgeCreate.php Standard create example
 *
 */
function civicrm_api3_pledge_create($params) {
  _civicrm_api3_pledge_format_params($params, TRUE);
  $values = $params;
  //format the custom fields
  _civicrm_api3_custom_format_params($params, $values, 'Pledge');
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $values);
}

/**
 * Delete a pledge
 *
 * @param  array   $params           array included 'pledge_id' of pledge to delete
 *
 * @return boolean        true if success, else false
 * @static void
 * {@getfields pledge_delete}
 * @example PledgeDelete.php
 * @access public
 */
function civicrm_api3_pledge_delete($params) {
  if (CRM_Pledge_BAO_Pledge::deletePledge($params['id'])) {
    return civicrm_api3_create_success(array(
      'id' => $params['id']
    ), $params, 'pledge', 'delete');
  }
  else {
    return civicrm_api3_create_error('Could not delete pledge');
  }
}

/**
 * @param $params
 */
function _civicrm_api3_pledge_delete_spec(&$params) {
  // set as not required as pledge_id also acceptable & no either/or std yet
  $params['id']['api.aliases'] = array('pledge_id');
}

/**
 * return field specification specific to get requests
 */
function _civicrm_api3_pledge_get_spec(&$params) {
  $params['next_pay_date'] = array(
    'name' => 'next_pay_date',
    'type' => 12,
    'title' => 'Pledge Made',
    'api.filter' => 0,
    'api.return' => 1,
  );
  $params['pledge_is_test']['api.default'] = 0;
  $params['pledge_financial_type_id']['api.aliases'] = array('contribution_type_id', 'contribution_type');

}

/**
 * return field specification specific to get requests
 */
function _civicrm_api3_pledge_create_spec(&$params) {

  $required = array('contact_id', 'amount', 'installments', 'start_date', 'financial_type_id');
  foreach ($required as $required_field) {
    $params[$required_field]['api.required'] = 1;
  }
  // @todo this can come from xml
  $params['amount']['api.aliases'] = array('pledge_amount');
  $params['financial_type_id']['api.aliases'] = array('contribution_type_id', 'contribution_type');
}

/**
 * Retrieve a set of pledges, given a set of input params
 *
 * @param  array $params input parameters. Use interrogate for possible fields
 *
 * @return array  array of pledges, if error an array with an error id and error message
 * {@getfields pledge_get}
 * @example PledgeGet.php
 * @access public
 */
function civicrm_api3_pledge_get($params) {
  $mode = CRM_Contact_BAO_Query::MODE_PLEDGE;
  $entity = 'pledge';

  list($dao, $query) = _civicrm_api3_get_query_object($params, $mode, $entity);

  $pledge = array();
  while ($dao->fetch()) {
    $pledge[$dao->pledge_id] = $query->store($dao);
  }

  return civicrm_api3_create_success($pledge, $params, 'pledge', 'get', $dao);
}

/**
 * Set default to not return test params
 */
function _civicrm_api3_pledge_get_defaults() {
  return array('pledge_test' => 0);
}

/**
 * Legacy function - I removed a bunch of stuff no longer required from here but it still needs
 * more culling
 * take the input parameter list as specified in the data model and
 * convert it into the same format that we use in QF and BAO object
 *
 * @param array $values The reformatted properties that we can use internally
 *                            '
 *
 * @param bool $create
 *
 * @internal param array $params Associative array of property name/value
 *                             pairs to insert in new contact.
 * @return array|CRM_Error
 * @access public
 */
function _civicrm_api3_pledge_format_params(&$values, $create = FALSE) {

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

