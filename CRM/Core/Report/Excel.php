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
 * Class CRM_Core_Report_Excel
 */
class CRM_Core_Report_Excel {

  /**
   * Code copied from phpMyAdmin (v2.6.1-pl3)
   * File: PHPMYADMIN/libraries/export/csv.php
   * Function: PMA_exportData
   *
   * Outputs a result set with a given header
   * in the string buffer result
   *
   * @param array $header
   *   column headers.
   * @param array $rows
   *   result set rows.
   * @param bool $outputHeader
   *
   * @return mixed
   *   empty if output is printed, else output
   *
   */
  public static function makeCSVTable($header, $rows, $outputHeader = TRUE) {

    $config = CRM_Core_Config::singleton();
    $separator = $config->fieldSeparator;
    $add_character = "\015\012";

    if ($outputHeader) {
      self::outputHeaderRow($header);
    }

    $fields_cnt = count($header);
    foreach ($rows as $row) {
      $schema_insert = '';
      $colNo = 0;

      foreach ($row as $j => $value) {
        if (!isset($value) || is_null($value) || $value === '') {
          $schema_insert .= '';
        }
        else {
          // loic1 : always enclose fields
          //$value = ereg_replace("\015(\012)?", "\012", $value);
          // Convert  carriage return to line feed.
          $value = preg_replace("/\015(\012)?/", "\012", $value);
          if ((substr($value, 0, 1) == CRM_Core_DAO::VALUE_SEPARATOR) &&
            (substr($value, -1, 1) == CRM_Core_DAO::VALUE_SEPARATOR)
          ) {

            $strArray = explode(CRM_Core_DAO::VALUE_SEPARATOR, $value);
            // Filter out empty value separated strings.
            foreach ($strArray as $key => $val) {
              if (trim($val) == '') {
                unset($strArray[$key]);
              }
            }

            $str = implode($separator, $strArray);
            $value = &$str;
          }

          $schema_insert .= '"' . str_replace('"', '""', $value) . '"';
        }

        if ($colNo < $fields_cnt - 1) {
          $schema_insert .= $separator;
        }
        $colNo++;
      }
      // end for

      $out = $schema_insert . $add_character;
      echo $out;
    }
  }

  /**
   * Output the header row for a csv file.
   *
   * @param array $header
   *   Array of field names.
   */
  public static function outputHeaderRow($header) {
    $schema_insert = '';
    $separator = Civi::settings()->get('fieldSeparator');
    foreach ($header as $field) {
      $schema_insert .= '"' . str_replace('"', '""', stripslashes($field)) . '"';
      $schema_insert .= $separator;
    }
    // end while
    // need to add PMA_exportOutputHandler functionality out here, rather than
    // doing it the moronic way of assembling a buffer
    // We append a hex newline at the end.
    echo trim(substr($schema_insert, 0, -1)) . "\015\012";
  }

  /**
   * @param string $fileName
   * @param string[] $header
   * @param array[] $rows
   * @param null $titleHeader
   * @param bool $outputHeader
   */
  public function writeHTMLFile($fileName, $header, $rows, $titleHeader = NULL, $outputHeader = TRUE) {
    if ($outputHeader) {
      CRM_Utils_System::download(CRM_Utils_String::munge($fileName),
        'application/vnd.ms-excel',
        CRM_Core_DAO::$_nullObject,
        'xls',
        FALSE
      );
    }

    echo "<table><thead><tr>";
    foreach ($header as $field) {
      echo "<th>$field</th>";
    }
    echo "</tr></thead><tbody>";

    foreach ($rows as $row) {
      $schema_insert = '';
      $colNo = 0;
      echo "<tr>";
      foreach ($row as $j => $value) {
        echo "<td>" . htmlentities($value, ENT_COMPAT, 'UTF-8') . "</td>";
      }
      echo "</tr>";
    }

    echo "</tbody></table>";
  }

  /**
   * Write a CSV file to the browser output.
   *
   * @param string $fileName
   *   The name of the file that will be downloaded (this is sent to the browser).
   * @param array $header
   *   An array of the headers.
   * @param array $rows
   *   An array of arrays of the table contents.
   *
   * @return void
   */
  public static function writeCSVFile($fileName, $header, $rows) {
    $null = NULL;
    CRM_Utils_System::download(CRM_Utils_String::munge($fileName),
      'text/x-csv',
      $null,
      'csv',
      FALSE
    );

    if (!empty($rows)) {
      return self::makeCSVTable($header, $rows, TRUE);
    }
  }

}
