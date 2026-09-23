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
 */
trait CRM_Contact_AccessTrait {

  /**
   * @see \Civi\Api4\Utils\CoreUtil::checkAccessRecord
   */
  public static function self_civi_api4_authorizeRecord(AuthorizeRecordEvent $e): void {
    $record = $e->getRecord();
    $userID = $e->getUserID();
    $delegateAction = $e->getActionName() === 'get' ? 'get' : 'update';
    $cid = $record['contact_id'] ?? NULL;
    if (!$cid && !empty($record['id'])) {
      $cid = CRM_Core_DAO::getFieldValue(__CLASS__, $record['id'], 'contact_id');
    }
    if (!$cid) {
      // With no contact id this must be part of an event locblock
      $e->setAuthorized(in_array(__CLASS__, ['CRM_Core_BAO_Phone', 'CRM_Core_BAO_Email', 'CRM_Core_BAO_Address', 'CRM_Core_BAO_IM']) &&
        CRM_Core_Permission::check('edit all events', $userID));
    }
    else {
      $e->setAuthorized(CoreUtil::checkAccessDelegated('Contact', $delegateAction, ['id' => $cid], $userID));
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
