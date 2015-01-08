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
 | Version 3, 19 November 2009.                                       |
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
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */
class CRM_Import_DataSource_CSV extends CRM_Import_DataSource {
  CONST
    NUM_ROWS_TO_INSERT = 100;

  /**
   * Provides information about the data source
   *
   * @return array collection of info about this data source
   *
   * @access public
   *
   */
  function getInfo() {
    return array('title' => ts('Comma-Separated Values (CSV)'));
  }

  /**
   * Function to set variables up before form is built
   *
   * @access public
   */
  function preProcess(&$form) {}

  /**
   * This is function is called by the form object to get the DataSource's
   * form snippet. It should add all fields necesarry to get the data
   * uploaded to the temporary table in the DB.
   *
   * @param $form
   *
   * @return void (operates directly on form argument)
   * @access public
   */
  function buildQuickForm(&$form) {
    $form->add('hidden', 'hidden_dataSource', 'CRM_Import_DataSource_CSV');

    $config = CRM_Core_Config::singleton();

    $uploadFileSize = CRM_Core_Config_Defaults::formatUnitSize($config->maxFileSize.'m');
    $uploadSize = round(($uploadFileSize / (1024 * 1024)), 2);
    $form->assign('uploadSize', $uploadSize);
    $form->add('File', 'uploadFile', ts('Import Data File'), 'size=30 maxlength=255', TRUE);
    $form->setMaxFileSize($uploadFileSize);
    $form->addRule('uploadFile', ts('File size should be less than %1 MBytes (%2 bytes)', array(1 => $uploadSize, 2 => $uploadFileSize)), 'maxfilesize', $uploadFileSize);
    $form->addRule('uploadFile', ts('Input file must be in CSV format'), 'utf8File');
    $form->addRule('uploadFile', ts('A valid file must be uploaded.'), 'uploadedfile');

    $form->addElement('checkbox', 'skipColumnHeader', ts('First row contains column headers'));
  }

  /**
   * Function to process the form
   *
   * @access public
   */
  function postProcess(&$params, &$db, &$form) {
    $file = $params['uploadFile']['name'];
    $result = self::_CsvToTable($db,
      $file,
      CRM_Utils_Array::value('skipColumnHeader', $params, FALSE),
      CRM_Utils_Array::value('import_table_name', $params),
      CRM_Utils_Array::value('fieldSeparator', $params, ',')
    );

    $form->set('originalColHeader', CRM_Utils_Array::value('original_col_header', $result));

    $table = $result['import_table_name'];
    $importJob = new CRM_Contact_Import_ImportJob($table);
    $form->set('importTableName', $importJob->getTableName());
  }

  /**
   * Create a table that matches the CSV file and populate it with the file's contents
   *
   * @param object $db     handle to the database connection
   * @param string $file   file name to load
   * @param bool   $headers  whether the first row contains headers
   * @param string $table  Name of table from which data imported.
   * @param string $fieldSeparator Character that seperates the various columns in the file
   *
   * @return string  name of the created table
   */
  private static function _CsvToTable(&$db,
    $file,
    $headers        = FALSE,
    $table          = NULL,
    $fieldSeparator = ','
  ) {
    $result = array();
    $fd = fopen($file, 'r');
    if (!$fd) {
      CRM_Core_Error::fatal("Could not read $file");
    }

    $config = CRM_Core_Config::singleton();
    // support tab separated
    if (strtolower($fieldSeparator) == 'tab' ||
      strtolower($fieldSeparator) == '\t'
    ) {
      $fieldSeparator = "\t";
    }

    $firstrow = fgetcsv($fd, 0, $fieldSeparator);

    // create the column names from the CSV header or as col_0, col_1, etc.
    if ($headers) {
      //need to get original headers.
      $result['original_col_header'] = $firstrow;

      $strtolower = function_exists('mb_strtolower') ? 'mb_strtolower' : 'strtolower';
      $columns    = array_map($strtolower, $firstrow);
      $columns    = str_replace(' ', '_', $columns);
      $columns    = preg_replace('/[^a-z_]/', '', $columns);

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
      foreach ($columns as & $column) $column = "`$column`";
    }
    else {
      $columns = array();
      foreach ($firstrow as $i => $_) $columns[] = "col_$i";
    }

    // FIXME: we should regen this table's name if it exists rather than drop it
    if (!$table) {
      $table = 'civicrm_import_job_' . md5(uniqid(rand(), TRUE));
    }

    $db->query("DROP TABLE IF EXISTS $table");

    $numColumns = count($columns);
    $create = "CREATE TABLE $table (" . implode(' text, ', $columns) . " text) ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci";
    $db->query($create);

    // the proper approach, but some MySQL installs do not have this enabled
    // $load = "LOAD DATA LOCAL INFILE '$file' INTO TABLE $table FIELDS TERMINATED BY '$fieldSeparator' OPTIONALLY ENCLOSED BY '\"'";
    // if ($headers) {   $load .= ' IGNORE 1 LINES'; }
    // $db->query($load);

    // parse the CSV line by line and build one big INSERT (while MySQL-escaping the CSV contents)
    if (!$headers) {
      rewind($fd);
    }

    $sql   = NULL;
    $first = TRUE;
    $count = 0;
    while ($row = fgetcsv($fd, 0, $fieldSeparator)) {
      // skip rows that dont match column count, else we get a sql error
      if (count($row) != $numColumns) {
        continue;
      }

      if (!$first) {
        $sql .= ', ';
      }

      $first = FALSE;
      $row = array_map('civicrm_mysql_real_escape_string', $row);
      $sql .= "('" . implode("', '", $row) . "')";
      $count++;

      if ($count >= self::NUM_ROWS_TO_INSERT && !empty($sql)) {
        $sql = "INSERT IGNORE INTO $table VALUES $sql";
        $db->query($sql);

        $sql   = NULL;
        $first = TRUE;
        $count = 0;
      }
    }

    if (!empty($sql)) {
      $sql = "INSERT IGNORE INTO $table VALUES $sql";
      $db->query($sql);
    }

    fclose($fd);

    //get the import tmp table name.
    $result['import_table_name'] = $table;

    return $result;
  }
}

/**
 * @param $string
 *
 * @return string
 */
function civicrm_mysql_real_escape_string($string) {
  static $dao = NULL;
  if (!$dao) {
    $dao = new CRM_Core_DAO();
  }
  return $dao->escape($string);
}

