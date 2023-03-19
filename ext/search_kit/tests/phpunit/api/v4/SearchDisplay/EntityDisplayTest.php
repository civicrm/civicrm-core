<?php

namespace api\v4\SearchDisplay;

// Not sure why this is needed but without it Jenkins crashed
require_once __DIR__ . '/../../../../../../../tests/phpunit/api/v4/Api4TestBase.php';

use api\v4\Api4TestBase;
use Civi\Api4\SearchDisplay;
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
        'select' => ['id', 'first_name', 'last_name', 'prefix_id:label'],
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
        ],
        'sort' => [
          ['first_name', 'ASC'],
        ],
      ])
      ->execute()->first();

    $schema = \CRM_Core_DAO::executeQuery('DESCRIBE civicrm_sk_my_new_entity')->fetchAll();
    $this->assertCount(5, $schema);
    $this->assertEquals('_row', $schema[0]['Field']);
    $this->assertStringStartsWith('int', $schema[0]['Type']);
    $this->assertEquals('PRI', $schema[0]['Key']);

    $rows = \CRM_Core_DAO::executeQuery('SELECT * FROM civicrm_sk_my_new_entity')->fetchAll();
    $this->assertCount(0, $rows);

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
  }

}
