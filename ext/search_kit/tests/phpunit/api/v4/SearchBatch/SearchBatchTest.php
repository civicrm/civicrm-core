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

  public function testImportBatch() {
    $name = uniqid();
    $savedSearch = $this->createTestRecord('SavedSearch', [
      'name' => $name,
      'label' => 'Contribution Batch Testing',
      'api_entity' => 'Contribution',
      'api_params' => [
        'version' => 4,
        'select' => [
          'total_amount',
          'financial_type_id:label',
          'Contribution_Contact_contact_id_01.first_name',
          'Contribution_Contact_contact_id_01.last_name',
          'Contribution_Contact_contact_id_01.gender_id:label',
          'Contribution_ContributionSoft_contribution_id_01.contact_id',
          'Contribution_ContributionSoft_contribution_id_01.amount',
        ],
        'orderBy' => [],
        'where' => [],
        'groupBy' => [],
        'join' => [
          [
            'Contact AS Contribution_Contact_contact_id_01',
            'LEFT',
            [
              'contact_id',
              '=',
              'Contribution_Contact_contact_id_01.id',
            ],
          ],
          [
            'ContributionSoft AS Contribution_ContributionSoft_contribution_id_01',
            'LEFT',
            [
              'id',
              '=',
              'Contribution_ContributionSoft_contribution_id_01.contribution_id',
            ],
            [
              'Contribution_ContributionSoft_contribution_id_01.soft_credit_type_id:name',
              '=',
              '"in_honor_of"',
            ],
          ],
        ],
        'having' => [],
      ],
    ]);

    $display = $this->createTestRecord('SearchDisplay', [
      'name' => $name,
      'label' => 'Contribution Batch Testing',
      'saved_search_id.name' => $name,
      'type' => 'batch',
      'settings' => [
        'classes' => [
          'table',
          'table-striped',
          'table-bordered',
          'crm-sticky-header',
        ],
        'limit' => 50,
        'pager' => [
          'hide_single' => TRUE,
        ],
        'columns' => [
          [
            'type' => 'field',
            'key' => 'total_amount',
            'label' => 'Total Amount',
            'tally' => [
              'fn' => 'SUM',
            ],
          ],
          [
            'type' => 'field',
            'key' => 'financial_type_id:label',
            'label' => 'Financial Type',
          ],
          [
            'type' => 'field',
            'key' => 'Contribution_Contact_contact_id_01.first_name',
            'label' => 'Contact First Name',
          ],
          [
            'type' => 'field',
            'key' => 'Contribution_Contact_contact_id_01.last_name',
            'label' => 'Contact Last Name',
          ],
          [
            'type' => 'field',
            'key' => 'Contribution_Contact_contact_id_01.gender_id:label',
            'label' => 'Contact Gender',
          ],
          [
            'type' => 'field',
            'key' => 'Contribution_ContributionSoft_contribution_id_01.contact_id',
            'label' => 'Soft Credit Contact ID',
          ],
          [
            'type' => 'field',
            'key' => 'Contribution_ContributionSoft_contribution_id_01.amount',
            'label' => 'Soft Credit Amount',
          ],
        ],
        'tally' => [],
      ],
      'acl_bypass' => FALSE,
    ]);

    $userJob = SearchDisplay::createBatch(FALSE)
      ->setSavedSearch($name)
      ->setDisplay($name)
      ->execute()->single();
    $apiName = 'Import_' . $userJob['id'];

    $fields = civicrm_api4($apiName, 'getFields', ['loadOptions' => TRUE])->indexBy('label');

    $fieldKeys = $fields->column('name');

    $this->assertContains('Female', $fields['Contact Gender']['options']);

    $softCreditContactId = $this->createTestRecord('Contact', ['first_name' => 'Softie', 'last_name' => 'Credit'])['id'];

    $lastName = uniqid(__FUNCTION__);

    // Add rows of data to import
    civicrm_api4($apiName, 'replace', [
      'where' => [['_id', '>', 0]],
      'records' => [
        [
          $fieldKeys['Total Amount'] => 100,
          $fieldKeys['Financial Type'] => 1,
          $fieldKeys['Contact First Name'] => 'Jane',
          $fieldKeys['Contact Last Name'] => $lastName,
          $fieldKeys['Contact Gender'] => 1,
          $fieldKeys['Soft Credit Contact ID'] => $softCreditContactId,
          $fieldKeys['Soft Credit Amount'] => 10,
        ],
        [
          $fieldKeys['Total Amount'] => 200,
          $fieldKeys['Financial Type'] => 2,
          $fieldKeys['Contact First Name'] => 'John',
          $fieldKeys['Contact Last Name'] => $lastName,
          $fieldKeys['Contact Gender'] => 2,
        ],
      ],
    ]);

    $import = civicrm_api4($apiName, 'import');
    $this->assertCount(2, $import);
    $this->assertEquals('IMPORTED', $import[0]['_status']);
    $this->assertEquals('IMPORTED', $import[1]['_status']);

    $contributions = civicrm_api4('Contribution', 'get', [
      'select' => ['id', 'financial_type_id', 'total_amount', 'contact_id.first_name', 'contact_id.gender_id'],
      'where' => [['contact_id.last_name', '=', $lastName]],
      'orderBy' => ['id' => 'ASC'],
    ]);
    $this->assertCount(2, $contributions);

    $this->assertEquals($import[0]['_entity_id'], $contributions[0]['id']);
    $this->assertEquals($import[1]['_entity_id'], $contributions[1]['id']);
    $this->assertEquals(100, $contributions[0]['total_amount']);
    $this->assertEquals(200, $contributions[1]['total_amount']);
    $this->assertEquals(1, $contributions[0]['financial_type_id']);
    $this->assertEquals(2, $contributions[1]['financial_type_id']);
    $this->assertEquals('Jane', $contributions[0]['contact_id.first_name']);
    $this->assertEquals('John', $contributions[1]['contact_id.first_name']);
    $this->assertEquals(1, $contributions[0]['contact_id.gender_id']);
    $this->assertEquals(2, $contributions[1]['contact_id.gender_id']);

    $contributionSoft = civicrm_api4('ContributionSoft', 'get', [
      'select' => ['contact_id', 'amount', 'soft_credit_type_id:name'],
      'where' => [['contribution_id', '=', $contributions[0]['id']]],
    ])->single();
    $this->assertEquals($softCreditContactId, $contributionSoft['contact_id']);
    $this->assertEquals(10, $contributionSoft['amount']);
    // This value was set in the ON clause & should have carried through
    $this->assertEquals('in_honor_of', $contributionSoft['soft_credit_type_id:name']);
  }

}
