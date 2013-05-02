<?php
// $Id: MembershipContributionLink.php 45502 2013-02-08 13:32:55Z kurund $


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
 * File for the CiviCRM APIv2 membership contribution link functions
 *
 * @todo Probably needs renaming
 *
 * @package CiviCRM_APIv2
 * @subpackage API_Membership
 *
 * @copyright CiviCRM LLC (c) 2004-2013
 * @version $Id: MembershipContributionLink.php 45502 2013-02-08 13:32:55Z kurund $
 */

/**
 * Include utility functions
 */
require_once 'api/v2/utils.php';

/**
 * Add or update a link between contribution and membership
 *
 * @param  array   $params           (reference ) input parameters
 *
 * @return array (reference )        membership_payment_id of created or updated record
 * @static void
 * @access public
 */
function &civicrm_membershipcontributionlink_create(&$params) {
  _civicrm_initialize();

  if (empty($params)) {
    return civicrm_create_error(ts('No input parameters present'));
  }

  if (!is_array($params)) {
    return civicrm_create_error(ts('Input parameters is not an array'));
  }

  if (!isset($params['contribution_id']) ||
    !isset($params['membership_id'])
  ) {
    return civicrm_create_error(ts('Required parameters missing'));
  }

  require_once 'CRM/Core/Transaction.php';
  $transaction = new CRM_Core_Transaction();

  require_once 'CRM/Member/DAO/MembershipPayment.php';
  $mpDAO = new CRM_Member_DAO_MembershipPayment();
  $mpDAO->copyValues($params);
  $result = $mpDAO->save();

  if (is_a($result, 'CRM_Core_Error')) {
    $transaction->rollback();
    return civicrm_create_error($result->_errors[0]['message']);
  }

  $transaction->commit();

  _civicrm_object_to_array($mpDAO, $mpArray);

  return $mpArray;
}

/**
 * Retrieve one / all contribution(s) / membership(s) linked to a
 * membership / contrbution.
 *
 * @param  array   $params           (reference ) input parameters
 *
 * @return array (reference )        array of properties, if error an array with an error id and error message
 * @static void
 * @access public
 */
function &civicrm_membershipcontributionlink_get(&$params) {
  _civicrm_initialize();

  if (empty($params)) {
    return civicrm_create_error(ts('No input parameters present'));
  }

  if (!is_array($params)) {
    return civicrm_create_error(ts('Input parameters is not an array'));
  }

  require_once 'CRM/Member/DAO/MembershipPayment.php';
  $mpDAO = new CRM_Member_DAO_MembershipPayment();
  $mpDAO->copyValues($params);
  $mpDAO->id = CRM_Utils_Array::value('membership_contribution_id', $params);
  $mpDAO->find();

  $values = array();
  while ($mpDAO->fetch()) {
    _civicrm_object_to_array($mpDAO, $mpArray);
    $mpArray['membership_contribution_id'] = $mpDAO->id;
    unset($mpArray['id']);
    $values[$mpDAO->id] = $mpArray;
  }

  return $values;
}

