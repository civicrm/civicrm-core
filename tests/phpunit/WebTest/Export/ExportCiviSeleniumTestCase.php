<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 | License along with this program; if not, contact CiviCRM LLC       |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

require_once 'CiviTest/CiviSeleniumTestCase.php';
class ExportCiviSeleniumTestCase extends CiviSeleniumTestCase {

  /**
   * Function to download CSV file.
   *
   * @params string $selector element selector(download button in most of the cases).
   * @params sting  $fileName file name to be download.
   * @params string $downloadDir download dir.
   *
   * @param $selector
   * @param string $fileName
   * @param string $downloadDir
   *
   * @return string downloaded file path.
   */
  function downloadCSV($selector, $fileName = 'CiviCRM_Contact_Search.csv', $downloadDir = '/tmp') {
    // File download path.
    $file = "{$downloadDir}/{$fileName}";

    // Delete file if already exists.
    if (file_exists($file)) {
      @unlink($file);
    }

    // Download file.
    // File will automatically download without confirmation.
    $this->click($selector);
    // Because it tends to cause problems, all uses of sleep() must be justified in comments
    // Sleep should never be used for wait for anything to load from the server
    // FIXME: consider doing the following assertion in a while loop
    // with a more reasonable sleep time of 2 seconds per loop iteration
    sleep(20);

    // File was downloaded?
    $this->assertTrue(file_exists($file), "CSV {$file} was not downloaded.");

    return $file;
  }

  /**
   * Function to read CSV file and fire provided assertions.
   *
   * @params string $file         file path of CSV file.
   * @params array  $checkHeaders check first row of csv
   *                              independent of index.
   * @params array  $checkRows    array of header and rows according to row index
   *                              eg: array(
   *                                    1 => array(
      // Row index 1
   // column name 'First Name', value 'Jones'
   *                                      'First Name' => 'Jones',
   *                                      'Last Name'  => 'Franklin'
   *                                    ),
   *                                    2 => array(
      // Row index 2
   *                                      'First Name' => 'Rajan',
   *                                      'Last Name'  => 'mayekar'
   *                                    ),
   *                                   );
   * @params int   $rowCount count rows (excluding header row).
   * @params array $settings used for override settings.
   */
  function reviewCSV($file, $checkColumns = array(
    ), $checkRows = array(), $rowCount = 0, $settings = array()) {
    // Check file exists before proceed.
    $this->assertTrue(($file && file_exists($file)), "Not able to locate {$file}.");

    // We are going to read downloaded file.
    $fd = fopen($file, 'r');
    if (!$fd) {
      $this->fail("Could not read {$file}.");
    }

    // Default seperator ','.
    $fieldSeparator = !empty($settings['fieldSeparator']) ? $settings['fieldSeparator'] : ',';

    $allRows = array();

    // Read header row.
    $headerRow = fgetcsv($fd, 0, $fieldSeparator);
    $allRows[] = $headerRow;

    // Read all other rows.
    while ($row = fgetcsv($fd, 0, $fieldSeparator)) {
      $allRows[] = $row;
    }

    // We have done with the CSV reading.
    fclose($fd);

    // Check header columns.
    if (!empty($checkColumns)) {
      foreach ($checkColumns as $column) {
        if (!in_array($column, $headerRow)) {
          $this->fail("Missing column {$column}.");
        }
      }
    }

    // Check row count, excluding header row.
    if ($rowCount && !($rowCount == (count($allRows) - 1))) {
      $this->fail("Mismatching row count");
    }

    // Check all other rows.
    if (!empty($checkRows)) {
      foreach ($checkRows as $rowIndex => $row) {
        if ($rowIndex == 0) {
          // Skip checking header row, since we are already doing it above.
          continue;
        }

        foreach ($row as $column => $value) {
          $headerIndex = array_search($column, $headerRow);
          if ($headerIndex === FALSE) {
            $this->fail("Not able to locate column {$column} for row index {$rowIndex}.");
          }

          if (!isset($allRows[$rowIndex][$headerIndex]) || !($value == $allRows[$rowIndex][$headerIndex])) {
            $this->fail("Expected: {$value}, Got: {$allRows[$rowIndex][$headerIndex]}, for column {$column} for row index {$rowIndex}.");
          }
        }
      }
    }

    // Delete file, since we no longer need it.
    if (empty($settings['skipDeleteFile'])) {
      @unlink($file);
    }
  }
}

