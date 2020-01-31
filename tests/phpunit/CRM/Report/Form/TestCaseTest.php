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

require_once 'CiviTest/CiviReportTestCase.php';

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
   * @return array
   */
  public function dataProvider() {
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
  public function badDataProvider() {
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
   * @dataProvider badDataProvider
   * @param $reportClass
   * @param $inputParams
   * @param $dataSet
   * @param $expectedOutputCsvFile
   * @throws \Exception
   */
  public function testBadReportOutput($reportClass, $inputParams, $dataSet, $expectedOutputCsvFile) {
    $config = CRM_Core_Config::singleton();
    CRM_Utils_File::sourceSQLFile($config->dsn, dirname(__FILE__) . "/{$dataSet}");

    $reportCsvFile = $this->getReportOutputAsCsv($reportClass, $inputParams);
    $reportCsvArray = $this->getArrayFromCsv($reportCsvFile);

    $expectedOutputCsvArray = $this->getArrayFromCsv(dirname(__FILE__) . "/{$expectedOutputCsvFile}");
    try {
      $this->assertCsvArraysEqual($expectedOutputCsvArray, $reportCsvArray);
    }
    catch (PHPUnit\Framework\AssertionFailedError $e) {
      /* OK */
    }
    catch (PHPUnit_Framework_AssertionFailedError $e) {
      /* OK */
    }
  }

  /**
   * Test processReportMode() Function in Reports
   */
  public function testOutputMode() {
    $clazz = new ReflectionClass('CRM_Report_Form');
    $reportForm = new CRM_Report_Form();

    $params = $clazz->getProperty('_params');
    $params->setAccessible(TRUE);
    $outputMode = $clazz->getProperty('_outputMode');
    $outputMode->setAccessible(TRUE);

    $params->setValue($reportForm, ['groups' => 4]);
    $reportForm->processReportMode();
    $this->assertEquals('group', $outputMode->getValue($reportForm));

    $params->setValue($reportForm, ['task' => 'copy']);
    $reportForm->processReportMode();
    $this->assertEquals('copy', $outputMode->getValue($reportForm));

    $params->setValue($reportForm, ['task' => 'print']);
    $reportForm->processReportMode();
    $this->assertEquals('print', $outputMode->getValue($reportForm));
  }

}
