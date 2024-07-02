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
   * @param array $task
   *   - 'scaffold-dir': Where to place files
   *      - Ex: '/var/www/example.com'
   *      - Ex: '/home/myuser/src/civicrm/srv'
   *   - 'scaffold-mode': How to install files. Options:
   *     - 'copy': Make an exact copy
   *     - 'symlink': Make a symbolic link
   *     - 'auto': Choose 'copy' or 'symlink' based on OS compat
   * @return void
   */
  public static function create(array $task): void {
    $destDir = $task['scaffold-dir'];
    $mode = $task['scaffold-mode'] ?? 'auto';

    if (empty($destDir)) {
      throw new \RuntimeException("Missing required parameter: scaffold-dir");
    }

    $srcDir = dirname(__DIR__, 3) . '/setup/res';

    if ($mode === 'auto') {
      $mode = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? 'copy' : 'symlink';
    }

    $dirs = [
      "$destDir",
      "$destDir/private",
      "$destDir/public",
      "$destDir/ext",
    ];

    foreach ($dirs as $dir) {
      if (!is_dir($dir)) {
        mkdir($dir);
      }
    }

    $files = [
      'civicrm.standalone.php.txt' => 'civicrm.standalone.php',
      'index.php.txt' => 'index.php',
      'htaccess.txt' => '.htaccess',
    ];
    foreach ($files as $srcFile => $destFile) {
      switch ($mode) {
        case 'copy':
          copy("$srcDir/$srcFile", "$destDir/$destFile");
          break;

        case 'symlink':
          if (file_exists("$destDir/$destFile")) {
            unlink("$destDir/$destFile");
          }
          symlink("$srcDir/$srcFile", "$destDir/$destFile");
          break;
      }
    }
  }

}
