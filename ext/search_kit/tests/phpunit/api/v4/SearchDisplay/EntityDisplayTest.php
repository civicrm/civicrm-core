<?php

namespace api\v4\SearchDisplay;

// Not sure why this is needed but without it Jenkins crashed
require_once __DIR__ . '/../../../../../../../tests/phpunit/api/v4/Api4TestBase.php';

use api\v4\Api4TestBase;
use Civi\API\Exception\UnauthorizedException;
use Civi\Api4\SearchDisplay;
use Civi\Search\Admin;
use Civi\Test\CiviEnvBuilder;

/**
 * @group headless
 */
class EntityDisplayTest extends Api4TestBase {

  public function setUpHeadless(): CiviEnvBuilder {
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  public function testEntityDisplay() {
    $lastName = uniqid(__FUNCTION__);

    $this->saveTestRecords('Contact', [
      'records' => [
        ['last_name' => $lastName, 'first_name' => 'c', 'prefix_id:name' => 'Ms.'],
        ['last_name' => $lastName, 'first_name' => 'b', 'prefix_id:name' => 'Dr.'],
        ['last_name' => $lastName, 'first_name' => 'a'],
      ],
    ]);

    $savedSearch = $this->createTestRecord('SavedSearch', [
      'label' => __FUNCTION__,
      'api_entity' => 'Contact',
      'api_params' => [
        'version' => 4,
        'select' => ['id', 'first_name', 'last_name', 'prefix_id:label', 'created_date', 'modified_date'],
        'where' => [['last_name', '=', $lastName]],
      ],
    ]);

    $display = SearchDisplay::create(FALSE)
      ->addValue('saved_search_id', $savedSearch['id'])
      ->addValue('type', 'entity')
      ->addValue('label', 'MyNewEntity')
      ->addValue('name', 'MyNewEntity')
      ->addValue('settings', [
        'columns' => [
          [
            'key' => 'id',
            'label' => 'Contact ID',
            'type' => 'field',
          ],
          [
            'key' => 'first_name',
            'label' => 'First Name',
            'type' => 'field',
          ],
          [
            'key' => 'last_name',
            'label' => 'Last Name',
            'type' => 'field',
          ],
          [
            'key' => 'prefix_id:label',
            'label' => 'Prefix',
            'type' => 'field',
          ],
          [
            'key' => 'created_date',
            'label' => 'Created Date',
            'type' => 'field',
          ],
          [
            'key' => 'modified_date',
            'label' => 'Modified Date',
            'type' => 'field',
          ],
        ],
        'entity_permission' => ['view all contacts'],
        'sort' => [
          ['first_name', 'ASC'],
        ],
      ])
      ->execute()->first();

    $schema = \CRM_Core_DAO::executeQuery('DESCRIBE civicrm_sk_my_new_entity')->fetchAll();
    $this->assertCount(7, $schema);
    $this->assertEquals('_row', $schema[0]['Field']);
    $this->assertStringStartsWith('int', $schema[0]['Type']);
    $this->assertEquals('PRI', $schema[0]['Key']);

    // created_date and modified_date should be NULLable and have no default/extra
    $this->assertEmpty($schema[5]['Default']);
    $this->assertEmpty($schema[5]['Extra']);
    $this->assertEquals('YES', $schema[5]['Null']);
    $this->assertEmpty($schema[6]['Default']);
    $this->assertEmpty($schema[6]['Extra']);
    $this->assertEquals('YES', $schema[5]['Null']);

    $rows = \CRM_Core_DAO::executeQuery('SELECT * FROM civicrm_sk_my_new_entity')->fetchAll();
    $this->assertCount(0, $rows);

    $getFields = civicrm_api4('SK_MyNewEntity', 'getFields', ['loadOptions' => TRUE])->indexBy('name');
    $this->assertNotEmpty($getFields['prefix_id']['options'][1]);
    $this->assertSame('Integer', $getFields['id']['data_type']);
    $this->assertSame('EntityRef', $getFields['id']['input_type']);
    $this->assertSame('Contact', $getFields['id']['fk_entity']);
    $this->assertSame('String', $getFields['first_name']['data_type']);
    $this->assertSame('Text', $getFields['first_name']['input_type']);
    $this->assertNull($getFields['first_name']['fk_entity']);
    $this->assertSame('Integer', $getFields['prefix_id']['data_type']);
    $this->assertSame('Select', $getFields['prefix_id']['input_type']);
    $this->assertNull($getFields['prefix_id']['fk_entity']);
    $this->assertSame('Timestamp', $getFields['created_date']['data_type']);
    $this->assertSame('Date', $getFields['created_date']['input_type']);
    $this->assertNull($getFields['created_date']['fk_entity']);

    civicrm_api4('SK_MyNewEntity', 'refresh');

    $rows = \CRM_Core_DAO::executeQuery('SELECT * FROM civicrm_sk_my_new_entity ORDER BY `_row`')->fetchAll();
    $this->assertCount(3, $rows);
    $this->assertEquals('a', $rows[0]['first_name']);
    $this->assertEquals('c', $rows[2]['first_name']);

    // Add a contact
    $this->createTestRecord('Contact', [
      'last_name' => $lastName,
      'first_name' => 'b2',
    ]);
    civicrm_api4('SK_MyNewEntity', 'refresh');

    \CRM_Core_Config::singleton()->userPermissionClass->permissions = ['view all contacts'];
    $rows = civicrm_api4('SK_MyNewEntity', 'get', [
      'select' => ['first_name', 'prefix_id:label'],
      'orderBy' => ['_row' => 'ASC'],
    ]);
    $this->assertCount(4, $rows);
    $this->assertEquals('a', $rows[0]['first_name']);
    $this->assertEquals('Dr.', $rows[1]['prefix_id:label']);
    $this->assertEquals('b', $rows[1]['first_name']);
    $this->assertEquals('b2', $rows[2]['first_name']);
    $this->assertEquals('c', $rows[3]['first_name']);
    $this->assertEquals('Ms.', $rows[3]['prefix_id:label']);

    // Ensure entity_permission setting is enforced
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM'];
    try {
      $noRows = civicrm_api4('SK_MyNewEntity', 'get');
    }
    catch (UnauthorizedException $e) {
    }
    $this->assertStringContainsString('SK_MyNewEntity', $e->getMessage());
  }

  public function testEntityDisplayWithJoin() {

    $lastName = uniqid(__FUNCTION__);
    $contacts = (array) $this->saveTestRecords('Individual', [
      'records' => [
        ['last_name' => $lastName, 'first_name' => 'c'],
        ['last_name' => $lastName, 'first_name' => 'b'],
        ['last_name' => $lastName, 'first_name' => 'a'],
      ],
    ]);
    $event1 = $this->createTestRecord('Event', ['title' => __FUNCTION__]);
    $event2 = $this->createTestRecord('Event', ['title' => __FUNCTION__ . '2']);
    $participants = $this->saveTestRecords('Participant', [
      'records' => [
        ['contact_id' => $contacts[0]['id'], 'event_id' => $event1['id']],
        ['contact_id' => $contacts[1]['id'], 'event_id' => $event2['id']],
      ],
    ]);

    $savedSearch = $this->createTestRecord('SavedSearch', [
      'label' => __FUNCTION__,
      'api_entity' => 'Contact',
      'api_params' => [
        'version' => 4,
        'select' => ['id', 'Contact_Participant_contact_id_01.event_id', 'Contact_Participant_contact_id_01.id'],
        'where' => [['last_name', '=', $lastName]],
        'join' => [
          ['Participant AS Contact_Participant_contact_id_01', 'LEFT', ['id', '=', 'Contact_Participant_contact_id_01.contact_id']],
        ],
      ],
    ]);

    SearchDisplay::create(FALSE)
      ->addValue('saved_search_id', $savedSearch['id'])
      ->addValue('type', 'entity')
      ->addValue('label', 'MyNewEntityWithJoin')
      ->addValue('name', 'MyNewEntityWithJoin')
      ->addValue('settings', [
        // Additional column data will be filled in automatically
        // @see SKEntitySubscriber::formatFieldSpec
        'columns' => [
          [
            'key' => 'id',
            'label' => 'Contact ID',
            'type' => 'field',
          ],
          [
            'key' => 'Contact_Participant_contact_id_01.event_id',
            'label' => 'Event ID',
            'type' => 'field',
          ],
          [
            'key' => 'Contact_Participant_contact_id_01.id',
            'label' => 'Participant ID',
            'type' => 'field',
          ],
        ],
        'sort' => [
          ['id', 'ASC'],
        ],
      ])
      ->execute();

    $fields = civicrm_api4('SK_MyNewEntityWithJoin', 'getFields', [], 'name');
    $this->assertCount(4, $fields);
    $this->assertSame('Integer', $fields['id']['data_type']);
    $this->assertSame('EntityRef', $fields['id']['input_type']);
    $this->assertSame('Contact', $fields['id']['fk_entity']);
    $this->assertSame('Integer', $fields['Contact_Participant_contact_id_01_event_id']['data_type']);
    $this->assertSame('EntityRef', $fields['Contact_Participant_contact_id_01_event_id']['input_type']);
    $this->assertSame('Event', $fields['Contact_Participant_contact_id_01_event_id']['fk_entity']);
    $this->assertSame('Integer', $fields['Contact_Participant_contact_id_01_id']['data_type']);
    $this->assertSame('EntityRef', $fields['Contact_Participant_contact_id_01_id']['input_type']);
    $this->assertSame('Participant', $fields['Contact_Participant_contact_id_01_id']['fk_entity']);

    civicrm_api4('SK_MyNewEntityWithJoin', 'refresh');
    $rows = (array) civicrm_api4('SK_MyNewEntityWithJoin', 'get', [
      'select' => ['*', 'Contact_Participant_contact_id_01_event_id.title', 'id.first_name'],
      'orderBy' => ['_row' => 'ASC'],
    ]);
    $this->assertCount(3, $rows);
    $this->assertEquals(array_column($contacts, 'id'), array_column($rows, 'id'));
    $this->assertEquals(array_column($contacts, 'first_name'), array_column($rows, 'id.first_name'));
    $this->assertEquals($event1['id'], $rows[0]['Contact_Participant_contact_id_01_event_id']);
    $this->assertEquals($event2['id'], $rows[1]['Contact_Participant_contact_id_01_event_id']);
    $this->assertNull($rows[2]['Contact_Participant_contact_id_01_event_id']);
    $this->assertEquals($participants[0]['id'], $rows[0]['Contact_Participant_contact_id_01_id']);
    $this->assertEquals($participants[1]['id'], $rows[1]['Contact_Participant_contact_id_01_id']);
    $this->assertNull($rows[2]['Contact_Participant_contact_id_01_id']);
    $this->assertEquals(__FUNCTION__, $rows[0]['Contact_Participant_contact_id_01_event_id.title']);
    $this->assertEquals(__FUNCTION__ . '2', $rows[1]['Contact_Participant_contact_id_01_event_id.title']);

    // Ensure joins are picked up by SearchKit
    $allowedEntities = Admin::getSchema();
    $joins = Admin::getJoins($allowedEntities);

    // Check forward-joins
    $joinsFromEntity = $joins['SK_MyNewEntityWithJoin'];
    $expected = [
      'SK_MyNewEntityWithJoin_Contact_id',
      'SK_MyNewEntityWithJoin_Event_Contact_Participant_contact_id_01_event_id',
      'SK_MyNewEntityWithJoin_Participant_Contact_Participant_contact_id_01_id',
    ];
    $this->assertEquals($expected, array_column($joinsFromEntity, 'alias'));

    // Check reverse-joins
    $eventJoin = \CRM_Utils_Array::findAll($joins['Event'], ['entity' => 'SK_MyNewEntityWithJoin']);
    $this->assertCount(1, $eventJoin);
    $this->assertSame('Event_SK_MyNewEntityWithJoin_Contact_Participant_contact_id_01_event_id', $eventJoin[0]['alias']);
  }

  public function testEntityWithSqlFunctions(): void {
    $cids = $this->saveTestRecords('Individual', [
      'records' => [
        ['first_name' => 'A', 'last_name' => 'A'],
        ['first_name' => 'B', 'last_name' => 'B'],
        ['first_name' => 'C', 'last_name' => 'C'],
      ],
    ])
      ->column('id');

    $this->saveTestRecords('Contribution', [
      'records' => [
        ['contact_id' => $cids[0], 'total_amount' => 50, 'receive_date' => '2020-01-01', 'financial_type_id:name' => 'Donation'],
        ['contact_id' => $cids[1], 'total_amount' => 65, 'receive_date' => '2020-02-02', 'financial_type_id:name' => 'Member Dues'],
        ['contact_id' => $cids[0], 'total_amount' => 55, 'receive_date' => '2021-01-01', 'financial_type_id:name' => 'Campaign Contribution'],
        ['contact_id' => $cids[2], 'total_amount' => 175, 'receive_date' => '2022-01-01', 'financial_type_id:name' => 'Donation'],
      ],
    ]);

    $this->createTestRecord('SavedSearch', [
      'name' => 'Major_Donors_entity_test',
      'label' => 'Major Donors - entity test',
      'api_entity' => 'Contribution',
      'api_params' => [
        'version' => 4,
        'select' => [
          'YEAR(receive_date) AS YEAR_receive_date',
          'COUNT(id) AS COUNT_id',
          'GROUP_CONCAT(DISTINCT contact_id.sort_name) AS GROUP_CONCAT_contact_id_sort_name',
          'SUM(total_amount) AS SUM_total_amount',
          'GROUP_CONCAT(DISTINCT financial_type_id:label) AS GROUP_CONCAT_financial_type_id_label',
        ],
        'where' => [
          ['contact_id', 'IN', $cids],
        ],
        'groupBy' => [
          'YEAR(receive_date)',
        ],
        'having' => [
          [
            'SUM_total_amount',
            '>=',
            '100',
          ],
        ],
      ],
    ]);
    $this->createTestRecord('SearchDisplay', [
      'name' => 'MajorDonorsEntityTestDbEntity1',
      'label' => 'Major Donors - entity test DB Entity 1',
      'saved_search_id.name' => 'Major_Donors_entity_test',
      'type' => 'entity',
      'settings' => [
        'sort' => [
          ['YEAR_receive_date', 'ASC'],
        ],
        'columns' => [
          [
            'type' => 'field',
            'key' => 'YEAR_receive_date',
            'label' => 'Year',
          ],
          [
            'type' => 'field',
            'key' => 'COUNT_id',
            'label' => '(Count) Contribution ID',
          ],
          [
            'type' => 'field',
            'key' => 'GROUP_CONCAT_contact_id_sort_name',
            'label' => '(List) Contact Sort Name',
          ],
          [
            'type' => 'field',
            'key' => 'SUM_total_amount',
            'label' => '(Sum) Total Amount',
          ],
          [
            'type' => 'field',
            'key' => 'GROUP_CONCAT_financial_type_id_label',
            'label' => '(List) Financial Type',
          ],
        ],
      ],
    ]);

    // Validate schema
    $schema = \CRM_Core_DAO::executeQuery('DESCRIBE civicrm_sk_major_donors_entity_test_db_entity1')->fetchAll();
    $this->assertCount(6, $schema);
    $this->assertEquals('_row', $schema[0]['Field']);
    $this->assertStringStartsWith('int', $schema[0]['Type']);
    $this->assertStringStartsWith('int', $schema[1]['Type']);
    $this->assertStringStartsWith('int', $schema[2]['Type']);
    $this->assertStringStartsWith('text', $schema[3]['Type']);
    $this->assertStringStartsWith('decimal', $schema[4]['Type']);
    $this->assertStringStartsWith('text', $schema[3]['Type']);

    civicrm_api4('SK_MajorDonorsEntityTestDbEntity1', 'refresh');

    $fields = civicrm_api4('SK_MajorDonorsEntityTestDbEntity1', 'getFields', ['loadOptions' => TRUE], 'name');
    $this->assertCount(6, $fields);

    $this->assertSame('Integer', $fields['YEAR_receive_date']['data_type']);
    $this->assertSame('Number', $fields['YEAR_receive_date']['input_type']);
    $this->assertFalse($fields['YEAR_receive_date']['options']);

    $this->assertSame('Integer', $fields['COUNT_id']['data_type']);
    $this->assertSame('Number', $fields['COUNT_id']['input_type']);
    $this->assertFalse($fields['COUNT_id']['options']);

    $this->assertSame('Money', $fields['SUM_total_amount']['data_type']);
    $this->assertSame('Number', $fields['SUM_total_amount']['input_type']);
    $this->assertFalse($fields['SUM_total_amount']['options']);

    $this->assertSame('String', $fields['GROUP_CONCAT_contact_id_sort_name']['data_type']);
    $this->assertSame('Text', $fields['GROUP_CONCAT_contact_id_sort_name']['input_type']);
    $this->assertFalse($fields['GROUP_CONCAT_contact_id_sort_name']['options']);
    $this->assertEquals(\CRM_Core_DAO::SERIALIZE_SEPARATOR_TRIMMED, $fields['GROUP_CONCAT_contact_id_sort_name']['serialize']);

    $this->assertSame('Integer', $fields['GROUP_CONCAT_financial_type_id_label']['data_type']);
    $this->assertSame('Select', $fields['GROUP_CONCAT_financial_type_id_label']['input_type']);
    $this->assertContains('Donation', $fields['GROUP_CONCAT_financial_type_id_label']['options']);
    $this->assertEquals(\CRM_Core_DAO::SERIALIZE_SEPARATOR_TRIMMED, $fields['GROUP_CONCAT_financial_type_id_label']['serialize']);

    $result = civicrm_api4('SK_MajorDonorsEntityTestDbEntity1', 'get', [
      'select' => ['*', 'GROUP_CONCAT_financial_type_id_label:name'],
    ]);
    $this->assertCount(2, $result);
    $this->assertSame(2020, $result[0]['YEAR_receive_date']);
    $this->assertSame(115.0, $result[0]['SUM_total_amount']);
    $this->assertCount(2, $result[0]['GROUP_CONCAT_financial_type_id_label']);
    $this->assertTrue(is_int($result[0]['GROUP_CONCAT_financial_type_id_label'][0]));
    $this->assertTrue(is_int($result[0]['GROUP_CONCAT_financial_type_id_label'][1]));
    $this->assertEquals(['Donation', 'Member Dues'], $result[0]['GROUP_CONCAT_financial_type_id_label:name']);

    $this->assertSame(2022, $result[1]['YEAR_receive_date']);
    $this->assertSame(175.0, $result[1]['SUM_total_amount']);
    $this->assertCount(1, $result[1]['GROUP_CONCAT_financial_type_id_label']);
    $this->assertTrue(is_int($result[1]['GROUP_CONCAT_financial_type_id_label'][0]));
    $this->assertEquals(['Donation'], $result[1]['GROUP_CONCAT_financial_type_id_label:name']);
  }

}
