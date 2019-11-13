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
 * Class CiviReportTestCase
 */
class CiviReportTestCase extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();
    $this->_sethtmlGlobals();
  }

  public function tearDown() {
    // TODO Figure out how to automatically drop all temporary tables.
    // Note that MySQL doesn't provide a way to list them, so we would need
    // to keep track ourselves (eg CRM_Core_TemporaryTableManager) or reset
    // the MySQL connection between test runs.

    $this->quickCleanup($this->_tablesToTruncate);
    parent::tearDown();
  }

  /**
   * @param $reportClass
   * @param array $inputParams
   *
   * @return string
   * @throws Exception
   */
  public function getReportOutputAsCsv($reportClass, $inputParams) {

    $reportObj = $this->getReportObject($reportClass, $inputParams);
    try {
      $rows = $reportObj->getResultSet();
      $tmpFile = $this->createTempDir() . CRM_Utils_File::makeFileName('CiviReport.csv');
      $csvContent = CRM_Report_Utils_Report::makeCsv($reportObj, $rows);
      file_put_contents($tmpFile, $csvContent);
    }
    catch (Exception $e) {
      throw $e;
    }
    return $tmpFile;
  }

  /**
   * @param $reportClass
   * @param array $inputParams
   *
   * @return array
   * @throws Exception
   */
  public function getReportObject($reportClass, $inputParams) {
    $config = CRM_Core_Config::singleton();
    $config->keyDisable = TRUE;
    $controller = new CRM_Core_Controller_Simple($reportClass, ts('some title'));
    $tmpReportVal = explode('_', $reportClass);
    $reportName = array_pop($tmpReportVal);
    $reportObj =& $controller->_pages[$reportName];

    $tmpGlobals = array();
    $tmpGlobals['_REQUEST']['force'] = 1;
    $tmpGlobals['_GET'][$config->userFrameworkURLVar] = 'civicrm/placeholder';
    $tmpGlobals['_SERVER']['QUERY_STRING'] = '';
    if (!empty($inputParams['fields'])) {
      $fields = implode(',', $inputParams['fields']);
      $tmpGlobals['_GET']['fld'] = $fields;
      $tmpGlobals['_GET']['ufld'] = 1;
    }
    if (!empty($inputParams['filters'])) {
      foreach ($inputParams['filters'] as $key => $val) {
        $tmpGlobals['_GET'][$key] = $val;
      }
    }
    if (!empty($inputParams['group_bys'])) {
      $groupByFields = implode(' ', $inputParams['group_bys']);
      $tmpGlobals['_GET']['gby'] = $groupByFields;
    }

    CRM_Utils_GlobalStack::singleton()->push($tmpGlobals);

    try {
      $reportObj->storeResultSet();
      $reportObj->buildForm();
    }
    catch (Exception $e) {
      // print_r($e->getCause()->getUserInfo());
      CRM_Utils_GlobalStack::singleton()->pop();
      throw $e;
    }
    CRM_Utils_GlobalStack::singleton()->pop();

    return $reportObj;
  }

  /**
   * @param $csvFile
   *
   * @return array
   */
  public function getArrayFromCsv($csvFile) {
    $arrFile = array();
    if (($handle = fopen($csvFile, "r")) !== FALSE) {
      while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $arrFile[] = $data;
      }
      fclose($handle);
    }
    return $arrFile;
  }

  /**
   * @param array $expectedCsvArray
   *   Two-dimensional array representing a CSV table.
   * @param array $actualCsvArray
   *   Two-dimensional array representing a CSV table.
   */
  public function assertCsvArraysEqual($expectedCsvArray, $actualCsvArray) {
    // TODO provide better debug output

    $flatData = "\n===== EXPECTED DATA ====\n"
      . $this->flattenCsvArray($expectedCsvArray)
      . "\n===== ACTUAL DATA ====\n"
      . $this->flattenCsvArray($actualCsvArray);

    $this->assertEquals(
      count($actualCsvArray),
      count($expectedCsvArray),
      'Arrays have different number of rows; in line ' . __LINE__ . '; data: ' . $flatData
    );

    foreach ($actualCsvArray as $intKey => $strVal) {
      $rowData = var_export(array(
        'expected' => $expectedCsvArray[$intKey],
        'actual' => $actualCsvArray[$intKey],
      ), TRUE);
      $this->assertNotNull($expectedCsvArray[$intKey]);
      $this->assertEquals(
        count($actualCsvArray[$intKey]),
        count($expectedCsvArray[$intKey]),
        'Arrays have different number of columns at row ' . $intKey . '; in line ' . __LINE__ . '; data: ' . $rowData
      );
      $this->assertEquals($expectedCsvArray[$intKey], $strVal);
    }
  }

  /**
   * @param $rows
   *
   * @return string
   */
  public function flattenCsvArray($rows) {
    $result = '';
    foreach ($rows as $row) {
      $result .= implode(',', $row) . "\n";
    }
    return $result;
  }

}
