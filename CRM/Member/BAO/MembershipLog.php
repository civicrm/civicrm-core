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
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
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

    return $membershipLog;
  }

  /**
   * Delete membership log record.
   *
   * @param int $id
   *
   * @return mixed
   *
   * @deprecated
   */
  public static function del($id) {
    return (bool) static::deleteRecord(['id' => $id]);
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

    $params = [1 => [$contactID, 'Integer']];
    CRM_Core_DAO::executeQuery($query, $params);
  }

}
