<?php

/**
 *  File for the Membership import class
 *
 *  (PHP 5)
 *
 * @package   CiviCRM
 *
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

use Civi\Api4\Membership;
use Civi\Api4\UserJob;
use Civi\Import\MembershipParser;

/**
 * @package   CiviCRM
 * @group headless
 * @group import
 */
class CRM_Member_Import_Parser_MembershipTest extends CiviUnitTestCase {
  use CRMTraits_Custom_CustomDataTrait;
  use CRMTraits_Import_ParserTrait;

  const MEMBERSHIP_TYPE_NAME = 'Mickey Mouse Club Member';

  /**
   * @var int
   */
  protected $userJobID;

  /**
   * Membership type id used in test function.
   *
   * @var int
   */
  protected $membershipTypeID;

  /**
   * Membership type id used in test function.
   *
   * @var int
   */
  protected $relationshipTypeID;

  /**
   * Set up for test.
   *
   * @throws \CRM_Core_Exception
   */
  public function setUp(): void {
    parent::setUp();
    $this->callAPISuccess('Extension', 'install', ['keys' => 'civiimport']);
    $params = [
      'contact_type_a' => 'Individual',
      'contact_type_b' => 'Organization',
      'name_a_b' => 'Test Employee of',
      'name_b_a' => 'Test Employer of',
    ];
    $this->relationshipTypeID = $this->relationshipTypeCreate($params);
    $organizationContactID = $this->organizationCreate();
    $this->restoreMembershipTypes();
    $params = [
      'name' => self::MEMBERSHIP_TYPE_NAME,
      'description' => NULL,
      'minimum_fee' => 10,
      'duration_unit' => 'year',
      'member_of_contact_id' => $organizationContactID,
      'period_type' => 'fixed',
      'duration_interval' => 1,
      'financial_type_id' => 1,
      'relationship_type_id' => $this->relationshipTypeID,
      'visibility' => 'Public',
      'is_active' => 1,
      'fixed_period_start_day' => 101,
      'fixed_period_rollover_day' => 1231,
    ];

    $membershipType = CRM_Member_BAO_MembershipType::add($params);
    $this->membershipTypeID = (int) $membershipType->id;

    $this->membershipStatusCreate('test status');
  }

  /**
   * Tears down the fixture, for example, closes a network connection.
   * This method is called after a test is executed.
   */
  public function tearDown(): void {
    $tablesToTruncate = [
      'civicrm_email',
      'civicrm_user_job',
      'civicrm_queue',
      'civicrm_queue_item',
    ];
    $this->relationshipTypeDelete($this->relationshipTypeID);
    $this->membershipTypeDelete(['id' => $this->membershipTypeID]);
    $this->quickCleanUpFinancialEntities();
    $this->quickCleanup($tablesToTruncate, TRUE);
    parent::tearDown();
  }

  /**
   *  Test Import.
   */
  public function testImport(): void {
    $this->individualCreate();
    $contact2Params = [
      'first_name' => 'Anthonita',
      'middle_name' => 'J.',
      'last_name' => 'Anderson',
      'prefix_id' => 3,
      'suffix_id' => 3,
      'email_primary.email' => 'b@c.com',
      'contact_type' => 'Individual',
    ];

    $this->individualCreate($contact2Params);
    $year = date('Y') - 1;
    $startDate2 = $year . '-10-09';
    $joinDate2 = $year . '-10-10';
    $params = [
      [
        'anthony_anderson@civicrm.org',
        $this->membershipTypeID,
        date('Y-m-d'),
        date('Y-m-d'),
      ],
      [
        $contact2Params['email_primary.email'],
        self::MEMBERSHIP_TYPE_NAME,
        $startDate2,
        $joinDate2,
      ],
    ];

    $importObject = $this->createImportObject(['Contact.email_primary.email', 'Membership.membership_type_id', 'Membership.start_date', 'Membership.join_date']);
    foreach ($params as $values) {
      $this->assertEquals(CRM_Import_Parser::VALID, $importObject->import($values), $values[0]);
    }
    $result = $this->callAPISuccess('membership', 'get', ['sequential' => 1])['values'];
    $this->assertCount(2, $result);
    $this->assertEquals($startDate2, $result[1]['start_date']);
    $this->assertEquals($joinDate2, $result[1]['join_date']);
    $contacts = $this->callAPISuccess('contact', 'get', ['email' => $contact2Params['email_primary.email'], 'sequential' => 1])['values'];
    $this->assertCount(1, $contacts);
  }

  /**
   * Test overriding a membership but not providing status.
   */
  public function testImportOverriddenMembershipButWithoutStatus(): void {
    $this->individualCreate(['email' => 'anthony_anderson2@civicrm.org']);
    $membershipImporter = new MembershipParser();
    $membershipImporter->setUserJobID($this->getUserJobID([
      'mapper' => [['Contact.email_primary.email'], ['Membership.membership_type_id'], ['Membership.start_date'], ['Membership.is_override']],
    ]));
    $membershipImporter->init();

    $importValues = [
      'anthony_anderson2@civicrm.org',
      $this->membershipTypeID,
      date('Y-m-d'),
      TRUE,
    ];
    try {
      $membershipImporter->validateValues($importValues);
      $this->fail('validation error expected.');
    }
    catch (CRM_Core_Exception $e) {
      $this->assertStringContainsString('Required parameter missing: Status', $e->getMessage());
      return;
    }

  }

  /**
   * Test that the passed in status is respected.
   */
  public function testImportOverriddenMembershipWithStatus(): void {
    $this->individualCreate(['email' => 'anthony_anderson3@civicrm.org']);
    $membershipImporter = $this->createImportObject([
      'Contact.email_primary.email',
      'Membership.membership_type_id',
      'Membership.start_date',
      'Membership.is_override',
      'Membership.status_id',
    ]);

    $importValues = [
      'anthony_anderson3@civicrm.org',
      $this->membershipTypeID,
      date('Y-m-d'),
      TRUE,
      'New',
    ];

    $importResponse = $membershipImporter->import($importValues);
    $this->assertEquals(CRM_Import_Parser::VALID, $importResponse);
  }

  public function testImportOverriddenMembershipWithValidOverrideEndDate(): void {
    $this->individualCreate(['email' => 'anthony_anderson4@civicrm.org']);
    $membershipImporter = new MembershipParser();
    $membershipImporter->setUserJobID($this->getUserJobID([
      'mapper' => [['Contact.email_primary.email'], ['Membership.membership_type_id'], ['Membership.start_date'], ['Membership.is_override'], ['Membership.status_id'], ['Membership.status_override_end_date']],
    ],
    [
      'Contact' => ['action' => 'update'],
    ]));
    $membershipImporter->init();

    $importValues = [
      'anthony_anderson4@civicrm.org',
      $this->membershipTypeID,
      date('Y-m-d'),
      TRUE,
      'New',
      date('Y-m-d'),
    ];

    $importResponse = $membershipImporter->import($importValues);
    $this->assertEquals(CRM_Import_Parser::VALID, $importResponse);
    $contacts = $this->callAPISuccess('Contact', 'get', ['email' => 'anthony_anderson4@civicrm.org', 'sequential' => 1])['values'];
    $this->assertCount(1, $contacts);
  }

  public function testImportOverriddenMembershipWithInvalidOverrideEndDate(): void {
    $this->individualCreate(['email' => 'anthony_anderson5@civicrm.org']);
    $this->userJobID = $this->getUserJobID([
      'mapper' => [['Contact.email_primary.email'], ['Membership.membership_type_id'], ['Membership.start_date'], ['Membership.is_override'], ['Membership.status_id'], ['Membership.status_override_end_date']],
      'onDuplicate' => CRM_Import_Parser::DUPLICATE_UPDATE,
    ]);
    $membershipImporter = new MembershipParser();
    $membershipImporter->setUserJobID($this->userJobID);
    $membershipImporter->init();

    $importValues = [
      'anthony_anderson5@civicrm.org',
      'General',
      date('Y-m-d'),
      1,
      $this->ids['MembershipStatus']['test status'],
      'abc',
    ];
    try {
      $membershipImporter->validateValues($importValues);
    }
    catch (CRM_Core_Exception $e) {
      $this->assertEquals('Invalid value for field(s) : Status Override End Date', $e->getMessage());
      return;
    }
    $this->fail('Exception expected');

  }

  /**
   * Test that memberships can still be imported if the status is renamed.
   *
   */
  public function testImportMembershipWithRenamedStatus(): void {
    $this->individualCreate(['email' => 'anthony_anderson3@civicrm.org']);

    $this->callAPISuccess('MembershipStatus', 'get', [
      'name' => 'New',
      'api.MembershipStatus.create' => [
        'label' => 'New-renamed',
      ],
    ]);
    $membershipImporter = $this->createImportObject([
      'Contact.email_primary.email',
      'Membership.membership_type_id',
      'Membership.start_date',
      'Membership.is_override',
      'Membership.status_id',
    ]);

    $importValues = [
      'anthony_anderson3@civicrm.org',
      $this->membershipTypeID,
      date('Y-m-d'),
      TRUE,
      'New-renamed',
    ];

    $importResponse = $membershipImporter->import($importValues);
    $this->assertEquals(CRM_Import_Parser::VALID, $importResponse);
    $createdStatusID = $this->callAPISuccessGetValue('Membership', ['return' => 'status_id']);
    $this->assertEquals(CRM_Core_PseudoConstant::getKey('CRM_Member_BAO_Membership', 'status_id', 'New'), $createdStatusID);
    $this->callAPISuccess('MembershipStatus', 'get', [
      'name' => 'New',
      'api.MembershipStatus.create' => [
        'label' => 'New',
      ],
    ]);
  }

  /**
   * Create an import object.
   *
   * @param array $fields
   *
   * @return \Civi\Import\MembershipParser
   */
  protected function createImportObject(array $fields): MembershipParser {
    $fieldMapper = [];
    $mapper = [];
    foreach ($fields as $index => $field) {
      $fieldMapper['mapper[' . $index . '][0]'] = $field;
      $mapper[] = [$field];
    }

    $membershipImporter = new MembershipParser();
    $membershipImporter->setUserJobID($this->getUserJobID(['mapper' => $mapper]));
    $membershipImporter->init();
    $membershipImporter->_contactType = 'Individual';
    return $membershipImporter;
  }

  /**
   * @param array $submittedValues
   * @param array $entityConfigurations
   *
   * @return int
   */
  protected function getUserJobID(array $submittedValues = [], $entityConfigurations = []): int {
    $queryFields = ['first_name'];
    foreach (array_keys($submittedValues['mapper']) as $key) {
      if ($key > 0) {
        $queryFields[] = '"value_' . $key . '" AS field_' . $key;
      }
    }
    $userJobID = UserJob::create()->setValues([
      'metadata' => [
        'submitted_values' => array_merge([
          'dataSource' => 'CRM_Import_DataSource_SQL',
          'sqlQuery' => 'SELECT ' . implode(', ', $queryFields) . ' FROM civicrm_contact',
        ], $submittedValues),
        'entity_configuration' => [
          'Contact' => array_merge(['contact_type' => 'Individual', 'dedupe_rule' => 'IndividualUnsupervised'], ($entityConfigurations['Contact'] ?? [])),
          'Membership' => array_merge(['action' => 'update'], ($entityConfigurations['Membership'] ?? [])),
        ],
      ],
      'status_id:name' => 'draft',
      'job_type' => 'membership_import',
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
   * Test importing to a custom field.
   */
  public function testImportCustomData(): void {
    $donaldDuckID = $this->individualCreate(['first_name' => 'Donald', 'last_name' => 'Duck']);
    $this->createCustomGroupWithFieldsOfAllTypes(['extends' => 'Membership']);
    $membershipImporter = $this->createImportObject([
      'Contact.id',
      'Membership.membership_type_id',
      'Membership.start_date',
      'Membership.' . $this->getCustomFieldName('text', 4),
      'Membership.' . $this->getCustomFieldName('select_string', 4),
    ]);
    $importValues = [
      $donaldDuckID,
      $this->membershipTypeID,
      date('Y-m-d'),
      'blah',
      'Red',
    ];

    $importResponse = $membershipImporter->import($importValues);
    $this->assertEquals(CRM_Import_Parser::VALID, $importResponse);
    $membership = $this->callAPISuccessGetSingle('Membership', []);
    $this->assertEquals('blah', $membership[$this->getCustomFieldName('text')]);
    $this->assertEquals('R', $membership[$this->getCustomFieldName('select_string')]);
  }

  /**
   * Test that one of the following is enough
   *  - contact_id + membership_type_id + start_date
   *  - external_identifier + membership_type_id + start_date
   *  - email_primary.email + membership_type_id + start_date
   *
   * @dataProvider requiredFields
   */
  public function testRequiredFields(array $dataProvider): void {
    $this->individualCreate(['external_identifier' => 'abc', 'email' => 'jenny@example.com']);
    $mapper = [
      ['name' => 'Membership.membership_type_id'],
      ['name' => 'Membership.id'],
      ['name' => 'Contact.id'],
      ['name' => 'Contact.external_identifier'],
      ['name' => 'Contact.email_primary.email'],
      ['name' => 'Membership.start_date'],
    ];
    foreach ($mapper as $index => $field) {
      if (!in_array($field['name'], $dataProvider)) {
        $mapper[$index]['name'] = 'do_not_import';
      }
    }

    $this->importCSV('membership_with_multiple_identifiers.csv', $mapper, ['onDuplicate' => CRM_Import_Parser::DUPLICATE_NOCHECK, 'saveMapping' => FALSE]);
    $dataSource = new CRM_Import_DataSource_CSV($this->userJobID);
    $row = $dataSource->getRow();
    $this->assertEquals('IMPORTED', $row['_status'], $row['_status_message']);
  }

  public static function requiredFields(): array {
    return [
      'contact_id' => [['Contact.id', 'Membership.membership_type_id', 'Membership.start_date']],
      'external_identifier' => [['Contact.external_identifier', 'Membership.membership_type_id', 'Membership.start_date']],
      'email' => [['Contact.email_primary.email', 'Membership.membership_type_id', 'Membership.start_date']],
    ];
  }

  /**
   * Test the full form-flow import.
   */
  public function testImportCSV() :void {
    $this->importCSV('memberships_invalid.csv', [
      ['name' => 'Contact.id'],
      ['name' => 'Membership.source'],
      ['name' => 'Membership.membership_type_id'],
      ['name' => 'Membership.start_date'],
      ['name' => 'do_not_import'],
    ]);
    $dataSource = new CRM_Import_DataSource_CSV($this->userJobID);
    $row = $dataSource->getRow();
    $this->assertEquals('ERROR', $row['_status']);
    $this->assertEquals('Invalid value for field(s) : Membership Type', $row['_status_message']);
    $userJob = UserJob::get()
      ->addSelect('status_id:name', 'status_id:label')
      ->addWhere('id', '=', $this->userJobID)
      ->execute()->single();
    $this->assertEquals('complete_with_errors', $userJob['status_id:name']);
    $this->assertEquals('Complete with Errors', $userJob['status_id:label']);
  }

  /**
   * Test the full form-flow import.
   */
  public function testImportCSVWithID() :void {
    $this->createTestEntity('Membership', [
      'membership_type_id:name' => 'General',
      'contact_id' => $this->individualCreate(),
    ]);
    $this->importCSV('memberships_with_id.csv', [
      ['name' => 'Membership.id'],
      ['name' => 'Membership.source'],
      ['name' => 'Membership.membership_type_id'],
      ['name' => 'Membership.start_date'],
      ['name' => 'do_not_import'],
    ]);
    $dataSource = new CRM_Import_DataSource_CSV($this->userJobID);
    $row = $dataSource->getRow();
    $this->assertEquals('IMPORTED', $row['_status']);
    $membership = Membership::get(FALSE)
      ->execute()->single();
    $this->assertEquals('2019-03-23', $membership['start_date']);
  }

  /**
   * Test the full form-flow import with ID and not all required fields.
   */
  public function testImportCSVWithIDMembershipTypeOptional() :void {
    $this->createTestEntity('Membership', [
      'membership_type_id:name' => 'General',
      'contact_id' => $this->individualCreate(),
      'start_date' => '2019-03-23',
    ]);
    $this->importCSV('memberships_with_id.csv', [
      ['name' => 'Membership.id'],
      ['name' => 'Membership.source'],
      ['name' => 'do_not_import'],
      ['name' => 'do_not_import'],
      ['name' => 'do_not_import'],
    ]);
    $dataSource = new CRM_Import_DataSource_CSV($this->userJobID);
    $row = $dataSource->getRow();
    $this->assertEquals('IMPORTED', $row['_status']);
    $membership = Membership::get(FALSE)
      ->execute()->single();
    $this->assertEquals('2019-03-23', $membership['start_date']);
    $this->assertEquals('Import', $membership['source']);
  }

  /**
   * Test the full form-flow import.
   *
   * @throws \CRM_Core_Exception
   */
  public function testImportTSV() :void {
    $this->individualCreate(['email' => 'member@example.com']);
    $this->importCSV('memberships_valid.tsv', [
      ['name' => 'Contact.email_primary.email'],
      ['name' => 'Membership.source'],
      ['name' => 'Membership.membership_type_id'],
      ['name' => 'Membership.start_date'],
      ['name' => 'do_not_import'],
    ], ['fieldSeparator' => 'tab']);
    $dataSource = new CRM_Import_DataSource_CSV($this->userJobID);
    $row = $dataSource->getRow();
    $this->assertEquals('IMPORTED', $row['_status']);
    $membership = $this->callAPISuccessGetSingle('Membership', []);
    $this->assertEquals('2019-03-23', $membership['join_date']);
  }

  /**
   * Test dates are parsed.
   */
  public function testUpdateWithCustomDates(): void {
    $this->createCustomGroupWithFieldOfType(['extends' => 'Membership'], 'date');
    $contactID = $this->individualCreate(['external_identifier' => 'ext-1']);
    $this->callAPISuccess('Membership', 'create', [
      'contact_id' => $contactID,
      'membership_type_id' => 'General',
      'start_date' => '2020-10-01',
    ]);
    $mapping = [
      ['name' => 'Membership.id'],
      ['name' => 'Membership.source'],
      ['name' => 'Membership.membership_type_id'],
      ['name' => 'Membership.start_date'],
      ['name' => 'Membership.' . $this->getCustomFieldName('date', 4)],
    ];
    $this->importCSV('memberships_update_custom_date.csv', $mapping, ['dateFormats' => 32]);
    $membership = $this->callAPISuccessGetSingle('Membership', []);
    $this->assertEquals('2021-03-23', $membership['start_date']);
    $this->assertEquals('2019-03-23 00:00:00', $membership[$this->getCustomFieldName('date')]);
  }

  /**
   * @param array $mappings
   *
   * @return array
   */
  protected function getMapperFromFieldMappings(array $mappings): array {
    $mapper = [];
    foreach ($mappings as $mapping) {
      $mapper[] = $mapping['name'];
    }
    return $mapper;
  }

  /**
   * Get the import's datasource form.
   *
   * @param array $submittedValues
   *
   * @return \CRM_Member_Import_Form_DataSource
   * @throws \CRM_Core_Exception
   */
  protected function getDataSourceForm(array $submittedValues): CRM_Member_Import_Form_DataSource {
    /** @var \CRM_Member_Import_Form_DataSource $form */
    $form = $this->getFormObject('CRM_Member_Import_Form_DataSource', $submittedValues);
    return $form;
  }

  /**
   * Get the import's mapField form.
   *
   * Defaults to contribution - other classes should override.
   *
   * @param array $submittedValues
   *
   * @return \CRM_Member_Import_Form_MapField
   * @noinspection PhpUnnecessaryLocalVariableInspection
   * @throws \CRM_Core_Exception
   */
  protected function getMapFieldForm(array $submittedValues): CRM_Member_Import_Form_MapField {
    /** @var \CRM_Member_Import_Form_MapField $form */
    $form = $this->getFormObject('CRM_Member_Import_Form_MapField', $submittedValues);
    return $form;
  }

  /**
   * Get the import's preview form.
   *
   * @param array $submittedValues
   *
   * @return \CRM_Member_Import_Form_Preview
   * @throws \CRM_Core_Exception
   */
  protected function getPreviewForm(array $submittedValues): CRM_Member_Import_Form_Preview {
    /** @var CRM_Member_Import_Form_Preview $form */
    $form = $this->getFormObject('CRM_Member_Import_Form_Preview', $submittedValues);
    return $form;
  }

}
