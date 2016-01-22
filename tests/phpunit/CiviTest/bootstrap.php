<?php
// ADAPTED FROM tools/scripts/phpunit

$GLOBALS['base_dir'] = dirname(dirname(dirname(__DIR__)));
$tests_dir = $GLOBALS['base_dir'] . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'phpunit';
$civi_pkgs_dir = $GLOBALS['base_dir'] . DIRECTORY_SEPARATOR . 'packages';
ini_set('safe_mode', 0);
ini_set('include_path',
  "{$GLOBALS['base_dir']}" . PATH_SEPARATOR .
  "$tests_dir" . PATH_SEPARATOR .
  "$civi_pkgs_dir" . PATH_SEPARATOR
  . ini_get('include_path'));

#  Relying on system timezone setting produces a warning,
#  doing the following prevents the warning message
if (file_exists('/etc/timezone')) {
  $timezone = trim(file_get_contents('/etc/timezone'));
  if (ini_set('date.timezone', $timezone) === FALSE) {
    echo "ini_set( 'date.timezone', '$timezone' ) failed\n";
  }
}

# Crank up the memory
ini_set('memory_limit', '2G');

require_once $GLOBALS['base_dir'] . '/vendor/autoload.php';

/*
require $GLOBALS['base_dir'] . DIRECTORY_SEPARATOR .
'packages' . DIRECTORY_SEPARATOR .
'PHPUnit' . DIRECTORY_SEPARATOR .
'Autoload.php';
 */

if (!defined('CIVICRM_UF') && getenv('CIVICRM_UF')) {
  define('CIVICRM_UF', getenv('CIVICRM_UF'));
}