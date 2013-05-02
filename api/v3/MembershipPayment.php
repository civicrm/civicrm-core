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
 * File for the CiviCRM APIv3 membership contribution link functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Membership
 * @todo delete function doesn't exist
 * @copyright CiviCRM LLC (c) 2004-2013
 * @version $Id: MembershipContributionLink.php 30171 2010-10-14 09:11:27Z mover $
 */

/**
 * Include utility functions
 */

require_once 'CRM/Member/DAO/MembershipPayment.php';

/**
 * Add or update a link between contribution and membership
 *
 * @param  array   $params           (reference ) input parameters
 *
 * @return array (reference )        membership_payment_id of created or updated record
 * {@getfields MembershipPayment_create}
 * @example MembershipPaymentCreate.php
 * @access public
 */
function civicrm_api3_membership_payment_create($params) {

  require_once 'CRM/Core/Transaction.php';
  $transaction = new CRM_Core_Transaction();


  $mpDAO = new CRM_Member_DAO_MembershipPayment();
  $mpDAO->copyValues($params);
  $result = $mpDAO->save();

  if (is_a($result, 'CRM_Core_Error')) {
    $transaction->rollback();
    return civicrm_api3_create_error($result->_errors[0]['message']);
  }

  $transaction->commit();

  _civicrm_api3_object_to_array($mpDAO, $mpArray[$mpDAO->id]);

  return civicrm_api3_create_success($mpArray, $params);
}

/**
 * Adjust Metadata for Create action
 * 
 * The metadata is used for setting defaults, documentation & validation
 * @param array $params array or parameters determined by getfields
 */
function _civicrm_api3_membership_payment_create_spec(&$params) {
  $params['membership_id']['api.required'] = 1;
  $params['contribution_id']['api.required'] = 1;
}

/**
 * Retrieve one / all contribution(s) / membership(s) linked to a
 * membership / contrbution.
 *
 * @param  array   $params  input parameters
 *
 * @return array  array of properties, if error an array with an error id and error message
 *  @example MembershipPaymentGet
 * {@getfields MembershipPayment_get}
 * @access public
 */
function civicrm_api3_membership_payment_get($params) {


  return _civicrm_api3_basic_get('CRM_Member_DAO_MembershipPayment', $params);
}

