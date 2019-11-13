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
 *  Access Control EntityRole.
 */
class CRM_ACL_BAO_EntityRole extends CRM_ACL_DAO_EntityRole {
  public static $_entityTable = NULL;

  /**
   * Get entity table.
   *
   * @return array|null
   */
  public static function entityTable() {
    if (!self::$_entityTable) {
      self::$_entityTable = [
        'civicrm_contact' => ts('Contact'),
        'civicrm_group' => ts('Group'),
      ];
    }
    return self::$_entityTable;
  }

  /**
   * @param array $params
   *
   * @return CRM_ACL_DAO_EntityRole
   */
  public static function create(&$params) {
    $dao = new CRM_ACL_DAO_EntityRole();
    $dao->copyValues($params);
    $dao->save();
    return $dao;
  }

  /**
   * @param array $params
   * @param $defaults
   */
  public static function retrieve(&$params, &$defaults) {
    CRM_Core_DAO::commonRetrieve('CRM_ACL_DAO_EntityRole', $params, $defaults);
  }

  /**
   * Update the is_active flag in the db.
   *
   * @param int $id
   *   Id of the database record.
   * @param bool $is_active
   *   Value we want to set the is_active field.
   *
   * @return bool
   *   true if we found and updated the object, else false
   */
  public static function setIsActive($id, $is_active) {
    return CRM_Core_DAO::setFieldValue('CRM_ACL_DAO_EntityRole', $id, 'is_active', $is_active);
  }

  /**
   * Delete Entity Role records.
   *
   * @param int $entityRoleId
   *   ID of the EntityRole record to be deleted.
   *
   */
  public static function del($entityRoleId) {
    $entityDAO = new CRM_ACL_DAO_EntityRole();
    $entityDAO->id = $entityRoleId;
    $entityDAO->find(TRUE);
    $entityDAO->delete();
  }

}
