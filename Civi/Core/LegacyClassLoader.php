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

namespace Civi\Core;

/**
 * Class LegacyClassLoader
 * @package Civi\Core
 *
 * This is just a PSR-4 wrapper for legacy PSR-0 classloader CRM_Core_ClassLoader
 *
 * It means you don't have to scrabble around to find the old classloader
 * if you already have PSR-4 autoloading up and running
 */
class LegacyClassLoader {

  public static function register() {
    $coreRoot = \dirname(__DIR__, 2);
    $classLoader = implode(DIRECTORY_SEPARATOR, [$coreRoot, 'CRM', 'Core', 'ClassLoader.php']);
    require_once $classLoader;
    \CRM_Core_ClassLoader::singleton()->register();
  }

}
