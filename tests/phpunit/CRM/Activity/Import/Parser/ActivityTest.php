<?php

/**
 *   This file is part of CiviCRM
 *
 *   CiviCRM is free software; you can redistribute it and/or
 *   modify it under the terms of the GNU Affero General Public License
 *   as published by the Free Software Foundation; either version 3 of
 *   the License, or (at your option) any later version.
 *
 *   CiviCRM is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU Affero General Public License for more details.
 *
 *   You should have received a copy of the GNU Affero General Public
 *   License along with this program.  If not, see
 *   <http://www.gnu.org/licenses/>.
 */

use Civi\Api4\UserJob;

/**
 *  Test Activity Import Parser functions
 *
 * @package   CiviCRM
 * @group headless
 * @group import
 */
class CRM_Activity_Import_Parser_ActivityTest extends CiviUnitTestCase {
  use CRMTraits_Custom_CustomDataTrait;
  use CRMTraits_Import_ParserTrait;

  /**
   * Prepare for tests.
   */
  public function setUp():void {
    parent::setUp();
    $this->createLoggedInUser();
  }

  /**
   * Clean up after test.
   */
  public function tearDown():void {
    $this->quickCleanup(['civicrm_contact', 'civicrm_email', 'civicrm_activity', 'civicrm_activity_contact', 'civicrm_user_job', 'civicrm_queue', 'civicrm_queue_item'], TRUE);
    parent::tearDown();
  }

  /**
   *  Test Import.
   *
   * So far this is just testing the class constructor & preparing for more
   * tests.
   */
  public function testImport(): void {
    $this->createCustomGroupWithFieldOfType(['extends' => 'Activity'], 'checkbox');
    $values = [
      'activity_details' => 'fascinating',
      'activity_type_id' => 1,
      'activity_date_time' => '2010-01-06',
      'target_contact_id' => $this->individualCreate(),
      'activity_subject' => 'riveting stuff',
      $this->getCustomFieldName('checkbox') => 'L',
    ];
    $this->importValues($values);
    $this->callAPISuccessGetSingle('Activity', [$this->getCustomFieldName('checkbox') => 'L']);
  }

  /**
   * Create an import object.
   *
   * @param array $fields
   *
   * @return \CRM_Activity_Import_Parser_Activity
   */
  protected function createImportObject(array $fields): \CRM_Activity_Import_Parser_Activity {
    $mapper = [];
    foreach ($fields as $field) {
      $mapper[] = [$field];
    }
    $importer = new CRM_Activity_Import_Parser_Activity();
    $this->userJobID = $this->getUserJobID(['mapper' => $mapper]);
    $importer->setUserJobID($this->userJobID);
    $importer->init();
    return $importer;
  }

  /**
   * Run the importer.
   *
   * @param array $values
   * @param int $expectedOutcome
   *
   * @return string The error message
   */
  protected function importValues(array $values, int $expectedOutcome = 1): string {
    $importer = $this->createImportObject(array_keys($values));
    try {
      $importer->validateValues(array_values($values));
    }
    catch (CRM_Core_Exception $e) {
      if ($expectedOutcome === 4) {
        return $e->getMessage();
      }
      throw $e;
    }
    // Stand in for rowNumber.
    $values[] = 1;
    $params = array_values($values);
    $importer->import($params);
    $dataSource = new CRM_Import_DataSource_CSV($this->userJobID);

    $row = $dataSource->getRow();
    if ($expectedOutcome === 1) {
      $this->assertEquals('IMPORTED', $row['_status']);
      return CRM_Import_Parser::VALID;
    }
    if ($expectedOutcome === 4) {
      $this->assertEquals('ERROR', $row['_status']);
      return $row['_status_message'];
    }
  }

  /**
   * Test validation of various fields.
   *
   * @dataProvider activityImportValidationProvider
   * @param array $input
   * @param string $expectedError
   */
  public function testActivityImportValidation(array $input, string $expectedError): void {
    // Supplement some values that can't be done in a data provider because of timing.
    if (!isset($input['target_contact_id'])) {
      $input['target_contact_id'] = $this->individualCreate();
    }
    if (isset($input['replace_me_custom_field'])) {
      $this->createCustomGroupWithFieldOfType(['extends' => 'Activity'], 'radio');
      $input[$this->getCustomFieldName('radio')] = $input['replace_me_custom_field'];
      unset($input['replace_me_custom_field']);
    }

    // There's both an outcome int, like VALID or ERROR, which importValues
    // checks, and then an error string which we check. If we're not expecting
    // an error string, then tell importValues the expected outcome is VALID.
    $actualError = $this->importValues($input,
      empty($expectedError) ? CRM_Import_Parser::VALID : CRM_Import_Parser::ERROR);
    $this->assertStringContainsString($expectedError, $actualError);
  }

  /**
   * Dataprovider for some import tests.
   * @return array
   */
  public function activityImportValidationProvider(): array {
    /**
     * Because this is a dataprovider that runs before setup, we
     * can't specify values that don't exist yet, but we're mostly
     * testing validation of just the specified ones anyway. The test itself
     * will need to fill in the rest.
     */

    // This needs to be a constant for the reasons above, but this
    // might make it easier to update in future if needed.
    $some_date = '2021-02-06';

    return [
      // explicit index number so easier to find when it fails
      0 => [
        'input' => [
          'activity_type_id' => 1,
          'activity_date_time' => $some_date,
          'activity_subject' => 'asubj',
        ],
        'expected_error' => '',
      ],

      1 => [
        'input' => [
          'activity_type_id' => 1,
          'activity_date_time' => $some_date,
          'activity_subject' => 'asubj',
          'replace_me_custom_field' => '3',
        ],
        'expected_error' => '',
      ],

      2 => [
        'input' => [
          'activity_type_id' => 'Meeting',
          'activity_date_time' => $some_date,
          'activity_subject' => 'asubj',
        ],
        'expected_error' => '',
      ],

      3 => [
        'input' => [
          'activity_type_id' => 1,
          'activity_date_time' => $some_date,
          'activity_subject' => 'asubj',
        ],
        'expected_error' => '',
      ],

      5 => [
        'input' => [
          'activity_type_id' => 1,
          'activity_date_time' => $some_date,
          'activity_subject' => 'asubj',
        ],
        'expected_error' => '',
      ],

      6 => [
        'input' => [
          'activity_type_id' => 'Meeting',
          'activity_date_time' => $some_date,
          'activity_subject' => 'asubj',
        ],
        'expected_error' => '',
      ],

      7 => [
        'input' => [
          'activity_date_time' => $some_date,
          'activity_subject' => 'asubj',
        ],
        'expected_error' => 'Missing required fields',
      ],

      8 => [
        'input' => [
          'activity_type_id' => '',
          'activity_date_time' => $some_date,
          'activity_subject' => 'asubj',
        ],
        'expected_error' => 'Missing required fields',
      ],

      9 => [
        'input' => [
          'activity_type_id' => 'Meeting',
          'activity_subject' => 'asubj',
        ],
        'expected_error' => 'Missing required fields',
      ],

      10 => [
        'input' => [
          'activity_type_id' => 'Meeting',
          'activity_date_time' => '',
          'activity_subject' => 'asubj',
        ],
        'expected_error' => 'Missing required fields',
      ],

      // @todo: This is inconsistent. Subject is required in the map UI but not
      // on import. Should subject be required? Personally I think the import
      // is correct and it shouldn't be required in UI.
      11 => [
        'input' => [
          'activity_type_id' => 'Meeting',
          'activity_date_time' => $some_date,
        ],
        'expected_error' => '',
      ],

      // @todo: This is inconsistent. Subject is required in the map UI but not
      // on import. Should subject be required? Personally I think the import
      // is correct and it shouldn't be required in UI.
      12 => [
        'input' => [
          'activity_type_id' => 'Meeting',
          'activity_date_time' => $some_date,
          'activity_subject' => '',
        ],
        'expected_error' => '',
      ],

      13 => [
        'input' => [
          'activity_type_id' => 'Meeting',
          'activity_date_time' => $some_date,
          'activity_subject' => 'asubj',
          'replace_me_custom_field' => 'InvalidValue',
        ],
        'expected_error' => 'Invalid value for field(s) : Integer radio',
      ],

      14 => [
        'input' => [
          'activity_type_id' => 'Meeting',
          'activity_date_time' => $some_date,
          'activity_subject' => 'asubj',
          'replace_me_custom_field' => '',
        ],
        'expected_error' => '',
      ],
      // a way to find the contact id is required.
      15 => [
        'input' => [
          'target_contact_id' => '',
          'activity_type_id' => 'Meeting',
          'activity_date_time' => $some_date,
          'activity_subject' => 'asubj',
        ],
        'expected_error' => 'No matching Contact found for ()',
      ],

    ];
  }

  /**
   * @param array $mappings
   *
   * @return array
   */
  protected function getMapperFromFieldMappings(array $mappings): array {
    $mapper = [];
    foreach ($mappings as $mapping) {
      $fieldInput = [$mapping['name']];
      $mapper[] = $fieldInput;
    }
    return $mapper;
  }

  /**
   * Test the full form-flow import.
   */
  public function testImportCSV() :void {
    $this->individualCreate(['email' => 'mum@example.com']);
    $this->importCSV('activity.csv', [
      ['name' => 'activity_date_time'],
      ['name' => 'activity_status_id'],
      ['name' => 'email'],
      ['name' => 'activity_type_id'],
      ['name' => 'activity_details'],
      ['name' => 'activity_duration'],
      ['name' => 'priority_id'],
      ['name' => 'activity_location'],
      ['name' => 'activity_subject'],
      ['name' => 'do_not_import'],
    ]);
    $dataSource = new CRM_Import_DataSource_CSV($this->userJobID);
    $row = $dataSource->getRow();
    $this->assertEquals('IMPORTED', $row['_status']);
    $this->callAPISuccessGetSingle('Activity', ['priority_id' => 'Urgent']);
  }

  /**
   * Test the full form-flow import and make sure we can map source/target external identifier correctly.
   */
  public function testImportCSVExternalIdentifier() :void {
    $contactID1 = $this->individualCreate(['email' => 'mum@example.com', 'external_identifier' => 'individual1']);
    $contactID2 = $this->individualCreate(['email' => 'mum@example.com', 'external_identifier' => 'individual2'], 'individual_2');
    $this->importCSV('activityexternalidentifier.csv', [
      ['name' => 'activity_date_time'],
      ['name' => 'activity_status_id'],
      ['name' => 'email'],
      ['name' => 'activity_type_id'],
      ['name' => 'activity_details'],
      ['name' => 'activity_duration'],
      ['name' => 'priority_id'],
      ['name' => 'activity_location'],
      ['name' => 'activity_subject'],
      ['name' => 'do_not_import'],
      ['name' => 'source_contact_external_identifier'],
      ['name' => 'external_identifier'],
    ]);
    $dataSource = new CRM_Import_DataSource_CSV($this->userJobID);
    $row = $dataSource->getRow();
    $this->assertEquals('IMPORTED', $row['_status']);
    $activity = $this->callAPISuccessGetSingle('Activity', ['priority_id' => 'Urgent']);
    $activityContacts = $this->callAPISuccess('ActivityContact', 'get', ['activity_id' => $activity['id'], 'sequential' => TRUE]);
    $this->assertCount(2, $activityContacts['values']);
    $this->assertEquals($activityContacts['values'][0]['contact_id'], $contactID1);
    $this->assertEquals($activityContacts['values'][1]['contact_id'], $contactID2);
  }

  /**
   * @param array $submittedValues
   *
   * @return int
   * @noinspection PhpDocMissingThrowsInspection
   */
  protected function getUserJobID(array $submittedValues = []): int {
    $userJobID = UserJob::create()->setValues([
      'metadata' => [
        'submitted_values' => array_merge([
          'contactType' => 'Individual',
          'contactSubType' => '',
          'dataSource' => 'CRM_Import_DataSource_SQL',
          'sqlQuery' => 'SELECT first_name FROM civicrm_contact',
          'onDuplicate' => CRM_Import_Parser::DUPLICATE_SKIP,
          'dedupe_rule_id' => NULL,
          'dateFormats' => CRM_Utils_Date::DATE_yyyy_mm_dd,
        ], $submittedValues),
      ],
      'status_id:name' => 'draft',
      'job_type' => 'activity_import',
    ])->execute()->first()['id'];
    if ($submittedValues['dataSource'] ?? NULL === 'CRM_Import_DataSource') {
      $dataSource = new CRM_Import_DataSource_CSV($userJobID);
    }
    else {
      $dataSource = new CRM_Import_DataSource_SQL($userJobID);
    }
    $dataSource->initialize();
    return $userJobID;
  }

  /**
   * Get the import's datasource form.
   *
   * Defaults to contribution - other classes should override.
   *
   * @param array $submittedValues
   *
   * @return \CRM_Activity_Import_Form_DataSource
   * @noinspection PhpUnnecessaryLocalVariableInspection
   */
  protected function getDataSourceForm(array $submittedValues): CRM_Activity_Import_Form_DataSource {
    /** @var \CRM_Activity_Import_Form_DataSource $form */
    $form = $this->getFormObject('CRM_Activity_Import_Form_DataSource', $submittedValues);
    return $form;
  }

  /**
   * Get the import's mapField form.
   *
   * Defaults to contribution - other classes should override.
   *
   * @param array $submittedValues
   *
   * @return \CRM_Activity_Import_Form_MapField
   * @noinspection PhpUnnecessaryLocalVariableInspection
   */
  protected function getMapFieldForm(array $submittedValues): CRM_Activity_Import_Form_MapField {
    /** @var \CRM_Activity_Import_Form_MapField $form */
    $form = $this->getFormObject('CRM_Activity_Import_Form_MapField', $submittedValues);
    return $form;
  }

  /**
   * Get the import's preview form.
   *
   * Defaults to contribution - other classes should override.
   *
   * @param array $submittedValues
   *
   * @return \CRM_Activity_Import_Form_Preview
   * @noinspection PhpUnnecessaryLocalVariableInspection
   */
  protected function getPreviewForm(array $submittedValues): CRM_Activity_Import_Form_Preview {
    /** @var CRM_Activity_Import_Form_Preview $form */
    $form = $this->getFormObject('CRM_Activity_Import_Form_Preview', $submittedValues);
    return $form;
  }

}
