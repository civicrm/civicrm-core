<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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

require_once 'CiviTest/CiviReportTestCase.php';

/**
 *  Test report outcome
 *
 * @package CiviCRM
 */
class CRM_Report_Form_Contribute_DetailTest extends CiviReportTestCase {
  protected $_tablesToTruncate = array(
    'civicrm_contact',
    'civicrm_email',
    'civicrm_phone',
    'civicrm_address',
    'civicrm_contribution',
  );

  /**
   * @return array
   */
  public function dataProvider() {
    return array(
      array(
        'CRM_Report_Form_Contribute_Detail',
        array(
          'fields' => array(
            'sort_name',
            'first_name',
            'email',
            'total_amount',
          ),
          'filters' => array(
            'total_amount_op' => 'gte',
            'total_amount_value' => 50,
          ),
          // FIXME: add filters
        ),
        'fixtures/dataset-ascii.sql',
        'fixtures/report-ascii.csv',
      ),
    );
  }

  public function setUp() {
    parent::setUp();
    $this->quickCleanup($this->_tablesToTruncate);
  }

  public function tearDown() {
    parent::tearDown();
    CRM_Core_DAO::executeQuery('DROP TEMPORARY TABLE IF EXISTS civireport_contribution_detail_temp1');
    CRM_Core_DAO::executeQuery('DROP TEMPORARY TABLE IF EXISTS civireport_contribution_detail_temp2');
    CRM_Core_DAO::executeQuery('DROP TEMPORARY TABLE IF EXISTS civireport_contribution_detail_temp3');
  }

  /**
   * @dataProvider dataProvider
   * @param $reportClass
   * @param $inputParams
   * @param $dataSet
   * @param $expectedOutputCsvFile
   * @throws \Exception
   */
  public function testReportOutput($reportClass, $inputParams, $dataSet, $expectedOutputCsvFile) {
    $config = CRM_Core_Config::singleton();
    CRM_Utils_File::sourceSQLFile($config->dsn, dirname(__FILE__) . "/{$dataSet}");

    $reportCsvFile = $this->getReportOutputAsCsv($reportClass, $inputParams);
    $reportCsvArray = $this->getArrayFromCsv($reportCsvFile);

    $expectedOutputCsvArray = $this->getArrayFromCsv(dirname(__FILE__) . "/{$expectedOutputCsvFile}");
    $this->assertCsvArraysEqual($expectedOutputCsvArray, $reportCsvArray);
  }

}
