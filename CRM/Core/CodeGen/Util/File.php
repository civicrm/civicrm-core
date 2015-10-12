<?php

/**
 * Class CRM_Core_CodeGen_Util_File
 */
class CRM_Core_CodeGen_Util_File {
  /**
   * @param $dir
   * @param int $perm
   */
  public static function createDir($dir, $perm = 0755) {
    if (!is_dir($dir)) {
      mkdir($dir, $perm, TRUE);
    }
  }

  /**
   * @param $dir
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
   * @param $prefix
   *
   * @return string
   */
  public static function createTempDir($prefix) {
    $newTempDir = tempnam(sys_get_temp_dir(), $prefix) . '.d';
    if (file_exists($newTempDir)) {
      self::removeDir($newTempDir);
    }
    self::createDir($newTempDir);

    return $newTempDir;
  }

  /**
   * Calculate a cumulative digest based on a collection of files.
   *
   * @param array $files
   *   List of file names (strings).
   * @param callable|string $digest a one-way hash function (string => string)
   *
   * @return string
   */
  public static function digestAll($files, $digest = 'md5') {
    $buffer = '';
    foreach ($files as $file) {
      $buffer .= $digest(file_get_contents($file));
    }
    return $digest($buffer);
  }

  /**
   * Find the path to the main Civi source tree.
   *
   * @return string
   * @throws RuntimeException
   */
  public static function findCoreSourceDir() {
    $path = str_replace(DIRECTORY_SEPARATOR, '/', __DIR__);
    if (!preg_match(':(.*)/CRM/Core/CodeGen/Util:', $path, $matches)) {
      throw new RuntimeException("Failed to determine path of code-gen");
    }

    return $matches[1];
  }

  /**
   * Find files in several directories using several filename patterns.
   *
   * @param array $pairs
   *   Each item is an array(0 => $searchBaseDir, 1 => $filePattern).
   * @return array
   *   Array of file paths
   */
  public static function findManyFiles($pairs) {
    $files = array();
    foreach ($pairs as $pair) {
      list ($dir, $pattern) = $pair;
      $files = array_merge($files, CRM_Utils_File::findFiles($dir, $pattern));
    }
    return $files;
  }

}
