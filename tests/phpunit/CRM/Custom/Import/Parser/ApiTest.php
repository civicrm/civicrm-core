<?php
/**
 * @file
 * File for the CRM_Contribute_Import_Parser_ContributionTest class.
 */

/**
 *  Test Contribution import parser.
 *
 * @package CiviCRM
 * @group headless
 */
class CRM_Custom_Import_Parser_ApiTest extends CiviUnitTestCase {

  use CRMTraits_Custom_CustomDataTrait;
  use CRMTraits_Import_ParserTrait;

  /**
   * Test the full form-flow import.
   */
  public function testImport(): void {
    $this->importCSV('contributions.csv', [
      ['name' => 'first_name'],
      ['name' => 'total_amount'],
      ['name' => 'receive_date'],
      ['name' => 'financial_type_id'],
      ['name' => 'email'],
    ]);
    $dataSource = new CRM_Import_DataSource_CSV($this->userJobID);
    $row = $dataSource->getRow();
    $this->assertEquals('ERROR', $row['_status']);
    $this->assertEquals('No matching Contact found for (mum@example.com )', $row['_status_message']);
  }

}
