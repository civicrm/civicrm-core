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

use Civi\Test\Invasive;

/**
 * Verify that the CiviReportTestCase provides a working set of
 * primitives for tests. Do this by running various scenarios
 * that should yield positive and negative results.
 *
 * Note: We need some report class to use as an example.
 * CRM_Report_Form_Contribute_DetailTest is chosen arbitrarily.
 *
 * @package CiviCRM
 */
class CRM_Report_Form_TestCaseTest extends CiviReportTestCase {
  protected $_tablesToTruncate = [
    'civicrm_contact',
    'civicrm_email',
    'civicrm_phone',
    'civicrm_address',
    'civicrm_contribution',
  ];

  /**
   * Financial data used in these tests is invalid - do not validate.
   *
   * Note ideally it would be fixed and we would always use valid data.
   *
   * @var bool
   */
  protected $isValidateFinancialsOnPostAssert = FALSE;

  /**
   * @return array
   */
  public function dataProvider(): array {
    $testCaseA = [
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
      'Contribute/fixtures/dataset-ascii.sql',
      'Contribute/fixtures/report-ascii.csv',
    ];

    return [
      $testCaseA,
      $testCaseA,
      $testCaseA,
      // We repeat the test a second time to
      // ensure that CiviReportTestCase can
      // clean up sufficiently to run
      // multiple tests.
    ];
  }

  /**
   * @return array
   */
  public function badDataProvider(): array {
    return [
      // This test-case is bad because the dataset-ascii.sql does not match the
      // report.csv (due to differences in international chars)
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
        'Contribute/fixtures/dataset-ascii.sql',
        'Contribute/fixtures/report.csv',
      ],
      // This test-case is bad because the filters check for
      // an amount >= $100, but the test data includes records
      // for $50.
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
            'total_amount_value' => 100,
          ],
          // FIXME: add filters
        ],
        'Contribute/fixtures/dataset-ascii.sql',
        'Contribute/fixtures/report.csv',
      ],
    ];
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public function setUp(): void {
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
  public function testReportOutput($reportClass, $inputParams, $dataSet, $expectedOutputCsvFile): void {
    $config = CRM_Core_Config::singleton();
    CRM_Utils_File::sourceSQLFile($config->dsn, __DIR__ . "/$dataSet");

    $reportCsvFile = $this->getReportOutputAsCsv($reportClass, $inputParams);
    $reportCsvArray = $this->getArrayFromCsv($reportCsvFile);

    $expectedOutputCsvArray = $this->getArrayFromCsv(__DIR__ . "/$expectedOutputCsvFile");
    $this->assertCsvArraysEqual($expectedOutputCsvArray, $reportCsvArray);
  }

  /**
   * @dataProvider badDataProvider
   *
   * @param $reportClass
   * @param $inputParams
   * @param $dataSet
   * @param $expectedOutputCsvFile
   *
   * @throws \CRM_Core_Exception
   */
  public function testBadReportOutput($reportClass, $inputParams, $dataSet, $expectedOutputCsvFile): void {
    CRM_Utils_File::sourceSQLFile(CIVICRM_DSN, __DIR__ . "/$dataSet");

    $reportCsvFile = $this->getReportOutputAsCsv($reportClass, $inputParams);
    $reportCsvArray = $this->getArrayFromCsv($reportCsvFile);

    $expectedOutputCsvArray = $this->getArrayFromCsv(__DIR__ . "/$expectedOutputCsvFile");
    $this->assertNotEquals($expectedOutputCsvArray[1], $reportCsvArray[1]);
  }

  /**
   * Test processReportMode() Function in Reports
   */
  public function testOutputMode(): void {
    $reportForm = new CRM_Report_Form();

    Invasive::set([$reportForm, '_params'], ['groups' => 4]);
    $reportForm->processReportMode();
    $this->assertEquals('group', Invasive::get([$reportForm, '_outputMode']));

    Invasive::set([$reportForm, '_params'], ['task' => 'copy']);
    $reportForm->processReportMode();
    $this->assertEquals('copy', Invasive::get([$reportForm, '_outputMode']));

    Invasive::set([$reportForm, '_params'], ['task' => 'print']);
    $reportForm->processReportMode();
    $this->assertEquals('print', Invasive::get([$reportForm, '_outputMode']));
  }

}
