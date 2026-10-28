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
 * Trait shared with entities attached to the contact record.
 *
 * Users of this trait must implement HookInterface.
 * @phpstan-require-implements \Civi\Core\HookInterface
 */
trait CRM_Contact_AccessTrait {

  /**
   * Callback for `hook_civicrm_pre` event.
   *
   * Enforce contact permissions on create, edit, and delete.
   *
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public static function checkContactAccessPre(\Civi\Core\Event\PreEvent $event): void {
    if (empty($event->params['check_permissions'])) {
      return;
    }
    $userID = \CRM_Core_Session::getLoggedInContactID() ?: 0;

    if ($event->action === 'create') {
      $cid = $event->params['contact_id'] ?? NULL;
      if ($cid && !CoreUtil::checkAccessDelegated('Contact', 'update', ['id' => $cid], $userID)) {
        throw new \Civi\API\Exception\UnauthorizedException(\ts('Permission denied to modify contact record'));
      }
      return;
    }

    if ($event->action === 'edit' && !empty($event->id)) {
      $storedCid = \CRM_Core_DAO::getFieldValue(static::class, $event->id, 'contact_id');
      if ($storedCid && !CoreUtil::checkAccessDelegated('Contact', 'update', ['id' => $storedCid], $userID)) {
        throw new \Civi\API\Exception\UnauthorizedException(\ts('Permission denied to modify contact record'));
      }
      if (array_key_exists('contact_id', $event->params)) {
        if (!CRM_Core_Permission::check('edit all contacts', $userID) && (string) $storedCid !== (string) $event->params['contact_id']) {
          throw new \Civi\API\Exception\UnauthorizedException(\ts('Cannot change the contact_id of an existing %1.', [1 => $event->entity]));
        }
        $newCid = $event->params['contact_id'];
        if ($newCid && (string) $storedCid !== (string) $newCid && !CoreUtil::checkAccessDelegated('Contact', 'update', ['id' => $newCid], $userID)) {
          throw new \Civi\API\Exception\UnauthorizedException(\ts('Permission denied to modify contact record'));
        }
      }
      return;
    }

    if ($event->action === 'delete' && !empty($event->id)) {
      $storedCid = \CRM_Core_DAO::getFieldValue(static::class, $event->id, 'contact_id');
      if ($storedCid && !CoreUtil::checkAccessDelegated('Contact', 'update', ['id' => $storedCid], $userID)) {
        throw new \Civi\API\Exception\UnauthorizedException(\ts('Permission denied to modify contact record'));
      }
    }
  }

  /**
   * Callback for `civi.api4.authorizeRecord` event.
   * @see \Civi\Api4\Utils\CoreUtil::checkAccessRecord
   */
  public static function checkContactAccessAuthorizeRecord(AuthorizeRecordEvent $e): void {
    $record = $e->getRecord();
    $userID = $e->getUserID();
    $delegateAction = $e->getActionName() === 'get' ? 'get' : 'update';
    $cid = $record['contact_id'] ?? NULL;
    $storedCid = NULL;

    if (!empty($record['id'])) {
      $storedCid = (int) CRM_Core_DAO::getFieldValue(__CLASS__, $record['id'], 'contact_id');
      if ($storedCid && !CoreUtil::checkAccessDelegated('Contact', $delegateAction, ['id' => $storedCid], $userID)) {
        $e->setAuthorized(FALSE);
        return;
      }
      if ($cid && $storedCid && (string) $cid !== (string) $storedCid && !CRM_Core_Permission::check('edit all contacts', $userID)) {
        $e->setAuthorized(FALSE);
        return;
      }
    }

    if (!empty($record['master_id'])) {
      $masterContactId = (int) CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Address', $record['master_id'], 'contact_id');
      if (!$masterContactId || !CoreUtil::checkAccessDelegated('Contact', 'get', ['id' => $masterContactId], $userID)) {
        $e->setAuthorized(FALSE);
        return;
      }
    }

    $targetCid = $cid ?: $storedCid;
    if (!$targetCid) {
      // With no contact id this must be part of an event locblock
      $e->setAuthorized(in_array(__CLASS__, ['CRM_Core_BAO_Phone', 'CRM_Core_BAO_Email', 'CRM_Core_BAO_Address', 'CRM_Core_BAO_IM']) &&
        CRM_Core_Permission::check('edit all events', $userID));
    }
    else {
      $e->setAuthorized(CoreUtil::checkAccessDelegated('Contact', $delegateAction, ['id' => $targetCid], $userID));
    }
  }

  /**
   * @inheritDoc
   */
  public function addSelectWhereClause(?string $entityName = NULL, ?int $userId = NULL, array $conditions = []): array {
    $entityName ??= CRM_Core_DAO_AllCoreTables::getEntityNameForClass(static::class);
    $locBlockEntities = ['Address', 'Email', 'Phone', 'IM'];

    if (!in_array($entityName, $locBlockEntities, TRUE)) {
      return parent::addSelectWhereClause($entityName, $userId, $conditions);
    }

    $tableName = static::getTableName();
    $clauses = [];
    $orClauses = [];

    // 1. Records linked to contacts
    $contactClauses = CRM_Utils_SQL::mergeSubquery('Contact');
    if (!empty($contactClauses)) {
      $contactSub = implode(' AND contact_id ', $contactClauses);
      $orClauses[] = "IN (SELECT id FROM $tableName WHERE contact_id $contactSub)";
    }
    elseif (CRM_Core_Permission::check([['view all contacts', 'edit all contacts']], $userId)) {
      $orClauses[] = "IN (SELECT id FROM $tableName WHERE contact_id IS NOT NULL)";
    }

    $clauses['id'] = [$orClauses];

    CRM_Utils_Hook::selectWhereClause($this, $clauses, $userId, $conditions);

    if (empty($clauses['id'][0])) {
      $clauses['id'] = ['= 0'];
    }

    return $clauses;
  }

}
