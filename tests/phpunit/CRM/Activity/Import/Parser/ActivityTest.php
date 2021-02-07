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
   */
  protected function importValues(array $values, $expectedOutcome = 1): void {
    $importer = $this->createImportObject(array_keys($values));
    $params = array_values($values);
    CRM_Core_Session::singleton()->set('dateTypes', 1);
    $this->assertEquals($expectedOutcome, $importer->import(NULL, $params));
  }

}
