<?php
namespace Civi\Setup;

class PackageUtil {

  /**
   * Locate the civicrm-packages source tree.
   *
   * @param string $srcPath
   *   The path to the civicrm-core source tree.
   * @return string
   *   The path to the civicrm-packages source tree.
   */
  public static function getPath($srcPath) {
    global $civicrm_paths;

    $candidates = [
      // TODO: Trace the code-path and allow reading $model for packages dir?
      $civicrm_paths['civicrm.packages']['path'] ?? NULL,
      implode(DIRECTORY_SEPARATOR, [$srcPath, 'packages']),
      implode(DIRECTORY_SEPARATOR, [dirname($srcPath), 'civicrm-packages']),
    ];

    foreach ($candidates as $candidate) {
      if (!empty($candidate) && file_exists($candidate)) {
        return $candidate;
      }
    }

    throw new \RuntimeException("Failed to locate civicrm-packages");
  }

}
