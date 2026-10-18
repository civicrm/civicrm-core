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

use Civi\API\Exception\UnauthorizedException;
use Civi\Api4\Event\AuthorizeRecordEvent;

/**
 * Trait for entities with an owner contact ID (e.g. created_id, created_by, or contact_id).
 *
 * Users of this trait must implement HookInterface.
 * @phpstan-require-implements \Civi\Core\HookInterface
 */
trait CRM_Core_OwnershipAccessTrait {

  /**
   * Get the column name(s) representing the owner contact ID.
   *
   * @return string|array
   */
  protected static function getOwnerContactIdField(): string|array {
    return 'created_id';
  }

  /**
   * Check if current user has admin permission to manage/reassign ownership.
   */
  protected static function checkOwnerAdminPermission(?int $userID = NULL): bool {
    return CRM_Core_Permission::check(['administer CiviCRM data'], $userID);
  }

  /**
   * Callback for `hook_civicrm_pre` event.
   *
   * Block non-admins from changing the owner contact ID of an existing record.
   *
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public static function checkOwnershipAccessPre(\Civi\Core\Event\PreEvent $event): void {
    if (!in_array($event->action, ['edit', 'create'], TRUE)) {
      return;
    }
    $ownerFields = (array) static::getOwnerContactIdField();
    $ownerFieldsToValidate = array_intersect($ownerFields, array_keys($event->params));
    if (!$ownerFieldsToValidate) {
      return;
    }
    if (empty($event->params['check_permissions']) || static::checkOwnerAdminPermission()) {
      return;
    }
    if ($event->action === 'create') {
      $userID = CRM_Core_Session::getLoggedInContactID() ?: 0;
      foreach ($ownerFieldsToValidate as $ownerField) {
        if (!empty($event->params[$ownerField]) && (string) $event->params[$ownerField] !== (string) $userID) {
          throw new UnauthorizedException(\ts('Cannot change the %1 of a new %2.', [
            1 => $ownerField,
            2 => $event->entity,
          ]));
        }
      }
      return;
    }
    if ($event->action === 'edit' && !empty($event->id)) {
      foreach ($ownerFieldsToValidate as $ownerField) {
        $storedOwner = \CRM_Core_DAO::getFieldValue(static::class, $event->id, $ownerField);
        if ((string) $storedOwner !== (string) $event->params[$ownerField]) {
          throw new UnauthorizedException(\ts('Cannot change the %1 of an existing %2.', [
            1 => $ownerField,
            2 => $event->entity,
          ]));
        }
      }
    }
  }

  /**
   * Callback for `civi.api4.authorizeRecord` event.
   *
   * Block non-admins from changing the owner contact ID of an existing record.
   *
   * @param \Civi\Api4\Event\AuthorizeRecordEvent $e
   */
  public static function checkOwnershipAccessAuthorizeRecord(AuthorizeRecordEvent $e): void {
    $record = $e->getRecord();
    if (!in_array($e->getActionName(), ['update', 'create'], TRUE)) {
      return;
    }
    $ownerFields = (array) static::getOwnerContactIdField();
    $ownerFieldsToValidate = array_intersect($ownerFields, array_keys($record));
    if (!$ownerFieldsToValidate) {
      return;
    }
    if (static::checkOwnerAdminPermission($e->getUserID())) {
      return;
    }
    if ($e->getActionName() === 'create') {
      $userID = $e->getUserID() ?: (CRM_Core_Session::getLoggedInContactID() ?: 0);
      foreach ($ownerFieldsToValidate as $ownerField) {
        if (!empty($record[$ownerField]) && (string) $record[$ownerField] !== (string) $userID) {
          $e->setAuthorized(FALSE);
          $e->stopPropagation();
          return;
        }
      }
      return;
    }
    if ($e->getActionName() === 'update' && !empty($record['id'])) {
      foreach ($ownerFieldsToValidate as $ownerField) {
        $storedOwner = \CRM_Core_DAO::getFieldValue(static::class, $record['id'], $ownerField);
        if ((string) $storedOwner !== (string) $record[$ownerField]) {
          $e->setAuthorized(FALSE);
          $e->stopPropagation();
          return;
        }
      }
    }
  }

}
