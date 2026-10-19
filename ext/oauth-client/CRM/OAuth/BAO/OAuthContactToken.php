<?php

use Civi\Api4\Event\AuthorizeRecordEvent;
use Civi\Core\Event\PreEvent;

class CRM_OAuth_BAO_OAuthContactToken extends CRM_OAuth_DAO_OAuthContactToken implements \Civi\Core\HookInterface {

  /**
   * Preproceesing for create/edit/delete actions
   */
  public static function self_hook_civicrm_pre(PreEvent $event): void {
    if (in_array($event->action, ['create', 'edit', 'delete'], TRUE)) {
      if (!empty($event->id) && empty($event->params['id'])) {
        $event->params['id'] = $event->id;
      }
      self::fillAndValidate($event->params, CRM_Core_Session::getLoggedInContactID());
    }
  }

  /**
   * Create or update OAuthContactToken based on array-data
   *
   * @param array $record
   * @return CRM_OAuth_DAO_OAuthContactToken
   */
  public static function create($record) {
    self::fillAndValidate($record, CRM_Core_Session::getLoggedInContactID());
    return static::writeRecord($record);
  }

  /**
   * @param $id
   * @return CRM_OAuth_BAO_OAuthContactToken
   * @throws CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public static function del($id) {
    $record = ['id' => $id];
    self::fillAndValidate($record, CRM_Core_Session::getLoggedInContactID());
    return static::deleteRecord($record);
  }

  /**
   * @see \Civi\Api4\Utils\CoreUtil::checkAccessRecord
   */
  public static function self_civi_api4_authorizeRecord(AuthorizeRecordEvent $e): void {
    $record = $e->getRecord();
    $userId = $e->getUserID();
    try {
      $record['check_permissions'] = TRUE;
      self::fillAndValidate($record, $userId);
      $e->setAuthorized(TRUE);
    }
    catch (\Civi\API\Exception\UnauthorizedException $exception) {
      $e->setAuthorized(FALSE);
    }
  }

  /**
   * @param $record
   * @param $userId
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  private static function fillAndValidate(&$record, $userId) {
    if (!empty($record['id'])) {
      $storedCid = CRM_Core_DAO::getFieldValue(__CLASS__, $record['id'], 'contact_id');
      if (!empty($record['check_permissions']) && $storedCid) {
        if (!CRM_Contact_BAO_Contact_Permission::allow($storedCid, CRM_Core_Permission::EDIT, $userId)) {
          throw new \Civi\API\Exception\UnauthorizedException('Access denied to contact');
        }
        if (
          !CRM_Core_Permission::check(['manage all OAuth contact tokens'], $userId) &&
          (string) $storedCid !== (string) $userId
        ) {
          throw new \Civi\API\Exception\UnauthorizedException('Access denied to OAuthContactToken for contact');
        }
        if (!empty($record['contact_id']) && (string) $record['contact_id'] !== (string) $storedCid && !CRM_Core_Permission::check(['manage all OAuth contact tokens'], $userId)) {
          throw new \Civi\API\Exception\UnauthorizedException('Access denied to OAuthContactToken for contact');
        }
      }
      if (empty($record['contact_id'])) {
        $record['contact_id'] = $storedCid;
      }
    }
    self::fillContactIdFromTag($record);
    if (!empty($record['check_permissions'])) {
      $cid = $record['contact_id'];
      if (!CRM_Contact_BAO_Contact_Permission::allow($cid, CRM_Core_Permission::EDIT, $userId)) {
        throw new \Civi\API\Exception\UnauthorizedException('Access denied to contact');
      }
      if (!CRM_Core_Permission::check('manage my OAuth contact tokens', $userId)) {
        throw new \Civi\API\Exception\UnauthorizedException('Access denied to OAuthContactToken');
      }
      if (
        !CRM_Core_Permission::check(['manage all OAuth contact tokens'], $userId) &&
        (string) $cid !== (string) $userId
      ) {
        throw new \Civi\API\Exception\UnauthorizedException('Access denied to OAuthContactToken for contact');
      }
    }
  }

  /**
   * @param array $record
   */
  private static function fillContactIdFromTag(&$record): void {
    if (isset($record['contact_id'])) {
      return;
    }

    $tag = $record['tag'] ?? NULL;

    if ('linkContact:' === substr($tag, 0, 12)) {
      $record['contact_id'] = substr($tag, 12);
    }
    elseif ('nullContactId' === $tag) {
      $record['contact_id'] = NULL;
    }
    elseif ('createContact' === $tag) {
      $contact = CRM_OAuth_ContactFromToken::createContact($record);
      $record['contact_id'] = $contact['id'];
    }
    else {
      $record['contact_id'] = CRM_Core_Session::getLoggedInContactID();
    }
  }

  /**
   * @param string|null $entityName
   * @param int|null $userId
   * @param array $conditions
   * @inheritDoc
   */
  public function addSelectWhereClause(?string $entityName = NULL, ?int $userId = NULL, array $conditions = []): array {
    $clauses = [];
    $loggedInContactID = CRM_Core_Session::getLoggedInContactID();

    // With 'manage all' permission, apply standard contact ACLs
    if (CRM_Core_Permission::check(['manage all OAuth contact tokens'])) {
      $clauses['contact_id'] = CRM_Utils_SQL::mergeSubquery('Contact');
    }
    // With 'manage my' permission, limit to just the current user
    elseif ($loggedInContactID && CRM_Core_Permission::check(['manage my OAuth contact tokens'])) {
      $clauses['contact_id'][] = "= $loggedInContactID";
    }
    // No permission, return nothing
    else {
      $clauses['contact_id'][] = "= -1";
    }
    CRM_Utils_Hook::selectWhereClause($this, $clauses, $userId, $conditions);
    return $clauses;
  }

}
