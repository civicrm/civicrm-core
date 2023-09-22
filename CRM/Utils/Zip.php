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

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * Utilities for working with zip files
 */
class CRM_Utils_Zip {

  /**
   * Given a zip file which contains a single root directory, determine the root's name.
   *
   * @param ZipArchive $zip
   *
   * @return mixed
   *   FALSE if #root level items !=1; otherwise, the name of base dir
   */
  public static function findBaseDirName(ZipArchive $zip) {
    $cnt = $zip->numFiles;

    $base = FALSE;
    $baselen = FALSE;

    for ($i = 0; $i < $cnt; $i++) {
      $filename = $zip->getNameIndex($i);
      if ($base === FALSE) {
        if (preg_match('/^[^\/]+\/$/', $filename) && $filename != './' && $filename != '../') {
          $base = $filename;
          $baselen = strlen($filename);
        }
        else {
          return FALSE;
        }
      }
      elseif (0 != substr_compare($base, $filename, 0, $baselen)) {
        return FALSE;
      }
    }

    return $base;
  }

  /**
   * Given a zip file, find all directory names in the root
   *
   * @param ZipArchive $zip
   *
   * @return array(string)
   *   no trailing /
   */
  public static function findBaseDirs(ZipArchive $zip) {
    $cnt = $zip->numFiles;
    $basedirs = [];

    for ($i = 0; $i < $cnt; $i++) {
      $filename = $zip->getNameIndex($i);
      // hypothetically, ./ or ../ would not be legit here
      if (preg_match('/^[^\/]+\/$/', $filename) && $filename != './' && $filename != '../') {
        $basedirs[] = rtrim($filename, '/');
      }
    }

    return $basedirs;
  }

  /**
   * Determine the name of the folder within a zip.
   *
   * @param ZipArchive $zip
   * @param string $expected
   *
   * @return string|bool
   *   Return string or FALSE
   */
  public static function guessBasedir(ZipArchive $zip, $expected) {
    $candidate = FALSE;
    $basedirs = CRM_Utils_Zip::findBaseDirs($zip);
    if (in_array($expected, $basedirs)) {
      $candidate = $expected;
    }
    elseif (count($basedirs) == 1) {
      $candidate = array_shift($basedirs);
    }
    if ($candidate !== FALSE && preg_match('/^[a-zA-Z0-9]/', $candidate)) {
      return $candidate;
    }
    else {
      return FALSE;
    }
  }

  /**
   * An inefficient helper for creating a ZIP file from data in memory.
   * This is only intended for building temp files for unit-testing.
   *
   * @param string $zipName
   *   file name.
   * @param array $dirs
   *   Array, list of directory paths.
   * @param array $files
   *   Array, keys are file names and values are file contents.
   * @return bool
   */
  public static function createTestZip($zipName, $dirs, $files) {
    $zip = new ZipArchive();
    $res = $zip->open($zipName, ZipArchive::OVERWRITE);
    if ($res === TRUE) {
      foreach ($dirs as $dir) {
        if (!$zip->addEmptyDir($dir)) {
          return FALSE;
        }
      }
      foreach ($files as $fileName => $fileData) {
        if (!$zip->addFromString($fileName, $fileData)) {
          return FALSE;
        }
      }
      $zip->close();
    }
    else {
      return FALSE;
    }
    return TRUE;
  }

}
