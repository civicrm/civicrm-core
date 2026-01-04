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
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Core_BAO_SiteToken extends CRM_Core_DAO_SiteToken implements \Civi\Core\HookInterface {

  /**
   * Callback for hook_civicrm_pre().
   * @param \Civi\Core\Event\PreEvent $event
   * @throws CRM_Core_Exception
   */
  public static function self_hook_civicrm_pre(\Civi\Core\Event\PreEvent $event) {
    // Here we perform some basic validation; note that the user in the admin UI
    // will probably never see any exceptions thrown here and may believe that
    // their submission was saved; also note more validation is recommended.
    // See "Note on Validation" at https://github.com/civicrm/civicrm-core/pull/30451#issuecomment-2184316807

    // On edit:
    if ($event->action === 'edit') {
      // Prevent changing of 'name' attribute for entities that are currently is_reserved.
      $currentValues = Civi\Api4\SiteToken::get(FALSE)
        ->addWhere('id', '=', $event->params['id'])
        ->execute()
        ->single();
      if ($currentValues['is_reserved']
        && !empty($event->params['name'])
        && ($event->params['name'] != $currentValues['name'])
      ) {
        throw new \Civi\API\Exception\UnauthorizedException('Permission denied to modify name on reserved Site Token');
      }
      // If we're still here, auto-fill modified_id.
      $event->params['modified_id'] = CRM_Core_Session::getLoggedInContactID();;
    }
    elseif ($event->action === 'delete') {
      // On delete, prevent deletion for entities that are currently is_reserved.
      $currentValues = Civi\Api4\SiteToken::get(FALSE)
        ->addWhere('id', '=', $event->params['id'])
        ->execute()
        ->single();
      if ($currentValues['is_reserved']) {
        throw new \Civi\API\Exception\UnauthorizedException('Permission denied to delete reserved Site Token');
      }
    }
  }

}
