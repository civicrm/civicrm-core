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
   * @param bool $abort
   *
   * @return int|null|string
   * @throws Exception
   */
  public static function ogID($groupID, $abort = TRUE) {
    $source = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Group',
      $groupID,
      'source'
    );

    if (strpos($source, 'OG Sync Group') !== FALSE) {
      preg_match('/:(\d+):$/', $source, $matches);
      if (is_numeric($matches[1])) {
        return $matches[1];
      }
    }
    if ($abort) {
      CRM_Core_Error::fatal();
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
      CRM_Core_Error::fatal();
    }
    return $contactID;
  }

  /**
   * @param $source
   * @param null $title
   * @param bool $abort
   *
   * @return null|string
   * @throws Exception
   */
  public static function groupID($source, $title = NULL, $abort = FALSE) {
    $query = "
SELECT id
  FROM civicrm_group
 WHERE source = %1";
    $params = array(1 => array($source, 'String'));

    if ($title) {
      $query .= " OR title = %2";
      $params[2] = array($title, 'String');
    }

    $groupID = CRM_Core_DAO::singleValueQuery($query, $params);
    if ($abort &&
      !$groupID
    ) {
      CRM_Core_Error::fatal();
    }

    return $groupID;
  }

}
