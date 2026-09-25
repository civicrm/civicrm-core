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

use Civi\Core\Event\PreEvent;
use Civi\Core\HookInterface;

/**
 * This class contains functions for managing Action Logs
 */
class CRM_Core_BAO_ActionLog extends CRM_Core_DAO_ActionLog implements HookInterface {

  /**
   * Callback for hook_civicrm_pre().
   *
   * @param \Civi\Core\Event\PreEvent $event
   */
  public static function self_hook_civicrm_pre(PreEvent $event): void {
    if ($event->action === 'create') {
      $event->params['action_date_time'] ??= date('YmdHis');
    }
  }

}
