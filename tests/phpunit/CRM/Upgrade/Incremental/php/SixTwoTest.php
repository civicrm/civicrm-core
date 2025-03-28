<?php

use Civi\Api4\Mapping;
use Civi\Api4\MappingField;
use Civi\Api4\UserJob;

/**
 * Class CRM_Upgrade_Incremental_php_SixOneTest
 * @group headless
 */
class CRM_Upgrade_Incremental_php_SixTwoTest extends CiviUnitTestCase {

  public function testUpdateUserJobs(): void {
    $mapping = Mapping::create(FALSE)
      ->setValues([
        'name' => 'Activity import',
        'mapping_type_id:name' => 'Import Activity',
      ])->execute()->single();
    $fields = [
      'activity_date_time',
      'do_not_import',
      'target_contact.email_primary.email',
      'source_contact.id',
    ];
    foreach ($fields as $index => $field) {
      MappingField::create()
        ->setValues(['name' => $field, 'mapping_id' => $mapping['id'], 'column_number' => $index])
        ->execute();
    }

    $userJobParameters = [
      'metadata' => [
        'DataSource' => ['table_name' => 'abc', 'column_headers' => ['Date Time', 'Something', 'Email']],
        'submitted_values' => [
          'dataSource' => 'CRM_Import_DataSource_SQL',
          'dateFormats' => CRM_Utils_Date::DATE_yyyy_mm_dd,
        ],
        'import_mappings' => [
          ['name' => 'activity_date_time'],
          ['name' => ''],
          ['name' => 'target_contact.email_primary.email'],
          ['name' => 'source_contact.id'],
        ],
      ],
      'status_id:name' => 'draft',
      'job_type' => 'activity_import',
    ];
    $userJobID = UserJob::create()->setValues($userJobParameters)->execute()->first()['id'];
    CRM_Upgrade_Incremental_php_SixTwo::upgradeImportMappingFields(NULL, 'Activity');

    $mappings = MappingField::get()
      ->addWhere('mapping_id', '=', $mapping['id'])
      ->execute();
    $this->assertEquals('Activity.activity_date_time', $mappings[0]['name']);
    $this->assertEquals('do_not_import', $mappings[1]['name']);
    $this->assertEquals('TargetContact.email_primary.email', $mappings[2]['name']);
    $this->assertEquals('SourceContact.id', $mappings[3]['name']);
    $job = UserJob::get(FALSE)->addWhere('id', '=', $userJobID)->execute()->single();
    $this->assertEquals([
      ['name' => 'Activity.activity_date_time'],
      ['name' => ''],
      ['name' => 'TargetContact.email_primary.email'],
      ['name' => 'SourceContact.id'],
    ], $job['metadata']['import_mappings']);

    $templateJob = UserJob::get(FALSE)
      ->addWhere('name', '=', 'import_Activity import')
      ->addWhere('is_template', '=', TRUE)->execute()->single();
    $this->assertEquals([
      ['name' => 'Activity.activity_date_time'],
      ['name' => ''],
      ['name' => 'TargetContact.email_primary.email'],
      ['name' => 'SourceContact.id'],
    ], $templateJob['metadata']['import_mappings']);

  }

}
