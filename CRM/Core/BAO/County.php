<?php
use Civi\Core\Attribute\CiviEventListener;
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

use Civi\Core\Event\PostEvent;

/**
 * This class contains functions for managing Counties.
 */
class CRM_Core_BAO_County extends CRM_Core_DAO_County {

  /**
   * Callback for hook_civicrm_post().
   *
   * @param \Civi\Core\Event\PostEvent $event
   */
  #[CiviEventListener(event: 'hook_civicrm_post::{self}')]
  public static function self_hook_civicrm_post(PostEvent $event): void {
    unset(\Civi::$statics['CRM_Core_PseudoConstant']);
  }

}
