<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
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
    \CRM_Core_DAO::executeQuery('CREATE TABLE civicrm_install_canary (id int(10) unsigned NOT NULL) ENGINE=InnoDB');
  }

}
