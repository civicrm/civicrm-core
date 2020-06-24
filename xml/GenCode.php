<?php

if (PHP_SAPI !== 'cli') {
  die("GenCode can only be run from command line.");
}

$includes = ['.', '../packages', '../../civicrm-packages', '..'];
ini_set('include_path', implode(PATH_SEPARATOR, $includes));
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
// avoid php warnings if timezone is not set - CRM-10844
date_default_timezone_set('UTC');

define('CIVICRM_UF', 'Drupal');
define('CIVICRM_UF_BASEURL', '/');
define('CIVICRM_L10N_BASEDIR', getenv('CIVICRM_L10N_BASEDIR') ? getenv('CIVICRM_L10N_BASEDIR') : __DIR__ . '/../l10n');
$GLOBALS['civicrm_paths']['cms.root']['url'] = 'http://gencode.example.com/do-not-use';

require_once 'CRM/Core/ClassLoader.php';
CRM_Core_ClassLoader::singleton()->register();

# TODO: pull these settings from configuration
$genCode = new CRM_Core_CodeGen_Main(
  // $CoreDAOCodePath
  '../CRM/Core/DAO/',
  // $sqlCodePath
  '../sql/',
  // $phpCodePath
  '../',
  // $tplCodePath
  '../templates/',
  // IGNORE
  NULL,
  // cms
  @$argv[3],
  // db version
  empty($argv[2]) ? NULL : $argv[2],
  // schema file
  empty($argv[1]) ? 'schema/Schema.xml' : $argv[1],
  // path to digest file
  getenv('CIVICRM_GENCODE_DIGEST') ? getenv('CIVICRM_GENCODE_DIGEST') : NULL
);
$genCode->main();
