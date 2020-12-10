<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | Use of this source code is governed by the AGPL license with some  |
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

  require_once 'CRM/Utils/Migrate/Export.php';
  $export = new CRM_Utils_Migrate_Export();
  $export->build();
  CRM_Utils_System::download('CustomGroupData.xml', 'text/plain', $export->toXML());
}

run();
