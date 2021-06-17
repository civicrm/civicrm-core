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
 * Search Display BAO
 */
class CRM_Search_BAO_SearchDisplay extends CRM_Search_DAO_SearchDisplay {

  /**
   * Ensure only super-admins are allowed to create or update displays with acl_bypass
   *
   * @param string $entityName
   * @param string $action
   * @param array $record
   * @param int $userCID
   * @return bool
   */
  public static function _checkAccess(string $entityName, string $action, array $record, int $userCID) {
    // If we hit this function at all, the user is not a super-admin
    // But they must be at least a regular administrator
    if (!CRM_Core_Permission::check('administer CiviCRM data')) {
      return FALSE;
    }
    if (in_array($action, ['create', 'update'], TRUE)) {
      // Do not allow acl_bypass to be set to TRUE
      if (!empty($record['acl_bypass'])) {
        return FALSE;
      }
      // Do not allow edits to an existing record with acl_bypass = TRUE
      if (!empty($record['id'])) {
        return !CRM_Core_DAO::getFieldValue(__CLASS__, $record['id'], 'acl_bypass');
      }
    }
    return TRUE;
  }

  /**
   * Ensure only super-admins may update SavedSearches linked to displays with acl_bypass
   *
   * @param \Civi\Api4\Event\AuthorizeRecordEvent $e
   */
  public static function savedSearchCheckAccessByDisplay(\Civi\Api4\Event\AuthorizeRecordEvent $e) {
    if ($e->getActionName() === 'update') {
      $id = (int) $e->getRecord()['id'];
      $sql = "SELECT COUNT(id) FROM civicrm_search_display WHERE acl_bypass = 1 AND saved_search_id = $id";
      if (CRM_Core_DAO::singleValueQuery($sql)) {
        $e->setAuthorized(FALSE);
      }
    }
  }

}
