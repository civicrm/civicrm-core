<?php
// This file is loaded when spawning separate CLI processes that need to
// talk to the test DB.

require_once dirname(dirname(dirname(__DIR__))) . '/CRM/Core/ClassLoader.php';
CRM_Core_ClassLoader::singleton()->register();

// BACKPORT WORKAROUND: This works around the constraint that the 4.4 class-loader does not setup include_path.
// === BEGIN BACKPORT WORKAROUND ===
global $civicrm_root;
if (empty($civicrm_root)) {
  $civicrm_root = dirname (dirname (dirname (dirname( __FILE__ ) )));
}
$include_path = '.'        . PATH_SEPARATOR .
                $civicrm_root . PATH_SEPARATOR .
                $civicrm_root . DIRECTORY_SEPARATOR . 'packages' . PATH_SEPARATOR .
                get_include_path( );
set_include_path( $include_path );
// === END BACKPORT WORKAROUND ===

define('CIVICRM_SETTINGS_PATH', __DIR__ . '/civicrm.settings.dist.php');
define('CIVICRM_SETTINGS_LOCAL_PATH', __DIR__ . '/civicrm.settings.local.php');

if (file_exists(CIVICRM_SETTINGS_LOCAL_PATH)) {
  require_once CIVICRM_SETTINGS_LOCAL_PATH;
}
require_once CIVICRM_SETTINGS_PATH;
