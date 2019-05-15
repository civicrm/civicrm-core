<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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

  /**
   * Test that the pagination widget is present.
   *
   * @dataProvider dataProvider
   * @param $reportClass
   * @param $inputParams
   * @throws \Exception
   */
  public function testPager($reportClass, $inputParams) {
    $contactID = $this->individualCreate();
    for ($i = 1; $i <= 51; $i++) {
      $this->contributionCreate(['contact_id' => $contactID, 'total_amount' => 50 + $i]);
    }
    $reportObj = $this->getReportObject($reportClass, $inputParams);
    $pager = $reportObj->getTemplate()->_tpl_vars['pager'];
    $this->assertEquals($pager->_response['numPages'], 2, "Pages in Pager");
  }

  /**
   * @return array
   */
  public function postalCodeDataProvider() {
    return array(
      array(
        'CRM_Report_Form_Contribute_Detail',
        array(
          'fields' => array(
            'sort_name',
            'first_name',
            'email',
            'total_amount',
            'postal_code',
          ),
          'filters' => array(
            'postal_code_value' => 'B10 G56',
            'postal_code_op' => 'has',
          ),
        ),
        'fixtures/dataset-ascii.sql',
        'fixtures/DetailPostalCodeTest-ascii.csv',
      ),
    );
  }

  /**
   * @dataProvider postalCodeDataProvider
   * @param $reportClass
   * @param $inputParams
   * @param $dataSet
   * @param $expectedOutputCsvFile
   * @throws \Exception
   */
  public function testPostalCodeSearchReportOutput($reportClass, $inputParams, $dataSet, $expectedOutputCsvFile) {
    $config = CRM_Core_Config::singleton();
    CRM_Utils_File::sourceSQLFile($config->dsn, dirname(__FILE__) . "/{$dataSet}");

    $reportCsvFile = $this->getReportOutputAsCsv($reportClass, $inputParams);
    $reportCsvArray = $this->getArrayFromCsv($reportCsvFile);

    $expectedOutputCsvArray = $this->getArrayFromCsv(dirname(__FILE__) . "/{$expectedOutputCsvFile}");
    $this->assertCsvArraysEqual($expectedOutputCsvArray, $reportCsvArray);
  }

  /**
   * Make sure the total amount of a contribution doesn't multiply by the number
   * of soft credits.
   */
  public function testMultipleSoftCredits() {
    $this->quickCleanup($this->_tablesToTruncate);

    $solParams = array(
      'first_name' => 'Solicitor 1',
      'last_name' => 'User ' . rand(),
      'contact_type' => 'Individual',
    );
    $solicitor1Id = $this->individualCreate($solParams);
    $solParams['first_name'] = 'Solicitor 2';
    $solicitor2Id = $this->individualCreate($solParams);
    $solParams['first_name'] = 'Donor';
    $donorId = $this->individualCreate($solParams);

    $contribParams = [
      'total_amount' => 150,
      'contact_id' => $donorId,
      // TODO: We're getting a "DB Error: already exists" when inserting a line
      // item, but that's beside the point for this test, so skipping.
      'skipLineItem' => 1,
    ];
    $contribId = $this->contributionCreate($contribParams);

    // Add two soft credits on the same contribution.
    $softParams = [
      'contribution_id' => $contribId,
      'amount' => 150,
      'contact_id' => $solicitor1Id,
      'soft_credit_type_id' => 1,
    ];
    $this->callAPISuccess('ContributionSoft', 'create', $softParams);
    $softParams['contact_id'] = $solicitor2Id;
    $softParams['amount'] = 100;
    $this->callAPISuccess('ContributionSoft', 'create', $softParams);

    $input = [
      'filters' => [
        'contribution_or_soft_op' => 'eq',
        'contribution_or_soft_value' => 'contributions_only',
      ],
      'fields' => [
        'sort_name',
        'email',
        'phone',
        'financial_type_id',
        'receive_date',
        'total_amount',
        'soft_credits',
      ],
    ];
    $obj = $this->getReportObject('CRM_Report_Form_Contribute_Detail', $input);
    $rows = $obj->getResultSet();
    $this->assertEquals(1, count($rows));
    $this->assertEquals('$ 150.00', $rows[0]['civicrm_contribution_total_amount']);
  }

}
