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
 * This class stores logic for managing schema upgrades in CiviCRM extensions.
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Extension_Upgrades {

  const QUEUE_NAME = 'ext-upgrade';

  /**
   * Determine whether any extensions have pending upgrades.
   *
   * @return bool
   */
  public static function hasPending() {
    $checks = CRM_Utils_Hook::upgrade('check');
    if (is_array($checks)) {
      foreach ($checks as $check) {
        if ($check) {
          return TRUE;
        }
      }
    }

    return FALSE;
  }

  /**
   * Fill a queue with upgrade tasks.
   *
   * @return CRM_Queue_Queue
   */
  public static function createQueue() {
    $queue = CRM_Queue_Service::singleton()->create([
      'type' => 'Sql',
      'name' => self::QUEUE_NAME,
      'reset' => TRUE,
    ]);

    CRM_Utils_Hook::upgrade('enqueue', $queue);

    return $queue;
  }

}
