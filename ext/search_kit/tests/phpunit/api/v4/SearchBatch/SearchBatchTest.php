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

    // Sample contact subtypes
    $this->SaveTestRecords('ContactType', [
      'records' => [
        ['name' => $name . '_1', 'label' => $name . '_1'],
        ['name' => $name . '_2', 'label' => $name . '_2'],
      ],
      'defaults' => [
        'parent_id:name' => 'Individual',
      ],
    ]);

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
            'required' => TRUE,
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
    $this->assertEquals($display['id'], $userJob['search_display_id']);

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
    $this->assertFalse($getFields['first_name']['nullable']);
    $this->assertEquals('Import field: Your Name', $getFields['first_name']['title']);
    $this->assertEquals('Integer', $getFields['gender_id']['data_type']);
    $this->assertContains('Male', $getFields['gender_id']['options']);
    $this->assertContains('Parent', $getFields['contact_sub_type']['options']);
    $this->assertFalse($getFields['contact_sub_type']['required']);
    $this->assertTrue($getFields['contact_sub_type']['nullable']);
    $this->assertEquals(1, $getFields['contact_sub_type']['serialize']);
    $this->assertEquals('Date', $getFields['birth_date']['data_type']);
    $this->assertEquals('Boolean', $getFields['is_deceased']['data_type']);
    $this->assertEquals('Integer', $getFields['_id']['data_type']);
    $this->assertEquals('Individual', $getFields['_entity_id']['fk_entity']);

    // The table was initialized with one empty row. Now create another.
    $created = civicrm_api4($apiName, 'create')->single();
    $this->assertEquals(2, $created['_id']);

    $created = civicrm_api4($apiName, 'get', ['where' => [['_id', '=', 2]]])->single();
    $this->assertNull($created['first_name']);
    $this->assertNull($created['gender_id']);
    $this->assertNull($created['contact_sub_type']);
    $this->assertNull($created['birth_date']);

    // And another
    civicrm_api4($apiName, 'create', [
      'values' => [
        'first_name' => 'Test',
        'gender_id' => 1,
        'birth_date' => '2019-01-01',
        'is_deceased' => FALSE,
        'contact_sub_type' => [$name . '_1', $name . '_2'],
      ],
    ]);

    $rows = civicrm_api4($apiName, 'get');
    $this->assertEquals([1, 2, 3], $rows->column('_id'));
    $this->assertEquals(['NEW', 'NEW', 'NEW'], $rows->column('_status'));
    $this->assertNULL($rows[0]['_entity_id']);
    $this->assertEquals([$name . '_1', $name . '_2'], $rows[2]['contact_sub_type']);

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

  /**
   * Ensure pseudo-fields like Activity.target_contact_id work with import batches.
   *
   * @see https://lab.civicrm.org/dev/core/-/work_items/6639
   *
   * Activity.target_contact_id and Activity.assignee_contact_id are API-only
   * pseudo-fields that live only in ActivitySpecProvider, not in the schema
   * entity definition.  This ensures they are handled correctly.
   */
  public function testActivityTargetContactBatch(): void {
    $name = uniqid(__FUNCTION__);

    // Create two contacts to use as target / assignee.
    [$cid1, $cid2] = $this->saveTestRecords('Contact', [
      'records' => [
        ['first_name' => 'Target', 'last_name' => $name],
        ['first_name' => 'Assignee', 'last_name' => $name],
      ],
    ])->column('id');

    $savedSearch = $this->createTestRecord('SavedSearch', [
      'name' => $name,
      'label' => 'Activity Batch - target_contact_id regression',
      'api_entity' => 'Activity',
      'api_params' => [
        'version' => 4,
        'select' => [
          'activity_type_id:label',
          'subject',
          'source_contact_id',
          'target_contact_id',
          'assignee_contact_id',
        ],
        'orderBy' => [],
        'where' => [],
      ],
    ]);

    $display = $this->createTestRecord('SearchDisplay', [
      'name' => $name,
      'label' => 'Activity Batch - target_contact_id regression',
      'saved_search_id.name' => $name,
      'type' => 'batch',
      'settings' => [
        'columns' => [
          [
            'type' => 'field',
            'key' => 'activity_type_id:label',
            'label' => 'Activity Type',
            'required' => TRUE,
          ],
          [
            'type' => 'field',
            'key' => 'subject',
            'label' => 'Subject',
          ],
          [
            'type' => 'field',
            'key' => 'source_contact_id',
            'label' => 'Source Contact',
            'required' => TRUE,
          ],
          [
            'type' => 'field',
            'key' => 'target_contact_id',
            'label' => 'Target Contacts',
          ],
          [
            'type' => 'field',
            'key' => 'assignee_contact_id',
            'label' => 'Assignee Contacts',
          ],
        ],
      ],
    ]);

    // Part 1: verify that BatchDisplaySubscriber correctly stored the spec.
    $savedDisplay = \Civi\Api4\SearchDisplay::get(FALSE)
      ->addWhere('name', '=', $name)
      ->execute()->single();

    $columnsByKey = array_column($savedDisplay['settings']['columns'], NULL, 'key');

    $targetSpec = $columnsByKey['target_contact_id']['spec'] ?? [];
    $this->assertEquals(
      \CRM_Core_DAO::SERIALIZE_COMMA,
      $targetSpec['serialize'] ?? NULL,
      'target_contact_id column spec must have serialize set; without the fix it is NULL'
    );
    $this->assertEquals(
      'EntityRef',
      $targetSpec['input_type'] ?? NULL,
      'target_contact_id column spec must have input_type=EntityRef; without the fix it is NULL'
    );
    $this->assertEquals(
      'Contact',
      $targetSpec['entity_reference']['entity'] ?? NULL,
      'target_contact_id column spec must reference Contact; without the fix entity_reference is NULL'
    );

    $assigneeSpec = $columnsByKey['assignee_contact_id']['spec'] ?? [];
    $this->assertEquals(
      \CRM_Core_DAO::SERIALIZE_COMMA,
      $assigneeSpec['serialize'] ?? NULL,
      'assignee_contact_id column spec must have serialize set; without the fix it is NULL'
    );
    $this->assertEquals(
      'EntityRef',
      $assigneeSpec['input_type'] ?? NULL,
      'assignee_contact_id column spec must have input_type=EntityRef; without the fix it is NULL'
    );

    // Part 2: end-to-end import – contact IDs must survive the round-trip.
    $userJob = SearchDisplay::createBatch(FALSE)
      ->setSavedSearch($name)
      ->setDisplay($name)
      ->execute()->single();
    $apiName = 'Import_' . $userJob['id'];

    // Meta::createSqlName() leaves these names unchanged, so the temp-table
    // columns are named identically to the original API fields.
    $targetColName = 'target_contact_id';
    $assigneeColName = 'assignee_contact_id';
    $this->assertNotNull(
      civicrm_api4($apiName, 'getFields', ['where' => [['name', '=', $targetColName]]])->first(),
      'Could not find temp-table column for target_contact_id'
    );

    // Update the pre-existing row (id=1) with all required Activity fields
    // plus the target/assignee contacts we want to import.
    civicrm_api4($apiName, 'update', [
      'where' => [['_id', '=', 1]],
      'values' => [
        'activity_type_id' => 1,
        'source_contact_id' => $cid1,
        $targetColName => [$cid1],
        $assigneeColName => [$cid2],
      ],
    ]);

    // Verify that the stored value is an array (not "Array").
    $stored = civicrm_api4($apiName, 'get', [
      'where' => [['_id', '=', 1]],
    ])->single();
    $this->assertIsArray(
      $stored[$targetColName],
      'target_contact_id value in temp table must be an array; without the fix it is the string "Array"'
    );
    $this->assertContains(
      $cid1,
      $stored[$targetColName],
      'The contact ID must be present in the stored target_contact_id array'
    );

    // Run the import and confirm the activity is created with target contacts.
    $import = civicrm_api4($apiName, 'import');
    $activityId = $import->first()['_entity_id'] ?? NULL;
    $importStatus = $import->first()['_status'];
    // If import encountered an error, fail with the status message to aid debugging.
    if ($importStatus !== 'IMPORTED') {
      $this->fail('Import failed with status "' . $importStatus . '": ' . ($import->first()['_status_message'] ?? 'no message'));
    }

    $activity = civicrm_api4('Activity', 'get', [
      'where' => [['id', '=', $activityId]],
      'select' => ['target_contact_id'],
    ])->single();

    $this->assertContains(
      $cid1,
      $activity['target_contact_id'],
      'Created activity must have the target contact set; without the fix target_contact_id is empty'
    );
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
          [
            'Email AS Contribution_Contact_contact_id_01_Email_01',
            'LEFT',
            [
              'Contribution_Contact_contact_id_01.id',
              '=',
              'Contribution_Contact_contact_id_01_Email_01.contact_id',
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
            'default' => '1',
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
      ->setRowCount(3)
      ->execute()->single();
    $apiName = 'Import_' . $userJob['id'];

    $fields = civicrm_api4($apiName, 'getFields', ['loadOptions' => TRUE])->indexBy('label');
    $fieldKeys = $fields->column('name');

    // Add another
    civicrm_api4($apiName, 'create', []);

    // Add another
    civicrm_api4($apiName, 'save', ['records' => [[]]]);

    // Ensure defaults have been filled
    $rows = civicrm_api4($apiName, 'get');
    $this->assertEquals([1, 2, 3, 4, 5], $rows->column('_id'));
    foreach ($rows as $row) {
      $this->assertEquals('1', $row[$fieldKeys['Financial Type']]);
      $this->assertNull($row[$fieldKeys['Total Amount']]);
      $this->assertNull($row[$fieldKeys['Contact First Name']]);
      $this->assertNull($row[$fieldKeys['Soft Credit Contact ID']]);
      $this->assertNull($row[$fieldKeys['Soft Credit Amount']]);
    }

    $this->assertContains('Female', $fields['Contact Gender']['options']);

    $softCreditContactId = $this->createTestRecord('Contact', ['first_name' => 'Softie', 'last_name' => 'Credit'])['id'];

    $lastName = uniqid(__FUNCTION__);

    // Add rows of data to import
    $newRows = civicrm_api4($apiName, 'replace', [
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
        [
          $fieldKeys['Total Amount'] => 300,
          $fieldKeys['Financial Type'] => 1,
          $fieldKeys['Contact First Name'] => 'Jin',
          $fieldKeys['Contact Last Name'] => $lastName,
          $fieldKeys['Soft Credit Contact ID'] => $softCreditContactId,
        ],
      ],
    ]);
    $this->assertCount(3, $newRows);

    $hookCalled = 0;
    $hookEntities = [];
    \CRM_Utils_Hook::singleton()->setHook('civicrm_importAlterMappedRow', function($importType, $context, &$mappedRow, $rowValues, $userJobID, $importEntities = NULL) use (&$hookCalled, &$hookEntities) {
      if ($context === 'import') {
        $hookCalled++;
        $hookEntities = $importEntities;
      }
    });

    $import = civicrm_api4($apiName, 'import');
    $this->assertGreaterThan(0, $hookCalled);
    $expectedEntities = [
      '' => [
        'entity' => 'Contribution',
        'join' => NULL,
      ],
      'Contribution_Contact_contact_id_01' => [
        'entity' => 'Contact',
        'join' => [],
      ],
      'Contribution_ContributionSoft_contribution_id_01' => [
        'entity' => 'ContributionSoft',
        'join' => [],
      ],
      'Contribution_Contact_contact_id_01_Email_01' => [
        'entity' => 'Email',
        'join' => ['Contribution_Contact_contact_id_01'],
      ],
    ];
    $this->assertEquals($expectedEntities, $hookEntities);
    $this->assertCount(3, $import);
    $this->assertEquals('IMPORTED', $import[0]['_status']);
    $this->assertEquals('IMPORTED', $import[1]['_status']);
    $this->assertEquals('IMPORTED', $import[2]['_status']);

    $contributions = civicrm_api4('Contribution', 'get', [
      'select' => ['id', 'financial_type_id', 'total_amount', 'contact_id.first_name', 'contact_id.gender_id'],
      'where' => [['contact_id.last_name', '=', $lastName]],
      'orderBy' => ['id' => 'ASC'],
    ]);
    $this->assertCount(3, $contributions);

    $this->assertEquals($import[0]['_entity_id'], $contributions[0]['id']);
    $this->assertEquals($import[1]['_entity_id'], $contributions[1]['id']);
    $this->assertEquals(100, $contributions[0]['total_amount']);
    $this->assertEquals(200, $contributions[1]['total_amount']);
    $this->assertEquals(300, $contributions[2]['total_amount']);
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

    $contributionSoft = civicrm_api4('ContributionSoft', 'get', [
      'select' => ['contact_id', 'amount', 'soft_credit_type_id:name'],
      'where' => [['contribution_id', '=', $contributions[2]['id']]],
    ])->single();
    // Not specified in 2nd contribution but implied by the total_amount
    $this->assertEquals(300, $contributionSoft['amount']);
  }

}
