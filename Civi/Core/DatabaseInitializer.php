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

namespace Civi\Core;

use Civi\Core\Event\SystemInstallEvent;

/**
 * Class DatabaseInitializer
 * @package Civi\Core
 */
class DatabaseInitializer {

  /**
   * Flush system to build the menu and MySQL triggers
   *
   * @param \Civi\Core\Event\SystemInstallEvent $event
   * @throws \CRM_Core_Exception
   */
  public static function initialize(SystemInstallEvent $event) {
    $api_params = [
      'version' => 3,
      'triggers' => 1,
      'session' => 1,
    ];
    civicrm_api('System', 'flush', $api_params);
  }

}
