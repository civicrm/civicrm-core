<?php
namespace Civi\ActionSchedule;

use Civi\Api4\Contact;

/**
 * Test scheduled-communications based on SavedSearches.
 *
 * @group ActionSchedule
 * @see \Civi\ActionSchedule\AbstractMappingTest
 * @group headless
 */
class SavedSearchMappingTest extends AbstractMappingTest {

  protected $savedSearch = [];

  protected function setUp(): void {
    parent::setUp();
    \CRM_Extension_System::singleton()->getManager()->enable('scheduled_communications');
    $this->useHelloFirstName();
    $this->savedSearch = [
      'label' => __CLASS__,
      'api_params' => [
        'select' => [],
        'where' => [],
      ],
    ];
  }

  protected function tearDown(): void {
    parent::tearDown();
    $this->quickCleanup([], TRUE);
  }

  public function testContactBirthDate(): void {
    $this->targetDate = '2015-02-01 00:00:00';

    $this->savedSearch['api_entity'] = 'Contact';
    $this->savedSearch['api_params']['where'] = [
      ['id', 'IN', array_column($this->contacts, 'id')],
    ];

    $this->startWeekAfter();
    $this->setIdField('id');
    $this->setDateField('birth_date');
    Contact::save(FALSE)
      ->addRecord(['birth_date' => '20150201', 'id' => $this->contacts['alice']['id']])
      ->addRecord(['birth_date' => '20150202', 'id' => $this->contacts['bob']['id']])
      // Deceased contact
      ->addRecord(['birth_date' => '20150202', 'id' => $this->contacts['edith']['id']])
      // Date too far in future
      ->addRecord(['birth_date' => '20160201', 'id' => $this->contacts['carol']['id']])
      // Francis email on hold
      ->addRecord(['birth_date' => '20150201', 'id' => $this->contacts['francis']['id']])
      // Do not mail dave
      ->addRecord(['birth_date' => '20150201', 'id' => $this->contacts['dave']['id']])
      ->execute();

    $this->runScheduleAndExpectMessages([
      [
        'time' => '2015-02-08 00:00:00',
        'to' => ['alice@example.org'],
        'subject' => '/Hello, Alice.*via subject/',
      ],
      [
        'time' => '2015-02-09 00:00:00',
        'to' => ['bob@example.org'],
        'subject' => '/Hello, Bob.*via subject/',
      ],
    ]);
  }

  public function testContactCustomDateField(): void {
    $customGroup = $this->customGroupCreate();
    $customField = $this->customFieldCreate([
      'custom_group_id' => $customGroup['id'],
      'data_type' => 'Date',
      'html_type' => 'Select Date',
      'default_value' => NULL,
    ]);
    $customFieldName = \CRM_Utils_Array::first($customGroup['values'])['name'] . '.' . \CRM_Utils_Array::first($customField['values'])['name'];

    $this->targetDate = '2015-02-01 00:00:00';

    $this->savedSearch['api_entity'] = 'Contact';

    $this->startOnTime();
    $this->setIdField('id');
    $this->setDateField($customFieldName);
    Contact::save(FALSE)
      ->addRecord([$customFieldName => '20150201', 'id' => $this->contacts['alice']['id']])
      ->execute();

    $this->runScheduleAndExpectMessages([
      [
        'time' => '2015-02-01 00:00:00',
        'to' => ['alice@example.org'],
        'subject' => '/Hello, Alice.*via subject/',
      ],
    ]);
  }

  public function testContactAsEntityReferenceField(): void {
    $customGroup = $this->customGroupCreate([
      'extends' => 'Activity',
    ]);
    $customField = $this->customFieldCreate([
      'custom_group_id' => $customGroup['id'],
      'html_type' => 'Autocomplete-Select',
      'data_type' => 'EntityReference',
      'fk_entity' => 'Contact',
      'default_value' => NULL,
    ]);
    $customFieldName = \CRM_Utils_Array::first($customGroup['values'])['name'] . '.' . \CRM_Utils_Array::first($customField['values'])['name'];

    $this->targetDate = '2015-02-01 00:00:00';

    $this->startOnTime();
    $this->setIdField($customFieldName);
    $this->setDateField('activity_date_time');
    $activity = $this->createTestEntity('Activity', [
      'activity_type_id:name' => 'Meeting',
      $customFieldName => $this->contacts['carol']['id'],
      'activity_date_time' => $this->targetDate,
      'source_contact_id' => $this->contacts['dave']['id'],
    ]);

    $this->savedSearch['api_entity'] = 'Activity';
    $this->savedSearch['api_params']['where'] = [
      ['id', '=', $activity['id']],
    ];

    $this->runScheduleAndExpectMessages([
      [
        'time' => '2015-02-01 00:00:00',
        'to' => ['carol@example.org'],
        'subject' => '/Hello, Carol.*via subject/',
      ],
    ]);
  }

  public function runScheduleAndExpectMessages(array $expectMessages): void {
    $savedSearch = $this->createTestEntity('SavedSearch', $this->savedSearch);
    $this->schedule->mapping_id = 'saved_search';
    $this->schedule->entity_value = $savedSearch['id'];
    parent::runScheduleAndExpectMessages($expectMessages);
  }

  protected function setIdField(string $fieldName): void {
    $this->schedule->entity_status = $fieldName;
    $this->savedSearch['api_params']['select'][] = $fieldName;
  }

  protected function setDateField(string $fieldName): void {
    $this->schedule->start_action_date = $fieldName;
    $this->savedSearch['api_params']['select'][] = $fieldName;
  }

  /**
   * Disable testDefault by returning no test cases
   */
  public function createTestCases() {
    return [];
  }

}
