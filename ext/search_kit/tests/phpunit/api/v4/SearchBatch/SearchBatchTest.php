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
            'label' => 'First Name',
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
    $fieldNames = array_column($schema, 'Field');
    // Check fields ignoring order
    $this->assertEqualsCanonicalizing($expectedFieldNames, $fieldNames);

    // Check the table has indices _id and _status
    $indices = \CRM_Core_DAO::executeQuery('SHOW INDEX FROM ' . $tableName)->fetchAll();
    $indexNames = array_column($indices, 'Key_name');
    $this->assertEquals(['PRIMARY', '_id', '_status'], $indexNames);

    // Check api getFields
    $apiName = 'Import_' . $userJob['id'];
    $getFields = (array) civicrm_api4($apiName, 'getFields')
      ->indexBy('name');
    // Check fields ignoring order
    $this->assertEqualsCanonicalizing($expectedFieldNames, array_keys($getFields));

    // The table was initialized with one empty row. Now create another.
    $created = civicrm_api4($apiName, 'create')->single();
    $this->assertEquals(2, $created['_id']);

    $rows = civicrm_api4($apiName, 'get');
    $this->assertEquals([1, 2], $rows->column('_id'));
    $this->assertEquals(['NEW', 'NEW'], $rows->column('_status'));
    $this->assertNULL($rows[0]['_entity_id']);

    // Delete the first row
    civicrm_api4($apiName, 'delete', [
      'where' => [['_id', '=', 1]],
    ]);
    $rows = civicrm_api4($apiName, 'get');
    $this->assertEquals([2], $rows->column('_id'));
    $this->assertEquals(['NEW'], $rows->column('_status'));
  }

}
