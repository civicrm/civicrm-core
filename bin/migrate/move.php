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
function run() {
  session_start();

  require_once '../../civicrm.config.php';
  require_once 'CRM/Core/Config.php';

  $config = CRM_Core_Config::singleton();

  // this does not return on failure
  CRM_Utils_System::authenticateScript(TRUE);
  if (!CRM_Core_Permission::check('administer CiviCRM')) {
    CRM_Utils_System::authenticateAbort("User does not have required permission (administer CiviCRM).\n", TRUE);
  }

  // doSiteMove is deprecated and slated for removal
  require_once 'CRM/Core/BAO/ConfigSetting.php';
  $moveStatus = CRM_Core_BAO_ConfigSetting::doSiteMove();

  echo $moveStatus . '<br />';
  echo ts("If no errors are displayed above, the site move steps have completed successfully. Please visit <a href=\"{$config->userFrameworkBaseURL}\">your moved site</a> and test the move.");
}

run();
