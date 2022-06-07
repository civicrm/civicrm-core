<?php

/**
 *  File for the Participant import class
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

use Civi\Api4\UserJob;

/**
 * @package   CiviCRM
 * @group headless
 */
class CRM_Participant_Import_Parser_ParticipantTest extends CiviUnitTestCase {

  use CRMTraits_Custom_CustomDataTrait;

  protected $entity = 'Participant';

  /**
   * Tears down the fixture, for example, closes a network connection.
   * This method is called after a test is executed.
   */
  public function tearDown(): void {
    $this->quickCleanup([
      'civicrm_event',
      'civicrm_participant',
      'civicrm_contact',
      'civicrm_email',
      'civicrm_user_job',
      'civicrm_queue',
      'civicrm_queue_item',
    ], TRUE);
    parent::tearDown();
  }

  /**
   * Import the csv file values.
   *
   * This function uses a flow that mimics the UI flow.
   *
   * @param string $csv Name of csv file.
   * @param array $fieldMappings
   * @param array $submittedValues
   */
  protected function importCSV(string $csv, array $fieldMappings, array $submittedValues = []): void {
    $submittedValues = array_merge([
      'uploadFile' => ['name' => __DIR__ . '/data/' . $csv],
      'skipColumnHeader' => TRUE,
      'fieldSeparator' => ',',
      'contactType' => CRM_Import_Parser::CONTACT_INDIVIDUAL,
      'mapper' => $this->getMapperFromFieldMappings($fieldMappings),
      'dataSource' => 'CRM_Import_DataSource_CSV',
      'file' => ['name' => $csv],
      'dateFormats' => CRM_Core_Form_Date::DATE_yyyy_mm_dd,
      'onDuplicate' => CRM_Import_Parser::DUPLICATE_UPDATE,
      'groups' => [],
    ], $submittedValues);
    /* @var \CRM_Event_Import_Form_DataSource $form */
    $form = $this->getFormObject('CRM_Event_Import_Form_DataSource', $submittedValues);
    $values = $_SESSION['_' . $form->controller->_name . '_container']['values'];
    $form->buildForm();
    $form->postProcess();
    // This gets reset in DataSource so re-do....
    $_SESSION['_' . $form->controller->_name . '_container']['values'] = $values;

    $this->userJobID = $form->getUserJobID();
    /* @var CRM_Event_Import_Form_MapField $form */
    $form = $this->getFormObject('CRM_Event_Import_Form_MapField', $submittedValues);
    $form->setUserJobID($this->userJobID);
    $form->buildForm();
    $form->postProcess();
    /* @var CRM_Event_Import_Form_Preview $form */
    $form = $this->getFormObject('CRM_Event_Import_Form_Preview', $submittedValues);
    $form->setUserJobID($this->userJobID);
    $form->buildForm();
    $form->postProcess();
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
    $this->campaignCreate(['name' => 'Soccer cup']);
    $this->eventCreate(['title' => 'Rain-forest Cup Youth Soccer Tournament']);
    $this->individualCreate(['email' => 'mum@example.com']);
    $this->importCSV('participant.csv', [
      ['name' => 'event_id'],
      ['name' => 'participant_campaign_id'],
      ['name' => 'email'],
      ['name' => 'participant_fee_amount'],
      ['name' => 'participant_fee_currency'],
      ['name' => 'participant_fee_level'],
      ['name' => 'participant_is_pay_later'],
      ['name' => 'participant_role_id'],
      ['name' => 'participant_source'],
      ['name' => 'participant_status_id'],
      ['name' => 'participant_register_date'],
      ['name' => 'do_not_import'],
    ]);
    $dataSource = new CRM_Import_DataSource_CSV($this->userJobID);
    $row = $dataSource->getRow();
    $this->assertEquals('IMPORTED', $row['_status']);
    $this->callAPISuccessGetSingle('Participant', ['campaign_id' => 'Soccer Cup']);
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
          'contactType' => CRM_Import_Parser::CONTACT_INDIVIDUAL,
          'contactSubType' => '',
          'dataSource' => 'CRM_Import_DataSource_SQL',
          'sqlQuery' => 'SELECT first_name FROM civicrm_contact',
          'onDuplicate' => CRM_Import_Parser::DUPLICATE_SKIP,
          'dedupe_rule_id' => NULL,
          'dateFormats' => CRM_Core_Form_Date::DATE_yyyy_mm_dd,
        ], $submittedValues),
      ],
      'status_id:name' => 'draft',
      'type_id:name' => 'participant_import',
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

}
