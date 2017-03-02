<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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

/**
 *  Test report outcome
 *
 * @package CiviCRM
 */
class CRM_Report_Form_Contribute_RepeatACLTest extends CiviReportTestCase {
  protected $_tablesToTruncate = array(
    'civicrm_contact',
    'civicrm_contribution',
    'civicrm_line_item',
  );

  public function setUp() {
    parent::setUp();
    $this->quickCleanup($this->_tablesToTruncate);
  }

  public function tearDown() {
    parent::tearDown();
  }

  /**
   * @return array
   */
  public function contributionDataProvider() {
    return array(
      array(
        'CRM_Report_Form_Contribute_Repeat',
        array(
          'fields' => array(
            'sort_name',
          ),
          'filters' => array(
            'receive_date1_relative' => '0',
            'receive_date2_relative' => '0',
            'receive_date1_from' => '20100101',
            'receive_date1_to' => '20101231',
            'receive_date2_from' => '20110101',
            'receive_date2_to' => '20111231',
          ),
        ),
        'fixtures/dataset-acl-ascii.sql',
        'fixtures/RepeatContributionNoACLTest-ascii.csv',
      ),
    );
  }

  /**
   * @dataProvider contributionDataProvider
   * @param $reportClass
   * @param $inputParams
   * @param $dataSet
   * @param $expectedOutputCsvFile
   * @throws \Exception
   */
  public function testContributionReportOutput($reportClass, $inputParams, $dataSet, $expectedOutputCsvFile) {
    $config = CRM_Core_Config::singleton();
    CRM_Utils_File::sourceSQLFile($config->dsn, dirname(__FILE__) . "/{$dataSet}");

    $reportCsvFile = $this->getReportOutputAsCsv($reportClass, $inputParams);
    $reportCsvArray = $this->getArrayFromCsv($reportCsvFile);

    $expectedOutputCsvArray = $this->getArrayFromCsv(dirname(__FILE__) . "/{$expectedOutputCsvFile}");
    $this->assertCsvArraysEqual($expectedOutputCsvArray, $reportCsvArray);
  }

  /**
   * @return array
   */
  public function contributionACLDataProvider() {
    return array(
      array(
        'CRM_Report_Form_Contribute_Repeat',
        array(
          'fields' => array(
            'sort_name',
          ),
          'filters' => array(
            'receive_date1_relative' => '0',
            'receive_date2_relative' => '0',
            'receive_date1_from' => '20100101',
            'receive_date1_to' => '20101231',
            'receive_date2_from' => '20110101',
            'receive_date2_to' => '20111231',
          ),
        ),
        'fixtures/dataset-acl-ascii.sql',
        'fixtures/RepeatContributionACLTest-ascii.csv',
      ),
    );
  }

  /**
   * @dataProvider contributionACLDataProvider
   * @param $reportClass
   * @param $inputParams
   * @param $dataSet
   * @param $expectedOutputCsvFile
   * @throws \Exception
   */
  public function testContributionACLReportOutput($reportClass, $inputParams, $dataSet, $expectedOutputCsvFile) {
    $this->setACL();
    $this->setPermissions(array(
      'view contributions of type Donation',
      'access CiviReport',
      'access Report Criteria',
      'administer Reports',
      'view all contacts',
    ));
    $config = CRM_Core_Config::singleton();
    CRM_Utils_File::sourceSQLFile($config->dsn, dirname(__FILE__) . "/{$dataSet}");

    $reportCsvFile = $this->getReportOutputAsCsv($reportClass, $inputParams);
    $reportCsvArray = $this->getArrayFromCsv($reportCsvFile);

    $expectedOutputCsvArray = $this->getArrayFromCsv(dirname(__FILE__) . "/{$expectedOutputCsvFile}");
    $this->assertCsvArraysEqual($expectedOutputCsvArray, $reportCsvArray);
    $this->setACL(FALSE);
  }

}
