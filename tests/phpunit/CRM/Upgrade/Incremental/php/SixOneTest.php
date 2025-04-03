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
        ],
      ],
      'status_id:name' => 'draft',
      'job_type' => 'contribution_import',
    ];
    $userJobID = UserJob::create()->setValues($userJobParameters)->execute()->first()['id'];
    CRM_Upgrade_Incremental_php_SixOne::upgradeImportMappingFields(NULL, 'Import Contribution');
    $job = UserJob::get(FALSE)->addWhere('id', '=', $userJobID)->execute()->single();
    $this->assertEquals([
      ['name' => 'contact.external_identifier'],
      ['name' => 'total_amount'],
      ['name' => 'receive_date'],
      ['name' => 'financial_type_id'],
      ['name' => 'source'],
    ], $job['metadata']['import_mappings']);
  }

}
