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
 * A PHP cron script to mail the result set of specified report to the
 * recipients mentioned for that report
 */
class CiviReportMail {
  function __construct() {
    $this->initialize();

    CRM_Utils_System::authenticateScript(TRUE);

    //log the execution of script
    CRM_Core_Error::debug_log_message('CiviReportMail.php');
  }

  function initialize() {
    require_once '../civicrm.config.php';
    require_once 'CRM/Core/Config.php';

    $config = CRM_Core_Config::singleton();
  }

  function run() {
    require_once 'CRM/Core/Lock.php';
    $lock = new CRM_Core_Lock('CiviReportMail');

    if ($lock->isAcquired()) {
      // try to unset any time limits
      if (!ini_get('safe_mode')) {
        set_time_limit(0);
      }

      // if there are named sets of settings, use them - otherwise use the default (null)
      require_once 'CRM/Report/Utils/Report.php';
      $result = CRM_Report_Utils_Report::processReport();
      echo $result['messages'];
    }
    else {
      throw new Exception('Could not acquire lock, another CiviReportMail process is running');
    }

    $lock->release();
  }
}

session_start();
$obj = new CiviReportMail;
$obj->run();

