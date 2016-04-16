<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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


require_once '../civicrm.config.php';
require_once 'CRM/Core/Config.php';
require_once 'CRM/Utils/Request.php';
$config = CRM_Core_Config::singleton();

CRM_Utils_System::authenticateScript(TRUE);

$job = CRM_Utils_Request::retrieve('job', 'String', CRM_Core_DAO::$_nullArray, FALSE, NULL, 'REQUEST');

require_once 'CRM/Core/JobManager.php';
$facility = new CRM_Core_JobManager();

if ($job === NULL) {
  $facility->execute();
}
else {
  $ignored = array("name", "pass", "key", "job");
  $params = array();
  foreach ($_REQUEST as $name => $value) {
    if (!in_array($name, $ignored)) {
      $params[$name] = CRM_Utils_Request::retrieve($name, 'String', CRM_Core_DAO::$_nullArray, FALSE, NULL, 'REQUEST');
    }
  }
  $facility->setSingleRunParams('job', $job, $params, 'From cron.php');
  $facility->executeJobByAction('job', $job);
}
