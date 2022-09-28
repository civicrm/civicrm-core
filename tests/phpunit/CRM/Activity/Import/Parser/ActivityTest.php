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

/**
 *  Test Activity Import Parser functions
 *
 * @package   CiviCRM
 * @group headless
 */
class CRM_Activity_Import_Parser_ActivityTest extends CiviUnitTestCase {
  use CRMTraits_Custom_CustomDataTrait;

  /**
   * Prepare for tests.
   */
  public function setUp():void {
    parent::setUp();
    $this->createLoggedInUser();
  }

  /**
   * Clean up after test.
   *
   * @throws \CRM_Core_Exception
   */
  public function tearDown():void {
    $this->quickCleanup(['civicrm_contact', 'civicrm_activity', 'civicrm_activity_contact'], TRUE);
    parent::tearDown();
  }

  /**
   *  Test Import.
   *
   * So far this is just testing the class constructor & preparing for more
   * tests.
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
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
    // @todo Eyes are weary so sanity-check this later:
    // This loop seems the same as array_values($fields)? And this appears
    // to only be called from one place that already has them sequentially
    // indexed so is it even needed?
    $fieldMapper = [];
    foreach ($fields as $index => $field) {
      $fieldMapper[] = $field;
    }
    $importer = new CRM_Activity_Import_Parser_Activity($fieldMapper);
    $importer->init();
    return $importer;
  }

  /**
   * Run the importer.
   *
   * @param array $values
   * @param int $expectedOutcome
   * @return string The error message
   */
  protected function importValues(array $values, $expectedOutcome = 1): string {
    $importer = $this->createImportObject(array_keys($values));
    $params = array_values($values);
    CRM_Core_Session::singleton()->set('dateTypes', 1);
    $outcome = $importer->import(NULL, $params);
    $this->assertEquals($expectedOutcome, $outcome);
    // If there was an error it's in element 0
    return $outcome === CRM_Import_Parser::VALID ? '' : $params[0];
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
          'activity_label' => 'Meeting',
          'activity_date_time' => $some_date,
          'activity_subject' => 'asubj',
        ],
        'expected_error' => '',
      ],

      3 => [
        'input' => [
          'activity_type_id' => 1,
          'activity_label' => 'Meeting',
          'activity_date_time' => $some_date,
          'activity_subject' => 'asubj',
        ],
        'expected_error' => '',
      ],

      4 => [
        'input' => [
          'activity_type_id' => 2,
          'activity_label' => 'Meeting',
          'activity_date_time' => $some_date,
          'activity_subject' => 'asubj',
        ],
        'expected_error' => 'Activity type label and Activity type ID are in conflict',
      ],

      5 => [
        'input' => [
          'activity_type_id' => 1,
          'activity_label' => '',
          'activity_date_time' => $some_date,
          'activity_subject' => 'asubj',
        ],
        'expected_error' => '',
      ],

      6 => [
        'input' => [
          'activity_type_id' => '',
          'activity_label' => 'Meeting',
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
          'activity_label' => '',
          'activity_date_time' => $some_date,
          'activity_subject' => 'asubj',
        ],
        'expected_error' => 'Missing required fields',
      ],

      9 => [
        'input' => [
          'activity_label' => 'Meeting',
          'activity_subject' => 'asubj',
        ],
        'expected_error' => 'Missing required fields',
      ],

      10 => [
        'input' => [
          'activity_label' => 'Meeting',
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
          'activity_label' => 'Meeting',
          'activity_date_time' => $some_date,
        ],
        'expected_error' => '',
      ],

      // @todo: This is inconsistent. Subject is required in the map UI but not
      // on import. Should subject be required? Personally I think the import
      // is correct and it shouldn't be required in UI.
      12 => [
        'input' => [
          'activity_label' => 'Meeting',
          'activity_date_time' => $some_date,
          'activity_subject' => '',
        ],
        'expected_error' => '',
      ],

      13 => [
        'input' => [
          'activity_label' => 'Meeting',
          'activity_date_time' => $some_date,
          'activity_subject' => 'asubj',
          'replace_me_custom_field' => 'InvalidValue',
        ],
        'expected_error' => 'Invalid value for field(s) : Integer radio',
      ],

      14 => [
        'input' => [
          'activity_label' => 'Meeting',
          'activity_date_time' => $some_date,
          'activity_subject' => 'asubj',
          'replace_me_custom_field' => '',
        ],
        'expected_error' => '',
      ],

      // @todo This is also inconsistent. The map UI requires target contact
      // but import is fine leaving it blank. In general civi is fine with
      // a blank target so possibly map UI should not require it.
      15 => [
        'input' => [
          'target_contact_id' => '',
          'activity_label' => 'Meeting',
          'activity_date_time' => $some_date,
          'activity_subject' => 'asubj',
        ],
        'expected_error' => '',
      ],

    ];
  }

}
