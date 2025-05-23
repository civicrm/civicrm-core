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
 * Objects that implement the DataSource interface can be used in CiviCRM imports.
 */
class Spreadsheet extends \CRM_Import_DataSource implements DataSourceInterface {
  use DataSourceTrait;
  protected const NUM_ROWS_TO_INSERT = 100;

  /**
   * Provides information about the data source.
   *
   * @return array
   *   collection of info about this data source
   */
  public function getInfo(): array {
    return [
      'title' => ts('Spreadsheet (xlsx, odt)'),
      'template' => 'CRM/Import/DataSource/Spreadsheet.tpl',
    ];
  }

  /**
   * This is function is called by the form object to get the DataSource's form
   * snippet.
   *
   * It should add all fields necessary to get the data
   * uploaded to the temporary table in the DB.
   *
   * @param \CRM_Contact_Import_Form_DataSource|\CRM_Import_Form_DataSourceConfig $form
   *
   * @throws \CRM_Core_Exception
   */
  public function buildQuickForm(\CRM_Import_Forms $form): void {
    if (\CRM_Utils_Request::retrieveValue('user_job_id', 'Integer')) {
      $this->setUserJobID(\CRM_Utils_Request::retrieveValue('user_job_id', 'Integer'));
    }
    $form->add('hidden', 'hidden_dataSource', 'CRM_Import_DataSource_Spreadsheet');
    $form->addElement('checkbox', 'isFirstRowHeader', ts('First row contains column headers'));

    $maxFileSizeMegaBytes = \CRM_Utils_File::getMaxFileSize();
    $maxFileSizeBytes = $maxFileSizeMegaBytes * 1024 * 1024;
    $form->assign('uploadSize', $maxFileSizeMegaBytes);
    $form->add('File', 'uploadFile', ts('Import Data File'), NULL, TRUE);
    $form->setMaxFileSize($maxFileSizeBytes);
    $form->addRule('uploadFile', ts('File size should be less than %1 MBytes (%2 bytes)', [
      1 => $maxFileSizeMegaBytes,
      2 => $maxFileSizeBytes,
    ]), 'maxfilesize', $maxFileSizeBytes);
    $form->registerRule('spreadsheet', 'callback', 'isValidSpreadsheet', __CLASS__);
    $form->addRule('uploadFile', ts('The file must be of type ODS (LibreOffice), XLSX (Excel).'), 'spreadsheet');

    $form->setDataSourceDefaults($this->getDefaultValues());
  }

  /**
   * Is the value in the uploaded file field a valid spreadsheet.
   *
   * @param array $file
   *
   * @return bool
   *
   * @noinspection PhpUnused
   */
  public static function isValidSpreadsheet(array $file): bool {
    $file_type = IOFactory::identify($file['tmp_name']);
    return in_array($file_type, ['Xlsx', 'Ods']);
  }

  /**
   * Get default values for excel dataSource fields.
   *
   * @return array
   */
  public function getDefaultValues(): array {
    return [
      'isFirstRowHeader' => 1,
    ];
  }

  /**
   * Get array array of field names that may be submitted for this data source.
   *
   * The quick form for the datasource is added by ajax - meaning that QuickForm
   * does not see them as part of the form. However, any fields listed in this array
   * will be taken from the `$_POST` and stored to the UserJob under the DataSource key.
   *
   * @return array
   */
  public function getSubmittableFields(): array {
    return ['isFirstRowHeader', 'uploadFile'];
  }

  /**
   * Initialize the datasource, based on the submitted values stored in the user job.
   *
   * Generally this will include transferring the data to a database table.
   *
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
    $dataRows = $objPHPExcel->getActiveSheet()->toArray(NULL, TRUE, TRUE, TRUE);

    // Remove the header
    if ($this->getSubmittedValue('isFirstRowHeader')) {
      $headers = array_values(array_shift($dataRows));
      $columnHeaders = $headers;
      $columns = $this->getColumnNamesFromHeaders($headers);
    }
    else {
      $columns = $this->getColumnNamesForUnnamedColumns(array_values($dataRows[1]));
      $columnHeaders = $columns;
    }

    $tableName = $this->createTempTableFromColumns($columns);
    $numColumns = count($columns);
    // Re-key data using the headers
    $sql = [];
    foreach ($dataRows as $row) {
      // CRM-17859 Trim non-breaking spaces from columns.
      $row = array_map([__CLASS__, 'trimWhiteSpace'], $row);
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

}
