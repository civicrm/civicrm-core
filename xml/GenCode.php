<?php

if (PHP_SAPI !== 'cli') {
  die("GenCode can only be run from command line.");
}

ini_set('include_path', '.' . PATH_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'packages' . PATH_SEPARATOR . '..');
// make sure the memory_limit is at least 512 MB
$memLimitString = trim(ini_get('memory_limit'));
$memLimitUnit = strtolower(substr($memLimitString, -1));
$memLimit = (int) $memLimitString;
switch ($memLimitUnit) {
  case 'g':
    $memLimit *= 1024;
  case 'm':
    $memLimit *= 1024;
  case 'k':
    $memLimit *= 1024;
}

if ($memLimit >= 0 and $memLimit < 536870912) {
  // Note: When processing all locales, CRM_Core_I18n::singleton() eats a lot of RAM.
  ini_set('memory_limit', -1);
}
date_default_timezone_set('UTC'); // avoid php warnings if timezone is not set - CRM-10844

define('CIVICRM_UF', 'Drupal');
define('CIVICRM_UF_BASEURL', '/');

require_once 'CRM/Core/ClassLoader.php';
CRM_Core_ClassLoader::singleton()->register();

# TODO: pull these settings from configuration
$genCode = new CRM_Core_CodeGen_Main(
  '../CRM/Core/DAO/', // $CoreDAOCodePath
  '../sql/', // $sqlCodePath
  '../', // $phpCodePath
  '../templates/', // $tplCodePath
  NULL, // IGNORE
  @$argv[3], // cms
  empty($argv[2]) ? NULL : $argv[2], // db version
  empty($argv[1]) ? 'schema/Schema.xml' : $argv[1], // schema file
  getenv('CIVICRM_GENCODE_DIGEST') ? getenv('CIVICRM_GENCODE_DIGEST') : NULL  // path to digest file
);
$genCode->main();
