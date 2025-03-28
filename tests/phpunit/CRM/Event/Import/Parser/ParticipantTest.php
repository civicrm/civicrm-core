<?php

/**
 *  File for the Participant import class
 */
use Civi\Api4\DedupeRule;
use Civi\Api4\DedupeRuleGroup;
use Civi\Api4\Participant;
use Civi\Api4\UserJob;

/**
 * @package   CiviCRM
 * @group headless
 * @group import
 */
class CRM_Event_Import_Parser_ParticipantTest extends CiviUnitTestCase {

  use CRMTraits_Custom_CustomDataTrait;
  use CRMTraits_Import_ParserTrait;

  protected string $entity = 'Participant';

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
    DedupeRule::delete()
      ->addWhere('rule_table', '!=', 'civicrm_email')
      ->addWhere('dedupe_rule_group_id.name', '=', 'IndividualUnsupervised')->execute();
    DedupeRuleGroup::update(FALSE)
      ->addWhere('name', '=', 'IndividualUnsupervised')
      ->setValues(['is_reserved' => TRUE])
      ->execute();
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
      $mapper[] = $mapping['name'];
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
      ['name' => 'Participant.event_id'],
      ['name' => 'do_not_import'],
      ['name' => 'Contact.external_identifier'],
      ['name' => 'Participant.fee_amount'],
      ['name' => 'Participant.fee_currency'],
      ['name' => 'Participant.fee_level'],
      ['name' => 'Participant.is_pay_later'],
      ['name' => 'Participant.role_id'],
      ['name' => 'Participant.source'],
      ['name' => 'Participant.status_id'],
      ['name' => 'Participant.register_date'],
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
    $this->createCustomGroupWithFieldOfType(['extends' => 'Participant'], 'radio', '', ['data_type' => 'Boolean']);
    $this->createTestEntity('Participant', [
      'status_id:name' => 'Pending from pay later',
      'contact_id' => $this->individualCreate(),
      'event_id' => $this->eventCreatePaid()['id'],
      'role_id:name' => ['Attendee'],
    ]);
    $this->importCSV('cancel_participant.csv', [
      ['name' => 'Participant.id'],
      ['name' => 'Participant.status_id'],
      ['name' => 'Participant.' . $this->getCustomFieldName('radio', 4)],
    ]);
    $dataSource = new CRM_Import_DataSource_CSV($this->userJobID);
    $row = $dataSource->getRow();
    $this->assertEquals('IMPORTED', $row['_status'], $row['_status_message']);
    $participant = Participant::get(FALSE)
      ->addWhere('id', '=', $row['_entity_id'])
      ->addSelect($this->getCustomFieldName('radio', 4))
      ->execute()->first();
    $this->assertEquals(TRUE, $participant[$this->getCustomFieldName('radio', 4)]);
    $row = $dataSource->getRow();
    $this->assertEquals('ERROR', $row['_status']);
    $this->assertEquals('Participant record not found for id 2', $row['_status_message']);
  }

  /**
   * Test that we cannot import to a template event.
   *
   * @throws \CRM_Core_Exception
   */
  public function testImportToTemplateEvent() :void {
    // When setting up for the test make sure the IDs match those in the csv.
    $this->assertEquals(1, $this->eventCreatePaid(['is_template' => TRUE])['id']);
    $this->assertEquals(3, $this->individualCreate());
    $this->importCSV('participant_with_event_id.csv', [
      ['name' => 'Participant.event_id'],
      ['name' => 'do_not_import'],
      ['name' => 'Participant.contact_id'],
      ['name' => 'Participant.fee_amount'],
      ['name' => 'do_not_import'],
      ['name' => 'Participant.fee_level'],
      ['name' => 'Participant.is_pay_later'],
      ['name' => 'Participant.role_id'],
      ['name' => 'Participant.source'],
      ['name' => 'Participant.status_id'],
      ['name' => 'Participant.register_date'],
      ['name' => 'do_not_import'],
      ['name' => 'do_not_import'],
    ]);
    $dataSource = new CRM_Import_DataSource_CSV($this->userJobID);
    $row = $dataSource->getRow();
    $this->assertEquals('ERROR', $row['_status']);
    $this->assertEquals('Missing required fields: Participant ID OR Event ID', $row['_status_message']);
  }

  /**
   * Test that imports work generally.
   *
   * @throws \CRM_Core_Exception
   */
  public function testImportParticipant() :void {
    $this->eventCreatePaid(['title' => 'Rain-forest Cup Youth Soccer Tournament']);
    $this->createCustomGroupWithFieldOfType(['extends' => 'Participant'], 'checkbox');
    $this->individualCreate(['external_identifier' => 'ref-77']);
    $this->importCSV('participant_with_ext_id.csv', [
      ['name' => 'Participant.event_id'],
      ['name' => 'do_not_import'],
      ['name' => 'Contact.external_identifier'],
      ['name' => 'Participant.fee_amount'],
      ['name' => 'Participant.fee_currency'],
      ['name' => 'Participant.fee_level'],
      ['name' => 'Participant.is_pay_later'],
      ['name' => 'Participant.role_id'],
      ['name' => 'Participant.source'],
      ['name' => 'Participant.status_id'],
      ['name' => 'Participant.register_date'],
      ['name' => 'do_not_import'],
      ['name' => 'Participant.' . $this->getCustomFieldName('checkbox', 4)],
    ]);
    $dataSource = new CRM_Import_DataSource_CSV($this->userJobID);
    $row = $dataSource->getRow();

    $participant = Participant::get(FALSE)
      ->addWhere('id', '=', $row['_entity_id'])
      ->addSelect('*', 'event_id.title', 'status_id:label', 'role_id:label')
      ->addSelect($this->getCustomFieldName('checkbox', 4))
      ->execute()->first();
    $this->assertEquals($row['event_title'], $participant['event_id.title']);
    $this->assertEquals($row['fee_amount'], $participant['fee_amount']);
    $this->assertEquals('Phoned up', $participant['source']);
    $this->assertEquals($row['participant_status'], $participant['status_id:label']);
    $this->assertEquals('2022-12-07 00:00:00', $participant['register_date']);
    $this->assertEquals(['Attendee', 'Volunteer'], $participant['role_id:label']);
    $this->assertEquals(0, $participant['is_pay_later']);
    $this->assertEquals(['P', 'M'], $participant[$this->getCustomFieldName('checkbox', 4)]);
  }

  /**
   * Test that imports work when skipping already-existing duplicates.
   *
   * @throws \CRM_Core_Exception
   */
  public function testImportParticipantSkipDuplicates() :void {
    $this->eventCreatePaid(['title' => 'Rain-forest Cup Youth Soccer Tournament']);
    $this->eventCreatePaid(['title' => 'Second event'], [], 'second');
    $contactID = $this->individualCreate(['external_identifier' => 'ref-77']);
    $this->createTestEntity('Participant', [
      'event_id' => $this->ids['Event']['PaidEvent'],
      'contact_id' => $contactID,
    ]);
    $this->importCSV('participant_with_ext_id.csv', [
      ['name' => 'Participant.event_id'],
      ['name' => 'do_not_import'],
      ['name' => 'Contact.external_identifier'],
      ['name' => 'Participant.fee_amount'],
      ['name' => 'Participant.fee_currency'],
      ['name' => 'Participant.fee_level'],
      ['name' => 'Participant.is_pay_later'],
      ['name' => 'Participant.role_id'],
      ['name' => 'Participant.source'],
      ['name' => 'Participant.status_id'],
      ['name' => 'Participant.register_date'],
      ['name' => 'do_not_import'],
      ['name' => 'do_not_import'],
    ], ['onDuplicate' => CRM_Import_Parser::DUPLICATE_SKIP]);
    $dataSource = new CRM_Import_DataSource_CSV($this->userJobID);
    $row = $dataSource->getRow();
    $this->assertEquals('DUPLICATE', $row['_status'], $row['_status_message']);
    $row = $dataSource->getRow();
    $this->assertEquals('IMPORTED', $row['_status']);
    $participants = Participant::get()
      ->addWhere('contact_id', '=', $contactID)
      ->addSelect('event_id.title')
      ->addOrderBy('id')
      ->execute();
    $this->assertCount(2, $participants);
    $participant = $participants->first();
    $this->assertEquals('Rain-forest Cup Youth Soccer Tournament', $participant['event_id.title']);
    $participant = $participants->last();
    $this->assertEquals('Second event', $participant['event_id.title']);
  }

  /**
   * Test that imports work when ignoring (duplicating) already-existing duplicates.
   *
   * @throws \CRM_Core_Exception
   */
  public function testImportParticipantIgnoreDuplicates() :void {
    $this->eventCreatePaid(['title' => 'Rain-forest Cup Youth Soccer Tournament']);
    $this->eventCreatePaid(['title' => 'Second event'], [], 'second');
    $contactID = $this->individualCreate(['external_identifier' => 'ref-77']);
    $this->createTestEntity('Participant', [
      'event_id' => $this->ids['Event']['PaidEvent'],
      'contact_id' => $contactID,
    ]);
    $this->importCSV('participant_with_ext_id.csv', [
      ['name' => 'Participant.event_id'],
      ['name' => 'do_not_import'],
      ['name' => 'Contact.external_identifier'],
      ['name' => 'Participant.fee_amount'],
      ['name' => 'Participant.fee_currency'],
      ['name' => 'Participant.fee_level'],
      ['name' => 'Participant.is_pay_later'],
      ['name' => 'Participant.role_id'],
      ['name' => 'Participant.source'],
      ['name' => 'Participant.status_id'],
      ['name' => 'Participant.register_date'],
      ['name' => 'do_not_import'],
      ['name' => 'do_not_import'],
    ], ['onDuplicate' => CRM_Import_Parser::DUPLICATE_NOCHECK]);
    $dataSource = new CRM_Import_DataSource_CSV($this->userJobID);
    $row = $dataSource->getRow();
    $this->assertEquals('IMPORTED', $row['_status'], $row['_status_message']);
    $row = $dataSource->getRow();
    $this->assertEquals('IMPORTED', $row['_status']);
    $participant = Participant::get()
      ->addWhere('contact_id', '=', $contactID)
      ->addSelect('event_id.title')
      ->execute();
    $this->assertCount(3, $participant);
  }

  /**
   * Test import parser match a contact using the dedupe rule with a custom field.
   *
   * It should match the created contact based on first name & custom field.
   */
  public function testImportWithCustomDedupeRule(): void {
    $this->eventCreateUnpaid(['title' => 'Rain-forest Cup Youth Soccer Tournament']);
    $this->addToDedupeRule();
    // Setting this rule to not reserved is a bit artificial, although it does happen
    // in the wild. The goal is to demonstrate that when we expose arbitrary dedupe
    // rules it works, plus to ensure the code tidy up does not go backwards.
    // We are already testing what was previously tested - ie contact_id,
    // external_identifier or email, (email is the limit of the reserved
    // un-supervised rule).
    DedupeRuleGroup::update(FALSE)
      ->addWhere('id', '=', $this->ids['DedupeRule']['unsupervised'])
      ->setValues(['is_reserved' => FALSE])
      ->execute();

    $this->individualCreate([$this->getCustomFieldName() => 'secret code', 'first_name' => 'Bob', 'last_name' => 'Smith'], 'bob');
    $this->importCSV('participant_with_dedupe_match.csv', [
      ['name' => 'Participant.event_id'],
      ['name' => 'Contact.first_name'],
      ['name' => 'Contact.last_name'],
      ['name' => 'Contact.' . $this->getCustomFieldName('text', 4)],
      ['name' => 'Participant.role_id'],
      ['name' => 'Participant.status_id'],
      ['name' => 'Participant.register_date'],
    ]);
    $participant = Participant::get(FALSE)
      ->addWhere('contact_id', '=', $this->ids['Contact']['bob'])
      ->execute()->first();
    $this->assertEquals($this->ids['Event']['event'], $participant['event_id']);
  }

  /**
   * Test that one of the following is enough
   *  - contact_id + event_id + status_id
   *  - external_identifier + event_id + status_id
   *  - email_primary.email + event_id + status_id
   *
   * @dataProvider requiredFields
   */
  public function testRequiredFields(array $dataProvider): void {
    $this->eventCreateUnpaid(['title' => 'Rain-forest Cup Youth Soccer Tournament']);
    $this->individualCreate(['external_identifier' => 'abc', 'email' => 'jenny@example.com']);
    $mapper = [
      ['name' => 'Participant.event_id'],
      ['name' => 'Participant.id'],
      ['name' => 'Participant.contact_id'],
      ['name' => 'Contact.external_identifier'],
      ['name' => 'Contact.email_primary.email'],
      ['name' => 'Participant.status_id'],
    ];
    foreach ($mapper as $index => $field) {
      if (!in_array($field['name'], $dataProvider)) {
        $mapper[$index]['name'] = 'do_not_import';
      }
    }

    $this->importCSV('participant_with_multiple_identifiers.csv', $mapper, ['onDuplicate' => CRM_Import_Parser::DUPLICATE_NOCHECK, 'saveMapping' => FALSE]);
    $dataSource = new CRM_Import_DataSource_CSV($this->userJobID);
    $row = $dataSource->getRow();
    $this->assertEquals('IMPORTED', $row['_status']);
  }

  public function requiredFields(): array {
    return [
      'contact_id' => [['Participant.contact_id', 'Participant.status_id', 'Participant.event_id']],
      'external_identifier' => [['Contact.external_identifier', 'Participant.status_id', 'Participant.event_id']],
      'email' => [['Contact.email_primary.email', 'Participant.status_id', 'Participant.event_id']],
    ];
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
