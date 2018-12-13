<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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

/**
 * Class ExportCiviSeleniumTestCase
 */
class ExportCiviSeleniumTestCase extends CiviSeleniumTestCase {

  /**
   * Download CSV file.
   *
   * @param string $selector
   *   Element selector(download button in most of the cases).
   * @param string $fileName
   *   File name to be download.
   * @param string $downloadDir
   *   Download dir.
   *
   * @return string
   *   downloaded file path.
   */
  public function downloadCSV($selector, $fileName = 'CiviCRM_Contact_Search.csv', $downloadDir = '/tmp') {
    // File download path.
    $file = "{$downloadDir}/{$fileName}";

    // Delete file if already exists.
    if (file_exists($file)) {
      @unlink($file);
    }

    $this->click($selector);

    // Wait for file to be downloaded
    for ($i = 1; $i < 15; ++$i) {
      sleep(2);
      if (file_exists($file)) {
        return $file;
      }
    }
    // Timeout
    $this->fail("CSV {$file} was not downloaded.");
  }

  /**
   * Read CSV file and fire provided assertions.
   *
   * @param string $file
   *   File path of CSV file.
   * @param array $checkColumns
   *   Check first row of csv.
   *                              independent of index.
   * @param array $checkRows
   *   Array of header and rows according to row index.
   *                              eg: array(
   *                                    1 => array(
   * // Row index 1
   * // column name 'First Name', value 'Jones'
   *                                      'First Name' => 'Jones',
   *                                      'Last Name'  => 'Franklin'
   *                                    ),
   *                                    2 => array(
   * // Row index 2
   *                                      'First Name' => 'Rajan',
   *                                      'Last Name'  => 'mayekar'
   *                                    ),
   *                                   );
   * @param int $rowCount
   *   Count rows (excluding header row).
   * @param array $settings
   *   Used for override settings.
   */
  public function reviewCSV($file, $checkColumns = array(), $checkRows = array(), $rowCount = 0, $settings = array()) {
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
