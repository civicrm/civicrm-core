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
 *
 */
class CRM_Core_Permission_DrupalBase extends CRM_Core_Permission_Base {

  /**
   * @param $uids
   *
   * @return string
   */
  public function getContactEmails($uids) {
    if (empty($uids)) {
      return '';
    }
    $uidString = implode(',', $uids);
    $sql = "
    SELECT     e.email
    FROM       civicrm_contact c
    INNER JOIN civicrm_email e     ON ( c.id = e.contact_id AND e.is_primary = 1 )
    INNER JOIN civicrm_uf_match uf ON ( c.id = uf.contact_id )
    WHERE      c.is_deceased = 0
    AND        c.is_deleted  = 0
    AND        uf.uf_id IN ( $uidString )
    ";

    $dao = CRM_Core_DAO::executeQuery($sql);

    $emails = [];
    while ($dao->fetch()) {
      $emails[] = $dao->email;
    }

    return implode(', ', $emails);
  }

  /**
   * Given a roles array, check for access requirements
   *
   * @param array $array
   *   The roles to check.
   *
   * @return bool
   *   true if yes, else false
   */
  public function checkGroupRole($array) {
    if (function_exists('user_load') && isset($array)) {
      $user = user_load($GLOBALS['user']->uid);
      //if giver roles found in user roles - return true
      foreach ($array as $key => $value) {
        if (in_array($value, $user->roles)) {
          return TRUE;
        }
      }
    }
    return FALSE;
  }

  /**
   * @inheritDoc
   */
  public function isModulePermissionSupported() {
    return TRUE;
  }

  /**
   * Get all the contact emails for users that have a specific permission.
   *
   * @param string $permissionName
   *   Name of the permission we are interested in.
   *
   * @return string
   *   a comma separated list of email addresses
   */
  public function permissionEmails($permissionName) {
    static $_cache = [];

    if (isset($_cache[$permissionName])) {
      return $_cache[$permissionName];
    }

    $uids = [];
    $sql = "
      SELECT {users}.uid, {role_permission}.permission
      FROM {users}
      JOIN {users_roles}
        ON {users}.uid = {users_roles}.uid
      JOIN {role_permission}
        ON {role_permission}.rid = {users_roles}.rid
      WHERE {role_permission}.permission = '{$permissionName}'
        AND {users}.status = 1
    ";

    $result = db_query($sql);
    foreach ($result as $record) {
      $uids[] = $record->uid;
    }

    $_cache[$permissionName] = self::getContactEmails($uids);
    return $_cache[$permissionName];
  }

  /**
   * @inheritDoc
   */
  public function upgradePermissions($permissions) {
    if (empty($permissions)) {
      throw new CRM_Core_Exception("Cannot upgrade permissions: permission list missing");
    }
    $query = db_delete('role_permission')
      ->condition('module', 'civicrm')
      ->condition('permission', array_keys($permissions), 'NOT IN');
    $query->execute();
  }

}
