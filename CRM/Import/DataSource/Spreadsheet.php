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

use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Import_DataSource_Spreadsheet extends CRM_Import_DataSource {
  protected const NUM_ROWS_TO_INSERT = 100;

  /**
   * Form fields declared for this datasource.
   *
   * @var string[]
   */
  protected $submittableFields = ['isFirstRowHeader', 'uploadFile'];

  /**
   * Provides information about the data source.
   *
   * @return array
   *   collection of info about this data source
   */
  public function getInfo(): array {
    return [
      'title' => ts('Spreadsheet (xlsx, odt)'),
      'template' => 'CRM/Contact/Import/Form/Spreadsheet.tpl',
    ];
  }

  /**
   * This is function is called by the form object to get the DataSource's form snippet.
   *
   * It should add all fields necessary to get the data
   * uploaded to the temporary table in the DB.
   *
   * @param CRM_Contact_Import_Form_DataSource|\CRM_Import_Form_DataSourceConfig $form
   */
  public function buildQuickForm(\CRM_Import_Forms $form): void {
    $form->add('hidden', 'hidden_dataSource', 'CRM_Import_DataSource_Spreadsheet');
    $form->addElement('checkbox', 'isFirstRowHeader', ts('First row contains column headers'));

    $maxFileSizeMegaBytes = CRM_Utils_File::getMaxFileSize();
    $maxFileSizeBytes = $maxFileSizeMegaBytes * 1024 * 1024;
    $form->assign('uploadSize', $maxFileSizeMegaBytes);
    $form->add('File', 'uploadFile', ts('Import Data File'), NULL, TRUE);
    $form->setMaxFileSize($maxFileSizeBytes);
    $form->addRule('uploadFile', ts('File size should be less than %1 MBytes (%2 bytes)', [
      1 => $maxFileSizeMegaBytes,
      2 => $maxFileSizeBytes,
    ]), 'maxfilesize', $maxFileSizeBytes);
    $form->addFormRule([__CLASS__, 'validateUploadedFile']);
    $form->setDataSourceDefaults($this->getDefaultValues());
  }

  /**
   * Initialize the datasource, based on the submitted values stored in the user job.
   *
   * @throws \CRM_Core_Exception
   */
  public function initialize(): void {
    $result = $this->uploadToTable();
    $this->addTrackingFieldsToTable($result['import_table_name']);

    $this->updateUserJobDataSource([
      'table_name' => $result['import_table_name'],
      'column_headers' => $result['column_headers'],
      'number_of_columns' => $result['number_of_columns'],
    ]);
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
      $row = array_map([__CLASS__, 'trimNonBreakingSpaces'], $row);
      $row = array_map(['CRM_Core_DAO', 'escapeString'], $row);
      $sql[] = "('" . implode("', '", $row) . "')";

      if (count($sql) >= self::NUM_ROWS_TO_INSERT) {
        CRM_Core_DAO::executeQuery("INSERT IGNORE INTO $tableName VALUES " . implode(', ', $sql));
        $sql = [];
      }
    }

    if (!empty($sql)) {
      CRM_Core_DAO::executeQuery("INSERT IGNORE INTO $tableName VALUES " . implode(', ', $sql));
    }

    return [
      'import_table_name' => $tableName,
      'number_of_columns' => $numColumns,
      'column_headers' => $columnHeaders,
    ];
  }

  /**
   * Get default values for csv dataSource fields.
   *
   * @return array
   */
  public function getDefaultValues(): array {
    return [
      'isFirstRowHeader' => 1,
      'template' => 'CRM/Contact/Import/Form/Spreadsheet.tpl',
    ];
  }

  /**
   * Validate the file type of the uploaded file.
   *
   * @param array $fields
   * @param array $files
   *
   * @return array
   */
  public static function validateUploadedFile(array $fields, $files): array {
    $file = $files['uploadFile'];
    $tmp_file = $file['tmp_name'];
    $file_type = IOFactory::identify($tmp_file);
    $errors = [];
    if (!in_array($file_type, ['Xlsx', 'Ods'])) {
      $errors['uploadFile'] = ts('The file must be of type ODS (LibreOffice), or XLSX (Excel).');
    }
    return $errors;
  }

}
