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


require_once '../civicrm.config.php';
require_once 'CRM/Core/Config.php';
require_once 'CRM/Utils/Request.php';
CRM_Core_Config::singleton();

CRM_Utils_System::authenticateScript(TRUE);

$job = CRM_Utils_Request::retrieve('job', 'String', NULL, FALSE, NULL, 'REQUEST');

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
      $params[$name] = CRM_Utils_Request::retrieve($name, 'String', NULL, FALSE, NULL, 'REQUEST');
    }
  }
  $facility->setSingleRunParams('job', $job, $params, 'From cron.php');
  $facility->executeJobByAction('job', $job);
}
