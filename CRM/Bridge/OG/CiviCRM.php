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
class CRM_Bridge_OG_CiviCRM {

  /**
   * @param int $groupID
   * @param $group
   * @param $op
   */
  public static function group($groupID, $group, $op) {
    if ($op == 'add') {
      self::groupAdd($groupID, $group);
    }
    else {
      self::groupDelete($groupID, $group);
    }
  }

  /**
   * @param int $groupID
   * @param $group
   */
  public static function groupAdd($groupID, $group) {
    $ogID = CRM_Bridge_OG_Utils::ogID($groupID);

    $node = new StdClass();
    if ($ogID) {
      $node->nid = $ogID;
    }

    global $user;
    $node->uid = $user->uid;
    $node->title = $group->title;
    $node->type = 'og';
    $node->status = 1;

    // set the og values
    $node->og_description = $group->description;
    $node->og_selective = OF_OPEN;
    $node->og_register = 0;
    $node->og_directory = 1;

    node_save($node);

    // also change the source field of the group
    CRM_Core_DAO::setFieldValue('CRM_Contact_DAO_Group',
      $groupID,
      'source',
      CRM_Bridge_OG_Utils::ogSyncName($node->nid)
    );
  }

  /**
   * @param int $groupID
   * @param $group
   */
  public static function groupDelete($groupID, $group) {
    $ogID = CRM_Bridge_OG_Utils::ogID($groupID);
    if (!$ogID) {
      return;
    }

    node_delete($ogID);
  }

  /**
   * @param int $groupID
   * @param $contactIDs
   * @param $op
   */
  public static function groupContact($groupID, $contactIDs, $op) {
    $config = CRM_Core_Config::singleton();
    $ogID = CRM_Bridge_OG_Utils::ogID($groupID);

    if (!$ogID) {
      return;
    }

    foreach ($contactIDs as $contactID) {
      $drupalID = CRM_Core_BAO_UFMatch::getUFId($contactID);
      if ($drupalID) {
        if ($op == 'add') {
          $group_membership = $config->userSystem->og_membership_create($ogID, $drupalID);
        }
        else {
          $group_membership = $config->userSystem->og_membership_delete($ogID, $drupalID);
        }
      }
    }
  }

}
