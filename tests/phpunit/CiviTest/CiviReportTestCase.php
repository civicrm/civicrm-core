<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

require_once 'CiviTest/CiviUnitTestCase.php';

class CiviReportTestCase extends CiviUnitTestCase {
  function setUp() {
    parent::setUp();
  }

  function getReportOutputAsCsv($reportClass, $inputParams) {
    $config = CRM_Core_Config::singleton();
    $config->keyDisable = TRUE;
    $controller = new CRM_Core_Controller_Simple($reportClass, ts('some title'));
    $reportObj =& $controller->_pages['Detail'];//FIXME - Detail is going to change
    $_REQUEST['force'] = 1;
    if (!empty($inputParams['fields'])) {
      $fields = implode(',', $inputParams['fields']);
      $_GET['fld']  = $fields;
      $_GET['ufld'] = 1;
    }

    $reportObj->storeResultSet();
    $reportObj->buildForm();
    $rows = $reportObj->getResultSet();

    $tmpFile = $this->createTempDir() . CRM_Utils_File::makeFileName('CiviReport.csv');
    $csvContent = CRM_Report_Utils_Report::makeCsv($reportObj, $rows);
    file_put_contents($tmpFile, $csvContent);

    return $tmpFile;
  }

  function getArrayFromCsv($csvFile) {
    $arrFile = array();
    if (($handle = fopen($csvFile, "r")) !== FALSE) {
      while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $arrFile[] = $data;
      }
      fclose($handle);
    }
    return $arrFile;
  }

  function compareCsvFiles($csvFile1, $csvFile2) {
    $arrFile1 = $this->getArrayFromCsv($csvFile1);
    $arrFile2 = $this->getArrayFromCsv($csvFile2);

    $intRow = 0;
    foreach($arrFile1 as $intKey => $strVal) {
      if (count($strVal) != count($arrFile2[$intKey])) {
        //FIXME : exit("Column count doesn't match\n");
      }
      if (!isset($arrFile2[$intKey]) || ($arrFile2[$intKey] != $strVal)) {
        //FIXME: exit("Column $intKey, row $intRow of $strFile1 doesn't match\n");
      }
      $intRow++;
    }
    // FIXME: print "All rows match fine.\n";
  }
}
