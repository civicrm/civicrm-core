<?php

/**
 * This function has been copied from DRUPAL_ROOT/includes/bootstrap.inc
 */

/**
 * Locate the appropriate configuration file.
 *
 * Try finding a matching configuration directory by stripping the
 * website's hostname from left to right and pathname from right to
 * left.  The first configuration file found will be used, the
 * remaining will ignored.  If no configuration file is found,
 * return a default value '$confdir/default'.
 *
 * Example for a fictitious site installed at
 * http://www.drupal.org/mysite/test/ the 'settings.php' is
 * searched in the following directories:
 *
 *  1. $confdir/www.drupal.org.mysite.test
 *  2. $confdir/drupal.org.mysite.test
 *  3. $confdir/org.mysite.test
 *
 *  4. $confdir/www.drupal.org.mysite
 *  5. $confdir/drupal.org.mysite
 *  6. $confdir/org.mysite
 *
 *  7. $confdir/www.drupal.org
 *  8. $confdir/drupal.org
 *  9. $confdir/org
 *
 * 10. $confdir/default
 *
 */

function civicrm_conf_init() {
    global $skipConfigError;

    static $conf = '';

    if ($conf) {
        return $conf;
    }

    /**
     * We are within the civicrm module, the drupal root is 2 links
     * above us, so use that
     */
    $currentDir = dirname( __FILE__ ) . DIRECTORY_SEPARATOR;
    if ( file_exists( $currentDir . 'settings_location.php' ) ) {
        include $currentDir . 'settings_location.php';
    }

    if ( defined( 'CIVICRM_CONFDIR' ) && ! isset( $confdir ) ) {
        $confdir = CIVICRM_CONFDIR;
    } else {
        // make it relative to civicrm.config.php, else php makes it relative
        // to the script that invokes it
        $moduleDir  = 'sites' . DIRECTORY_SEPARATOR . 'all' . DIRECTORY_SEPARATOR . 'modules';
        $contribDir = $moduleDir . DIRECTORY_SEPARATOR . 'contrib';
        // check to see if this is under sites/all/modules/contrib or subdir civicrm-core
        if ( strpos( $currentDir, $contribDir ) !== false || strpos( $currentDir, 'civicrm-core' ) !== false) {
            $confdir = $currentDir . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..';
        // check to see if this is under sites/all/modules
        } else if ( strpos( $currentDir, $moduleDir ) !== false ) {
            $confdir = $currentDir . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..';
        } else if ( strpos( $currentDir, 'plugins' . DIRECTORY_SEPARATOR . 'civicrm' . DIRECTORY_SEPARATOR . 'civicrm' ) !== false ) {
             //if its wordpress
            $confdir = $currentDir . DIRECTORY_SEPARATOR . '..';
        } else {
            $confdir = $currentDir . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR;
        }
    }

    if ( file_exists( $confdir . DIRECTORY_SEPARATOR . 'civicrm.settings.php' ) ) {
        return $confdir;
    }

    if ( ! file_exists( $confdir ) && ! $skipConfigError ) {
        echo "Could not find valid configuration dir, best guess: $confdir<br/><br/>\n";
        exit( );
    }

    // since drupal 7, alias could be defined in sites/sites.php
    if ( file_exists( $confdir . "/sites.php" ) ) {
      include $confdir . "/sites.php";
    } else {
      $sites = array();
    }

    $phpSelf  = array_key_exists( 'PHP_SELF' , $_SERVER ) ? $_SERVER['PHP_SELF' ] : '';
    $httpHost = array_key_exists( 'HTTP_HOST', $_SERVER ) ? $_SERVER['HTTP_HOST'] : '';

    $uri    = explode('/', $phpSelf );
    $server = explode('.', implode('.', array_reverse(explode(':', rtrim($httpHost, '.')))));
    for ($i = count($uri) - 1; $i > 0; $i--) {
        for ($j = count($server); $j > 0; $j--) {
            $dir = implode('.', array_slice($server, -$j)) . implode('.', array_slice($uri, 0, $i));
            if (file_exists("$confdir/$dir/civicrm.settings.php")) {
                $conf = "$confdir/$dir";
                return $conf;
            }
            // check for alias
            if (isset($sites[$dir]) && file_exists("$confdir/{$sites[$dir]}/civicrm.settings.php")) {
              $conf = "$confdir/{$sites[$dir]}";
              return $conf;
            }
        }
    }

    // FIXME: problem spot for Drupal 5.1 config dir layout
    $conf = "$confdir/default";
    return $conf;
}

$settingsFile = civicrm_conf_init( ) . '/civicrm.settings.php';
define('CIVICRM_SETTINGS_PATH', $settingsFile);
$error = @include_once( $settingsFile );
if ( $error == false ) {
    echo "Could not load the settings file at: {$settingsFile}\n";
    exit( );
}

// Load class loader
global $civicrm_root;
require_once $civicrm_root . '/CRM/Core/ClassLoader.php';
CRM_Core_ClassLoader::singleton()->register();
