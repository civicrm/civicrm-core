<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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

/**
 * A PHP cron script to run the outstanding and scheduled CiviMail jobs
 * initiated by Owen Barton from a mailing sent by Lobo to crm-mail
 *
 * The structure of the file is set to mimiic soap.php which is a stand-alone
 * script and hence does not have any UF issues. You should be able to run
 * this script using a web url or from the command line
 */
function run() {
  session_start();

  if (!function_exists('drush_get_context')) {
    require_once '../civicrm.config.php';
  }

  require_once 'CRM/Core/Config.php';
  $config = CRM_Core_Config::singleton();

  // this does not return on failure
  CRM_Utils_System::authenticateScript(TRUE);

  // we now use DB locks on a per job basis
  require_once 'CRM/Mailing/BAO/Mailing.php';
  CRM_Mailing_BAO_Mailing::processQueue();
}

// you can run this program either from an apache command, or from the cli
if (php_sapi_name() == "cli") {
  require_once ("bin/cli.php");
  $cli = new civicrm_cli();

  require_once 'CRM/Mailing/BAO/Mailing.php';
  CRM_Mailing_BAO_Mailing::processQueue();

  // from the webserver
}
else {
  run();
}

