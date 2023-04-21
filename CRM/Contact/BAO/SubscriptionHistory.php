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

/**
 * BAO object for civicrm_subscription_history table.
 */
class CRM_Contact_BAO_SubscriptionHistory extends CRM_Contact_DAO_SubscriptionHistory implements \Civi\Core\HookInterface {

  /**
   * @deprecated
   * @param array $params
   * @return CRM_Contact_DAO_SubscriptionHistory
   */
  public static function create($params) {
    return self::writeRecord($params);
  }

  /**
   * Callback for hook_civicrm_pre().
   *
   * @param \Civi\Core\Event\PreEvent $event
   *
   * @throws \CRM_Core_Exception
   */
  public static function self_hook_civicrm_pre(\Civi\Core\Event\PreEvent $event): void {
    if ($event->action === 'create' || $event->action === 'edit') {
      $event->params['date'] = date('YmdHis');
    }
  }

  /**
   * Erase a contact's subscription history records.
   *
   * @param int $id
   *   The contact id.
   */
  public static function deleteContact($id) {
    $history = new CRM_Contact_BAO_SubscriptionHistory();
    $history->contact_id = $id;
    $history->delete();
  }

}
