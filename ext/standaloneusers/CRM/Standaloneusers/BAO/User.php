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

}
