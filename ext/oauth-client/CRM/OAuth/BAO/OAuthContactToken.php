<?php

class CRM_OAuth_BAO_OAuthContactToken extends CRM_OAuth_DAO_OAuthContactToken {

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
   * @param string $entityName
   * @param string $action
   * @param array $record
   * @param $userId
   * @return bool
   * @see CRM_Core_DAO::checkAccess
   */
  public static function _checkAccess(string $entityName, string $action, array $record, $userId): bool {
    try {
      $record['check_permissions'] = TRUE;
      self::fillAndValidate($record, $userId);
      return TRUE;
    }
    catch (\Civi\API\Exception\UnauthorizedException $e) {
      return FALSE;
    }
  }

  /**
   * @param $record
   * @param $userId
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  private static function fillAndValidate(&$record, $userId) {
    if (!empty($record['id']) && empty($record['contact_id'])) {
      $record['contact_id'] = CRM_Core_DAO::getFieldValue(__CLASS__, $record['id'], 'contact_id');
    }
    self::fillContactIdFromTag($record);
    if (!empty($record['check_permissions'])) {
      $cid = $record['contact_id'];
      if (!CRM_Contact_BAO_Contact_Permission::allow($cid, CRM_Core_Permission::EDIT, $userId)) {
        throw new \Civi\API\Exception\UnauthorizedException('Access denied to contact');
      }
      if (!CRM_Core_Permission::check([['manage all OAuth contact tokens', 'manage my OAuth contact tokens']], $userId)) {
        throw new \Civi\API\Exception\UnauthorizedException('Access denied to OAuthContactToken');
      }
      if (
        !CRM_Core_Permission::check(['manage all OAuth contact tokens'], $userId) &&
        $cid != $userId
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
   * @inheritDoc
   */
  public function addSelectWhereClause() {
    $clauses = [];
    $loggedInContactID = CRM_Core_Session::getLoggedInContactID();

    // With 'manage all' permission, apply standard contact ACLs
    if (CRM_Core_Permission::check(['manage all OAuth contact tokens'])) {
      $clauses['contact_id'] = CRM_Utils_SQL::mergeSubquery('Contact');
    }
    // With 'manage my' permission, limit to just the current user
    elseif ($loggedInContactID && CRM_Core_Permission::check(['manage my OAuth contact tokens'])) {
      $clauses['contact_id'] = "= $loggedInContactID";
    }
    // No permission, return nothing
    else {
      $clauses['contact_id'] = "= -1";
    }
    CRM_Utils_Hook::selectWhereClause($this, $clauses);
    return $clauses;
  }

}
