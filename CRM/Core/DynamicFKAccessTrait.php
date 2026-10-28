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
 * Trait for entities with an entity_table + entity_id dynamic FK.
 *
 * Users of this trait must implement HookInterface.
 * @phpstan-require-implements \Civi\Core\HookInterface
 */
trait CRM_Core_DynamicFKAccessTrait {

  /**
   * Callback for hook_civicrm_pre.
   *
   * Enforce permissions on dynamic foreign keys for entities using this trait.
   *
   * @param \Civi\Core\Event\PreEvent $event
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public static function checkDynamicFKAccessPre(\Civi\Core\Event\PreEvent $event): void {
    if (empty($event->params['check_permissions'])) {
      return;
    }

    $userID = \CRM_Core_Session::getLoggedInContactID() ?: 0;
    $eid = $event->params['entity_id'] ?? NULL;
    $table = $event->params['entity_table'] ?? NULL;
    $id = $event->id ?: ($event->params['id'] ?? NULL);

    if ($eid && !$table && $id) {
      $table = CRM_Core_DAO::getFieldValue(static::class, $id, 'entity_table');
    }
    if ($table && !$eid && $id) {
      $eid = CRM_Core_DAO::getFieldValue(static::class, $id, 'entity_id');
    }

    $checked = [];
    if ($eid && $table) {
      $targetEntity = CRM_Core_DAO_AllCoreTables::getEntityNameForTable($table);
      if ($targetEntity === NULL) {
        throw new \CRM_Core_Exception(sprintf('Cannot resolve permissions for dynamic foreign key in "%s". Invalid table reference "%s".',
          static::getTableName(), $table));
      }
      if (!CoreUtil::checkAccessDelegated($targetEntity, 'update', ['id' => $eid], $userID)) {
        throw new \Civi\API\Exception\UnauthorizedException(\ts('Permission denied to modify %1 record.', [1 => $targetEntity]));
      }
      $checked[] = "$table:$eid";
    }

    if ($id) {
      $storedEid = CRM_Core_DAO::getFieldValue(static::class, $id, 'entity_id');
      $storedTable = CRM_Core_DAO::getFieldValue(static::class, $id, 'entity_table');
      if ($storedEid && $storedTable && !in_array("$storedTable:$storedEid", $checked, TRUE)) {
        $storedEntity = CRM_Core_DAO_AllCoreTables::getEntityNameForTable($storedTable);
        if ($storedEntity === NULL) {
          throw new \CRM_Core_Exception(sprintf('Cannot resolve permissions for dynamic foreign key in "%s". Invalid table reference "%s".',
            static::getTableName(), $storedTable));
        }
        if (!CoreUtil::checkAccessDelegated($storedEntity, 'update', ['id' => $storedEid], $userID)) {
          throw new \Civi\API\Exception\UnauthorizedException(\ts('Permission denied to modify %1 record.', [1 => $storedEntity]));
        }
      }
    }
  }

  /**
   * @see \Civi\Api4\Utils\CoreUtil::checkAccessRecord
   */
  public static function checkDynamicFKAccessAuthorizeRecord(AuthorizeRecordEvent $e): void {
    $record = $e->getRecord();
    $userID = $e->getUserID();
    $delegateAction = $e->getActionName() === 'get' ? 'get' : 'update';
    $eid = $record['entity_id'] ?? NULL;
    $table = $record['entity_table'] ?? NULL;
    $id = $record['id'] ?? NULL;

    if ($eid && !$table && $id) {
      $table = CRM_Core_DAO::getFieldValue(static::class, $id, 'entity_table');
    }
    if ($table && !$eid && $id) {
      $eid = CRM_Core_DAO::getFieldValue(static::class, $id, 'entity_id');
    }

    $checked = [];
    if ($eid && $table) {
      $targetEntity = CRM_Core_DAO_AllCoreTables::getEntityNameForTable($table);
      if ($targetEntity === NULL) {
        throw new \CRM_Core_Exception(sprintf('Cannot resolve permissions for dynamic foreign key in "%s". Invalid table reference "%s".',
          static::getTableName(), $table));
      }
      if (!CoreUtil::checkAccessDelegated($targetEntity, $delegateAction, ['id' => $eid], $userID)) {
        $e->setAuthorized(FALSE);
        return;
      }
      $checked[] = "$table:$eid";
    }

    if ($id) {
      $storedEid = CRM_Core_DAO::getFieldValue(static::class, $id, 'entity_id');
      $storedTable = CRM_Core_DAO::getFieldValue(static::class, $id, 'entity_table');
      if ($storedEid && $storedTable && !in_array("$storedTable:$storedEid", $checked, TRUE)) {
        $storedEntity = CRM_Core_DAO_AllCoreTables::getEntityNameForTable($storedTable);
        if ($storedEntity === NULL) {
          throw new \CRM_Core_Exception(sprintf('Cannot resolve permissions for dynamic foreign key in "%s". Invalid table reference "%s".',
            static::getTableName(), $storedTable));
        }
        if (!CoreUtil::checkAccessDelegated($storedEntity, $delegateAction, ['id' => $storedEid], $userID)) {
          $e->setAuthorized(FALSE);
          return;
        }
        $checked[] = "$storedTable:$storedEid";
      }
    }

    if ($checked && $e->isAuthorized() !== FALSE) {
      $e->setAuthorized(TRUE);
    }
  }

}
