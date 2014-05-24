<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                               |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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


/*
 */

/**
 * Class CRM_Cron_Action
 */
class CRM_Cron_Action {
  /**
   *
   */
  function __construct() {
    // you can run this program either from an apache command, or from the cli
    if (php_sapi_name() == "cli") {
      require_once ("cli.php");
      $cli = new civicrm_cli();
      //if it doesn't die, it's authenticated
    }
    else {
      //from the webserver
      $this->initialize();

      $config = CRM_Core_Config::singleton();

      // this does not return on failure
      CRM_Utils_System::authenticateScript(TRUE);

      //log the execution time of script
      CRM_Core_Error::debug_log_message('action.cronjob.php');
    }
  }

  function initialize() {
    require_once '../civicrm.config.php';
    require_once 'CRM/Core/Config.php';

    $config = CRM_Core_Config::singleton();
  }

  /**
   * @param null $now
   */
  public function run($now = NULL) {
    require_once 'CRM/Core/BAO/ActionSchedule.php';
    CRM_Core_BAO_ActionSchedule::processQueue($now);
  }
}

$cron = new CRM_Cron_Action();
$cron->run();

