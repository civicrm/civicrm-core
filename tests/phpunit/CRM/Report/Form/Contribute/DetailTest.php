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
 *  Test report outcome
 *
 * @package CiviCRM
 */
class CRM_Report_Form_Contribute_DetailTest extends CiviReportTestCase {
  protected $_tablesToTruncate = [
    'civicrm_contact',
    'civicrm_email',
    'civicrm_phone',
    'civicrm_address',
    'civicrm_contribution',
  ];

  /**
   * @return array
   */
  public function dataProvider() {
    return [
      [
        'CRM_Report_Form_Contribute_Detail',
        [
          'fields' => [
            'sort_name',
            'first_name',
            'email',
            'total_amount',
          ],
          'filters' => [
            'total_amount_op' => 'gte',
            'total_amount_value' => 50,
          ],
          // FIXME: add filters
        ],
        'fixtures/dataset-ascii.sql',
        'fixtures/report-ascii.csv',
      ],
    ];
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
    return [
      [
        'CRM_Report_Form_Contribute_Detail',
        [
          'fields' => [
            'sort_name',
            'first_name',
            'email',
            'total_amount',
            'address_postal_code',
          ],
          'filters' => [
            'address_postal_code_value' => 'B10 G56',
            'address_postal_code_op' => 'has',
          ],
        ],
        'fixtures/dataset-ascii.sql',
        'fixtures/DetailPostalCodeTest-ascii.csv',
      ],
    ];
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

    $solParams = [
      'first_name' => 'Solicitor 1',
      'last_name' => 'User ' . rand(),
      'contact_type' => 'Individual',
    ];
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
