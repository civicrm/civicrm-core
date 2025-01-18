<?php
namespace Civi\ext\search_kit\tests\phpunit\api\v4\SearchDisplay;

// Not sure why this is needed but without it Jenkins crashed
require_once __DIR__ . '/../../../../../../../tests/phpunit/api/v4/Api4TestBase.php';

use api\v4\Api4TestBase;
use Civi\Api4\Activity;
use Civi\Api4\Email;
use Civi\Api4\SearchDisplay;
use Civi\Test\CiviEnvBuilder;

/**
 * @group headless
 */
class EditableSearchTest extends Api4TestBase {
  use \Civi\Test\ACLPermissionTrait;

  public function setUpHeadless(): CiviEnvBuilder {
    // See: https://docs.civicrm.org/dev/en/latest/testing/phpunit/#civitest
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  /**
   * Test in-place editable for update and create.
   */
  public function testInPlaceEditAndCreate() {
    $lastName = uniqid(__FUNCTION__);
    $sampleData = [
      ['first_name' => 'One', 'last_name' => $lastName],
      ['last_name' => $lastName, 'gender_id' => 1],
    ];
    $cids = $this->saveTestRecords('Contact', [
      'records' => $sampleData,
    ])->column('id');
    $emailId = $this->createTestRecord('Email', [
      'contact_id' => $cids[0],
      'email' => 'testmail@unit.test',
    ])['id'];
    $phoneId = $this->createTestRecord('Phone', [
      'contact_id' => $cids[1],
      'phone' => '123456',
    ])['id'];

    $params = [
      'checkPermissions' => FALSE,
      'return' => 'page:1',
      'seed' => 123,
      'savedSearch' => [
        'api_entity' => 'Contact',
        'api_params' => [
          'version' => 4,
          'select' => ['first_name', 'Contact_Email_contact_id_01.email', 'Contact_Phone_contact_id_01.phone'],
          'where' => [['last_name', '=', $lastName]],
          'join' => [
            [
              "Email AS Contact_Email_contact_id_01",
              "LEFT",
              ["id", "=", "Contact_Email_contact_id_01.contact_id"],
              ["Contact_Email_contact_id_01.is_primary", "=", TRUE],
            ],
            [
              "Phone AS Contact_Phone_contact_id_01",
              "LEFT",
              ["id", "=", "Contact_Phone_contact_id_01.contact_id"],
            ],
          ],
        ],
      ],
      'display' => [
        'type' => 'table',
        'label' => 'tesdDisplay',
        'settings' => [
          'limit' => 20,
          'pager' => FALSE,
          'columns' => [
            [
              'key' => 'first_name',
              'label' => 'Name',
              'type' => 'field',
              'editable' => TRUE,
              'icons' => [['field' => 'activity_type_id:icon', 'side' => 'left']],
            ],
            [
              'key' => 'Contact_Email_contact_id_01.email',
              'label' => 'Email',
              'type' => 'field',
              'editable' => TRUE,
            ],
            [
              'key' => 'Contact_Phone_contact_id_01.phone',
              'label' => 'Phone',
              'type' => 'field',
              'editable' => TRUE,
            ],
            [
              'key' => 'gender_id:label',
              'label' => 'Gender',
              'type' => 'field',
              'editable' => TRUE,
            ],
          ],
          'sort' => [
            ['id', 'ASC'],
          ],
          'editableRow' => [
            'create' => TRUE,
          ],
        ],
      ],
    ];
    $result = civicrm_api4('SearchDisplay', 'run', $params);

    $this->assertEquals($cids[0], $result[0]['key']);

    $this->assertEquals($cids[0], $result[0]['key']);
    // Contact 1 first name can be updated
    $this->assertEquals('One', $result[0]['columns'][0]['val']);
    $this->assertTrue($result[0]['columns'][0]['edit']);
    // Contact 1 email can be updated
    $this->assertEquals('testmail@unit.test', $result[0]['columns'][1]['val']);
    $this->assertTrue($result[0]['columns'][1]['edit']);
    // Contact 1 - new phone can be created
    $this->assertEquals('', $result[0]['columns'][2]['val']);
    $this->assertTrue($result[0]['columns'][2]['edit']);

    $this->assertEquals($cids[1], $result[1]['key']);
    // Contact 2 first name can be added
    $this->assertEquals('', $result[1]['columns'][0]['val']);
    $this->assertTrue($result[1]['columns'][0]['edit']);
    // Contact 2 - new email can be created
    $this->assertEquals('', $result[1]['columns'][1]['val']);
    $this->assertTrue($result[1]['columns'][1]['edit']);
    // Contact 2 phone can be updated
    $this->assertEquals('123456', $result[1]['columns'][2]['val']);
    $this->assertTrue($result[1]['columns'][2]['edit']);

    $this->assertNotEmpty($result->editable['gender_id:label']['options']);
    $this->assertEquals('Select', $result->editable['gender_id:label']['input_type']);

    // Try doing some inline-edits
    $params['rowKey'] = $cids[0];
    $params['values'] = ['first_name' => 'One Up'];
    $result = civicrm_api4('SearchDisplay', 'inlineEdit', $params);
    $this->assertCount(1, $result);
    $this->assertEquals($cids[0], $result[0]['key']);
    $this->assertEquals('One Up', $result[0]['columns'][0]['val']);

    $params['rowKey'] = $cids[0];
    $params['values'] = ['gender_id:label' => 2];
    $result = civicrm_api4('SearchDisplay', 'inlineEdit', $params);
    $this->assertCount(1, $result);
    $this->assertEquals($cids[0], $result[0]['key']);
    $this->assertEquals('Male', $result[0]['columns'][3]['val']);

    $params['rowKey'] = $cids[0];
    $params['values'] = ['Contact_Email_contact_id_01.email' => 'testmail@unit.tested'];
    $result = civicrm_api4('SearchDisplay', 'inlineEdit', $params);
    $this->assertCount(1, $result);
    $this->assertEquals($cids[0], $result[0]['key']);
    $this->assertEquals('testmail@unit.tested', $result[0]['columns'][1]['val']);
    // Email should have been updated not created
    $this->assertEquals('testmail@unit.tested', Email::get(FALSE)->addWhere('id', '=', $emailId)->execute()->single()['email']);

    // Create new phone for contact 0
    $params['rowKey'] = $cids[0];
    $params['values'] = ['Contact_Phone_contact_id_01.phone' => '654321'];
    $result = civicrm_api4('SearchDisplay', 'inlineEdit', $params);
    $this->assertCount(1, $result);
    $this->assertEquals($cids[0], $result[0]['key']);
    $this->assertEquals('654321', $result[0]['columns'][2]['val']);

    // Create new email for contact 1
    $params['rowKey'] = $cids[1];
    $params['values'] = [
      'Contact_Email_contact_id_01.email' => 'testmail2@unit.tested',
      'first_name' => 'Hello Hi',
    ];
    $result = civicrm_api4('SearchDisplay', 'inlineEdit', $params);
    $this->assertCount(1, $result);
    $this->assertEquals($cids[1], $result[0]['key']);
    $this->assertEquals('testmail2@unit.tested', $result[0]['columns'][1]['val']);
    $this->assertEquals('Hello Hi', $result[0]['columns'][0]['val']);

    // Create a whole new row
    $params['rowKey'] = NULL;
    $params['values'] = [
      'first_name' => 'Newbie',
      'gender_id:label' => 2,
      'Contact_Email_contact_id_01.email' => 'newbie@unit.tested',
      'Contact_Phone_contact_id_01.phone' => '555-1234',
    ];
    $result = civicrm_api4('SearchDisplay', 'inlineEdit', $params);
    $this->assertCount(1, $result);
    $this->assertEquals('Newbie', $result[0]['columns'][0]['val']);
    $this->assertEquals('newbie@unit.tested', $result[0]['columns'][1]['val']);
    $this->assertEquals('555-1234', $result[0]['columns'][2]['val']);
    $this->assertEquals('Male', $result[0]['columns'][3]['val']);
  }

  public function testEditableContactFields() {
    $source = uniqid(__FUNCTION__);
    $sampleData = [
      ['contact_type' => 'Individual', 'first_name' => 'One'],
      ['contact_type' => 'Individual'],
      ['contact_type' => 'Organization'],
      ['contact_type' => 'Household'],
    ];
    $contact = $this->saveTestRecords('Contact', [
      'defaults' => ['source' => $source],
      'records' => $sampleData,
    ]);

    $params = [
      'checkPermissions' => FALSE,
      'return' => 'page:1',
      'savedSearch' => [
        'api_entity' => 'Contact',
        'api_params' => [
          'version' => 4,
          'select' => ['first_name', 'organization_name', 'household_name'],
          'where' => [['source', '=', $source]],
        ],
      ],
      'display' => [
        'type' => 'table',
        'label' => 'tesdDisplay',
        'settings' => [
          'actions' => TRUE,
          'pager' => [],
          'columns' => [
            [
              'key' => 'first_name',
              'label' => 'First',
              'dataType' => 'String',
              'type' => 'field',
              'editable' => TRUE,
            ],
            [
              'key' => 'organization_name',
              'label' => 'First',
              'dataType' => 'String',
              'type' => 'field',
              'editable' => TRUE,
            ],
            [
              'key' => 'household_name',
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

    // Ensure first_name is editable but not organization_name or household_name
    $this->assertTrue($result[0]['columns'][0]['edit']);
    $this->assertTrue(!isset($result[0]['columns'][1]['edit']));
    $this->assertTrue(!isset($result[0]['columns'][2]['edit']));

    // Second Individual
    $this->assertEquals($contact[1]['id'], $result[1]['key']);
    $this->assertTrue($result[1]['columns'][0]['edit']);
    $this->assertTrue(!isset($result[1]['columns'][1]['edit']));
    $this->assertTrue(!isset($result[1]['columns'][2]['edit']));

    // Third contact: Organization
    $this->assertTrue(!isset($result[2]['columns'][0]['edit']));
    $this->assertTrue($result[2]['columns'][1]['edit']);
    $this->assertTrue(!isset($result[2]['columns'][2]['edit']));

    // Third contact: Household
    $this->assertTrue(!isset($result[3]['columns'][0]['edit']));
    $this->assertTrue(!isset($result[3]['columns'][1]['edit']));
    $this->assertTrue($result[3]['columns'][2]['edit']);
  }

  public function testEditableCustomFields() {
    $subject = uniqid(__FUNCTION__);

    $contact = $this->createTestRecord('Contact');

    // CustomGroup based on Activity Type
    $this->createTestRecord('CustomGroup', [
      'extends' => 'Activity',
      'extends_entity_column_value:name' => ['Meeting', 'Phone Call'],
      'title' => 'meeting_phone',
    ]);
    $this->createTestRecord('CustomField', [
      'custom_group_id.name' => 'meeting_phone',
      'label' => 'sub_field',
      'html_type' => 'Text',
    ]);

    $sampleData = [
      ['activity_type_id:name' => 'Meeting', 'meeting_phone.sub_field' => 'Abc'],
      ['activity_type_id:name' => 'Phone Call'],
      ['activity_type_id:name' => 'Email'],
    ];
    $aid = $this->saveTestRecords('Activity', [
      'defaults' => ['subject' => $subject, 'source_contact_id', $contact['id']],
      'records' => $sampleData,
    ])->column('id');

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

    // First Activity
    $this->assertTrue($result[0]['columns'][0]['edit']);
    $this->assertTrue($result[0]['columns'][1]['edit']);
    $this->assertEquals($activityTypes['Meeting'], $result[0]['data']['activity_type_id']);

    // Second Activity
    $this->assertTrue($result[1]['columns'][0]['edit']);
    $this->assertTrue($result[1]['columns'][1]['edit']);
    $this->assertEquals($activityTypes['Phone Call'], $result[1]['data']['activity_type_id']);

    // Third Activity
    $this->assertTrue($result[2]['columns'][0]['edit']);
    $this->assertTrue(!isset($result[2]['columns'][1]['edit']));
    $this->assertEquals($activityTypes['Email'], $result[2]['data']['activity_type_id']);

    // Edit custom data for second activity
    $params['rowKey'] = $aid[1];
    $params['values'] = ['meeting_phone.sub_field' => 'tested!'];
    $result = civicrm_api4('SearchDisplay', 'inlineEdit', $params);
    $this->assertCount(1, $result);
    $this->assertEquals($aid[1], $result[0]['key']);
    $this->assertEquals('tested!', $result[0]['columns'][1]['val']);

    // Edit custom data for second activity
    $params['rowKey'] = $aid[2];
    $params['values'] = ['meeting_phone.sub_field' => 'blocked!'];
    try {
      $result = civicrm_api4('SearchDisplay', 'inlineEdit', $params);
      $this->fail();
    }
    catch (\CRM_Core_Exception $e) {
    }
    $this->assertStringContainsString('Inline edit failed', $e->getMessage());
  }

  public function testEditableRelationshipCustomFields() {
    $this->createTestRecord('CustomGroup', [
      'title' => 'TestChildFields',
      'extends' => 'Relationship',
      'extends_entity_column_value:name' => ['Child of'],
    ]);
    $this->createTestRecord('CustomField', [
      'custom_group_id:name' => 'TestChildFields',
      'label' => 'Child',
      'html_type' => 'Text',
    ]);

    $this->createTestRecord('CustomGroup', [
      'title' => 'TestSpouseFields',
      'extends' => 'Relationship',
      'extends_entity_column_value:name' => ['Spouse of'],
    ]);
    $this->createTestRecord('CustomField', [
      'custom_group_id:name' => 'TestSpouseFields',
      'label' => 'Spouse',
      'html_type' => 'Text',
    ]);

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
    $this->assertEquals($contacts[0], $result[0]['key']);
    $this->assertEquals($contacts[1], $result[1]['key']);
    $this->assertEquals($contacts[2], $result[2]['key']);

    // First contact has a child relation but not a spouse
    $this->assertEquals('c', $result[0]['columns'][1]['val']);
    $this->assertTrue($result[0]['columns'][1]['edit']);
    $this->assertEquals('', $result[0]['columns'][2]['val']);
    $this->assertTrue($result[0]['columns'][2]['edit']);
    $this->assertEquals('abc', $result[0]['columns'][3]['val']);
    $this->assertTrue($result[0]['columns'][3]['edit']);
    $this->assertEquals('', $result[0]['columns'][4]['val']);
    $this->assertArrayNotHasKey('edit', $result[0]['columns'][4]);

    // Second contact has a spouse relation but not a child
    $this->assertEquals('s', $result[1]['columns'][1]['val']);
    $this->assertTrue($result[1]['columns'][1]['edit']);
    $this->assertEquals('Married', $result[1]['columns'][2]['val']);
    $this->assertTrue($result[1]['columns'][2]['edit']);
    $this->assertEquals('', $result[1]['columns'][3]['val']);
    $this->assertArrayNotHasKey('edit', $result[1]['columns'][3]);
    $this->assertEquals('', $result[1]['columns'][4]['val']);
    $this->assertTrue($result[1]['columns'][4]['edit']);

    // Third contact is all alone in this world...
    $this->assertEquals('', $result[2]['columns'][1]['val']);
    $this->assertArrayNotHasKey('edit', $result[2]['columns'][1]);
    $this->assertEquals('', $result[2]['columns'][2]['val']);
    $this->assertArrayNotHasKey('edit', $result[2]['columns'][2]);
    $this->assertEquals('', $result[2]['columns'][3]['val']);
    $this->assertArrayNotHasKey('edit', $result[2]['columns'][3]);
    $this->assertEquals('', $result[2]['columns'][4]['val']);
    $this->assertArrayNotHasKey('edit', $result[2]['columns'][4]);
  }

  public function testDraggableSearchDisplay() {
    $name = __FUNCTION__;
    $this->createTestRecord('OptionGroup', [
      'title' => $name,
    ]);
    $optionIds = $this->saveTestRecords('OptionValue', [
      'defaults' => ['option_group_id.name' => $name],
      'records' => [
        ['value' => 'A'],
        ['value' => 'B'],
        ['value' => 'C'],
        ['value' => 'D'],
        ['value' => 'E'],
      ],
    ])->column('id');
    $params = [
      'checkPermissions' => FALSE,
      'return' => 'page:1',
      'savedSearch' => [
        'api_entity' => 'OptionValue',
        'api_params' => [
          'version' => 4,
          'select' => ['value', 'weight'],
          'where' => [['option_group_id.name', '=', $name]],
        ],
      ],
      'display' => [
        'type' => 'table',
        'settings' => [
          'draggable' => 'weight',
          'columns' => [
            [
              'key' => 'value',
              'label' => 'Value',
              'type' => 'field',
            ],
            [
              'key' => 'weight',
              'label' => 'Weight',
              'type' => 'field',
            ],
          ],
        ],
      ],
    ];
    $result = civicrm_api4('SearchDisplay', 'run', $params);
    $this->assertSame($optionIds, $result->column('key'));
    $weights = array_column($result->column('data'), 'weight');
    $this->assertSame([1, 2, 3, 4, 5], $weights);

    // Put the last item first
    $editResult = SearchDisplay::inlineEdit()
      ->setSavedSearch($params['savedSearch'])
      ->setDisplay($params['display'])
      ->setReturn('draggableWeight')
      ->setRowKey($optionIds[4])
      ->setValues(['weight' => 1])
      ->execute();
    // Updated weights should be returned
    $this->assertEquals([$optionIds[4] => 1, $optionIds[0] => 2, $optionIds[1] => 3, $optionIds[2] => 4, $optionIds[3] => 5], (array) $editResult);

    // Run the search again - weights will have been updated
    $result = civicrm_api4('SearchDisplay', 'run', $params);
    // Last id will be first
    $lastId = array_pop($optionIds);
    array_unshift($optionIds, $lastId);
    $this->assertSame($optionIds, $result->column('key'));
    $values = array_column($result->column('data'), 'value');
    $this->assertSame(['E', 'A', 'B', 'C', 'D'], $values);
    $weights = array_column($result->column('data'), 'weight');
    $this->assertSame([1, 2, 3, 4, 5], $weights);
  }

}
