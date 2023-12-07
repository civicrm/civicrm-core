<?php

/**
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * Business access object for the User entity.
 */
class CRM_Standaloneusers_BAO_User extends CRM_Standaloneusers_DAO_User implements \Civi\Core\HookInterface {

  /**
   * Event fired after an action is taken on a User record.
   * @param \Civi\Core\Event\PreEvent $event
   */
  public static function self_hook_civicrm_pre(\Civi\Core\Event\PreEvent $event) {
    if (in_array($event->action, ['create', 'edit'])
        && empty($event->params['when_updated'])) {
      // Track when_updated.
      $event->params['when_updated'] = date('YmdHis');
    }
  }

  public static function updateLastAccessed() {
    $sess = CRM_Core_Session::singleton();
    $ufID = (int) $sess->get('ufID');
    CRM_Core_DAO::executeQuery("UPDATE civicrm_uf_match SET when_last_accessed = NOW() WHERE id = $ufID");
    $sess->set('lastAccess', time());
  }

  public static function getPreferredLanguages(): array {
    return CRM_Core_I18n::uiLanguages(FALSE);
  }

  public static function getTimeZones(): array {
    $timeZones = [];
    foreach (\DateTimeZone::listIdentifiers() as $timezoneId) {
      $timeZones[$timezoneId] = $timezoneId;
    }
    return $timeZones;
  }

  /**
   * Check access permission
   *
   * @param string $entityName
   * @param string $action
   * @param array $record
   * @param integer|null $userID
   * @return boolean
   * @see CRM_Core_DAO::checkAccess
   */
  public static function _checkAccess(string $entityName, string $action, array $record, ?int $userID): bool {
    // Prevent users from deleting their own user account
    if (in_array($action, ['delete'], TRUE)) {
      $sess = CRM_Core_Session::singleton();
      $ufID = (int) $sess->get('ufID');
      if ($record['id'] == $ufID) {
        return FALSE;
      };
    }
    return TRUE;
  }

}
