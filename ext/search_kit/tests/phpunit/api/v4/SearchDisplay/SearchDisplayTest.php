<?php
namespace api\v4\SearchDisplay;

use Civi\Api4\SavedSearch;
use Civi\Api4\SearchDisplay;
use Civi\Test\HeadlessInterface;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class SearchDisplayTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface, TransactionalInterface {
  use \Civi\Test\Api4TestTrait;

  public function setUpHeadless() {
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  public function testGetOptions(): void {
    $displayTypeOptions = \Civi\Api4\SearchDisplay::getFields(FALSE)
      ->setLoadOptions(['id', 'name', 'label', 'description', 'icon', 'grouping'])
      ->addWhere('name', '=', 'type')
      ->execute()
      ->first()['options'];

    $displayTypeOptions = array_column($displayTypeOptions, NULL, 'id');
    $this->assertEquals('non-viewable', $displayTypeOptions['autocomplete']['grouping']);
  }

  public function testGetDefault() {
    $params = [
      'api_entity' => 'Contact',
      'api_params' => [
        'version' => 4,
        'select' => ['first_name', 'display_name', 'contact_sub_type:label', 'gender_id'],
        'where' => [],
      ],
    ];
    $display = SearchDisplay::getDefault(FALSE)
      ->setSavedSearch($params)
      ->addSelect('*', 'saved_search_id.api_entity', 'saved_search_id.api_entity:label', 'type:name', 'type:icon')
      ->execute()->single();

    $this->assertCount(5, $display['settings']['columns']);
    $this->assertEquals('Contacts', $display['label']);
    $this->assertEquals('crm-search-display-table', $display['type:name']);
    $this->assertEquals('fa-table', $display['type:icon']);
    $this->assertEquals('Contact', $display['saved_search_id.api_entity']);
    $this->assertEquals('Contacts', $display['saved_search_id.api_entity:label']);

    // Default sort order should have been added
    $this->assertEquals([['sort_name', 'ASC']], $display['settings']['sort']);

    // `display_name` column should have a link
    $this->assertEquals('Contact', $display['settings']['columns'][1]['link']['entity']);
    $this->assertEquals('view', $display['settings']['columns'][1]['link']['action']);
    $this->assertEmpty($display['settings']['columns'][1]['link']['join']);

    // `first_name` column should not have a link
    $this->assertArrayNotHasKey('link', $display['settings']['columns'][0]);
  }

  public function testGetDefaultNoEntity() {
    $display = SearchDisplay::getDefault(FALSE)
      ->addSelect('*', 'saved_search_id', 'type:name', 'type:icon')
      ->execute()->single();

    $this->assertCount(0, $display['settings']['columns']);
    $this->assertEquals('', $display['label']);
    $this->assertEquals('crm-search-display-table', $display['type:name']);
    $this->assertEquals('fa-table', $display['type:icon']);
    $this->assertNull($display['saved_search_id']);
  }

  public function testGetSearchTasksRegisterEvent(): void {
    \CRM_Core_BAO_ConfigSetting::enableComponent('CiviEvent');

    $tasks = SearchDisplay::getSearchTasks(FALSE)
      ->setSavedSearch([
        'api_entity' => 'Contact',
        'api_params' => [
          'version' => 4,
          'select' => ['id'],
        ],
      ])
      ->setDisplay([
        'type' => 'table',
        'label' => __FUNCTION__,
        'settings' => [
          'actions' => TRUE,
        ],
      ])
      ->execute()->indexBy('name');

    $this->assertArrayNotHasKey('contact.' . \CRM_Contact_Task::ADD_EVENT, (array) $tasks, 'Old ADD_EVENT task should be excluded via redundant list');
    $this->assertArrayHasKey('event.register', (array) $tasks);
    $task = $tasks['event.register'];
    $this->assertSame('Register for Event', $task['title']);
    $this->assertSame('fa-ticket', $task['icon']);
    $this->assertNotEmpty($task['uiDialog']);
  }

  public function testRegisterEventEmptySourceSent(): void {
    \CRM_Core_BAO_ConfigSetting::enableComponent('CiviEvent');

    $contact = $this->createTestRecord('Contact', ['first_name' => 'Test', 'last_name' => 'EmptySource']);
    $event = $this->createTestRecord('Event', [
      'title' => 'Empty Source Event',
      'event_type_id' => 1,
      'start_date' => 'now + 1 month',
      'end_date' => 'now + 2 months',
    ]);

    // Save with empty source — optional fields are not stripped by JS
    $result = \Civi\Api4\Participant::save(FALSE)
      ->setMatch(['contact_id', 'event_id'])
      ->setDefaults([
        'status_id:name' => 'Registered',
        'role_id' => 1,
      ])
      ->addRecord([
        'contact_id' => $contact['id'],
        'event_id' => $event['id'],
        'source' => '',
      ])
      ->execute();

    $participant = $result->first();
    $this->assertNotEmpty($participant['id']);
    $this->assertArrayHasKey('source', $participant);
    $this->assertEquals('', $participant['source']);

    // Verify upsert: same contact+event returns same id (no duplicate created)
    $result2 = \Civi\Api4\Participant::save(FALSE)
      ->setMatch(['contact_id', 'event_id'])
      ->setDefaults([
        'status_id:name' => 'Registered',
        'role_id' => 1,
      ])
      ->addRecord([
        'contact_id' => $contact['id'],
        'event_id' => $event['id'],
        'source' => '',
      ])
      ->execute();
    $this->assertEquals($participant['id'], $result2->first()['id']);
  }

  public function testRegisterEventCustomField(): void {
    \CRM_Core_BAO_ConfigSetting::enableComponent('CiviEvent');

    $contact = $this->createTestRecord('Contact', ['first_name' => 'Test', 'last_name' => 'CustomField']);
    $event = $this->createTestRecord('Event', [
      'title' => 'Custom Field Event',
      'event_type_id' => 1,
      'start_date' => 'now + 1 month',
      'end_date' => 'now + 2 months',
    ]);

    $cgName = 'reg_test_' . uniqid();
    $customGroup = $this->createTestRecord('CustomGroup', [
      'name' => $cgName,
      'title' => 'Register Test',
      'extends' => 'Participant',
    ]);
    $this->createTestRecord('CustomField', [
      'custom_group_id.name' => $cgName,
      'label' => 'My Field',
      'name' => 'my_field',
      'html_type' => 'Text',
    ]);

    // Save with custom field value
    \Civi\Api4\Participant::save(FALSE)
      ->setMatch(['contact_id', 'event_id'])
      ->setDefaults([
        'status_id:name' => 'Registered',
        'role_id' => 1,
      ])
      ->addRecord([
        'contact_id' => $contact['id'],
        'event_id' => $event['id'],
        $cgName . '.my_field' => 'custom value',
      ])
      ->execute();

    // Verify custom field value persisted via get with explicit select
    $saved = \Civi\Api4\Participant::get(FALSE)
      ->addWhere('contact_id', '=', $contact['id'])
      ->addWhere('event_id', '=', $event['id'])
      ->addSelect('id', $cgName . '.my_field')
      ->execute()->first();
    $this->assertNotEmpty($saved['id']);
    $this->assertEquals('custom value', $saved[$cgName . '.my_field']);

    // Upsert preserves custom field value when not overwritten
    \Civi\Api4\Participant::save(FALSE)
      ->setMatch(['contact_id', 'event_id'])
      ->setDefaults([
        'status_id:name' => 'Registered',
        'role_id' => 1,
      ])
      ->addRecord(['contact_id' => $contact['id'], 'event_id' => $event['id']])
      ->execute();

    $saved2 = \Civi\Api4\Participant::get(FALSE)
      ->addWhere('contact_id', '=', $contact['id'])
      ->addWhere('event_id', '=', $event['id'])
      ->addSelect('id', $cgName . '.my_field')
      ->execute()->first();
    $this->assertEquals('custom value', $saved2[$cgName . '.my_field']);
  }

  public function testRegisterEventOverwritesRolesOnResave(): void {
    \CRM_Core_BAO_ConfigSetting::enableComponent('CiviEvent');

    $contact = $this->createTestRecord('Contact', ['first_name' => 'Test', 'last_name' => 'RoleOverwrite']);
    $event = $this->createTestRecord('Event', [
      'title' => 'Role Overwrite Event',
      'event_type_id' => 1,
      'start_date' => 'now + 1 month',
      'end_date' => 'now + 2 months',
    ]);

    // Register with multiple roles
    $result = \Civi\Api4\Participant::save(FALSE)
      ->setMatch(['contact_id', 'event_id'])
      ->setDefaults([
        'status_id:name' => 'Registered',
        'role_id' => [1, 2],
      ])
      ->addRecord(['contact_id' => $contact['id'], 'event_id' => $event['id']])
      ->execute();
    $participantId = $result->first()['id'];
    $this->assertNotEmpty($participantId);

    // Verify both roles saved
    $saved = \Civi\Api4\Participant::get(FALSE)
      ->addSelect('id', 'role_id')
      ->addWhere('id', '=', $participantId)
      ->execute()->first();
    $this->assertEquals([1, 2], $saved['role_id']);

    // Re-register with a single role — should overwrite, not merge
    \Civi\Api4\Participant::save(FALSE)
      ->setMatch(['contact_id', 'event_id'])
      ->setDefaults([
        'status_id:name' => 'Registered',
        'role_id' => [1],
      ])
      ->addRecord(['contact_id' => $contact['id'], 'event_id' => $event['id']])
      ->execute();

    $saved2 = \Civi\Api4\Participant::get(FALSE)
      ->addSelect('id', 'role_id')
      ->addWhere('id', '=', $participantId)
      ->execute()->first();
    $this->assertEquals([1], $saved2['role_id'], 'Roles should be overwritten, not appended');
  }

  public function testRegisterEventPermissionCheck(): void {
    \CRM_Core_BAO_ConfigSetting::enableComponent('CiviEvent');

    $basePerms = ['access CiviCRM', 'manage own search_kit'];
    $savedSearch = [
      'api_entity' => 'Contact',
      'api_params' => [
        'version' => 4,
        'select' => ['id'],
      ],
    ];
    $display = [
      'type' => 'table',
      'label' => __FUNCTION__,
      'settings' => [
        'actions' => TRUE,
      ],
    ];

    // Without 'edit event participants', task should be hidden
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = $basePerms;

    $tasks = SearchDisplay::getSearchTasks(TRUE)
      ->setSavedSearch($savedSearch)
      ->setDisplay($display)
      ->execute()->indexBy('name');

    $this->assertArrayNotHasKey('event.register', (array) $tasks);

    // With the permission, task should be visible
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = array_merge($basePerms, ['edit event participants']);

    $tasks2 = SearchDisplay::getSearchTasks(TRUE)
      ->setSavedSearch($savedSearch)
      ->setDisplay($display)
      ->execute()->indexBy('name');

    $this->assertArrayHasKey('event.register', (array) $tasks2);
  }

  public function testRegisterEventMultipleContacts(): void {
    \CRM_Core_BAO_ConfigSetting::enableComponent('CiviEvent');

    $contacts = $this->saveTestRecords('Contact', [
      'records' => 2,
      'defaults' => ['first_name' => 'Multi'],
    ]);
    $event = $this->createTestRecord('Event', [
      'title' => 'Multi Contact Event',
      'event_type_id' => 1,
      'start_date' => 'now + 1 month',
      'end_date' => 'now + 2 months',
    ]);

    // Simulate what the batch runner does: save each contact with same defaults
    $ids = [];
    foreach ($contacts as $contact) {
      $result = \Civi\Api4\Participant::save(FALSE)
        ->setMatch(['contact_id', 'event_id'])
        ->setDefaults([
          'status_id:name' => 'Registered',
          'role_id' => 1,
        ])
        ->addRecord(['contact_id' => $contact['id'], 'event_id' => $event['id']])
        ->execute();
      $ids[] = $result->first()['id'];
    }

    $this->assertCount(2, $ids);
    $this->assertNotEquals($ids[0], $ids[1], 'Each contact should get their own participant record');
  }

  public function testRegisterEventDefaultRoleFromEvent(): void {
    \CRM_Core_BAO_ConfigSetting::enableComponent('CiviEvent');

    $contact = $this->createTestRecord('Contact', ['first_name' => 'Test', 'last_name' => 'DefaultRole']);
    $event = $this->createTestRecord('Event', [
      'title' => 'Default Role Event',
      'event_type_id' => 1,
      'default_role_id' => 2,
      'start_date' => 'now + 1 month',
      'end_date' => 'now + 2 months',
    ]);

    // Save without role_id — event's default_role_id should apply via BAO
    $result = \Civi\Api4\Participant::save(FALSE)
      ->setMatch(['contact_id', 'event_id'])
      ->setDefaults([
        'status_id:name' => 'Registered',
      ])
      ->addRecord(['contact_id' => $contact['id'], 'event_id' => $event['id']])
      ->execute();

    $saved = \Civi\Api4\Participant::get(FALSE)
      ->addSelect('id', 'role_id')
      ->addWhere('id', '=', $result->first()['id'])
      ->execute()->first();
    $this->assertEquals([2], $saved['role_id']);
  }

  public function testRegisterEventDefaultStatusId(): void {
    \CRM_Core_BAO_ConfigSetting::enableComponent('CiviEvent');

    $contact = $this->createTestRecord('Contact', ['first_name' => 'Test', 'last_name' => 'DefaultStatus']);
    $event = $this->createTestRecord('Event', [
      'title' => 'Default Status Event',
      'event_type_id' => 1,
      'start_date' => 'now + 1 month',
      'end_date' => 'now + 2 months',
    ]);

    // Save without status_id — BAO should apply default
    $result = \Civi\Api4\Participant::save(FALSE)
      ->setMatch(['contact_id', 'event_id'])
      ->setDefaults([
        'role_id' => 1,
      ])
      ->addRecord(['contact_id' => $contact['id'], 'event_id' => $event['id']])
      ->execute();

    $saved = \Civi\Api4\Participant::get(FALSE)
      ->addSelect('id', 'status_id')
      ->addWhere('id', '=', $result->first()['id'])
      ->execute()->first();
    // Pending from cold (status_id 1) is the BAO default for new participants
    $this->assertNotNull($saved['status_id']);
    $this->assertEquals(1, $saved['status_id']);
  }

  public function testAutoFormatName() {
    // Create 3 saved searches; they should all get unique names
    $savedSearch0 = SavedSearch::create(FALSE)
      ->addValue('label', 'My test search')
      ->execute()->first();
    $savedSearch1 = SavedSearch::create(FALSE)
      ->addValue('label', 'My test search')
      ->execute()->first();
    $savedSearch2 = SavedSearch::create(FALSE)
      ->addValue('label', 'My test search')
      ->execute()->first();
    // Name will be created from munged label
    $this->assertEquals('My_test_search', $savedSearch0['name'], "SavedSearch 0");
    // Name will have _r appended to ensure it's unique, where r is a string of
    // random chars.
    $this->assertEquals('My_test_search_', substr($savedSearch1['name'], 0, 15), "SavedSearch 1");
    $this->assertEquals('My_test_search_', substr($savedSearch2['name'], 0, 15), "SavedSearch 2");
    $this->assertNotSame($savedSearch1['name'], $savedSearch2['name'], "SavedSearch 1,2");

    $display0 = SearchDisplay::create()
      ->addValue('saved_search_id', $savedSearch0['id'])
      ->addValue('label', 'My test display')
      ->addValue('type', 'table')
      ->execute()->first();
    $display1 = SearchDisplay::create()
      ->addValue('saved_search_id', $savedSearch0['id'])
      ->addValue('label', 'My test display')
      ->addValue('type', 'table')
      ->execute()->first();
    $display2 = SearchDisplay::create()
      ->addValue('saved_search_id', $savedSearch1['id'])
      ->addValue('label', 'My test display')
      ->addValue('type', 'table')
      ->execute()->first();
    // Name will be created from munged label
    $this->assertEquals('My_test_display', $display0['name'], "SearchDisplay 0");
    // Name will have _r appended (r is random string) to ensure it's unique to
    // savedSearch0.
    $this->assertEquals('My_test_display_', substr($display1['name'], 0, 16), "SearchDisplay 1");
    // This is for a different saved search so doesn't need a suffix appended
    $this->assertEquals('My_test_display', $display2['name'], "SearchDisplay 2");
  }

  public function testGetMarkup(): void {
    $savedSearch = $this->createTestRecord('SavedSearch', [
      'label' => 'testGetMarkup',
      'name' => 'testGetMarkup',
      'api_entity' => 'Individual',
      'api_params' => [
        'version' => 4,
      ],
    ]);
    $searchDisplay = $this->createTestRecord('SearchDisplay', [
      'label' => 'testGetMarkupDisplay',
      'name' => 'testGetMarkupDisplay',
      'saved_search_id' => $savedSearch['id'],
      'type' => 'list',
      'settings' => [
        'columns' => [
          [
            'key' => 'id',
            'rewrite' => '"[test] & <escape>"',
          ],
        ],
      ],
    ]);

    $result = SearchDisplay::getMarkup(FALSE)
      ->addWhere('id', '=', $searchDisplay['id'])
      ->addFilter('first_name', 'Fil')
      ->execute()->first();

    $this->assertEquals('crmSearchDisplayList', $result['module']);

    $expected = <<<MARKUP
    <crm-search-display-list search="'testGetMarkup'" display="'testGetMarkupDisplay'" api-entity="Individual" settings="{columns: [{key: 'id', rewrite: '&quot;[test] &amp; &lt;escape&gt;&quot;'}]}" filters="{first_name: 'Fil'}"></crm-search-display-list>
    MARKUP;
    $this->assertSame($expected, $result['markup']);
  }

}
