<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2016
 * $Id$
 *
 */
class CRM_Member_BAO_MembershipLog extends CRM_Member_DAO_MembershipLog {

  /**
   * Add the membership log record.
   *
   * @param array $params
   *   Properties of the log item.
   *
   * @return CRM_Member_DAO_MembershipLog|CRM_Core_Error
   */
  public static function add($params) {
    $membershipLog = new CRM_Member_DAO_MembershipLog();
    $membershipLog->copyValues($params);
    $membershipLog->save();
    $membershipLog->free();

    return $membershipLog;
  }

  /**
   * Delete membership log record.
   *
   * @param int $membershipID
   *
   * @return mixed
   */
  public static function del($membershipID) {
    $membershipLog = new CRM_Member_DAO_MembershipLog();
    $membershipLog->membership_id = $membershipID;
    return $membershipLog->delete();
  }

  /**
   * Reset the modified ID to NULL for log items by the given contact ID.
   *
   * @param int $contactID
   */
  public static function resetModifiedID($contactID) {
    $query = "
UPDATE civicrm_membership_log
   SET modified_id = null
 WHERE modified_id = %1";

    $params = array(1 => array($contactID, 'Integer'));
    CRM_Core_DAO::executeQuery($query, $params);
  }

}
