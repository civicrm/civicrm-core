<?php
namespace api\v4\SearchDisplay;

// This is apparently necessary due to autoloader issues with test classes
require_once 'tests/phpunit/api/v4/Api4TestBase.php';
require_once 'tests/phpunit/api/v4/Custom/CustomTestBase.php';

use api\v4\Custom\CustomTestBase;
use Civi\Api4\Activity;
use Civi\Api4\Contact;
use Civi\Api4\CustomField;
use Civi\Api4\CustomGroup;
use Civi\Test\CiviEnvBuilder;

/**
 * @group headless
 */
class SearchRunWithCustomFieldTest extends CustomTestBase {

  public function setUpHeadless(): CiviEnvBuilder {
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  /**
   * Delete all created custom groups.
   *
   * @throws \CRM_Core_Exception
   */
  public function tearDown(): void {
    // Core bug: `civicrm_entity_file` doesn't get cleaned up when a contact is deleted,
    // so trying to delete `civicrm_file` causes a constraint violation :(
    // For now, truncate the table manually.
    $this->cleanup([
      'tablesToTruncate' => [
        'civicrm_entity_file',
      ],
    ]);
    // Now the file record can be auto-deleted
    parent::tearDown();
  }

  /**
   * Test running a searchDisplay with various filters.
   */
  public function testRunWithImageField() {
    CustomGroup::create(FALSE)
      ->addValue('title', 'TestSearchFields')
      ->addValue('extends', 'Individual')
      ->execute();

    CustomField::create(FALSE)
      ->addValue('label', 'MyFile')
      ->addValue('custom_group_id.name', 'TestSearchFields')
      ->addValue('html_type', 'File')
      ->addValue('data_type', 'File')
      ->execute();

    $lastName = uniqid(__FUNCTION__);

    $file = $this->createTestRecord('File', [
      'mime_type' => 'image/png',
      'uri' => "tmp/$lastName.png",
    ]);

    $sampleData = [
      ['first_name' => 'Zero', 'last_name' => $lastName, 'TestSearchFields.MyFile' => $file['id']],
      ['first_name' => 'One', 'middle_name' => 'None', 'last_name' => $lastName],
    ];
    $this->saveTestRecords('Contact', ['records' => $sampleData]);

    $params = [
      'checkPermissions' => FALSE,
      'return' => 'page:1',
      'savedSearch' => [
        'api_entity' => 'Contact',
        'api_params' => [
          'version' => 4,
          'select' => ['id', 'first_name', 'last_name', 'TestSearchFields.MyFile'],
          'where' => [['last_name', '=', $lastName]],
        ],
      ],
      'display' => [
        'type' => 'table',
        'label' => '',
        'settings' => [
          'limit' => 20,
          'pager' => TRUE,
          'columns' => [
            [
              'key' => 'id',
              'label' => 'Contact ID',
              'type' => 'field',
            ],
            [
              'key' => 'TestSearchFields.MyFile',
              'label' => 'Type',
              'type' => 'image',
              'empty_value' => 'http://example.com/image',
            ],
          ],
          'sort' => [
            ['id', 'ASC'],
          ],
        ],
      ],
    ];

    $result = civicrm_api4('SearchDisplay', 'run', $params);
    $this->assertStringContainsString('id=' . $file['id'], $result[0]['data']['TestSearchFields.MyFile']);
    $this->assertStringContainsString('id=' . $file['id'], $result[0]['columns'][1]['img']['src']);
    $this->assertEmpty($result[1]['data']['TestSearchFields.MyFile']);
    // Placeholder image
    $this->assertStringContainsString('example.com', $result[1]['columns'][1]['img']['src']);
  }

  public function testEditableRelationshipCustomFields() {

    CustomGroup::create(FALSE)
      ->addValue('title', 'TestChildFields')
      ->addValue('extends', 'Relationship')
      ->addValue('extends_entity_column_value:name', ['Child of'])
      ->addChain('fields', CustomField::create()
        ->addValue('custom_group_id', '$id')
        ->addValue('label', 'Child')
        ->addValue('html_type', 'Text')
      )
      ->execute();

    CustomGroup::create(FALSE)
      ->addValue('title', 'TestSpouseFields')
      ->addValue('extends', 'Relationship')
      ->addValue('extends_entity_column_value:name', ['Spouse of'])
      ->addChain('fields', CustomField::create()
        ->addValue('custom_group_id', '$id')
        ->addValue('label', 'Spouse')
        ->addValue('html_type', 'Text')
      )
      ->execute();

    $contacts = $this->saveTestRecords('Contact', [
      'records' => array_fill(0, 3, []),
    ]);
    // Reverse the contacts array so we don't get an accidental match of
    // sequential contact ids and relationship ids. This test needs to ensure
    // that the correct id is always being used.
    $contacts = array_column(array_reverse((array) $contacts), 'id');

    $spouse = $this->createTestRecord('Contact', ['first_name' => 's'])['id'];
    $child = $this->createTestRecord('Contact', ['first_name' => 'c'])['id'];

    $childRel = $this->createTestRecord('Relationship', [
      'contact_id_b' => $contacts[0],
      'contact_id_a' => $child,
      'relationship_type_id:name' => 'Child of',
      'TestChildFields.Child' => 'abc',
    ])['id'];
    $spouseRel = $this->createTestRecord('Relationship', [
      'contact_id_a' => $contacts[1],
      'contact_id_b' => $spouse,
      'relationship_type_id:name' => 'Spouse of',
      'description' => 'Married',
    ])['id'];

    $params = [
      'checkPermissions' => FALSE,
      'return' => 'page:1',
      'savedSearch' => [
        'api_entity' => 'Contact',
        'api_params' => [
          'version' => 4,
          'select' => [
            'first_name',
            'Relative.first_name',
            'Relative.description',
            'Relative.TestChildFields.Child',
            'Relative.TestSpouseFields.Spouse',
          ],
          'where' => [['id', 'IN', $contacts]],
          'join' => [
            [
              'Contact AS Relative',
              'LEFT',
              'RelationshipCache',
              ['id', '=', 'Relative.far_contact_id'],
            ],
          ],
        ],
      ],
      'display' => [
        'type' => 'table',
        'label' => '',
        'settings' => [
          'limit' => 20,
          'pager' => FALSE,
          'columns' => [
            [
              'key' => 'first_name',
              'label' => 'Name',
              'type' => 'field',
              'editable' => TRUE,
            ],
            [
              'key' => 'Relative.first_name',
              'label' => 'Child Name',
              'type' => 'field',
              'editable' => TRUE,
            ],
            [
              'key' => 'Relative.description',
              'label' => 'Relationship Description',
              'type' => 'field',
              'editable' => TRUE,
            ],
            [
              'key' => 'Relative.TestChildFields.Child',
              'label' => 'Child Custom',
              'type' => 'field',
              'editable' => TRUE,
            ],
            [
              'key' => 'Relative.TestSpouseFields.Spouse',
              'label' => 'Spouse Custom',
              'type' => 'field',
              'editable' => TRUE,
            ],
          ],
          'sort' => [
            // To match the reversed array of contacts
            ['id', 'DESC'],
          ],
        ],
      ],
    ];

    $result = civicrm_api4('SearchDisplay', 'run', $params);
    $this->assertCount(3, $result);
    // Editing first name - edit id should always match primary contact id
    $this->assertEquals($contacts[0], $result[0]['columns'][0]['edit']['record']['id']);
    $this->assertEquals($contacts[1], $result[1]['columns'][0]['edit']['record']['id']);
    $this->assertEquals($contacts[2], $result[2]['columns'][0]['edit']['record']['id']);

    // First contact has a child relation but not a spouse
    $this->assertEquals('c', $result[0]['columns'][1]['val']);
    $this->assertEquals($child, $result[0]['columns'][1]['edit']['record']['id']);
    $this->assertEquals('', $result[0]['columns'][2]['val']);
    $this->assertEquals($childRel, $result[0]['columns'][2]['edit']['record']['id']);
    $this->assertEquals('abc', $result[0]['columns'][3]['val']);
    $this->assertEquals($childRel, $result[0]['columns'][3]['edit']['record']['id']);
    $this->assertNull($result[0]['columns'][4]['val']);
    $this->assertArrayNotHasKey('edit', $result[0]['columns'][4]);

    // Second contact has a spouse relation but not a child
    $this->assertEquals('s', $result[1]['columns'][1]['val']);
    $this->assertEquals($spouse, $result[1]['columns'][1]['edit']['record']['id']);
    $this->assertEquals('Married', $result[1]['columns'][2]['val']);
    $this->assertEquals($spouseRel, $result[1]['columns'][2]['edit']['record']['id']);
    $this->assertNull($result[1]['columns'][3]['val']);
    $this->assertArrayNotHasKey('edit', $result[1]['columns'][3]);
    $this->assertNull($result[1]['columns'][4]['val']);
    $this->assertEquals($spouseRel, $result[1]['columns'][4]['edit']['record']['id']);

    // Third contact is all alone in this world...
    $this->assertNull($result[2]['columns'][1]['val']);
    $this->assertArrayNotHasKey('edit', $result[2]['columns'][1]);
    $this->assertNull($result[2]['columns'][2]['val']);
    $this->assertArrayNotHasKey('edit', $result[2]['columns'][2]);
    $this->assertNull($result[2]['columns'][3]['val']);
    $this->assertArrayNotHasKey('edit', $result[2]['columns'][3]);
    $this->assertNull($result[2]['columns'][4]['val']);
    $this->assertArrayNotHasKey('edit', $result[2]['columns'][4]);
  }

  public function testEditableCustomFields() {
    $subject = uniqid(__FUNCTION__);

    $contact = Contact::create(FALSE)
      ->execute()->single();

    // CustomGroup based on Activity Type
    CustomGroup::create(FALSE)
      ->addValue('extends', 'Activity')
      ->addValue('extends_entity_column_value:name', ['Meeting', 'Phone Call'])
      ->addValue('title', 'meeting_phone')
      ->addChain('field', CustomField::create()
        ->addValue('custom_group_id', '$id')
        ->addValue('label', 'sub_field')
        ->addValue('html_type', 'Text')
      )
      ->execute();

    $sampleData = [
      ['activity_type_id:name' => 'Meeting', 'meeting_phone.sub_field' => 'Abc'],
      ['activity_type_id:name' => 'Phone Call'],
      ['activity_type_id:name' => 'Email'],
    ];
    $activity = $this->saveTestRecords('Activity', [
      'defaults' => ['subject' => $subject, 'source_contact_id', $contact['id']],
      'records' => $sampleData,
    ]);

    $activityTypes = array_column(
      Activity::getFields(FALSE)->setLoadOptions(['id', 'name'])->addWhere('name', '=', 'activity_type_id')->execute()->single()['options'],
      'id',
      'name'
    );

    $params = [
      'checkPermissions' => FALSE,
      'return' => 'page:1',
      'savedSearch' => [
        'api_entity' => 'Activity',
        'api_params' => [
          'version' => 4,
          'select' => ['subject', 'meeting_phone.sub_field'],
          'where' => [['subject', '=', $subject]],
        ],
      ],
      'display' => [
        'type' => 'table',
        'label' => '',
        'settings' => [
          'actions' => TRUE,
          'pager' => [],
          'columns' => [
            [
              'key' => 'subject',
              'label' => 'First',
              'dataType' => 'String',
              'type' => 'field',
              'editable' => TRUE,
            ],
            [
              'key' => 'meeting_phone.sub_field',
              'label' => 'First',
              'dataType' => 'String',
              'type' => 'field',
              'editable' => TRUE,
            ],
          ],
          'sort' => [
            ['id', 'ASC'],
          ],
        ],
      ],
      'afform' => NULL,
    ];

    $result = civicrm_api4('SearchDisplay', 'run', $params);
    // Custom field editable
    $expectedCustomFieldEdit = [
      'entity' => 'Activity',
      'input_type' => 'Text',
      'data_type' => 'String',
      'options' => FALSE,
      'serialize' => FALSE,
      'nullable' => TRUE,
      'fk_entity' => NULL,
      'value_key' => 'meeting_phone.sub_field',
      'record' => ['id' => $activity[0]['id']],
      'action' => 'update',
      'value_path' => 'meeting_phone.sub_field',
    ];
    $expectedSubjectEdit = ['value_key' => 'subject', 'value_path' => 'subject'] + $expectedCustomFieldEdit;

    // First Activity
    $this->assertEquals($expectedSubjectEdit, $result[0]['columns'][0]['edit']);
    $this->assertEquals($expectedCustomFieldEdit, $result[0]['columns'][1]['edit']);
    $this->assertEquals($activityTypes['Meeting'], $result[0]['data']['activity_type_id']);

    // Second Activity
    $expectedSubjectEdit['record']['id'] = $activity[1]['id'];
    $expectedCustomFieldEdit['record']['id'] = $activity[1]['id'];
    $this->assertEquals($expectedSubjectEdit, $result[1]['columns'][0]['edit']);
    $this->assertEquals($expectedCustomFieldEdit, $result[1]['columns'][1]['edit']);
    $this->assertEquals($activityTypes['Phone Call'], $result[1]['data']['activity_type_id']);

    // Third Activity
    $expectedSubjectEdit['record']['id'] = $activity[2]['id'];
    $this->assertEquals($expectedSubjectEdit, $result[2]['columns'][0]['edit']);
    $this->assertTrue(!isset($result[2]['columns'][1]['edit']));
    $this->assertEquals($activityTypes['Email'], $result[2]['data']['activity_type_id']);
  }

  public function testMultiValuedFields():void {
    CustomGroup::create(FALSE)
      ->addValue('extends', 'Contact')
      ->addValue('title', 'my_test')
      ->addChain('field', CustomField::create()
        ->addValue('custom_group_id', '$id')
        ->addValue('label', 'my_field')
        ->addValue('html_type', 'Select')
        ->addValue('serialize', 1)
        ->addValue('option_values', ['zero', 'one', 'two', 'three'])
      )
      ->execute();

    $lastName = uniqid(__FUNCTION__);

    $sampleContacts = [
      ['first_name' => 'A', 'my_test.my_field' => [0, 2, 3]],
      ['first_name' => 'B', 'my_test.my_field' => [0]],
    ];
    Contact::save(FALSE)
      ->setRecords($sampleContacts)
      ->addDefault('last_name', $lastName)
      ->execute();

    $params = [
      'checkPermissions' => FALSE,
      'return' => 'page:1',
      'savedSearch' => [
        'api_entity' => 'Contact',
        'api_params' => [
          'version' => 4,
          'select' => ['first_name', 'last_name'],
          'where' => [['last_name', '=', $lastName]],
        ],
      ],
      'display' => [
        'type' => 'table',
        'label' => '',
        'settings' => [
          'columns' => [
            [
              'key' => 'first_name',
              'label' => 'First',
              'dataType' => 'String',
              'type' => 'field',
            ],
            [
              'key' => 'last_name',
              'label' => 'Last',
              'dataType' => 'String',
              'type' => 'field',
              'rewrite' => '[last_name] [my_test.my_field:label]',
            ],
          ],
          'sort' => [
            ['id', 'ASC'],
          ],
        ],
      ],
    ];

    $result = civicrm_api4('SearchDisplay', 'run', $params);

    $this->assertEquals(['zero', 'two', 'three'], $result[0]['data']['my_test.my_field:label']);
    $this->assertEquals("$lastName zero, two, three", $result[0]['columns'][1]['val']);
    $this->assertEquals(['zero'], $result[1]['data']['my_test.my_field:label']);
    $this->assertEquals("$lastName zero", $result[1]['columns'][1]['val']);
  }

  public function testEntityReferenceJoins() {
    CustomGroup::create()->setValues([
      'title' => 'EntityRefFields',
      'extends' => 'Individual',
    ])->execute();
    CustomField::create()->setValues([
      'label' => 'Favorite Nephew',
      'name' => 'favorite_nephew',
      'custom_group_id.name' => 'EntityRefFields',
      'html_type' => 'Autocomplete-Select',
      'data_type' => 'EntityReference',
      'fk_entity' => 'Contact',
    ])->execute();
    $nephewId = $this->createTestRecord('Contact', ['first_name' => 'Dewey', 'last_name' => 'Duck'])['id'];
    $uncleId = $this->createTestRecord('Contact', ['first_name' => 'Donald', 'last_name' => 'Duck', 'EntityRefFields.favorite_nephew' => $nephewId])['id'];
    $contact = Contact::get(FALSE)
      ->addSelect('first_name', 'EntityRefFields.favorite_nephew.first_name')
      ->addWhere('id', '=', $uncleId)
      ->execute()
      ->first();
    $this->assertEquals('Donald', $contact['first_name']);
    $this->assertEquals('Dewey', $contact['EntityRefFields.favorite_nephew.first_name']);

    $params = [
      'checkPermissions' => FALSE,
      'return' => 'page:1',
      'savedSearch' => [
        'api_entity' => 'Contact',
        'api_params' => [
          'version' => 4,
          'select' => ['EntityRefFields.favorite_nephew.first_name'],
          'where' => [['id', '=', $uncleId]],
        ],
        "join" => [
          [
            "Contact+AS+Contact_Contact_favorite_nephew_01",
            "LEFT",
            [
              "EntityRefFields.favorite_nephew",
              "=",
              "Contact_Contact_favorite_nephew_01.id",
            ],
          ],
        ],
      ],
    ];
    $result = civicrm_api4('SearchDisplay', 'run', $params);
    $this->assertEquals('Dewey', $result[0]['columns'][0]['val']);
  }

  public function testJoinWithCustomFieldEndingIn_() {
    $subject = uniqid(__FUNCTION__);

    $contact = Contact::create(FALSE)
      ->execute()->single();

    // CustomGroup based on Activity Type
    CustomGroup::create(FALSE)
      ->addValue('extends', 'Activity')
      ->addValue('title', 'testactivity2')
      ->addChain('field', CustomField::create()
        ->addValue('custom_group_id', '$id')
        ->addValue('label', 'testactivity_')
        ->addValue('data_type', 'Boolean')
        ->addValue('html_type', 'Radio')
      )
      ->execute();

    $sampleData = [
      ['activity_type_id:name' => 'Meeting', 'testactivity2.testactivity_' => TRUE],
    ];
    $this->saveTestRecords('Activity', [
      'defaults' => ['subject' => $subject, 'source_contact_id', $contact['id']],
      'records' => $sampleData,
    ]);

    $params = [
      'checkPermissions' => FALSE,
      'return' => 'page:1',
      'savedSearch' => [
        'api_entity' => 'Contact',
        'api_params' => [
          'version' => 4,
          'select' => [
            'id',
            'GROUP_CONCAT(DISTINCT Contact_ActivityContact_Activity_01.testactivity2.testactivity_:label) AS GROUP_CONCAT_Contact_ActivityContact_Activity_01_testactivity2_testactivity__label',
          ],
          'orderBy' => [],
          'where' => [['contact_type:name', '=', 'Individual']],
          'groupBy' => ['id'],
          'join' => [
            ['Activity AS Contact_ActivityContact_Activity_01', 'INNER', 'ActivityContact',
              ['id', '=', 'Contact_ActivityContact_Activity_01.contact_id'],
              ['Contact_ActivityContact_Activity_01.record_type_id:name', '=', '"Activity Source"'],
              ['Contact_ActivityContact_Activity_01.activity_type_id:name', '=', '"Meeting"'],
            ],
          ],
          'having' => [],
        ],
      ],
      'display' => [
        'type' => 'table',
        'label' => '',
        'settings' => [
          'actions' => TRUE,
          'pager' => [],
          'columns' => [
            [
              'type' => 'field',
              'key' => 'id',
              'dataType' => 'Integer',
              'label' => 'Contact ID',
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'GROUP_CONCAT_Contact_ActivityContact_Activity_01_testactivity2_testactivity__label',
              'dataType' => 'Boolean',
              'label' => '(List) Contact Activities: testactivity2: testactivity_',
              'sortable' => TRUE,
            ],
          ],
          'sort' => [
            ['id', 'ASC'],
          ],
        ],
      ],
      'afform' => NULL,
    ];

    $result = civicrm_api4('SearchDisplay', 'run', $params);

    $this->assertArrayHasKey('GROUP_CONCAT_Contact_ActivityContact_Activity_01_testactivity2_testactivity__label', $result[0]['data']);
    $this->assertEquals('Yes', $result[0]['data']['GROUP_CONCAT_Contact_ActivityContact_Activity_01_testactivity2_testactivity__label'][0]);
  }

}
