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
  const
    NUM_ROWS_TO_INSERT = 100;

  /**
   * Form fields declared for this datasource.
   *
   * @var string[]
   */
  protected $submittableFields = ['skipColumnHeader', 'uploadField'];

  /**
   * Provides information about the data source.
   *
   * @return array
   *   collection of info about this data source
   */
  public function getInfo(): array {
    return ['title' => ts('Comma-Separated Values (CSV)')];
  }

  /**
   * This is function is called by the form object to get the DataSource's form snippet.
   *
   * It should add all fields necessary to get the data
   * uploaded to the temporary table in the DB.
   *
   * @param CRM_Core_Form $form
   *
   * @throws \CRM_Core_Exception
   */
  public function buildQuickForm(&$form) {
    $form->add('hidden', 'hidden_dataSource', 'CRM_Import_DataSource_CSV');

    $uploadFileSize = CRM_Utils_Number::formatUnitSize(Civi::settings()->get('maxFileSize') . 'm', TRUE);
    //Fetch uploadFileSize from php_ini when $config->maxFileSize is set to "no limit".
    if (empty($uploadFileSize)) {
      $uploadFileSize = CRM_Utils_Number::formatUnitSize(ini_get('upload_max_filesize'), TRUE);
    }
    $uploadSize = round(($uploadFileSize / (1024 * 1024)), 2);
    $form->assign('uploadSize', $uploadSize);
    $form->add('File', 'uploadFile', ts('Import Data File'), NULL, TRUE);
    $form->setMaxFileSize($uploadFileSize);
    $form->addRule('uploadFile', ts('File size should be less than %1 MBytes (%2 bytes)', [
      1 => $uploadSize,
      2 => $uploadFileSize,
    ]), 'maxfilesize', $uploadFileSize);
    $form->addRule('uploadFile', ts('Input file must be in CSV format'), 'utf8File');
    $form->addRule('uploadFile', ts('A valid file must be uploaded.'), 'uploadedfile');

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

    $this->updateUserJobMetadata('DataSource', [
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

      $strtolower = function_exists('mb_strtolower') ? 'mb_strtolower' : 'strtolower';
      $columns = array_map($strtolower, $firstrow);
      $columns = array_map('trim', $columns);
      $columns = str_replace(' ', '_', $columns);
      $columns = preg_replace('/[^a-z_]/', '', $columns);

      // need to take care of null as well as duplicate col names.
      $duplicateColName = FALSE;
      if (count($columns) != count(array_unique($columns))) {
        $duplicateColName = TRUE;
      }

      // need to truncate values per mysql field name length limits
      // mysql allows 64, but we need to account for appending colKey
      // CRM-9079
      foreach ($columns as $colKey => & $colName) {
        if (strlen($colName) > 58) {
          $colName = substr($colName, 0, 58);
        }
      }

      if (in_array('', $columns) || $duplicateColName) {
        foreach ($columns as $colKey => & $colName) {
          if (!$colName) {
            $colName = "col_$colKey";
          }
          elseif ($duplicateColName) {
            $colName .= "_$colKey";
          }
        }
      }

      // CRM-4881: we need to quote column names, as they may be MySQL reserved words
      foreach ($columns as & $column) {
        $column = "`$column`";
      }
    }
    else {
      $columns = [];
      foreach ($firstrow as $i => $_) {
        $columns[] = "column_$i";
      }
      $result['column_headers'] = $columns;
    }

    $table = CRM_Utils_SQL_TempTable::build()->setDurable();
    $tableName = $table->getName();
    CRM_Core_DAO::executeQuery("DROP TABLE IF EXISTS $tableName");
    $table->createWithColumns(implode(' text, ', $columns) . ' text');

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
      $row = array_map(['CRM_Import_DataSource_CSV', 'trimNonBreakingSpaces'], $row);
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
   * Trim non-breaking spaces in a multibyte-safe way.
   * See also dev/core#2127 - avoid breaking strings ending in à or any other
   * unicode character sharing the same 0xA0 byte as a non-breaking space.
   *
   * @param string $string
   * @return string The trimmed string
   */
  public static function trimNonBreakingSpaces(string $string): string {
    $encoding = mb_detect_encoding($string, NULL, TRUE);
    if ($encoding === FALSE) {
      // This could mean a couple things. One is that the string is
      // ASCII-encoded but contains a non-breaking space, which causes
      // php to fail to detect the encoding. So let's just do what we
      // did before which works in that situation and is at least no
      // worse in other situations.
      return trim($string, chr(0xC2) . chr(0xA0));
    }
    elseif ($encoding !== 'UTF-8') {
      $string = mb_convert_encoding($string, 'UTF-8', [$encoding]);
    }
    return preg_replace("/^(\u{a0})+|(\u{a0})+$/", '', $string);
  }

}
