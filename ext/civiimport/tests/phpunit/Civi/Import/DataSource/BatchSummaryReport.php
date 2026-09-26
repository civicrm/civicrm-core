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

namespace Civi\Import\DataSource;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Exception as ReaderException;

/**
 * Test-only datasource for a vendor "Batch Summary Report" xlsx export.
 *
 * Unlike a normal tabular export, the report is a run of repeating blocks -
 * a group label line, a timestamp line, a repeated column-header row, one
 * or more data rows, a "Total" subtotal row, then blank separator rows -
 * preceded by a title/date-range preamble.
 *
 * @internal exists to back CRM_Batch_Import_Parser_BatchTest::testImportBatchSummaryReport() -
 * any class implementing DataSourceInterface is auto-discovered by
 * ClassScanner and appears in every import's datasource dropdown, so this
 * would need a more presentable getInfo()/permission gate before it could
 * be considered a real, general-purpose datasource.
 */
class BatchSummaryReport extends \CRM_Import_DataSource implements DataSourceInterface {
  use DataSourceTrait;

  protected const NUM_ROWS_TO_INSERT = 100;

  public function getInfo(): array {
    return [
      'title' => ts('Batch Summary Report (test fixture)'),
    ];
  }

  public function buildQuickForm(\CRM_Import_Forms $form): void {
    if (\CRM_Utils_Request::retrieveValue('user_job_id', 'Integer')) {
      $this->setUserJobID(\CRM_Utils_Request::retrieveValue('user_job_id', 'Integer'));
    }
    $form->add('hidden', 'hidden_dataSource', self::class);
    $maxFileSizeMegaBytes = \CRM_Utils_File::getMaxFileSize();
    $maxFileSizeBytes = $maxFileSizeMegaBytes * 1024 * 1024;
    $form->assign('uploadSize', $maxFileSizeMegaBytes);
    $form->add('File', 'uploadFile', ts('Import Data File'), NULL, TRUE);
    $form->setMaxFileSize($maxFileSizeBytes);
    $form->addRule('uploadFile', ts('File size should be less than %1 MBytes (%2 bytes)', [
      1 => $maxFileSizeMegaBytes,
      2 => $maxFileSizeBytes,
    ]), 'maxfilesize', $maxFileSizeBytes);
    $form->registerRule('spreadsheet', 'callback', 'isValidSpreadsheet', Spreadsheet::class);
    $form->addRule('uploadFile', ts('The file must be of type ODS (LibreOffice), XLSX (Excel).'), 'spreadsheet');
  }

  public function getSubmittableFields(): array {
    return ['uploadFile'];
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public function initialize(): void {
    try {
      $result = $this->uploadToTable();
      $this->updateUserJobDataSource([
        'table_name' => $result['import_table_name'],
        'column_headers' => $result['column_headers'],
        'number_of_columns' => $result['number_of_columns'],
      ]);
    }
    catch (ReaderException $e) {
      throw new \CRM_Core_Exception(ts('Spreadsheet not loaded.') . '' . $e->getMessage());
    }
  }

  /**
   * @throws \CRM_Core_Exception
   * @throws \Civi\Core\Exception\DBQueryException
   * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
   */
  private function uploadToTable(): array {
    $file_type = IOFactory::identify($this->getSubmittedValue('uploadFile')['name']);
    $objReader = IOFactory::createReader($file_type);
    $objReader->setReadDataOnly(TRUE);

    $objPHPExcel = $objReader->load($this->getSubmittedValue('uploadFile')['name']);
    $sheetRows = $objPHPExcel->getActiveSheet()->toArray(NULL, TRUE, TRUE, TRUE);

    [$columnHeaders, $dataRows] = $this->extractDataRows($sheetRows);
    $columns = $this->getColumnNamesFromHeaders($columnHeaders);

    $tableName = $this->createTempTableFromColumns($columns);
    $numColumns = count($columns);

    $sql = [];
    foreach ($dataRows as $row) {
      $row = array_map(['CRM_Core_DAO', 'escapeString'], $row);
      $sql[] = "('" . implode("', '", $row) . "')";
      if (count($sql) >= self::NUM_ROWS_TO_INSERT) {
        \CRM_Core_DAO::executeQuery("INSERT IGNORE INTO $tableName VALUES " . implode(', ', $sql));
        $sql = [];
      }
    }
    if (!empty($sql)) {
      \CRM_Core_DAO::executeQuery("INSERT IGNORE INTO $tableName VALUES " . implode(', ', $sql));
    }
    $this->addTrackingFieldsToTable($tableName);

    return [
      'import_table_name' => $tableName,
      'number_of_columns' => $numColumns,
      'column_headers' => $columnHeaders,
    ];
  }

  /**
   * Walk the raw sheet rows and pull out just the real batch data rows.
   *
   * Skips: the report title/date-range preamble before the first header
   * row is found; repeats of that header row (one per block); blank
   * separator rows; "Total" subtotal rows; and single-non-blank-cell rows
   * (the group label / timestamp lines that precede each block's header).
   *
   * @param array $sheetRows
   *
   * @return array{0: string[], 1: array[]}
   *   [column headers, data rows] - both using the header row's column
   *   count, in header order.
   *
   * @throws \CRM_Core_Exception
   */
  private function extractDataRows(array $sheetRows): array {
    $headers = NULL;
    $rows = [];
    foreach ($sheetRows as $sheetRow) {
      $row = array_map([self::class, 'trimWhitespace'], array_map('strval', array_values($sheetRow)));
      if ($headers === NULL) {
        if (($row[0] ?? NULL) === 'Batch Type') {
          $headers = $row;
        }
        continue;
      }
      $nonBlankValues = array_values(array_filter($row, fn(string $value): bool => $value !== ''));
      if ($row === $headers || count($nonBlankValues) <= 1) {
        // Blank separator, a repeated block header, or a lone group-label/
        // timestamp line - none of these are data rows.
        continue;
      }
      if ($nonBlankValues[0] === 'Total') {
        continue;
      }
      $rows[] = array_merge(array_slice($row, 0, count($headers)), ['Open']);
    }
    if ($headers === NULL) {
      throw new \CRM_Core_Exception('Could not find the "Batch Type" header row in the uploaded report.');
    }
    // The report carries no status column - every batch it lists is newly
    // recorded, so a fixed 'Open' status is appended as a synthetic column.
    $headers[] = 'Status';
    return [$headers, $rows];
  }

}
