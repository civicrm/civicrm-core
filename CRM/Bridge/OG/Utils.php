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
class CRM_Bridge_OG_Utils {
  const aclEnabled = 1, syncFromCiviCRM = 1;

  /**
   * @return int
   */
  public static function aclEnabled() {
    return self::aclEnabled;
  }

  /**
   * Switch to stop synchronization from CiviCRM.
   * This was always false before, and is always true
   * now.  Most likely, this needs to be a setting.
   */
  public static function syncFromCiviCRM() {
    // make sure that acls are not enabled
    //RMT -- the following makes no f**king sense...
    //return ! self::aclEnabled & self::syncFromCiviCRM;
    return TRUE;
  }

  /**
   * @param int $ogID
   *
   * @return string
   */
  public static function ogSyncName($ogID) {
    return "OG Sync Group :{$ogID}:";
  }

  /**
   * @param int $ogID
   *
   * @return string
   */
  public static function ogSyncACLName($ogID) {
    return "OG Sync Group ACL :{$ogID}:";
  }

  /**
   * @param int $groupID
   *
   * @return int|null|string
   * @throws Exception
   */
  public static function ogID($groupID) {
    $source = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Group',
      $groupID,
      'source'
    );

    if (str_contains($source, 'OG Sync Group')) {
      preg_match('/:(\d+):$/', $source, $matches);
      if (is_numeric($matches[1])) {
        return $matches[1];
      }
    }
    return NULL;
  }

  /**
   * @param int $ufID
   *
   * @return int
   * @throws Exception
   */
  public static function contactID($ufID) {
    $contactID = CRM_Core_BAO_UFMatch::getContactId($ufID);
    if ($contactID) {
      return $contactID;
    }
    // else synchronize contact for this user

    $account = user_load($ufID);

    CRM_Core_BAO_UFMatch::synchronizeUFMatch($account, $ufID, $account->mail, 'Drupal');
    $contactID = CRM_Core_BAO_UFMatch::getContactId($ufID);
    if (!$contactID) {
      throw new CRM_Core_Exception('no contact found');
    }
    return $contactID;
  }

  /**
   * @param string $source
   * @param string|null $title
   * @param bool $abort
   *
   * @return null|string
   * @throws \CRM_Core_Exception
   */
  public static function groupID($source, $title = NULL, $abort = FALSE) {
    $query = "
SELECT id
  FROM civicrm_group
 WHERE source = %1";
    $params = [1 => [$source, 'String']];

    if ($title) {
      $query .= " OR title = %2";
      $params[2] = [$title, 'String'];
    }

    $groupID = CRM_Core_DAO::singleValueQuery($query, $params);
    if ($abort &&
      !$groupID
    ) {
      throw new CRM_Core_Exception('no group found');
    }

    return $groupID;
  }

}
