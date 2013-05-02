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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

/**
 *When runing script from cli :
 * 1. By default script is being used for civimail processing.
 * eg : nice -19 php bin/EmailProcessor.php -u<login> -p<password> -s<sites(or default)>
 *
 * 2. Pass "activities" as argument to use script for 'Email To Activity Processing'.
 * eg : nice -19 php bin/EmailProcessor.php -u<login> -p<password> -s<sites(or default)> activities
 *
 */

// bootstrap the environment and run the processor
// you can run this program either from an apache command, or from the cli
if (php_sapi_name() == "cli") {
  require_once ("bin/cli.php");
  $cli = new civicrm_cli();
  //if it doesn't die, it's authenticated
  //log the execution of script
  CRM_Core_Error::debug_log_message('EmailProcessor.php from the cli');
  require_once 'CRM/Core/Lock.php';
  $lock = new CRM_Core_Lock('EmailProcessor');

  if (!$lock->isAcquired()) {
    throw new Exception('Could not acquire lock, another EmailProcessor process is running');
  }

  require_once 'CRM/Utils/Mail/EmailProcessor.php';

  // check if the script is being used for civimail processing or email to
  // activity processing.
  if (isset($cli->args[0]) && $cli->args[0] == "activities") {
    CRM_Utils_Mail_EmailProcessor::processActivities();
  }
  else {
    CRM_Utils_Mail_EmailProcessor::processBounces();
  }
  $lock->release();
}
else {
  session_start();
  require_once '../civicrm.config.php';
  require_once 'CRM/Core/Config.php';
  $config = CRM_Core_Config::singleton();
  CRM_Utils_System::authenticateScript(TRUE);

  require_once 'CRM/Utils/System.php';
  CRM_Utils_System::loadBootStrap();

  //log the execution of script
  CRM_Core_Error::debug_log_message('EmailProcessor.php');

  require_once 'CRM/Core/Lock.php';
  $lock = new CRM_Core_Lock('EmailProcessor');

  if (!$lock->isAcquired()) {
    throw new Exception('Could not acquire lock, another EmailProcessor process is running');
  }

  // try to unset any time limits
  if (!ini_get('safe_mode')) {
    set_time_limit(0);
  }

  require_once 'CRM/Utils/Mail/EmailProcessor.php';

  // cleanup directories with old mail files (if they exist): CRM-4452
  CRM_Utils_Mail_EmailProcessor::cleanupDir($config->customFileUploadDir . DIRECTORY_SEPARATOR . 'CiviMail.ignored');
  CRM_Utils_Mail_EmailProcessor::cleanupDir($config->customFileUploadDir . DIRECTORY_SEPARATOR . 'CiviMail.processed');

  // check if the script is being used for civimail processing or email to
  // activity processing.
  $isCiviMail = CRM_Utils_Array::value('emailtoactivity', $_REQUEST) ? FALSE : TRUE;
  CRM_Utils_Mail_EmailProcessor::process($isCiviMail);

  $lock->release();
}

