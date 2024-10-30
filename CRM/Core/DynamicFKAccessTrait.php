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

use Civi\Api4\Event\AuthorizeRecordEvent;
use Civi\Api4\Utils\CoreUtil;

/**
 * Trait for with entities with an entity_table + entity_id dynamic FK.
 */
trait CRM_Core_DynamicFKAccessTrait {

  /**
   * @see \Civi\Api4\Utils\CoreUtil::checkAccessRecord
   */
  public static function self_civi_api4_authorizeRecord(AuthorizeRecordEvent $e): void {
    $record = $e->getRecord();
    $userID = $e->getUserID();
    $delegateAction = $e->getActionName() === 'get' ? 'get' : 'update';
    $eid = $record['entity_id'] ?? NULL;
    $table = $record['entity_table'] ?? NULL;
    if (!$eid && !empty($record['id'])) {
      $eid = CRM_Core_DAO::getFieldValue(__CLASS__, $record['id'], 'entity_id');
    }
    if ($eid && !$table && !empty($record['id'])) {
      $table = CRM_Core_DAO::getFieldValue(__CLASS__, $record['id'], 'entity_table');
    }
    if ($eid && $table) {
      $targetEntity = CRM_Core_DAO_AllCoreTables::getEntityNameForTable($table);
      if ($targetEntity === NULL) {
        throw new \CRM_Core_Exception(sprintf('Cannot resolve permissions for dynamic foreign key in "%s". Invalid table reference "%s".',
          static::getTableName(), $table));
      }
      $e->setAuthorized(CoreUtil::checkAccessDelegated($targetEntity, $delegateAction, ['id' => $eid], $userID));
    }
  }

}
