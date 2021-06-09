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

/**
 * Trait shared with entities attached to the contact record.
 */
trait CRM_Contact_AccessTrait {

  /**
   * @param string $entityName
   * @param string $action
   * @param array $record
   * @param int $userID
   * @return bool
   * @see CRM_Core_DAO::checkAccess
   */
  public static function _checkAccess(string $entityName, string $action, array $record, int $userID) {
    $cid = $record['contact_id'] ?? NULL;
    if (!$cid && !empty($record['id'])) {
      $cid = CRM_Core_DAO::getFieldValue(__CLASS__, $record['id'], 'contact_id');
    }
    if (!$cid) {
      // With no contact id this must be part of an event locblock
      return in_array(__CLASS__, ['CRM_Core_BAO_Phone', 'CRM_Core_BAO_Email', 'CRM_Core_BAO_Address']) &&
        CRM_Core_Permission::check('edit all events', $userID);
    }
    return \Civi\Api4\Utils\CoreUtil::checkAccessDelegated('Contact', 'update', ['id' => $cid], $userID);
  }

}
