<?php

class CRM_Core_CodeGen_Util_File {
  static function createDir($dir, $perm = 0755) {
    if (!is_dir($dir)) {
      mkdir($dir, $perm, TRUE);
    }
  }

  static function removeDir($dir) {
    foreach (glob("$dir/*") as $tempFile) {
      unlink($tempFile);
    }
    rmdir($dir);
  }

  static function createTempDir($prefix) {
    if (isset($_SERVER['TMPDIR'])) {
      $tempDir = $_SERVER['TMPDIR'];
    }
    else {
      $tempDir = '/tmp';
    }

    $newTempDir = $tempDir . '/' . $prefix . rand(1, 10000);

    if (file_exists($newTempDir)) {
      self::removeDir($newTempDir);
    }
    self::createDir($newTempDir);

    return $newTempDir;
  }
}
