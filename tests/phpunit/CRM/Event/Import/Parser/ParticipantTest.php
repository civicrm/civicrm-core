<?php

/**
 *  File for the Participant import class
 */

use Civi\Api4\UserJob;

/**
 * @package   CiviCRM
 * @group headless
 * @group import
 */
class CRM_Event_Import_Parser_ParticipantTest extends CiviUnitTestCase {

  use CRMTraits_Custom_CustomDataTrait;

  protected $entity = 'Participant';

  /**
   * @var int
   */
  protected $userJobID;

  /**
   * Tears down the fixture, for example, closes a network connection.
   * This method is called after a test is executed.
   */
  public function tearDown(): void {
    $this->quickCleanUpFinancialEntities();
    $this->quickCleanup([
      'civicrm_contact',
      'civicrm_email',
      'civicrm_user_job',
      'civicrm_queue',
      'civicrm_queue_item',
      'civicrm_mapping',
      'civicrm_mapping_field',
      'civicrm_uf_field',
      'civicrm_uf_group',
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
      'contactType' => 'Individual',
      'mapper' => $this->getMapperFromFieldMappings($fieldMappings),
      'dataSource' => 'CRM_Import_DataSource_CSV',
      'file' => ['name' => $csv],
      'dateFormats' => CRM_Utils_Date::DATE_yyyy_mm_dd,
      'onDuplicate' => CRM_Import_Parser::DUPLICATE_UPDATE,
      'groups' => [],
      'saveMapping' => TRUE,
      'saveMappingName' => 'my mapping',
      'saveMappingDesc' => 'new mapping',
    ], $submittedValues);
    /** @var \CRM_Event_Import_Form_DataSource $form */
    $form = $this->getFormObject('CRM_Event_Import_Form_DataSource', $submittedValues);
    $values = $_SESSION['_' . $form->controller->_name . '_container']['values'];
    $form->buildForm();
    $form->postProcess();
    // This gets reset in DataSource so re-do....
    $_SESSION['_' . $form->controller->_name . '_container']['values'] = $values;

    $this->userJobID = $form->getUserJobID();
    /** @var CRM_Event_Import_Form_MapField $form */
    $form = $this->getFormObject('CRM_Event_Import_Form_MapField', $submittedValues);
    $form->setUserJobID($this->userJobID);
    $form->buildForm();
    $this->assertTrue($form->validate());
    $form->postProcess();
    /** @var CRM_Event_Import_Form_Preview $form */
    $form = $this->getFormObject('CRM_Event_Import_Form_Preview', $submittedValues);
    $form->setUserJobID($this->userJobID);
    $form->buildForm();
    $this->assertTrue($form->validate());
    try {
      $form->postProcess();
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      $queue = Civi::queue('user_job_' . $this->userJobID);
      $runner = new CRM_Queue_Runner([
        'queue' => $queue,
        'errorMode' => CRM_Queue_Runner::ERROR_ABORT,
      ]);
      $runner->runAll();
    }
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
   * Test that an external id will not match to a deleted contact..
   *
   * @throws \CRM_Core_Exception
   */
  public function testImportWithExternalID() :void {
    $this->eventCreatePaid(['title' => 'Rain-forest Cup Youth Soccer Tournament']);
    $this->individualCreate(['external_identifier' => 'ref-77', 'is_deleted' => TRUE]);
    $this->importCSV('participant_with_ext_id.csv', [
      ['name' => 'event_id'],
      ['name' => 'do_not_import'],
      ['name' => 'external_identifier'],
      ['name' => 'fee_amount'],
      ['name' => 'fee_currency'],
      ['name' => 'fee_level'],
      ['name' => 'is_pay_later'],
      ['name' => 'role_id'],
      ['name' => 'source'],
      ['name' => 'status_id'],
      ['name' => 'register_date'],
      ['name' => 'do_not_import'],
      ['name' => 'do_not_import'],
    ]);
    $dataSource = new CRM_Import_DataSource_CSV($this->userJobID);
    $row = $dataSource->getRow();
    $this->assertEquals('ERROR', $row['_status']);
  }

  /**
   * Test that we can do an update using the participant ID.
   *
   * @throws \CRM_Core_Exception
   */
  public function testImportUpdateUsingID() :void {
    // Ensure that the next id on the participant table is 1 since that is in the csv.
    $this->quickCleanup(['civicrm_participant']);
    $this->individualCreate();
    $this->createTestEntity('Participant', [
      'status_id:name' => 'Pending from pay later',
      'contact_id' => $this->individualCreate(),
      'event_id' => $this->eventCreatePaid()['id'],
      'role_id:name' => ['Attendee'],
    ]);
    $this->importCSV('cancel_participant.csv', [
      ['name' => 'id'],
      ['name' => 'status_id'],
    ]);
    $dataSource = new CRM_Import_DataSource_CSV($this->userJobID);
    $row = $dataSource->getRow();
    $this->assertEquals('IMPORTED', $row['_status'], $row['_status_message']);
    $row = $dataSource->getRow();
    $this->assertEquals('ERROR', $row['_status']);
    $this->assertEquals('Participant record not found for id 2', $row['_status_message']);
  }

  /**
   * Test that imports work generally.
   *
   * @throws \CRM_Core_Exception
   */
  public function testImportParticipant() :void {
    $this->eventCreatePaid(['title' => 'Rain-forest Cup Youth Soccer Tournament']);
    $this->createCustomGroupWithFieldOfType(['extends' => 'Participant'], 'checkbox');
    $contactID = $this->individualCreate(['external_identifier' => 'ref-77']);
    $this->importCSV('participant_with_ext_id.csv', [
      ['name' => 'event_id'],
      ['name' => 'do_not_import'],
      ['name' => 'external_identifier'],
      ['name' => 'fee_amount'],
      ['name' => 'fee_currency'],
      ['name' => 'fee_level'],
      ['name' => 'is_pay_later'],
      ['name' => 'role_id'],
      ['name' => 'source'],
      ['name' => 'status_id'],
      ['name' => 'register_date'],
      ['name' => 'do_not_import'],
      ['name' => $this->getCustomFieldName('checkbox')],
    ]);
    $dataSource = new CRM_Import_DataSource_CSV($this->userJobID);
    $row = $dataSource->getRow();
    $result = $this->callAPISuccess('Participant', 'get', [
      'contact_id' => $contactID,
      'sequential' => TRUE,
    ])['values'][0];

    $this->assertEquals($row['event_title'], $result['event_title']);
    $this->assertEquals($row['fee_amount'], $result['participant_fee_amount']);
    $this->assertEquals($row['participant_source'], $result['participant_source']);
    $this->assertEquals($row['participant_status'], $result['participant_status']);
    $this->assertEquals('2022-12-07 00:00:00', $result['participant_register_date']);
    $this->assertEquals(['Attendee', 'Volunteer'], $result['participant_role']);
    $this->assertEquals(0, $result['participant_is_pay_later']);
    $this->assertEquals(['P', 'M'], array_keys($result[$this->getCustomFieldName('checkbox')]));
  }

  /**
   * @param array $submittedValues
   *
   * @return int
   * @throws \CRM_Core_Exception
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
