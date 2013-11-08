<?php

// This file is loaded on all systems running tests. To override settings on
// your local system, please create "civicrm.settings.local.php" and put
// the settings there.

//--- you shouldn't have to modify anything under this line, but might want to put the compiled templates CIVICRM_TEMPLATE_COMPILEDIR in a different folder than our default location ----------

if ( ! defined( 'CIVICRM_DSN' ) && ! empty( $GLOBALS['mysql_user'] ) ) {
  $dbName = ! empty( $GLOBALS['mysql_db'] ) ? $GLOBALS['mysql_db'] : 'civicrm_tests_dev';
  if ( empty( $GLOBALS['mysql_pass'] ) && $GLOBALS['mysql_pass_need_password'] ) {
    $GLOBALS['mysql_pass'] = PHPUnit_TextUI_Command::getPassword( 'Password' );
  }
  define( 'CIVICRM_DSN'          , "mysql://{$GLOBALS['mysql_user']}:{$GLOBALS['mysql_pass']}@{$GLOBALS['mysql_host']}/{$dbName}?new_link=true" );
}



if (!defined("CIVICRM_DSN")) {
  $dsn= getenv("CIVICRM_TEST_DSN");
  if (!empty ($dsn)) {
    define("CIVICRM_DSN",$dsn);
  } else {
    echo "\nFATAL: no DB connection configured (CIVICRM_DSN). \nYou can either create/edit " . __DIR__ . "/civicrm.settings.local.php\n";
    if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
      echo "OR set it in your shell:\n \$export CIVICRM_TEST_DSN=mysql://db_username:db_password@localhost/civicrm_tests_dev \n";
    } else {
      echo "OR set it in your shell:\n SETX CIVICRM_TEST_DSN mysql://db_username:db_password@localhost/civicrm_tests_dev \n
      (you will need to open a new command shell before it takes effect)";
    }
echo "\n\n
If you haven't done so already, you need to create (once) a database dedicated to the unit tests:
mysql -uroot -p
create database civicrm_tests_dev;
grant ALL on civicrm_tests_dev.* to db_username@localhost identified by 'db_password';
grant SUPER on *.* to db_username@localhost identified by 'db_password';\n";
    die ("");
  }
}

/**
 * Content Management System (CMS) Host:
 *
 * CiviCRM can be hosted in either Drupal, Joomla or WordPress.
*/
define('CIVICRM_UF', 'UnitTests');


global $civicrm_root;
if (empty($civicrm_root)) {
  $civicrm_root = dirname (dirname (dirname (dirname( __FILE__ ) )));
}
#$civicrm_root = '/var/www/drupal7.dev.civicrm.org/public/sites/devel.drupal7.tests.dev.civicrm.org/modules/civicrm';

// set this to a temporary directory. it defaults to /tmp/civi on linux
//define( 'CIVICRM_TEMPLATE_COMPILEDIR', 'the/absolute/path/' );

if (!defined("CIVICRM_TEMPLATE_COMPILEDIR")) {
  if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    define( 'CIVICRM_TEMPLATE_COMPILEDIR', getenv ('TMP') . DIRECTORY_SEPARATOR . 'civi' . DIRECTORY_SEPARATOR );
  } else {
    define( 'CIVICRM_TEMPLATE_COMPILEDIR', '/tmp/civi/' );
  }
}

define( 'CIVICRM_SITE_KEY', 'phpunittestfakekey' );

/**
 * Site URLs:
 *
 * This section defines absolute and relative URLs to access the host CMS (Drupal or Joomla) resources.
 *
 * IMPORTANT: Trailing slashes should be used on all URL settings.
 *
 *
 * EXAMPLE - Drupal Installations:
 * If your site's home url is http://www.example.com/drupal/
 * these variables would be set as below. Modify as needed for your install.
 *
 * CIVICRM_UF_BASEURL - home URL for your site:
 *      define( 'CIVICRM_UF_BASEURL' , 'http://www.example.com/drupal/' );
 *
 * EXAMPLE - Joomla Installations:
 * If your site's home url is http://www.example.com/joomla/
 *
 * CIVICRM_UF_BASEURL - home URL for your site:
 * Administration site:
 *      define( 'CIVICRM_UF_BASEURL' , 'http://www.example.com/joomla/administrator/' );
 * Front-end site:
 *      define( 'CIVICRM_UF_BASEURL' , 'http://www.example.com/joomla/' );
 *
 */
if (!defined('CIVICRM_UF_BASEURL')) {
  define( 'CIVICRM_UF_BASEURL'      , 'http://FIX ME' );
}

/**
 * Configure MySQL to throw more errors when encountering unusual SQL expressions.
 *
 * If undefined, the value is determined automatically. For CiviCRM tarballs, it defaults
 * to FALSE; for SVN checkouts, it defaults to TRUE.
 */
define('CIVICRM_MYSQL_STRICT', TRUE);

/**
 *
 * Do not change anything below this line. Keep as is
 *
 */

$include_path = '.'        . PATH_SEPARATOR .
                $civicrm_root . PATH_SEPARATOR .
                $civicrm_root . DIRECTORY_SEPARATOR . 'packages' . PATH_SEPARATOR .
                get_include_path( );
set_include_path( $include_path );

if ( function_exists( 'variable_get' ) && variable_get('clean_url', '0') != '0' ) {
    define( 'CIVICRM_CLEANURL', 1 );
} else {
    define( 'CIVICRM_CLEANURL', 0 );
}

// force PHP to auto-detect Mac line endings
ini_set('auto_detect_line_endings', '1');

// make sure the memory_limit is at least 64 MB
$memLimitString = trim(ini_get('memory_limit'));
$memLimitUnit   = strtolower(substr($memLimitString, -1));
$memLimit       = (int) $memLimitString;
switch ($memLimitUnit) {
    case 'g': $memLimit *= 1024;
    case 'm': $memLimit *= 1024;
    case 'k': $memLimit *= 1024;
}
if ($memLimit >= 0 and $memLimit < 67108864) {
    ini_set('memory_limit', '64M');
}

require_once 'CRM/Core/ClassLoader.php';
CRM_Core_ClassLoader::singleton()->register();

global $civicrm_db_settings;
$civicrm_db_settings = new CRM_DB_Settings();
