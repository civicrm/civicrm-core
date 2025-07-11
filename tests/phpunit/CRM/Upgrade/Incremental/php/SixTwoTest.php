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
          // All 3 variants of activity_date_time should wind up as 'Activity.activity_date_time'
          ['name' => 'activity_date_time'],
          ['name' => 'Activity.Activity.activity_date_time'],
          ['name' => 'Activity.activity_date_time'],
          ['name' => 'target_contact.email_primary.email'],
          ['name' => 'source_contact.id'],
          // Another variant of one that got a bit messaged up.
          ['name' => 'Activity.TargetContact.id'],
        ],
      ],
      'status_id:name' => 'draft',
      'job_type' => 'activity_import',
    ];
    $userJobID = UserJob::create()->setValues($userJobParameters)->execute()->first()['id'];
    $userJobID2 = UserJob::create()->setValues($userJobParameters + ['is_template' => TRUE, 'name' => 'template_name'])->execute()->first()['id'];

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
      ['name' => 'Activity.activity_date_time'],
      ['name' => 'Activity.activity_date_time'],
      ['name' => 'TargetContact.email_primary.email'],
      ['name' => 'SourceContact.id'],
      ['name' => 'TargetContact.id'],
    ], $job['metadata']['import_mappings']);

    $templateJob = UserJob::get(FALSE)
      ->addWhere('id', 'IN', [$userJobID, $userJobID2])
      ->execute()->indexBy('id');
    $this->assertEquals([
      ['name' => 'Activity.activity_date_time'],
      ['name' => 'Activity.activity_date_time'],
      ['name' => 'Activity.activity_date_time'],
      ['name' => 'TargetContact.email_primary.email'],
      ['name' => 'SourceContact.id'],
      ['name' => 'TargetContact.id'],
    ], $templateJob[$userJobID]['metadata']['import_mappings']);

    $this->assertEquals([
      ['name' => 'Activity.activity_date_time'],
      ['name' => 'Activity.activity_date_time'],
      ['name' => 'Activity.activity_date_time'],
      ['name' => 'TargetContact.email_primary.email'],
      ['name' => 'SourceContact.id'],
      ['name' => 'TargetContact.id'],
    ], $templateJob[$userJobID2]['metadata']['import_mappings']);

    // Now check a re-run will not double-append prefixes
    CRM_Upgrade_Incremental_php_SixTwo::upgradeImportMappingFields(NULL, 'Activity');
    $templateJob = UserJob::get(FALSE)
      ->addWhere('id', 'IN', [$userJobID, $userJobID2])
      ->execute()->indexBy('id');
    $this->assertEquals([
      ['name' => 'Activity.activity_date_time'],
      ['name' => 'Activity.activity_date_time'],
      ['name' => 'Activity.activity_date_time'],
      ['name' => 'TargetContact.email_primary.email'],
      ['name' => 'SourceContact.id'],
      ['name' => 'TargetContact.id'],
    ], $templateJob[$userJobID]['metadata']['import_mappings']);

    $this->assertEquals([
      ['name' => 'Activity.activity_date_time'],
      ['name' => 'Activity.activity_date_time'],
      ['name' => 'Activity.activity_date_time'],
      ['name' => 'TargetContact.email_primary.email'],
      ['name' => 'SourceContact.id'],
      ['name' => 'TargetContact.id'],
    ], $templateJob[$userJobID2]['metadata']['import_mappings']);

  }

  /**
   * After some confusion the correct contact ID field for membership imports is Contact.id.
   *
   * https://github.com/civicrm/civicrm-core/pull/33110
   *
   * @return void
   */
  public function testUpdateMembershipUserJobs(): void {
    $mapping = Mapping::create(FALSE)
      ->setValues([
        'name' => 'Membership import',
        'mapping_type_id:name' => 'Import Membership',
      ])->execute()->single();
    $fields = [
      'contact_id',
      'Contact.id',
      'Membership.contact_id',
    ];

    foreach ($fields as $index => $field) {
      MappingField::create()
        ->setValues(['name' => $field, 'mapping_id' => $mapping['id'], 'column_number' => $index])
        ->execute();
    }

    CRM_Upgrade_Incremental_php_SixTwo::upgradeImportMappingFields(NULL, 'Membership');
    $mappings = MappingField::get()
      ->addWhere('mapping_id', '=', $mapping['id'])
      ->execute();
    $this->assertEquals('Contact.id', $mappings[0]['name']);
    $this->assertEquals('Contact.id', $mappings[1]['name']);
    $this->assertEquals('Contact.id', $mappings[2]['name']);

    $templateJob = UserJob::get(FALSE)
      ->addWhere('name', '=', 'import_Membership import')
      ->addWhere('is_template', '=', TRUE)->execute()->single();
    $this->assertEquals([['name' => 'Contact.id'], ['name' => 'Contact.id'], ['name' => 'Contact.id']], $templateJob['metadata']['import_mappings']);
  }

  /**
   * Checks the upgrade copes with contribution contact fields not correctly updated in 6.1.
   *
   * These fields are available in contribution import with civiimport but not other imports &
   * were missed out in 6.1
   *
   * @return void
   * @throws \CRM_Core_Exception
   */
  public function testUpdateContributionUserJobs(): void {
    $mapping = Mapping::create(FALSE)
      ->setValues([
        'name' => 'Contribution import',
        'mapping_type_id:name' => 'Import Contribution',
      ])->execute()->single();
    $fields = [
      'first_name',
      // this should still be handled.
      'contact.first_name',
      'last_name',
      'middle_name',
      'nick_name',
      'do_not_trade',
      'do_not_email',
      'do_not_mail',
      'do_not_sms',
      'do_not_phone',
      'is_opt_out',
      'external_identifier',
      'legal_identifier',
      'legal_name',
      'preferred_communication_method',
      'preferred_language',
      'gender_id',
      'prefix_id',
      'suffix_id',
      'job_title',
      'birth_date',
      'deceased_date',
      'household_name',
      NULL,
    ];
    $importMappings = [];
    $expectedImportMappings = [];
    foreach ($fields as $index => $field) {
      MappingField::create()
        ->setValues(['name' => $field, 'mapping_id' => $mapping['id'], 'column_number' => $index])
        ->execute();
      $importMappings[] = ['name' => $field];
      if ($field === 'contact.first_name') {
        $expectedImportMappings[] = ['name' => 'Contact.first_name'];
      }
      elseif ($field === NULL) {
        $expectedImportMappings[] = ['name' => ''];
      }
      else {
        $expectedImportMappings[] = ['name' => 'Contact.' . $field];
      }
    }

    $userJobParameters = [
      'metadata' => [
        'DataSource' => ['table_name' => 'abc', 'column_headers' => ['Date Time', 'Something', 'Email']],
        'submitted_values' => [
          'dataSource' => 'CRM_Import_DataSource_SQL',
          'dateFormats' => CRM_Utils_Date::DATE_yyyy_mm_dd,
        ],
        'import_mappings' => $importMappings,
      ],
      'status_id:name' => 'draft',
      'job_type' => 'contribution_import',
    ];
    $userJobID = UserJob::create()->setValues($userJobParameters)->execute()->first()['id'];
    CRM_Upgrade_Incremental_php_SixTwo::upgradeImportMappingFields(NULL, 'Contribution');

    $mappings = MappingField::get()
      ->addWhere('mapping_id', '=', $mapping['id'])
      ->execute();
    $this->assertEquals('Contact.first_name', $mappings[0]['name']);
    $this->assertEquals('Contact.first_name', $mappings[1]['name']);
    $job = UserJob::get(FALSE)->addWhere('id', '=', $userJobID)->execute()->single();
    $this->assertEquals($expectedImportMappings, $job['metadata']['import_mappings']);

    $templateJob = UserJob::get(FALSE)
      ->addWhere('name', '=', 'import_Contribution import')
      ->addWhere('is_template', '=', TRUE)->execute()->single();
    $this->assertEquals($expectedImportMappings, $templateJob['metadata']['import_mappings']);

  }

}
