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
 * Class InstallationCanary
 * @package Civi\Core
 */
class InstallationCanary {

  /**
   * Check whether the install has run before.
   *
   * Circa v4.7.betaX, we introduced a new mechanism for tracking installation
   * and firing a post-install event. However, it's fairly difficult to test the
   * edge-cases directly, so this canary should fire if there are any problems
   * in the design/implementation of the installation-tracker.
   *
   * This should not exist. It should be removed in a future version.
   *
   * @param \Civi\Core\Event\SystemInstallEvent $event
   * @throws \CRM_Core_Exception
   */
  public static function check(SystemInstallEvent $event) {
    if (\CRM_Core_DAO::checkTableExists('civicrm_install_canary')) {
      throw new \CRM_Core_Exception("Found installation canary. This suggests that something went wrong with tracking installation process. Please post to forum or JIRA.");
    }
    \Civi::log()->info('Creating canary table');
    \CRM_Core_DAO::executeQuery('CREATE TABLE civicrm_install_canary (id int(10) unsigned NOT NULL, PRIMARY KEY (id)) ENGINE=InnoDB');
  }

}
