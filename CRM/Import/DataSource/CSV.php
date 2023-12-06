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
class CRM_Import_DataSource_CSV extends CRM_Import_DataSource {
  private const NUM_ROWS_TO_INSERT = 100;

  /**
   * Form fields declared for this datasource.
   *
   * @var string[]
   */
  protected $submittableFields = ['skipColumnHeader', 'uploadFile', 'fieldSeparator'];

  /**
   * Provides information about the data source.
   *
   * @return array
   *   collection of info about this data source
   */
  public function getInfo(): array {
    return [
      'title' => ts('Comma-Separated Values (CSV)'),
      'template' => 'CRM/Contact/Import/Form/CSV.tpl',
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
    $form->add('hidden', 'hidden_dataSource', 'CRM_Import_DataSource_CSV');

    $maxFileSizeMegaBytes = CRM_Utils_File::getMaxFileSize();
    $maxFileSizeBytes = $maxFileSizeMegaBytes * 1024 * 1024;
    $form->assign('uploadSize', $maxFileSizeMegaBytes);
    $form->add('File', 'uploadFile', ts('Import Data File'), NULL, TRUE);
    $form->add('text', 'fieldSeparator', ts('Import Field Separator'), ['size' => 2], TRUE);
    $form->setMaxFileSize($maxFileSizeBytes);
    $form->addRule('uploadFile', ts('File size should be less than %1 MBytes (%2 bytes)', [
      1 => $maxFileSizeMegaBytes,
      2 => $maxFileSizeBytes,
    ]), 'maxfilesize', $maxFileSizeBytes);
    $form->addRule('uploadFile', ts('Input file must be in CSV format'), 'utf8File');
    $form->addRule('uploadFile', ts('A valid file must be uploaded.'), 'uploadedfile');
    $form->setDataSourceDefaults($this->getDefaultValues());
    $form->addElement('checkbox', 'skipColumnHeader', ts('First row contains column headers'));
  }

  /**
   * Initialize the datasource, based on the submitted values stored in the user job.
   *
   * @throws \CRM_Core_Exception
   */
  public function initialize(): void {
    $result = $this->csvToTable(
      $this->getSubmittedValue('uploadFile')['name'],
      $this->getSubmittedValue('skipColumnHeader'),
      $this->getSubmittedValue('fieldSeparator') ?? ','
    );
    $this->addTrackingFieldsToTable($result['import_table_name']);

    $this->updateUserJobDataSource([
      'table_name' => $result['import_table_name'],
      'column_headers' => $result['column_headers'],
      'number_of_columns' => $result['number_of_columns'],
    ]);
  }

  /**
   * Create a table that matches the CSV file and populate it with the file's contents
   *
   * @param string $file
   *   File name to load.
   * @param bool $headers
   *   Whether the first row contains headers.
   * @param string $fieldSeparator
   *   Character that separates the various columns in the file.
   *
   * @return array
   *   name of the created table
   * @throws \CRM_Core_Exception
   */
  private function csvToTable(
    $file,
    $headers = FALSE,
    $fieldSeparator = ','
  ) {
    $result = [];
    $fd = fopen($file, 'r');
    if (!$fd) {
      throw new CRM_Core_Exception("Could not read $file");
    }
    if (filesize($file) == 0) {
      throw new CRM_Core_Exception("$file is empty. Please upload a valid file.");
    }

    // support tab separated
    if (strtolower($fieldSeparator) === 'tab' ||
      strtolower($fieldSeparator) === '\t'
    ) {
      $fieldSeparator = "\t";
    }

    $firstrow = fgetcsv($fd, 0, $fieldSeparator);
    // create the column names from the CSV header or as col_0, col_1, etc.
    if ($headers) {
      //need to get original headers.
      $result['column_headers'] = $firstrow;
      $columns = $this->getColumnNamesFromHeaders($firstrow);
    }
    else {
      $columns = $this->getColumnNamesForUnnamedColumns($firstrow);
      $result['column_headers'] = $columns;
    }

    $tableName = $this->createTempTableFromColumns($columns);

    $numColumns = count($columns);

    // the proper approach, but some MySQL installs do not have this enabled
    // $load = "LOAD DATA LOCAL INFILE '$file' INTO TABLE $table FIELDS TERMINATED BY '$fieldSeparator' OPTIONALLY ENCLOSED BY '\"'";
    // if ($headers) {   $load .= ' IGNORE 1 LINES'; }
    // $db->query($load);

    // parse the CSV line by line and build one big INSERT (while MySQL-escaping the CSV contents)
    if (!$headers) {
      rewind($fd);
    }

    $sql = NULL;
    $first = TRUE;
    $count = 0;
    while ($row = fgetcsv($fd, 0, $fieldSeparator)) {
      // skip rows that dont match column count, else we get a sql error
      if (count($row) != $numColumns) {
        continue;
      }
      // A blank line will be array(0 => NULL)
      if ($row === [NULL]) {
        continue;
      }

      if (!$first) {
        $sql .= ', ';
      }

      $first = FALSE;

      // CRM-17859 Trim non-breaking spaces from columns.
      $row = array_map([__CLASS__, 'trimNonBreakingSpaces'], $row);
      $row = array_map(['CRM_Core_DAO', 'escapeString'], $row);
      $sql .= "('" . implode("', '", $row) . "')";
      $count++;

      if ($count >= self::NUM_ROWS_TO_INSERT && !empty($sql)) {
        CRM_Core_DAO::executeQuery("INSERT IGNORE INTO $tableName VALUES $sql");

        $sql = NULL;
        $first = TRUE;
        $count = 0;
      }
    }

    if (!empty($sql)) {
      CRM_Core_DAO::executeQuery("INSERT IGNORE INTO $tableName VALUES $sql");
    }

    fclose($fd);

    //get the import tmp table name.
    $result['import_table_name'] = $tableName;
    $result['number_of_columns'] = $numColumns;
    return $result;
  }

  /**
   * Get default values for csv dataSource fields.
   *
   * @return array
   */
  public function getDefaultValues(): array {
    return [
      'fieldSeparator' => CRM_Core_Config::singleton()->fieldSeparator,
      'skipColumnHeader' => 1,
      'template' => 'CRM/Contact/Import/Form/CSV.tpl',
    ];

  }

}
