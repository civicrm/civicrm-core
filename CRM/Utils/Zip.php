<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2015
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
  static public function findBaseDirName(ZipArchive $zip) {
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
  static public function findBaseDirs(ZipArchive $zip) {
    $cnt = $zip->numFiles;
    $basedirs = array();

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
   * @param $expected
   *
   * @return string|bool
   *   Return string or FALSE
   */
  static public function guessBasedir(ZipArchive $zip, $expected) {
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
  static public function createTestZip($zipName, $dirs, $files) {
    $zip = new ZipArchive();
    $res = $zip->open($zipName, ZipArchive::CREATE);
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
