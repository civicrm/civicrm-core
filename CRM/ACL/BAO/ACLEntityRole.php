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
 *  Access Control AclRole.
 */
class CRM_ACL_BAO_ACLEntityRole extends CRM_ACL_DAO_ACLEntityRole {

  /**
   * Whitelist of possible values for the entity_table field
   *
   * @return array
   */
  public static function entityTables(): array {
    return [
      'civicrm_contact' => ts('Contact'),
      'civicrm_group' => ts('Group'),
    ];
  }

  /**
   * @param array $params
   *
   * @return CRM_ACL_DAO_EntityRole
   */
  public static function create(&$params) {
    return self::writeRecord($params);
  }

  /**
   * @param array $params
   * @param $defaults
   */
  public static function retrieve(&$params, &$defaults) {
    CRM_Core_DAO::commonRetrieve(__CLASS__, $params, $defaults);
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
    return CRM_Core_DAO::setFieldValue(__CLASS__, $id, 'is_active', $is_active);
  }

  /**
   * Delete Dedupe Entity Role records.
   *
   * @param int $entityRoleId
   *   ID of the EntityRole record to be deleted.
   *
   */
  public static function del($entityRoleId) {
    return parent::deleteRecord(['id' => $entityRoleId]);
  }

}
