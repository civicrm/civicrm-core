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

use Civi\Import\ActivityParser;
use Civi\Test\Invasive;

/**
 * Test general Import Parser functions
 *
 * @package   CiviCRM
 * @group headless
 * @group import
 */
class CRM_Import_ParserTest extends CiviUnitTestCase {

  public function setUp(): void {
    parent::setUp();
    $extension = \Civi\Api4\Extension::get(FALSE)
      ->addWhere('key', '=', 'civiimport')
      ->execute()->first();
    if (empty($extension['status']) || $extension['status'] !== 'installed') {
      $this->callAPISuccess('Extension', 'install', ['keys' => 'civiimport']);
    }
  }

  /**
   * Provides test cases for contact type guessing
   */
  public function contactTypeProvider(): array {
    return [
      'explicit contact type' => [
        ['contact_type' => 'Organization'],
        'Organization',
      ],
      'individual field 1' => [
        ['first_name' => 'John'],
        'Individual',
      ],
      'individual field 2' => [
        ['formal_title' => 'Sir John'],
        'Individual',
      ],
      // `organization_name` is used to cache the individual's current employer's name
      // So this test case ensures the guesser doesn't get confused by that.
      'individual field 3' => [
        ['organization_name' => 'Smith Corp', 'last_name' => 'Smith'],
        'Individual',
      ],
      'organization field 1' => [
        ['organization_name' => 'ACME Corp'],
        'Organization',
      ],
      'organization field 2' => [
        ['sic_code' => 123],
        'Organization',
      ],
      'household field 1' => [
        ['household_name' => 'Smith Family'],
        'Household',
      ],
      'household field 2' => [
        ['primary_contact_id' => 123],
        'Household',
      ],
      'non-specific field' => [
        ['email' => 'test@example.com'],
        'Individual',
      ],
      'empty values' => [
        [],
        'Individual',
      ],
    ];
  }

  /**
   * @dataProvider contactTypeProvider
   */
  public function testGuessContactType(array $values, string $expectedType): void {
    $activityParser = new ActivityParser();
    $result = Invasive::call([$activityParser, 'guessContactType'], [$values]);
    $this->assertEquals($expectedType, $result);
  }

  /**
   * Test that importAlterMappedRow hook is called with the correct importEntities.
   */
  public function testImportAlterMappedRowEntitiesHook(): void {
    $mockDataSource = $this->createMock(\CRM_Import_DataSource::class);

    $parser = $this->getMockBuilder(\Civi\Import\GenericParser::class)
      ->onlyMethods(['getDataSourceObject', 'getUserJob', 'getUserJobID'])
      ->getMock();
    $parser->method('getDataSourceObject')->willReturn($mockDataSource);
    $parser->method('getUserJobID')->willReturn(123);
    $parser->method('getUserJob')->willReturn([
      'id' => 123,
      'job_type' => 'activity_import',
      'metadata' => [
        'base_entity' => 'Activity',
        'import_mappings' => [
          ['name' => 'Activity.subject'],
        ],
        'DataSource' => [
          'number_of_columns' => 1,
        ],
      ],
    ]);

    $parser->init();

    $hookCalled = 0;
    $hookEntities = [];
    \CRM_Utils_Hook::singleton()->setHook('civicrm_importAlterMappedRow', function($importType, $context, &$mappedRow, $rowValues, $userJobID, $importEntities = NULL) use (&$hookCalled, &$hookEntities) {
      if ($context === 'import') {
        $hookCalled++;
        $hookEntities = $importEntities;
      }
    });

    try {
      $parser->import(['Subject of Activity', 1]);
    }
    catch (\Exception $e) {
      // Ignored: we only care about the hook execution before save
    }

    $this->assertEquals(1, $hookCalled);
    $expectedEntities = [
      '' => [
        'entity' => 'Activity',
        'join' => NULL,
      ],
      'Contact' => [
        'entity' => 'Contact',
        'join' => [],
      ],
    ];
    $this->assertEquals($expectedEntities, $hookEntities);
  }

  /**
   * Test transformed field value handling for ambiguous import values.
   *
   * @dataProvider ambiguousFieldValueProvider
   */
  public function testAmbiguousFieldValueTransformation(string $fieldName, string $importedValue, $expectedResult): void {
    $parser = new ActivityParser();
    Invasive::set([$parser, 'ambiguousOptions'], [
      $fieldName => [
        strtolower($importedValue) => [1, 2],
      ],
    ]);
    Invasive::set([$parser, 'importableFieldsMetadata'], [
      $fieldName => [
        'name' => $fieldName,
        'type' => CRM_Utils_Type::T_INT,
        'options' => [
          strtolower($importedValue) => 1,
        ],
      ],
    ]);

    $result = Invasive::call([$parser, 'getTransformedFieldValue'], [$fieldName, $importedValue]);
    $this->assertEquals($expectedResult, $result);
  }

  public function ambiguousFieldValueProvider(): array {
    return [
      'ambiguous financial_type_id' => [
        'financial_type_id',
        'Donation',
        'invalid_import_value',
      ],
      'ambiguous gender_id' => [
        'gender_id',
        'Other',
        'invalid_import_value',
      ],
      'ambiguous state_province_id' => [
        'state_province_id',
        'WA',
        'WA',
      ],
      'ambiguous address_primary.state_province_id' => [
        'address_primary.state_province_id',
        'WA',
        'WA',
      ],
      'ambiguous county_id' => [
        'county_id',
        'Washington',
        'Washington',
      ],
      'ambiguous address_primary.county_id' => [
        'address_primary.county_id',
        'Washington',
        'Washington',
      ],
    ];
  }

  /**
   * Test that ambiguous financial type options return 'invalid_import_value'
   * instead of passing through raw string.
   */
  public function testAmbiguousFinancialTypeImport(): void {
    $ft1 = \Civi\Api4\FinancialType::get(FALSE)
      ->addWhere('name', '=', 'Donation')
      ->execute()->first();
    $ft2ID = NULL;
    if ($ft1) {
      \Civi\Api4\FinancialType::update(FALSE)
        ->addWhere('id', '=', $ft1['id'])
        ->addValue('name', 'Coins')
        ->execute();
    }
    try {
      $ft2 = \Civi\Api4\FinancialType::create(FALSE)
        ->addValue('name', 'Donation')
        ->addValue('label', 'Donation 2')
        ->execute()->first();
      $ft2ID = $ft2['id'];

      $parser = new \Civi\Import\ContributionParser();
      $parser->setImportableFieldsMetadata([
        'financial_type_id' => [
          'name' => 'financial_type_id',
          'title' => 'Financial Type',
          'type' => CRM_Utils_Type::T_INT,
          'entity' => 'Contribution',
          'html' => ['type' => 'Select'],
        ],
      ]);
      $result = Invasive::call([$parser, 'getTransformedFieldValue'], ['financial_type_id', 'Donation']);
      $this->assertEquals('invalid_import_value', $result);
    }
    finally {
      if ($ft2ID) {
        \Civi\Api4\FinancialType::delete(FALSE)->addWhere('id', '=', $ft2ID)->execute();
      }
      if ($ft1) {
        \Civi\Api4\FinancialType::update(FALSE)
          ->addWhere('id', '=', $ft1['id'])
          ->addValue('name', 'Donation')
          ->execute();
      }
    }
  }

}
