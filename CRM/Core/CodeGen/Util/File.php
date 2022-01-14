<?php

/**
 * Class CRM_Core_CodeGen_Util_File
 */
class CRM_Core_CodeGen_Util_File {

  /**
   * @param string $dir
   * @param int $perm
   */
  public static function createDir($dir, $perm = 0755) {
    if (!is_dir($dir)) {
      mkdir($dir, $perm, TRUE);
    }
  }

  /**
   * @param string $dir
   */
  public static function cleanTempDir($dir) {
    foreach (glob("$dir/*") as $tempFile) {
      unlink($tempFile);
    }
    rmdir($dir);
    if (preg_match(':^(.*)\.d$:', $dir, $matches)) {
      if (file_exists($matches[1])) {
        unlink($matches[1]);
      }
    }
  }

  /**
   * @param string $prefix
   *
   * @return string
   */
  public static function createTempDir($prefix) {
    $newTempDir = tempnam(sys_get_temp_dir(), $prefix) . '.d';
    if (file_exists($newTempDir)) {
      self::cleanTempDir($newTempDir);
    }
    self::createDir($newTempDir);

    return $newTempDir;
  }

}
