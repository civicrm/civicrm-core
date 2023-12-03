<?php
// phpcs:disable
use CRM_Standaloneusers_ExtensionUtil as E;
// phpcs:enable

class CRM_Standaloneusers_BAO_Role extends CRM_Standaloneusers_DAO_Role implements \Civi\Core\HookInterface {

  /**
   * Event fired after an action is taken on a Role record.
   * @param \Civi\Core\Event\PostEvent $event
   */
  public static function self_hook_civicrm_post(\Civi\Core\Event\PostEvent $event) {
    // Reset cache
    Civi::cache('metadata')->clear();
  }

}
