<?php

use Civi\Api4\UserJob;

/**
 * Class CRM_Upgrade_Incremental_php_SixOneTest
 * @group headless
 */
class CRM_Upgrade_Incremental_php_SixOneTest extends CiviUnitTestCase {

  public function testUpdateUserJobs(): void {
    $userJobParameters = [
      'metadata' => [
        'DataSource' => ['table_name' => 'abc', 'column_headers' => ['External Identifier', 'Amount Given', 'Contribution Date', 'Financial Type', 'In honor']],
        'submitted_values' => [
          'contactType' => 'Individual',
          'contactSubType' => '',
          'dataSource' => 'CRM_Import_DataSource_SQL',
          'onDuplicate' => CRM_Import_Parser::DUPLICATE_SKIP,
          'dedupe_rule_id' => NULL,
          'dateFormats' => CRM_Utils_Date::DATE_yyyy_mm_dd,
        ],
        'import_mappings' => [
          ['name' => 'external_identifier'],
          ['name' => 'total_amount'],
          ['name' => 'receive_date'],
          ['name' => 'financial_type_id'],
          ['name' => 'contribution_source'],
          ['name' => 'soft_credit.contact.id'],
        ],
      ],
      'status_id:name' => 'draft',
      'job_type' => 'contribution_import',
    ];
    $userJobID = UserJob::create()->setValues($userJobParameters)->execute()->first()['id'];
    $userJobID2 = UserJob::create()->setValues($userJobParameters)->execute()->first()['id'];
    CRM_Upgrade_Incremental_php_SixOne::upgradeImportMappingFields(NULL, 'Import Contribution');

    $job = UserJob::get(FALSE)->addWhere('id', 'IN', [$userJobID, $userJobID2])->execute()->indexBy('id');
    $this->assertEquals([
      ['name' => 'Contact.external_identifier'],
      ['name' => 'Contribution.total_amount'],
      ['name' => 'Contribution.receive_date'],
      ['name' => 'Contribution.financial_type_id'],
      ['name' => 'Contribution.source'],
      ['name' => 'SoftCreditContact.id'],
    ], $job[$userJobID]['metadata']['import_mappings']);
    $this->assertEquals([
      ['name' => 'Contact.external_identifier'],
      ['name' => 'Contribution.total_amount'],
      ['name' => 'Contribution.receive_date'],
      ['name' => 'Contribution.financial_type_id'],
      ['name' => 'Contribution.source'],
      ['name' => 'SoftCreditContact.id'],
    ], $job[$userJobID2]['metadata']['import_mappings']);

    // Run a SixTwo script to check for no double updates.
    CRM_Upgrade_Incremental_php_SixTwo::upgradeImportMappingFields(NULL, 'Import Contribution');
    $job = UserJob::get(FALSE)->addWhere('id', 'IN', [$userJobID, $userJobID2])->execute()->indexBy('id');
    $this->assertEquals([
      ['name' => 'Contact.external_identifier'],
      ['name' => 'Contribution.total_amount'],
      ['name' => 'Contribution.receive_date'],
      ['name' => 'Contribution.financial_type_id'],
      ['name' => 'Contribution.source'],
      ['name' => 'SoftCreditContact.id'],
    ], $job[$userJobID]['metadata']['import_mappings']);
    $this->assertEquals([
      ['name' => 'Contact.external_identifier'],
      ['name' => 'Contribution.total_amount'],
      ['name' => 'Contribution.receive_date'],
      ['name' => 'Contribution.financial_type_id'],
      ['name' => 'Contribution.source'],
      ['name' => 'SoftCreditContact.id'],
    ], $job[$userJobID2]['metadata']['import_mappings']);
  }

}
