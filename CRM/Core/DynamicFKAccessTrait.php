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
 * Trait for with entities with an entity_table + entity_id dynamic FK.
 */
trait CRM_Core_DynamicFKAccessTrait {

  /**
   * @param string $action
   * @param array $record
   * @param int|NULL $userID
   * @return bool
   * @see CRM_Core_DAO::checkAccess
   */
  public static function _checkAccess(string $action, array $record, $userID) {
    $eid = $record['entity_id'] ?? NULL;
    $table = $record['entity_table'] ?? NULL;
    if (!$eid && !empty($record['id'])) {
      $eid = CRM_Core_DAO::getFieldValue(__CLASS__, $record['id'], 'entity_id');
    }
    if ($eid && !$table && !empty($record['id'])) {
      $table = CRM_Core_DAO::getFieldValue(__CLASS__, $record['id'], 'entity_table');
    }
    if ($eid && $table) {
      $bao = CRM_Core_DAO_AllCoreTables::getBAOClassName(CRM_Core_DAO_AllCoreTables::getClassForTable($table));
      return $bao::checkAccess(CRM_Core_Permission::EDIT, ['id' => $eid], $userID);
    }
  }

}
