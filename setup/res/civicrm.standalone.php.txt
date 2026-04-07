<?php

/**
 * Stub file for CiviCRM Standalone
 *
 * It provides a common codepath for index.php / cv / any other entrypoint to
 * locate and run the CiviCRM autoloader, classloader and settingsloader
 */

// this file should set these two globals
global $appRootPath, $settingsPath;

// this file should always be in the project root
$appRootPath = __DIR__;

// standard file structure with top-level private, public and extensions dirs.
$autoLoader = implode(DIRECTORY_SEPARATOR, [$appRootPath, 'core', 'vendor', 'autoload.php']);
$classLoader = implode(DIRECTORY_SEPARATOR, [$appRootPath, 'core', 'CRM', 'Core', 'ClassLoader.php']);

# // alternative composer-style file structure:
# $autoLoader = implode(DIRECTORY_SEPARATOR, [$appRootPath , 'vendor', 'autoload.php']);
# $classLoader = implode(DIRECTORY_SEPARATOR, [$appRootPath, 'vendor', 'civicrm', 'civicrm-core', 'CRM', 'Core', 'ClassLoader.php']);

$settingsPath = implode(DIRECTORY_SEPARATOR, [__DIR__, 'private', 'civicrm.settings.php']);

require_once $autoLoader;
require_once $classLoader;
\CRM_Core_ClassLoader::singleton()->register();
\Civi\Core\SettingsManager::bootSettings($settingsPath);
