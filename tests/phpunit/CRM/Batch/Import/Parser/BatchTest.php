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

use Civi\Api4\Batch;
use Civi\Api4\UserJob;
use Civi\Import\DataSource\Spreadsheet;

/**
 * Test importing to the Batch entity via the generic civiimport form flow.
 *
 * @group headless
 * @group import
 */
class CRM_Batch_Import_Parser_BatchTest extends CiviUnitTestCase {

  use CRMTraits_Import_ParserTrait {
    submitDataSourceForm as traitSubmitDataSourceForm;
  }

  public function setUp(): void {
    parent::setUp();
    $this->callApiV3Success('Extension', 'install', ['keys' => 'civiimport']);
  }

  public function tearDown(): void {
    $this->quickCleanup(['civicrm_batch']);
    parent::tearDown();
  }

  /**
   * Test the full form-flow import (DataSource -> MapField -> Preview).
   *
   * @throws \CRM_Core_Exception
   */
  public function testImport(): void {
    $this->importCSV('batches.csv', [
      ['name' => 'Batch.title'],
      ['name' => 'Batch.description'],
      ['name' => 'Batch.status_id'],
      ['name' => 'Batch.total'],
      ['name' => 'Batch.item_count'],
    ]);

    $dataSource = new CRM_Import_DataSource_CSV($this->userJobID);
    $row = $dataSource->getRow();
    $this->assertEquals('IMPORTED', $row['_status'], $row['_status_message']);
    $row = $dataSource->getRow();
    $this->assertEquals('IMPORTED', $row['_status'], $row['_status_message']);
    $row = $dataSource->getRow();
    $this->assertEquals('ERROR', $row['_status']);

    $batches = Batch::get(FALSE)
      ->addSelect('title', 'description', 'status_id:name', 'total', 'item_count')
      ->addOrderBy('id')
      ->execute();
    $this->assertCount(2, $batches);
    $this->assertEquals('Batch One', $batches[0]['title']);
    $this->assertEquals('First test batch', $batches[0]['description']);
    $this->assertEquals('Open', $batches[0]['status_id:name']);
    $this->assertEquals('100.00', $batches[0]['total']);
    $this->assertEquals(10, $batches[0]['item_count']);
    $this->assertEquals('Batch Two', $batches[1]['title']);
    $this->assertEquals('Closed', $batches[1]['status_id:name']);
  }

  /**
   * Test the full form-flow import from an xlsx spreadsheet.
   *
   * @throws \CRM_Core_Exception
   */
  public function testImportXlsx(): void {
    $this->importCSV('batches.xlsx', [
      ['name' => 'Batch.title'],
      ['name' => 'Batch.description'],
      ['name' => 'Batch.status_id'],
      ['name' => 'Batch.total'],
      ['name' => 'Batch.item_count'],
    ], [
      'dataSource' => Spreadsheet::class,
      'isFirstRowHeader' => TRUE,
    ]);

    $dataSource = new Spreadsheet($this->userJobID);
    $row = $dataSource->getRow();
    $this->assertEquals('IMPORTED', $row['_status'], $row['_status_message']);
    $row = $dataSource->getRow();
    $this->assertEquals('IMPORTED', $row['_status'], $row['_status_message']);
    $row = $dataSource->getRow();
    $this->assertEquals('ERROR', $row['_status']);

    $batches = Batch::get(FALSE)
      ->addSelect('title', 'description', 'status_id:name', 'total', 'item_count')
      ->addOrderBy('id')
      ->execute();
    $this->assertCount(2, $batches);
    $this->assertEquals('Batch One', $batches[0]['title']);
    $this->assertEquals('First test batch', $batches[0]['description']);
    $this->assertEquals('Open', $batches[0]['status_id:name']);
    $this->assertEquals('100.00', $batches[0]['total']);
    $this->assertEquals(10, $batches[0]['item_count']);
    $this->assertEquals('Batch Two', $batches[1]['title']);
    $this->assertEquals('Closed', $batches[1]['status_id:name']);
  }

  /**
   * Test importing from the multi-block "Batch Summary Report" xlsx shape,
   * via a purpose-built test datasource that skips the report's title,
   * group/timestamp lines and subtotal rows, mapping just the main fields.
   *
   * @throws \CRM_Core_Exception
   */
  public function testImportBatchSummaryReport(): void {
    $file = $this->createBatchSummaryReportFixture();
    $this->importCSV(basename($file), [
      ['name' => ''],
      // Group Value is always blank in the real report - repurposed here to
      // carry a status label, since Batch.status_id is required and the
      // report has no natural status column of its own.
      ['name' => 'Batch.status_id'],
      ['name' => 'Batch.title'],
      ['name' => ''],
      ['name' => ''],
      ['name' => ''],
      ['name' => 'Batch.item_count'],
      ['name' => 'Batch.total'],
    ], [
      'dataSource' => \Civi\Import\DataSource\BatchSummaryReport::class,
      'uploadFile' => ['name' => $file],
    ]);

    $dataSource = new \Civi\Import\DataSource\BatchSummaryReport($this->userJobID);
    $this->assertEquals(
      ['Batch Type', 'Group Value', 'Batch', 'Batch Date', 'Docs', 'Checks', 'Total Item', 'Amount'],
      $dataSource->getColumnHeaders()
    );
    for ($i = 0; $i < 3; $i++) {
      $row = $dataSource->getRow();
      $this->assertEquals('IMPORTED', $row['_status'], $row['_status_message']);
    }

    $batches = Batch::get(FALSE)
      ->addSelect('title', 'total', 'item_count')
      ->addOrderBy('id')
      ->execute();
    $this->assertCount(3, $batches);
    $this->assertEquals('100093', $batches[0]['title']);
    $this->assertEquals('5400.00', $batches[0]['total']);
    $this->assertEquals(8, $batches[0]['item_count']);
    $this->assertEquals('100094', $batches[1]['title']);
    $this->assertEquals('215.00', $batches[1]['total']);
    $this->assertEquals(4, $batches[1]['item_count']);
    $this->assertEquals('100095', $batches[2]['title']);
    $this->assertEquals('50.00', $batches[2]['total']);
    $this->assertEquals(2, $batches[2]['item_count']);

    unlink($file);
  }

  /**
   * Build the anonymized multi-block report as a real xlsx file.
   *
   * The fixture is built at runtime, using the same PhpSpreadsheet library
   * the datasource itself reads with, rather than committing a binary file.
   *
   * @return string
   *   Path to the generated file.
   */
  private function createBatchSummaryReportFixture(): string {
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $header = ['Batch Type', 'Group Value', 'Batch', 'Batch Date', 'Docs', 'Checks', 'Total Item', 'Amount'];
    $rows = array_merge([
      ['Batch Summary Report'],
      ['- All'],
      ['08/01/2026 - 08/31/2026'],
      [],
    ], $this->getBatchSummaryReportBlock('8/7/2026 1:35:58 AM', $header, ['Credit Cards', 'Open', '100093', '08/08/2026', '8', '0', '8', '5400.00']),
      $this->getBatchSummaryReportBlock('8/14/2026 1:03:54 PM', $header, ['Credit Cards', 'Open', '100094', '08/14/2026', '4', '0', '4', '215.00']),
      $this->getBatchSummaryReportBlock('8/20/2026 2:40:22 AM', $header, ['Credit Cards', 'Open', '100095', '08/21/2026', '2', '0', '2', '50.00'])
    );
    $sheet->fromArray($rows);
    $path = tempnam(sys_get_temp_dir(), 'batch_summary_report') . '.xlsx';
    (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($path);
    return $path;
  }

  /**
   * Build one "CREDIT CARDS" block of the report: group label,
   * timestamp, the (repeated) column header row, the data row, a "Total"
   * subtotal row (summing Docs/Checks/Total Item/Amount), and two blank
   * separator rows - matching the real report's shape.
   *
   * @param string $timestamp
   * @param array $header
   * @param array $dataRow
   *
   * @return array
   */
  private function getBatchSummaryReportBlock(string $timestamp, array $header, array $dataRow): array {
    return [
      ['CREDIT CARDS'],
      [$timestamp],
      $header,
      $dataRow,
      ['', '', 'Total', '', $dataRow[4], $dataRow[5], $dataRow[6], $dataRow[7]],
      [],
      [],
    ];
  }

  /**
   * Get the import's datasource form.
   *
   * @param array $submittedValues
   *
   * @return \Civi\Test\FormWrapper
   */
  protected function getDataSourceForm(array $submittedValues): \Civi\Test\FormWrapper {
    return $this->getTestForm('CRM_CiviImport_Form_Generic_DataSource', $submittedValues);
  }

  /**
   * Submit the data source form and record the base entity being imported.
   *
   * The generic DataSource form derives the base entity from the current
   * url path (e.g civicrm/import/batch) when no UserJob yet exists to read
   * it from - which isn't available outside a real page request - so it is
   * set directly on the UserJob metadata here instead, once the job has
   * been created.
   *
   * @param string $csv
   * @param array $submittedValues
   */
  protected function submitDataSourceForm(string $csv, array $submittedValues = []): void {
    $this->traitSubmitDataSourceForm($csv, $submittedValues);
    $metadata = UserJob::get(FALSE)
      ->addWhere('id', '=', $this->userJobID)
      ->execute()->single()['metadata'];
    $metadata['base_entity'] = 'Batch';
    UserJob::update(FALSE)
      ->addWhere('id', '=', $this->userJobID)
      ->setValues(['metadata' => $metadata])
      ->execute();
  }

  /**
   * Get the import's mapField form.
   *
   * @param array $submittedValues
   *
   * @return \Civi\Test\FormWrapper
   */
  protected function getMapFieldForm(array $submittedValues): \Civi\Test\FormWrapper {
    return $this->getTestForm('CRM_CiviImport_Form_Generic_MapField', $submittedValues, ['id' => $this->userJobID]);
  }

  /**
   * Get the import's preview form.
   *
   * @param array $submittedValues
   *
   * @return \Civi\Test\FormWrapper
   */
  protected function getPreviewForm(array $submittedValues): \Civi\Test\FormWrapper {
    return $this->getTestForm('CRM_CiviImport_Form_Generic_Preview', $submittedValues, ['id' => $this->userJobID]);
  }

}
