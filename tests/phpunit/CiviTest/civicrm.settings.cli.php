<?php
// This file is loaded when spawning separate CLI processes that need to
// talk to the test DB.

require_once dirname(dirname(dirname(__DIR__))) . '/CRM/Core/ClassLoader.php';
CRM_Core_ClassLoader::singleton()->register();

define('CIVICRM_SETTINGS_PATH', __DIR__ . '/civicrm.settings.dist.php');
define('CIVICRM_SETTINGS_LOCAL_PATH', __DIR__ . '/civicrm.settings.local.php');

if (file_exists(CIVICRM_SETTINGS_LOCAL_PATH)) {
  require_once CIVICRM_SETTINGS_LOCAL_PATH;
}
require_once CIVICRM_SETTINGS_PATH;
