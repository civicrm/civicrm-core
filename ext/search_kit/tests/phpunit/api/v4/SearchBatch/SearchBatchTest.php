<?php
namespace api\v4\SearchDisplay;

use Civi\Api4\SearchDisplay;
use Civi\Test\HeadlessInterface;

/**
 * @group headless
 */
class SearchBatchTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface {
  use \Civi\Test\Api4TestTrait;

  public function setUpHeadless() {
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->install('civiimport')
      ->apply();
  }

  public function testMetadata(): void {
    $name = uniqid();
    $savedSearch = $this->createTestRecord('SavedSearch', [
      'name' => $name,
      'label' => 'the_unit_test_search',
      'api_entity' => 'Individual',
      'api_params' => [
        'version' => 4,
        'select' => [
          'result_row_num',
          'first_name',
          'contact_sub_type:label',
          'gender_id:label',
          'birth_date',
          'is_deceased',
        ],
        'orderBy' => [],
        'where' => [],
      ],
    ]);
    $display = $this->createTestRecord('SearchDisplay', [
      'name' => $name,
      'label' => 'MyBatch',
      'saved_search_id.name' => $name,
      'type' => 'batch',
      'settings' => [
        'columns' => [
          [
            'type' => 'field',
            'key' => 'result_row_num',
            'label' => 'Row',
          ],
          [
            'type' => 'field',
            'key' => 'first_name',
            'label' => 'Your Name',
          ],
          [
            'type' => 'field',
            'key' => 'contact_sub_type:label',
            'label' => 'Subtype',
          ],
          [
            'type' => 'field',
            'key' => 'gender_id:label',
            'label' => 'Gender',
          ],
          [
            'type' => 'field',
            'key' => 'birth_date',
            'label' => 'Birth Date',
          ],
          [
            'type' => 'field',
            'key' => 'is_deceased',
            'label' => 'Is Deceased',
          ],
        ],
      ],
    ]);

    $userJob = SearchDisplay::createBatch(FALSE)
      ->setSavedSearch($name)
      ->setDisplay($name)
      ->execute()->single();

    $tableName = $userJob['metadata']['DataSource']['table_name'];

    $expectedFieldNames = ['_entity_id', '_status', '_status_message', '_id', 'first_name', 'contact_sub_type', 'gender_id', 'birth_date', 'is_deceased'];

    // Query the database schema to ensure the table has all columns defined
    $schema = \CRM_Core_DAO::executeQuery('DESCRIBE ' . $tableName)->fetchAll();
    $fields = array_column($schema, NULL, 'Field');
    // Check fields ignoring order
    $this->assertEqualsCanonicalizing($expectedFieldNames, array_keys($fields));
    $this->assertEquals('text', $fields['first_name']['Type']);
    // Fields with options are also text
    $this->assertEquals('text', $fields['gender_id']['Type']);
    $this->assertEquals('date', $fields['birth_date']['Type']);
    // Different sql databases report this type differently but they're all acceptable
    $this->assertContains($fields['is_deceased']['Type'], ['tinyint', 'boolean', 'tinyint(1)']);

    // Check the table has indices _id and _status
    $indices = \CRM_Core_DAO::executeQuery('SHOW INDEX FROM ' . $tableName)->fetchAll();
    $indexNames = array_column($indices, 'Key_name');
    $this->assertEquals(['PRIMARY', '_id', '_status'], $indexNames);

    // Check api getFields
    $apiName = 'Import_' . $userJob['id'];
    $getFields = (array) civicrm_api4($apiName, 'getFields', ['loadOptions' => TRUE])
      ->indexBy('name');
    // Check fields ignoring order
    $this->assertEqualsCanonicalizing($expectedFieldNames, array_keys($getFields));

    $this->assertEquals('Your Name', $getFields['first_name']['label']);
    $this->assertEquals('Import field: Your Name', $getFields['first_name']['title']);
    $this->assertEquals('Integer', $getFields['gender_id']['data_type']);
    $this->assertContains('Male', $getFields['gender_id']['options']);
    $this->assertContains('Parent', $getFields['contact_sub_type']['options']);
    $this->assertEquals('Date', $getFields['birth_date']['data_type']);
    $this->assertEquals('Boolean', $getFields['is_deceased']['data_type']);
    $this->assertEquals('Integer', $getFields['_id']['data_type']);
    $this->assertEquals('Individual', $getFields['_entity_id']['fk_entity']);

    // The table was initialized with one empty row. Now create another.
    $created = civicrm_api4($apiName, 'create')->single();
    $this->assertEquals(2, $created['_id']);

    // And another
    civicrm_api4($apiName, 'create', [
      'values' => [
        'first_name' => 'Test',
        'gender_id' => 1,
        'birth_date' => '2019-01-01',
        'is_deceased' => FALSE,
      ],
    ]);

    $rows = civicrm_api4($apiName, 'get');
    $this->assertEquals([1, 2, 3], $rows->column('_id'));
    $this->assertEquals(['NEW', 'NEW', 'NEW'], $rows->column('_status'));
    $this->assertNULL($rows[0]['_entity_id']);

    $run = civicrm_api4('SearchDisplay', 'runBatch', [
      'savedSearch' => $name,
      'display' => $name,
      'userJobId' => $userJob['id'],
    ]);
    $this->assertCount(3, $run);
    $editable = $run->editable;
    $this->assertEquals('Text', $editable['first_name']['input_type']);
    $this->assertEquals('Individual', $editable['first_name']['entity']);
    $this->assertEquals('Select', $editable['gender_id:label']['input_type']);
    $this->assertNotEmpty($editable['gender_id:label']['options']);
    $this->assertIsArray($editable['gender_id:label']['options']);
    $this->assertEquals('Select', $editable['contact_sub_type:label']['input_type']);
    $this->assertEquals('Date', $editable['birth_date']['input_type']);
    $this->assertEquals('CheckBox', $editable['is_deceased']['input_type']);

    $this->assertSame('Test', $run[2]['data']['first_name']);
    $this->assertSame(1, $run[2]['data']['gender_id']);
    $this->assertSame('2019-01-01', $run[2]['data']['birth_date']);
    $this->assertSame(FALSE, $run[2]['data']['is_deceased']);

    // Delete the first row
    civicrm_api4($apiName, 'delete', [
      'where' => [['_id', '=', 1]],
    ]);
    $rows = civicrm_api4($apiName, 'get');
    $this->assertEquals([2, 3], $rows->column('_id'));
    $this->assertEquals(['NEW', 'NEW'], $rows->column('_status'));
  }

}
