<?php
namespace api\v4\SearchDisplay;

// This is apparently necessary due to autoloader issues with test classes
require_once 'tests/phpunit/api/v4/Api4TestBase.php';

use api\v4\Api4TestBase;
use Civi\Api4\Contact;
use Civi\Test\CiviEnvBuilder;

/**
 * @group headless
 */
class SearchRunWithCustomFieldTest extends Api4TestBase {

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
    $this->createTestRecord('CustomGroup', [
      'title' => 'TestSearchFields',
      'extends' => 'Individual',
    ]);

    $this->createTestRecord('CustomField', [
      'label' => 'MyFile',
      'custom_group_id.name' => 'TestSearchFields',
      'html_type' => 'File',
      'data_type' => 'File',
    ]);

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

  public function testMultiValuedFields():void {
    $this->createTestRecord('CustomGroup', [
      'extends' => 'Contact',
      'title' => 'my_test',
    ]);
    $this->createTestRecord('CustomField', [
      'custom_group_id.name' => 'my_test',
      'label' => 'my_field',
      'html_type' => 'Select',
      'serialize' => 1,
      'option_values' => ['zero', 'one', 'two', 'three'],
    ]);

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
    $this->createTestRecord('CustomGroup', [
      'title' => 'EntityRefFields',
      'extends' => 'Individual',
    ]);
    $this->createTestRecord('CustomField', [
      'label' => 'Favorite Nephew',
      'name' => 'favorite_nephew',
      'custom_group_id.name' => 'EntityRefFields',
      'html_type' => 'Autocomplete-Select',
      'data_type' => 'EntityReference',
      'fk_entity' => 'Contact',
    ]);
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

    $contact = $this->createTestRecord('Contact');

    // CustomGroup based on Activity Type
    $this->createTestRecord('CustomGroup', [
      'extends' => 'Activity',
      'title' => 'testactivity2',
    ]);
    $this->createTestRecord('CustomField', [
      'custom_group_id.name' => 'testactivity2',
      'label' => 'testactivity_',
      'data_type' => 'Boolean',
      'html_type' => 'Radio',
    ]);

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
