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


namespace Civi\Setup;

/**
 * Install basic scaffolding for standalone. This creates a handful of small, static
 * folders and files.
 *
 * NOTE: This file MUST be self-sufficient. It will be loaded by itself in a pre-install
 * environment.
 */
class StandaloneScaffold {

  /**
   * Install basic scaffolding for standalone. This creates a handful of small, static
   * folders and files.
   *
   * @param string $destDir
   *   Ex: '/var/www/example.com'
   *   Ex: '/home/myuser/src/civicrm/srv'
   * @param string $mode
   *   How to install files. Options:
   *   - 'copy': Make an exact copy
   *   - 'symlink': Make a symbolic link
   *   - 'auto': Choose 'copy' or 'symlink' based on OS compat
   *
   * @return void
   */
  public static function create(string $destDir, string $mode = 'auto'): void {
    $srcDir = dirname(__DIR__, 3) . '/setup/res';

    if ($mode === 'auto') {
      $mode = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? 'copy' : 'symlink';
    }

    $dirs = [
      "$destDir",
      "$destDir/web",
      "$destDir/data",
    ];

    foreach ($dirs as $dir) {
      if (!is_dir($dir)) {
        mkdir($dir);
      }
    }

    $files = [
      'civicrm.config.php.standalone.txt' => 'civicrm.config.php.standalone',
      'index.php.txt' => 'web/index.php',
      'htaccess.txt' => 'web/.htaccess',
    ];
    foreach ($files as $srcFile => $destFile) {
      switch ($mode) {
        case 'copy':
          copy("$srcDir/$srcFile", "$destDir/$destFile");
          break;

        case 'symlink':
          symlink("$srcDir/$srcFile", "$destDir/$destFile");
          break;
      }
    }
  }

}
