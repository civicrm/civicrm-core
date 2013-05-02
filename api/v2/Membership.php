<?php
// $Id: Membership.php 45502 2013-02-08 13:32:55Z kurund $


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
 * File for the CiviCRM APIv2 membership functions
 *
 * @package CiviCRM_APIv2
 * @subpackage API_Membership
 *
 * @copyright CiviCRM LLC (c) 2004-2013
 * @version $Id: Membership.php 45502 2013-02-08 13:32:55Z kurund $
 *
 */

/**
 * Files required for this package
 */
require_once 'api/v2/utils.php';
require_once 'CRM/Utils/Rule.php';
require_once 'api/v2/MembershipContact.php';
require_once 'api/v2/MembershipType.php';
require_once 'api/v2/MembershipStatus.php';

/**
 * Deletes an existing contact membership
 *
 * This API is used for deleting a contact membership
 *
 * @param  Int  $membershipID   Id of the contact membership to be deleted
 *
 * @return null if successfull, object of CRM_Core_Error otherwise
 * @access public
 */
function civicrm_membership_delete(&$membershipID) {
  _civicrm_initialize();

  if (empty($membershipID)) {
    return civicrm_create_error('Membership ID cannot be empty.');
  }

  // membershipID should be numeric
  if (!is_numeric($membershipID)) {
    return civicrm_create_error('Input parameter should be numeric');
  }

  require_once 'CRM/Member/BAO/Membership.php';
  CRM_Member_BAO_Membership::deleteRelatedMemberships($membershipID);

  $membership = new CRM_Member_BAO_Membership();
  $result = $membership->deleteMembership($membershipID);

  return $result ? civicrm_create_success() : civicrm_create_error('Error while deleting Membership');
}

/**
 *
 * @param <type> $contactID
 *
 * @return <type>
 * @deprecated compatilibility wrappers
 */
function civicrm_contact_memberships_get(&$contactID) {
  return civicrm_membership_contact_get($contactID);
}

/**
 *
 * @param <type> $params
 *
 * @return <type>
 */
function civicrm_contact_membership_create(&$params) {
  return civicrm_membership_contact_create($params);
}

/**
 * wrapper function according to new api standards
 */
function civicrm_membership_create(&$params) {
  return civicrm_membership_contact_create($params);
}

/**
 *
 * @param <type> $params
 *
 * @return <type>
 */
function civicrm_membership_types_get(&$params) {
  return civicrm_membership_type_get($params);
}

/**
 *
 * @param <type> $params
 *
 * @return <type>
 */
function civicrm_membership_statuses_get(&$params) {
  return civicrm_membership_status_get($params);
}

