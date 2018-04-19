<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
 * @copyright CiviCRM LLC (c) 2004-2018
 */

/**
 *  Access Control EntityRole.
 */
class CRM_ACL_BAO_EntityRole extends CRM_ACL_DAO_EntityRole {
  static $_entityTable = NULL;

  /**
   * Get entity table.
   *
   * @return array|null
   */
  public static function entityTable() {
    if (!self::$_entityTable) {
      self::$_entityTable = array(
        'civicrm_contact' => ts('Contact'),
        'civicrm_group' => ts('Group'),
      );
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
   * @return Object
   *   DAO object on success, null otherwise
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
