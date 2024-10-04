<?php
namespace api\v4\SearchDisplay;

// Not sure why this is needed but without it Jenkins crashed
require_once __DIR__ . '/../../../../../../../tests/phpunit/api/v4/Api4TestBase.php';

use api\v4\Api4TestBase;
use Civi\API\Exception\UnauthorizedException;
use Civi\Api4\Action\GetLinks;
use Civi\Api4\Activity;
use Civi\Api4\Address;
use Civi\Api4\Contact;
use Civi\Api4\ContactType;
use Civi\Api4\Email;
use Civi\Api4\Phone;
use Civi\Api4\SavedSearch;
use Civi\Api4\SearchDisplay;
use Civi\Api4\UFMatch;
use Civi\Test\CiviEnvBuilder;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class SearchRunTest extends Api4TestBase implements TransactionalInterface {
  use \Civi\Test\ACLPermissionTrait;

  public function setUpHeadless(): CiviEnvBuilder {
    // Civi\Test has many helpers, like install(), uninstall(), sql(), and sqlFile().
    // See: https://docs.civicrm.org/dev/en/latest/testing/phpunit/#civitest
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  /**
   * @inheritDoc
   */
  protected function setUp(): void {
    \CRM_Core_BAO_ConfigSetting::enableAllComponents();
    parent::setUp();
  }

  /**
   * @inheritDoc
   */
  public function tearDown(): void {
    \Civi\Api4\Setting::revert(FALSE)
      ->addSelect('geoProvider')
      ->execute();
    parent::tearDown();
  }

  /**
   * Test running a searchDisplay with various filters.
   */
  public function testRunWithFilters() {
    foreach (['Tester', 'Bot'] as $type) {
      ContactType::create(FALSE)
        ->addValue('parent_id.name', 'Individual')
        ->addValue('label', $type)
        ->addValue('name', $type)
        ->execute();
    }

    $lastName = uniqid(__FUNCTION__);
    $sampleData = [
      ['first_name' => 'One', 'last_name' => $lastName, 'contact_sub_type' => ['Tester', 'Bot']],
      ['first_name' => 'Two', 'last_name' => $lastName, 'contact_sub_type' => ['Tester']],
      ['first_name' => 'Three', 'last_name' => $lastName, 'contact_sub_type' => ['Bot']],
      ['first_name' => 'Four', 'middle_name' => 'None', 'last_name' => $lastName],
    ];
    Contact::save(FALSE)->setRecords($sampleData)->execute();

    $params = [
      'checkPermissions' => FALSE,
      'return' => 'page:1',
      'savedSearch' => [
        'api_entity' => 'Contact',
        'api_params' => [
          'version' => 4,
          'select' => ['id', 'first_name', 'middle_name', 'last_name', 'contact_sub_type:label', 'is_deceased'],
          'where' => [
            ['do_not_email', 'IS EMPTY'],
          ],
        ],
      ],
      'display' => [
        'type' => 'table',
        'label' => 'tesdDisplay',
        'settings' => [
          'limit' => 20,
          'pager' => TRUE,
          'columns' => [
            [
              'key' => 'id',
              'label' => 'Contact ID',
              'dataType' => 'Integer',
              'type' => 'field',
            ],
            [
              'key' => 'first_name',
              'label' => 'First Name',
              'dataType' => 'String',
              'type' => 'field',
            ],
            [
              'key' => 'last_name',
              'label' => 'Last Name',
              'dataType' => 'String',
              'type' => 'field',
            ],
            [
              'key' => 'contact_sub_type:label',
              'label' => 'Type',
              'dataType' => 'String',
              'type' => 'field',
            ],
            [
              'key' => 'is_deceased',
              'label' => 'Deceased',
              'dataType' => 'Boolean',
              'type' => 'field',
            ],
          ],
          'sort' => [
            ['id', 'ASC'],
          ],
        ],
      ],
      'filters' => ['last_name' => $lastName],
      'afform' => NULL,
    ];

    $result = civicrm_api4('SearchDisplay', 'run', $params);
    $this->assertCount(4, $result);

    $params['filters']['first_name'] = ['One', 'Two'];
    $result = civicrm_api4('SearchDisplay', 'run', $params);
    $this->assertCount(2, $result);
    $this->assertEquals('One', $result[0]['data']['first_name']);
    $this->assertEquals('Two', $result[1]['data']['first_name']);
    $count = civicrm_api4('SearchDisplay', 'run', ['return' => 'row_count'] + $params);
    $this->assertCount(2, $count);

    // Raw value should be boolean, view value should be string
    $this->assertEquals(FALSE, $result[0]['data']['is_deceased']);
    $this->assertEquals(ts('No'), $result[0]['columns'][4]['val']);

    $params['filters'] = ['last_name' => $lastName, 'id' => ['>' => $result[0]['data']['id'], '<=' => $result[1]['data']['id'] + 1]];
    $params['sort'] = [['first_name', 'ASC']];
    $result = civicrm_api4('SearchDisplay', 'run', $params);
    $this->assertCount(2, $result);
    $this->assertEquals('Three', $result[0]['data']['first_name']);
    $this->assertEquals('Two', $result[1]['data']['first_name']);
    $count = civicrm_api4('SearchDisplay', 'run', ['return' => 'row_count'] + $params);
    $this->assertCount(2, $count);

    $params['filters'] = ['last_name' => $lastName, 'contact_sub_type:label' => ['Tester', 'Bot']];
    $result = civicrm_api4('SearchDisplay', 'run', $params);
    $this->assertCount(3, $result);
    $count = civicrm_api4('SearchDisplay', 'run', ['return' => 'row_count'] + $params);
    $this->assertCount(3, $count);

    // Comma indicates first_name OR last_name
    $params['filters'] = ['first_name,last_name' => $lastName, 'contact_sub_type' => ['Tester']];
    $result = civicrm_api4('SearchDisplay', 'run', $params);
    $this->assertCount(2, $result);
    $count = civicrm_api4('SearchDisplay', 'run', ['return' => 'row_count'] + $params);
    $this->assertCount(2, $count);

    // Comma indicates first_name OR middle_name, matches "One" or "None"
    $params['filters'] = ['first_name,middle_name' => 'one', 'last_name' => $lastName];
    $result = civicrm_api4('SearchDisplay', 'run', $params);
    $this->assertCount(2, $result);
    $count = civicrm_api4('SearchDisplay', 'run', ['return' => 'row_count'] + $params);
    $this->assertCount(2, $count);
  }

  public function testDefaultDisplayLinks(): void {
    $group1 = $this->createTestRecord('Group', ['title' => uniqid('a')])['id'];
    $group2 = $this->createTestRecord('Group', ['title' => uniqid('b')])['id'];
    $contact1 = $this->createTestRecord('Individual', ['last_name' => 'b', 'first_name' => 'b'])['id'];
    $contact2 = $this->createTestRecord('Individual', ['last_name' => 'a', 'first_name' => 'a'])['id'];
    // Add both contacts to group2
    $this->saveTestRecords('GroupContact', [
      'records' => [
        ['contact_id' => $contact1, 'group_id' => $group2],
        ['contact_id' => $contact2, 'group_id' => $group2],
      ],
    ]);

    $params = [
      'checkPermissions' => FALSE,
      'return' => 'page:1',
      'savedSearch' => [
        'api_entity' => 'Group',
        'api_params' => [
          'version' => 4,
          'select' => [
            'title',
            'Group_GroupContact_Contact_01.sort_name',
          ],
          'join' => [
            [
              'Contact AS Group_GroupContact_Contact_01',
              'LEFT',
              'GroupContact',
              ['id', '=', 'Group_GroupContact_Contact_01.group_id'],
              ['Group_GroupContact_Contact_01.status:name', '=', '"Added"'],
            ],
          ],
          'where' => [],
        ],
      ],
      'display' => NULL,
      'sort' => [
        ['title', 'ASC'],
        ['Group_GroupContact_Contact_01.sort_name', 'ASC'],
      ],
      'filters' => ['id' => [$group1, $group2]],
    ];

    $result = civicrm_api4('SearchDisplay', 'run', $params);
    $this->assertCount(1, $result[0]['columns'][0]['links']);
    $this->assertNull($result[0]['columns'][1]['val']);
    $this->assertArrayNotHasKey('links', $result[0]['columns'][1]);
    $this->assertCount(1, $result[1]['columns'][0]['links']);
    $this->assertCount(1, $result[1]['columns'][1]['links']);
    $this->assertCount(1, $result[2]['columns'][0]['links']);
    $this->assertCount(1, $result[2]['columns'][1]['links']);
    $this->assertContains('View Group', array_column($result[0]['columns'][2]['links'], 'text'));
    $this->assertContains('Update Group', array_column($result[0]['columns'][2]['links'], 'text'));
    $this->assertContains('Delete Group', array_column($result[0]['columns'][2]['links'], 'text'));
    // Add and browse links should not be shown in rows
    $this->assertNotContains('Add Group', array_column($result[0]['columns'][2]['links'], 'text'));
    $this->assertNotContains('Browse Group', array_column($result[0]['columns'][2]['links'], 'text'));
    // No contact links in 1st row since the group is empty
    $this->assertNotContains('View Contact', array_column($result[0]['columns'][2]['links'], 'text'));
    $this->assertNotContains('Delete Contact', array_column($result[0]['columns'][2]['links'], 'text'));
    $this->assertContains('View Contact', array_column($result[1]['columns'][2]['links'], 'text'));
    $this->assertContains('Delete Contact', array_column($result[1]['columns'][2]['links'], 'text'));
    $this->assertContains('View Contact', array_column($result[2]['columns'][2]['links'], 'text'));
    $this->assertContains('Delete Contact', array_column($result[2]['columns'][2]['links'], 'text'));
    // Add and browse links should not be shown in rows
    $this->assertNotContains('Add Contact', array_column($result[1]['columns'][2]['links'], 'text'));
    $this->assertNotContains('Browse Contact', array_column($result[2]['columns'][2]['links'], 'text'));
  }

  /**
   * Test return values are augmented by tokens.
   */
  public function testWithTokens() {
    $lastName = uniqid(__FUNCTION__);
    $sampleData = [
      ['first_name' => 'One', 'last_name' => $lastName, 'source' => 'Unit test'],
      ['first_name' => 'Two', 'last_name' => $lastName, 'source' => 'Unit test'],
    ];
    Contact::save(FALSE)->setRecords($sampleData)->execute();

    $params = [
      'checkPermissions' => FALSE,
      'return' => 'page:1',
      'savedSearch' => [
        'api_entity' => 'Contact',
        'api_params' => [
          'version' => 4,
          'select' => ['id', 'display_name'],
          'where' => [['last_name', '=', $lastName]],
        ],
      ],
      'display' => [
        'type' => 'table',
        'label' => 'tesdDisplay',
        'settings' => [
          'limit' => 20,
          'pager' => TRUE,
          'columns' => [
            [
              'key' => 'id',
              'label' => 'Contact ID',
              'dataType' => 'Integer',
              'type' => 'field',
            ],
            [
              'key' => 'display_name',
              'label' => 'Display Name',
              'dataType' => 'String',
              'type' => 'field',
              'link' => [
                'path' => 'civicrm/test/token-[sort_name]',
              ],
            ],
          ],
          'sort' => [
            ['id', 'ASC'],
          ],
        ],
      ],
    ];

    $result = civicrm_api4('SearchDisplay', 'run', $params);
    $this->assertCount(2, $result);
    $this->assertNotEmpty($result->first()['data']['display_name']);
    // Assert that display name was added to the search due to the link token
    $this->assertNotEmpty($result->first()['data']['sort_name']);

    // These items are not part of the search, but will be added via links
    $this->assertArrayNotHasKey('contact_type', $result->first()['data']);
    $this->assertArrayNotHasKey('source', $result->first()['data']);
    $this->assertArrayNotHasKey('last_name', $result->first()['data']);

    // Add links
    $params['display']['settings']['columns'][] = [
      'type' => 'links',
      'label' => 'Links',
      'links' => [
        ['path' => 'civicrm/test-[source]-[contact_type]'],
        ['path' => 'civicrm/test-[last_name]'],
      ],
    ];
    $result = civicrm_api4('SearchDisplay', 'run', $params);
    $this->assertEquals('Individual', $result->first()['data']['contact_type']);
    $this->assertEquals('Unit test', $result->first()['data']['source']);
    $this->assertEquals($lastName, $result->first()['data']['last_name']);
  }

  public function testActionAndTaskLinks():void {
    $contributions = $this->saveTestRecords('Contribution', [
      'records' => [
        ['total_amount' => 100],
      ],
    ]);
    $params = [
      'checkPermissions' => FALSE,
      'return' => 'page:1',
      'savedSearch' => [
        'api_entity' => 'Contribution',
        'api_params' => [
          'version' => 4,
          'select' => ['contact_id.display_name'],
          'where' => [['id', 'IN', $contributions->column('id')]],
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
              'key' => 'contact_id.display_name',
              'label' => 'Contact',
              'dataType' => 'String',
              'type' => 'field',
            ],
            [
              'type' => 'buttons',
              'links' => [
                [
                  'entity' => 'Contribution',
                  'task' => 'contribution.' . \CRM_Contribute_Task::PDF_RECEIPT,
                  'icon' => 'fa-external-link',
                ],
                [
                  'entity' => 'Contribution',
                  'task' => 'update',
                  'icon' => 'fa-pencil',
                ],
                [
                  'entity' => 'Contribution',
                  'title' => 'Delete',
                  'action' => 'delete',
                  'icon' => 'fa-trash',
                  'target' => 'crm-popup',
                ],
              ],
            ],
          ],
          'sort' => [
            ['id', 'ASC'],
          ],
        ],
      ],
    ];
    $result = civicrm_api4('SearchDisplay', 'run', $params);
    // TODO: This test may need to be updated as core tasks evolve
    $this->assertEquals(1, $result->count());
    // 1st link is to a quickform-based search task (CRM_Contribute_Task::PDF_RECEIPT)
    $this->assertArrayNotHasKey('task', $result[0]['columns'][1]['links'][0]);
    $this->assertStringContainsString('id=' . $contributions[0]['id'] . '&qfKey=', $result[0]['columns'][1]['links'][0]['url']);
    $this->assertEquals('fa-external-link', $result[0]['columns'][1]['links'][0]['icon']);
    // 2nd link is to the native SK bulk-update task
    $this->assertArrayNotHasKey('url', $result[0]['columns'][1]['links'][1]);
    $this->assertArrayNotHasKey('action', $result[0]['columns'][1]['links'][1]);
    $this->assertEquals('update', $result[0]['columns'][1]['links'][1]['task']);
    $this->assertEquals('fa-pencil', $result[0]['columns'][1]['links'][1]['icon']);
    // 3rd link is a popup link to the delete contribution quickform
    $this->assertArrayNotHasKey('task', $result[0]['columns'][1]['links'][2]);
    $this->assertStringContainsString('action=delete&id=' . $contributions[0]['id'], $result[0]['columns'][1]['links'][2]['url']);
    $this->assertEquals('crm-popup', $result[0]['columns'][1]['links'][2]['target']);
    $this->assertEquals('fa-trash', $result[0]['columns'][1]['links'][2]['icon']);
    $this->assertEquals('Delete', $result[0]['columns'][1]['links'][2]['title']);
  }

  public function testEnableDisableTaskLinks():void {
    $contributionPage = $this->createTestRecord('ContributionPage', [
      'is_active' => TRUE,
    ]);
    $params = [
      'checkPermissions' => FALSE,
      'return' => 'page:1',
      'savedSearch' => [
        'api_entity' => 'ContributionPage',
        'api_params' => [
          'version' => 4,
          'select' => ['title'],
          'where' => [['id', '=', $contributionPage['id']]],
        ],
      ],
      'display' => [
        'type' => 'table',
        'label' => 'testDisplay',
        'settings' => [
          'actions' => TRUE,
          'pager' => [],
          'columns' => [
            [
              'key' => 'title',
              'label' => 'Title',
              'dataType' => 'String',
              'type' => 'field',
            ],
            [
              'type' => 'buttons',
              'links' => [
                [
                  'entity' => 'ContributionPage',
                  'task' => 'enable',
                  'icon' => 'fa-pencil',
                ],
                [
                  'entity' => 'ContributionPage',
                  'task' => 'disable',
                  'icon' => 'fa-pencil',
                ],
              ],
            ],
          ],
          'sort' => [
            ['id', 'ASC'],
          ],
        ],
      ],
    ];
    $result = civicrm_api4('SearchDisplay', 'run', $params);
    $this->assertEquals(1, $result->count());
    $this->assertCount(1, $result[0]['columns'][1]['links']);
    // Native SK tasks should not have a url
    $this->assertArrayNotHasKey('url', $result[0]['columns'][1]['links'][0]);
    $this->assertArrayNotHasKey('action', $result[0]['columns'][1]['links'][0]);
    $this->assertEquals('disable', $result[0]['columns'][1]['links'][0]['task']);
    $this->assertEquals('fa-pencil', $result[0]['columns'][1]['links'][0]['icon']);
  }

  public function testRelationshipCacheLinks():void {
    $case = $this->createTestRecord('Case');
    $relationships = $this->saveTestRecords('Relationship', [
      'records' => [
        ['contact_id_a' => $this->createTestRecord('Contact')['id'], 'is_active' => TRUE],
        ['contact_id_a' => $this->createTestRecord('Contact')['id'], 'is_active' => FALSE, 'case_id' => $case['id']],
      ],
    ]);
    $params = [
      'checkPermissions' => FALSE,
      'return' => 'page:1',
      'savedSearch' => [
        'api_entity' => 'RelationshipCache',
        'api_params' => [
          'version' => 4,
          'select' => ['near_contact_id.display_name'],
          'where' => [['relationship_id', 'IN', $relationships->column('id')]],
          'join' => [
            [
              'Case AS RelationshipCache_Case_case_id_01',
              'LEFT',
              ['case_id', '=', 'RelationshipCache_Case_case_id_01.id'],
            ],
          ],
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
              'key' => 'near_contact_id.display_name',
              'label' => 'Contact',
              'dataType' => 'String',
              'type' => 'field',
            ],
            [
              'type' => 'links',
              'links' => [
                [
                  'entity' => 'Relationship',
                  'action' => 'view',
                  'icon' => 'fa-external-link',
                ],
                [
                  'title' => '0',
                  'entity' => 'Relationship',
                  'task' => 'delete',
                  'icon' => 'fa-trash',
                ],
                [
                  'entity' => 'Relationship',
                  'task' => 'enable',
                ],
                [
                  'entity' => 'Relationship',
                  'task' => 'disable',
                ],
                [
                  'entity' => 'Case',
                  'action' => 'view',
                  'join' => 'RelationshipCache_Case_case_id_01',
                  'text' => 'Manage Case',
                ],
              ],
            ],
          ],
          'sort' => [
            ['relationship_id', 'ASC'],
          ],
        ],
      ],
    ];
    $result = civicrm_api4('SearchDisplay', 'run', $params);
    $this->assertCount(4, $result);
    $this->assertCount(3, $result[0]['columns'][1]['links']);
    $this->assertCount(4, $result[2]['columns'][1]['links']);
    // 1st link is to a quickform-based action
    $this->assertArrayNotHasKey('task', $result[0]['columns'][1]['links'][0]);
    $this->assertStringContainsString('id=' . $relationships[0]['id'], $result[0]['columns'][1]['links'][0]['url']);
    // 2nd link is to delete
    $this->assertEquals('fa-trash', $result[0]['columns'][1]['links'][1]['icon']);
    // Ensure "empty" titles are still returned
    $this->assertEquals('0', $result[0]['columns'][1]['links'][1]['title']);
    // 3rd link is the disable task for active relationships or the enable task for inactive ones
    $this->assertEquals('disable', $result[0]['columns'][1]['links'][2]['task']);
    $this->assertEquals('disable', $result[1]['columns'][1]['links'][2]['task']);
    $this->assertEquals('enable', $result[2]['columns'][1]['links'][2]['task']);
    $this->assertEquals('enable', $result[3]['columns'][1]['links'][2]['task']);
    $this->assertStringContainsString('Enable', $result[3]['columns'][1]['links'][2]['title']);
    // 4th link is to the case, and only for the relevant entity
    $this->assertEquals('Manage Case', $result[2]['columns'][1]['links'][3]['text']);
    $this->assertStringContainsString("id={$case['id']}", $result[3]['columns'][1]['links'][3]['url']);
  }

  /**
   * Test smarty rewrite syntax.
   */
  public function testRunWithSmartyRewrite() {
    $lastName = uniqid(__FUNCTION__);
    $sampleData = [
      ['first_name' => 'One', 'last_name' => $lastName, 'nick_name' => 'Uno'],
      ['first_name' => 'Two', 'last_name' => $lastName],
    ];
    $contacts = Contact::save(FALSE)->setRecords($sampleData)->execute();
    Email::create(FALSE)
      ->addValue('contact_id', $contacts[0]['id'])
      ->addValue('email', 'testmail@unit.test')
      ->execute();

    $params = [
      'checkPermissions' => FALSE,
      'return' => 'page:1',
      'savedSearch' => [
        'api_entity' => 'Contact',
        'api_params' => [
          'version' => 4,
          'select' => ['id', 'first_name', 'last_name', 'nick_name', 'Contact_Email_contact_id_01.email', 'Contact_Email_contact_id_01.location_type_id:label'],
          'where' => [['last_name', '=', $lastName]],
          'join' => [
            [
              "Email AS Contact_Email_contact_id_01",
              "LEFT",
              ["id", "=", "Contact_Email_contact_id_01.contact_id"],
              ["Contact_Email_contact_id_01.is_primary", "=", TRUE],
            ],
          ],
        ],
      ],
      'display' => [
        'type' => 'table',
        'label' => 'tesdDisplay',
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
              'key' => 'first_name',
              'label' => 'Name',
              'type' => 'field',
              'rewrite' => '{if $nick_name}{$nick_name}{else}[first_name]{/if} [last_name]',
            ],
            [
              'key' => 'Contact_Email_contact_id_01.email',
              'label' => 'Email',
              'type' => 'field',
              'rewrite' => '{if $Contact_Email_contact_id_01.email}{$Contact_Email_contact_id_01.email} ({$Contact_Email_contact_id_01.location_type_id_label}){/if}',
            ],
          ],
          'sort' => [
            ['id', 'ASC'],
          ],
        ],
      ],
    ];
    $result = civicrm_api4('SearchDisplay', 'run', $params);
    $this->assertEquals("Uno $lastName", $result[0]['columns'][1]['val']);
    $this->assertEquals("Two $lastName", $result[1]['columns'][1]['val']);
    $this->assertEquals("testmail@unit.test (Home)", $result[0]['columns'][2]['val']);
    $this->assertEquals("", $result[1]['columns'][2]['val']);

    // Try running it with illegal tags like {crmApi}
    $params['display']['columns'][1]['rewrite'] = '{crmApi entity="Email" action="get" va="notAllowed"}';
    try {
      civicrm_api4('SearchDisplay', 'run', $params);
      $this->fail();
    }
    catch (\Exception $e) {
    }

    // Start with email as base entity and use implicit join

    $params = [
      'checkPermissions' => FALSE,
      'return' => 'page:1',
      'savedSearch' => [
        'api_entity' => 'Email',
        'api_params' => [
          'version' => 4,
          'select' => ['id', 'email', 'contact_id', 'contact_id.first_name', 'contact_id.last_name', 'contact_id.nick_name'],
          'where' => [['contact_id.last_name', '=', $lastName]],
        ],
      ],
      'display' => [
        'type' => 'table',
        'label' => 'testDisplay',
        'settings' => [
          'limit' => 20,
          'pager' => TRUE,
          'columns' => [
            [
              'key' => 'contact_id',
              'label' => 'Contact ID',
              'type' => 'field',
              'rewrite' => '#{$contact_id.id} is #{$contact_id}',
            ],
            [
              'key' => 'first_name',
              'label' => 'Name',
              'type' => 'field',
              'rewrite' => '{if $contact_id.nick_name}{$contact_id.nick_name}{else}[contact_id.first_name]{/if} {$contact_id.last_name}',
            ],
          ],
          'sort' => [
            ['id', 'ASC'],
          ],
        ],
      ],
    ];
    $result = civicrm_api4('SearchDisplay', 'run', $params);
    $this->assertEquals("#{$contacts[0]['id']} is #{$contacts[0]['id']}", $result[0]['columns'][0]['val']);
    $this->assertEquals("Uno $lastName", $result[0]['columns'][1]['val']);
  }

  /**
   * Test in-place editable for update and create.
   */
  public function testInPlaceEditAndCreate() {
    $lastName = uniqid(__FUNCTION__);
    $sampleData = [
      ['first_name' => 'One', 'last_name' => $lastName],
      ['last_name' => $lastName],
    ];
    $contacts = Contact::save(FALSE)->setRecords($sampleData)->execute()->column('id');
    $email = Email::create(FALSE)
      ->addValue('contact_id', $contacts[0])
      ->addValue('email', 'testmail@unit.test')
      ->execute()->single()['id'];
    $phone = Phone::create(FALSE)
      ->addValue('contact_id', $contacts[1])
      ->addValue('phone', '123456')
      ->execute()->single()['id'];

    $params = [
      'checkPermissions' => FALSE,
      'return' => 'page:1',
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
          ],
          'sort' => [
            ['id', 'ASC'],
          ],
        ],
      ],
    ];
    $result = civicrm_api4('SearchDisplay', 'run', $params);

    $this->assertEquals($contacts[0], $result[0]['key']);

    // Contact 1 first name can be updated
    $this->assertEquals('One', $result[0]['columns'][0]['val']);
    $this->assertEquals($contacts[0], $result[0]['columns'][0]['edit']['record']['id']);
    $this->assertEquals('Contact', $result[0]['columns'][0]['edit']['entity']);
    $this->assertEquals('Text', $result[0]['columns'][0]['edit']['input_type']);
    $this->assertEquals('String', $result[0]['columns'][0]['edit']['data_type']);
    $this->assertEquals('first_name', $result[0]['columns'][0]['edit']['value_key']);
    $this->assertEquals('update', $result[0]['columns'][0]['edit']['action']);
    $this->assertEquals('One', $result[0]['data'][$result[0]['columns'][0]['edit']['value_path']]);

    // Contact 1 email can be updated
    $this->assertEquals('testmail@unit.test', $result[0]['columns'][1]['val']);
    $this->assertEquals($email, $result[0]['columns'][1]['edit']['record']['id']);
    $this->assertEquals('Email', $result[0]['columns'][1]['edit']['entity']);
    $this->assertEquals('Email', $result[0]['columns'][1]['edit']['input_type']);
    $this->assertEquals('String', $result[0]['columns'][1]['edit']['data_type']);
    $this->assertEquals('email', $result[0]['columns'][1]['edit']['value_key']);
    $this->assertEquals('update', $result[0]['columns'][1]['edit']['action']);
    $this->assertEquals('testmail@unit.test', $result[0]['data'][$result[0]['columns'][1]['edit']['value_path']]);

    // Contact 1 - new phone can be created
    $this->assertNull($result[0]['columns'][2]['val']);
    $this->assertEquals(['contact_id' => $contacts[0]], $result[0]['columns'][2]['edit']['record']);
    $this->assertEquals('Phone', $result[0]['columns'][2]['edit']['entity']);
    $this->assertEquals('Text', $result[0]['columns'][2]['edit']['input_type']);
    $this->assertEquals('String', $result[0]['columns'][2]['edit']['data_type']);
    $this->assertEquals('phone', $result[0]['columns'][2]['edit']['value_key']);
    $this->assertEquals('create', $result[0]['columns'][2]['edit']['action']);
    $this->assertEquals('Contact_Phone_contact_id_01.phone', $result[0]['columns'][2]['edit']['value_path']);

    // Contact 2 first name can be added
    $this->assertNull($result[1]['columns'][0]['val']);
    $this->assertEquals($contacts[1], $result[1]['columns'][0]['edit']['record']['id']);
    $this->assertEquals('Contact', $result[1]['columns'][0]['edit']['entity']);
    $this->assertEquals('Text', $result[1]['columns'][0]['edit']['input_type']);
    $this->assertEquals('String', $result[1]['columns'][0]['edit']['data_type']);
    $this->assertEquals('first_name', $result[1]['columns'][0]['edit']['value_key']);
    $this->assertEquals('update', $result[1]['columns'][0]['edit']['action']);
    $this->assertEquals('first_name', $result[1]['columns'][0]['edit']['value_path']);

    // Contact 2 - new email can be created
    $this->assertNull($result[1]['columns'][1]['val']);
    $this->assertEquals(['contact_id' => $contacts[1], 'is_primary' => TRUE], $result[1]['columns'][1]['edit']['record']);
    $this->assertEquals('Email', $result[1]['columns'][1]['edit']['entity']);
    $this->assertEquals('Email', $result[1]['columns'][1]['edit']['input_type']);
    $this->assertEquals('String', $result[1]['columns'][1]['edit']['data_type']);
    $this->assertEquals('email', $result[1]['columns'][1]['edit']['value_key']);
    $this->assertEquals('create', $result[1]['columns'][1]['edit']['action']);
    $this->assertEquals('Contact_Email_contact_id_01.email', $result[1]['columns'][1]['edit']['value_path']);

    // Contact 2 phone can be updated
    $this->assertEquals('123456', $result[1]['columns'][2]['val']);
    $this->assertEquals($phone, $result[1]['columns'][2]['edit']['record']['id']);
    $this->assertEquals('Phone', $result[1]['columns'][2]['edit']['entity']);
    $this->assertEquals('Text', $result[1]['columns'][2]['edit']['input_type']);
    $this->assertEquals('String', $result[1]['columns'][2]['edit']['data_type']);
    $this->assertEquals('phone', $result[1]['columns'][2]['edit']['value_key']);
    $this->assertEquals('update', $result[1]['columns'][2]['edit']['action']);
    $this->assertEquals('123456', $result[1]['data'][$result[0]['columns'][2]['edit']['value_path']]);
  }

  /**
   * Test running a searchDisplay as a restricted user.
   */
  public function testDisplayACLCheck() {
    $lastName = uniqid(__FUNCTION__);
    $sampleData = [
      ['first_name' => 'User', 'last_name' => uniqid('user')],
      ['first_name' => 'One', 'last_name' => $lastName],
      ['first_name' => 'Two', 'last_name' => $lastName],
      ['first_name' => 'Three', 'last_name' => $lastName],
      ['first_name' => 'Four', 'last_name' => $lastName],
    ];
    $sampleData = Contact::save(FALSE)
      ->setRecords($sampleData)->execute()
      ->column('id', 'first_name');

    // Create logged-in user
    UFMatch::delete(FALSE)
      ->addWhere('uf_id', '=', 6)
      ->execute();
    UFMatch::create(FALSE)->setValues([
      'contact_id' => $sampleData['User'],
      'uf_name' => 'superman',
      'uf_id' => 6,
    ])->execute();

    $session = \CRM_Core_Session::singleton();
    $session->set('userID', $sampleData['User']);
    $hooks = \CRM_Utils_Hook::singleton();
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = [
      'access CiviCRM',
    ];

    $search = SavedSearch::create(FALSE)
      ->setValues([
        'name' => uniqid(__FUNCTION__),
        'api_entity' => 'Contact',
        'api_params' => [
          'version' => 4,
          'select' => ['id', 'first_name', 'last_name'],
          'where' => [['last_name', '=', $lastName]],
        ],
      ])
      ->addChain('display', SearchDisplay::create()
        ->setValues([
          'type' => 'table',
          'label' => uniqid(__FUNCTION__),
          'saved_search_id' => '$id',
          'settings' => [
            'limit' => 20,
            'pager' => TRUE,
            'columns' => [
              [
                'key' => 'id',
                'label' => 'Contact ID',
                'dataType' => 'Integer',
                'type' => 'field',
              ],
              [
                'key' => 'first_name',
                'label' => 'First Name',
                'dataType' => 'String',
                'type' => 'field',
                'link' => [
                  'entity' => 'Contact',
                  'action' => 'update',
                ],
              ],
              [
                'key' => 'last_name',
                'label' => 'Last Name',
                'dataType' => 'String',
                'type' => 'field',
              ],
            ],
            'sort' => [
              ['id', 'ASC'],
            ],
          ],
        ]), 0)
      ->execute()->first();

    $params = [
      'return' => 'page:1',
      'savedSearch' => $search['name'],
      'display' => $search['display']['name'],
      'afform' => NULL,
    ];

    $hooks->setHook('civicrm_aclWhereClause', [$this, 'aclWhereHookNoResults']);
    $result = civicrm_api4('SearchDisplay', 'run', $params);
    $this->assertCount(0, $result);

    $this->allowedContactId = $sampleData['Two'];
    $hooks->setHook('civicrm_aclWhereClause', [$this, 'aclWhereOnlyOne']);
    $this->cleanupCachedPermissions();
    $result = civicrm_api4('SearchDisplay', 'run', $params);
    $this->assertCount(1, $result);
    $this->assertEquals($sampleData['Two'], $result[0]['data']['id']);

    $hooks->setHook('civicrm_aclWhereClause', [$this, 'aclWhereGreaterThan']);
    $this->cleanupCachedPermissions();
    $result = civicrm_api4('SearchDisplay', 'run', $params);
    $this->assertCount(2, $result);
    $this->assertEquals($sampleData['Three'], $result[0]['data']['id']);
    $this->assertEquals($sampleData['Four'], $result[1]['data']['id']);

    // Ensure edit link is only shown for contacts we have permission to edit
    $hooks->setHook('civicrm_aclWhereClause', [$this, 'aclViewAllEditOne']);
    $this->cleanupCachedPermissions();
    $result = civicrm_api4('SearchDisplay', 'run', $params);
    $this->assertCount(4, $result);
    $this->assertNotEmpty($result[1]['columns'][1]['links']);
    $this->assertTrue(empty($result[1]['columns'][0]['links']));
    $this->assertTrue(empty($result[1]['columns'][2]['links']));
    $this->assertTrue(empty($result[1]['columns'][3]['links']));
  }

  public function testWithACLBypass() {
    $config = \CRM_Core_Config::singleton();
    $config->userPermissionClass->permissions = ['all CiviCRM permissions and ACLs'];

    $lastName = uniqid(__FUNCTION__);
    $searchName = uniqid(__FUNCTION__);
    $displayName = uniqid(__FUNCTION__);
    $sampleData = [
      ['first_name' => 'One', 'last_name' => $lastName],
      ['first_name' => 'Two', 'last_name' => $lastName],
      ['first_name' => 'Three', 'last_name' => $lastName],
      ['first_name' => 'Four', 'last_name' => $lastName],
    ];
    Contact::save()->setRecords($sampleData)->execute();

    // Super admin may create a display with acl_bypass
    $search = SavedSearch::create()
      ->setValues([
        'name' => $searchName,
        'title' => 'Test Saved Search',
        'api_entity' => 'Contact',
        'api_params' => [
          'version' => 4,
          'select' => ['id', 'first_name', 'last_name'],
          'where' => [],
        ],
      ])
      ->addChain('display', SearchDisplay::create()
        ->setValues([
          'saved_search_id' => '$id',
          'name' => $displayName,
          'type' => 'table',
          'label' => 'TestDisplay',
          'acl_bypass' => TRUE,
          'settings' => [
            'limit' => 20,
            'pager' => TRUE,
            'columns' => [
              [
                'key' => 'id',
                'label' => 'Contact ID',
                'dataType' => 'Integer',
                'type' => 'field',
              ],
              [
                'key' => 'first_name',
                'label' => 'First Name',
                'dataType' => 'String',
                'type' => 'field',
              ],
              [
                'key' => 'last_name',
                'label' => 'Last Name',
                'dataType' => 'String',
                'type' => 'field',
              ],
            ],
            'sort' => [
              ['id', 'ASC'],
            ],
          ],
        ]))
      ->execute()->first();

    // Super admin may update a display with acl_bypass
    SearchDisplay::update()->addWhere('name', '=', $displayName)
      ->addValue('label', 'Test Display')
      ->execute();

    $config->userPermissionClass->permissions = ['administer CiviCRM'];
    // Ordinary admin may not edit display because it has acl_bypass
    $error = NULL;
    try {
      SearchDisplay::update()->addWhere('name', '=', $displayName)
        ->addValue('label', 'Test Display')
        ->execute();
    }
    catch (UnauthorizedException $e) {
      $error = $e->getMessage();
    }
    $this->assertStringContainsString('failed', $error);

    // Ordinary admin may not change the value of acl_bypass
    $error = NULL;
    try {
      SearchDisplay::update()->addWhere('name', '=', $displayName)
        ->addValue('acl_bypass', FALSE)
        ->execute();
    }
    catch (UnauthorizedException $e) {
      $error = $e->getMessage();
    }
    $this->assertStringContainsString('failed', $error);

    // Ordinary admin may not edit the search because the display has acl_bypass
    $error = NULL;
    try {
      SavedSearch::update()->addWhere('name', '=', $searchName)
        ->addValue('title', 'Tested Search')
        ->execute();
    }
    catch (UnauthorizedException $e) {
      $error = $e->getMessage();
    }
    $this->assertStringContainsString('failed', $error);

    $params = [
      'checkPermissions' => FALSE,
      'return' => 'page:1',
      'savedSearch' => $searchName,
      'display' => $displayName,
      'filters' => ['last_name' => $lastName],
      'afform' => NULL,
    ];

    $config->userPermissionClass->permissions = ['access CiviCRM'];

    $result = civicrm_api4('SearchDisplay', 'run', $params);
    $this->assertCount(4, $result);

    $config->userPermissionClass->permissions = ['all CiviCRM permissions and ACLs'];
    $params['checkPermissions'] = TRUE;

    $result = civicrm_api4('SearchDisplay', 'run', $params);
    $this->assertCount(4, $result);

    $config->userPermissionClass->permissions = ['administer CiviCRM'];
    $error = NULL;
    try {
      civicrm_api4('SearchDisplay', 'run', $params);
    }
    catch (UnauthorizedException $e) {
      $error = $e->getMessage();
    }
    $this->assertStringContainsString('denied', $error);

    $config->userPermissionClass->permissions = ['all CiviCRM permissions and ACLs'];

    // Super users can update the acl_bypass field
    SearchDisplay::update()->addWhere('name', '=', $displayName)
      ->addValue('acl_bypass', FALSE)
      ->execute();

    $config->userPermissionClass->permissions = ['view all contacts'];
    // And ordinary users can now run it
    $result = civicrm_api4('SearchDisplay', 'run', $params);
    $this->assertCount(4, $result);

    // But not edit
    $error = NULL;
    try {
      SearchDisplay::update()->addWhere('name', '=', $displayName)
        ->addValue('label', 'Tested Display')
        ->execute();
    }
    catch (UnauthorizedException $e) {
      $error = $e->getMessage();
    }
    $this->assertStringContainsString('failed', $error);

    $config->userPermissionClass->permissions = ['administer CiviCRM'];

    // Admins can edit the search and the display
    SavedSearch::update()->addWhere('name', '=', $searchName)
      ->addValue('title', 'Tested Search')
      ->execute();
    SearchDisplay::update()->addWhere('name', '=', $displayName)
      ->addValue('label', 'Tested Display')
      ->execute();

    // But they can't edit the acl_bypass field
    $error = NULL;
    try {
      SearchDisplay::update()->addWhere('name', '=', $displayName)
        ->addValue('acl_bypass', TRUE)
        ->execute();
    }
    catch (UnauthorizedException $e) {
      $error = $e->getMessage();
    }
    $this->assertStringContainsString('failed', $error);
  }

  /**
   * Test running a searchDisplay with random sorting.
   */
  public function testSortByRand() {
    $lastName = uniqid(__FUNCTION__);
    $sampleData = [
      ['first_name' => 'One', 'last_name' => $lastName],
      ['first_name' => 'Two', 'last_name' => $lastName],
      ['first_name' => 'Three', 'last_name' => $lastName],
      ['first_name' => 'Four', 'last_name' => $lastName],
    ];
    Contact::save(FALSE)->setRecords($sampleData)->execute();

    $params = [
      'checkPermissions' => FALSE,
      'return' => 'page:1',
      'savedSearch' => [
        'api_entity' => 'Contact',
        'api_params' => [
          'version' => 4,
          'select' => ['id', 'first_name', 'last_name'],
          'where' => [['last_name', '=', $lastName]],
        ],
      ],
      'display' => [
        'type' => 'list',
        'label' => 'tesdDisplay',
        'settings' => [
          'limit' => 20,
          'pager' => TRUE,
          'columns' => [
            [
              'key' => 'first_name',
              'label' => 'First Name',
              'dataType' => 'String',
              'type' => 'field',
            ],
          ],
          'sort' => [
            ['RAND()', 'ASC'],
          ],
        ],
      ],
      'afform' => NULL,
    ];

    // Without seed, results are returned in unpredictable order
    // (hard to test this, but we can at least assert we get the correct number of results back)
    $unseeded = civicrm_api4('SearchDisplay', 'run', $params);
    $this->assertCount(4, $unseeded);

    // Seed must be an integer
    $params['seed'] = 'hello';
    try {
      civicrm_api4('SearchDisplay', 'run', $params);
      $this->fail();
    }
    catch (\CRM_Core_Exception $e) {
    }

    // With a random seed, results should be shuffled in stable order
    $params['seed'] = 12345678987654321;
    $seeded = civicrm_api4('SearchDisplay', 'run', $params);

    // Same seed, same order every time
    for ($i = 0; $i <= 9; ++$i) {
      $repeat = civicrm_api4('SearchDisplay', 'run', $params);
      $this->assertEquals($seeded->column('data'), $repeat->column('data'));
    }
  }

  public function testRunWithGroupBy() {
    Activity::delete(FALSE)
      ->addWhere('activity_type_id:name', 'IN', ['Meeting', 'Phone Call'])
      ->execute();

    $cid = Contact::create(FALSE)
      ->execute()->first()['id'];
    $sampleData = [
      ['subject' => 'abc', 'activity_type_id:name' => 'Meeting', 'source_contact_id' => $cid],
      ['subject' => 'def', 'activity_type_id:name' => 'Meeting', 'source_contact_id' => $cid],
      ['subject' => 'xyz', 'activity_type_id:name' => 'Phone Call', 'source_contact_id' => $cid],
    ];
    $aids = Activity::save(FALSE)
      ->setRecords($sampleData)
      ->execute()->column('id');

    $params = [
      'checkPermissions' => FALSE,
      'return' => 'page:1',
      'savedSearch' => [
        'api_entity' => 'Activity',
        'api_params' => [
          'version' => 4,
          'select' => [
            "activity_type_id:label",
            "GROUP_CONCAT(DISTINCT subject) AS GROUP_CONCAT_subject",
          ],
          'groupBy' => ['activity_type_id'],
          'where' => [],
        ],
      ],
    ];

    $result = civicrm_api4('SearchDisplay', 'run', $params);

    $this->assertEquals(['abc', 'def'], $result[0]['data']['GROUP_CONCAT_subject']);
    $this->assertEquals(['xyz'], $result[1]['data']['GROUP_CONCAT_subject']);
  }

  /**
   * Test conditional styles
   */
  public function testCssRules() {
    $lastName = uniqid(__FUNCTION__);
    $sampleContacts = [
      ['first_name' => 'Zero', 'last_name' => $lastName, 'is_deceased' => TRUE],
      ['first_name' => 'One', 'last_name' => $lastName],
      ['first_name' => 'Two', 'last_name' => $lastName],
      ['first_name' => 'Three', 'last_name' => $lastName],
    ];
    $contacts = Contact::save(FALSE)->setRecords($sampleContacts)->execute();
    $sampleEmails = [
      ['contact_id' => $contacts[0]['id'], 'email' => 'abc@123', 'on_hold' => 1],
      ['contact_id' => $contacts[0]['id'], 'email' => 'def@123', 'on_hold' => 0],
      ['contact_id' => $contacts[1]['id'], 'email' => 'ghi@123', 'on_hold' => 0],
      ['contact_id' => $contacts[2]['id'], 'email' => 'jkl@123', 'on_hold' => 2],
    ];
    Email::save(FALSE)->setRecords($sampleEmails)->execute();

    $search = [
      'name' => 'Test',
      'label' => 'Test Me',
      'api_entity' => 'Contact',
      'api_params' => [
        'version' => 4,
        'select' => [
          'id',
          'display_name',
          'GROUP_CONCAT(DISTINCT Contact_Email_contact_id_01.email) AS GROUP_CONCAT_Contact_Email_contact_id_01_email',
        ],
        'where' => [['last_name', '=', $lastName]],
        'groupBy' => ['id'],
        'join' => [
          [
            'Email AS Contact_Email_contact_id_01',
            'LEFT',
            ['id', '=', 'Contact_Email_contact_id_01.contact_id'],
          ],
        ],
        'having' => [],
      ],
      'acl_bypass' => FALSE,
    ];

    $display = [
      'type' => 'table',
      'settings' => [
        'actions' => TRUE,
        'limit' => 50,
        'classes' => ['table', 'table-striped'],
        'pager' => [
          'show_count' => TRUE,
          'expose_limit' => TRUE,
        ],
        'columns' => [
          [
            'type' => 'field',
            'key' => 'id',
            'dataType' => 'Integer',
            'label' => 'Contact ID',
            'sortable' => TRUE,
            'alignment' => 'text-center',
          ],
          [
            'type' => 'field',
            'key' => 'display_name',
            'dataType' => 'String',
            'label' => 'Display Name',
            'sortable' => TRUE,
            'link' => [
              'entity' => 'Contact',
              'action' => 'view',
              'target' => '_blank',
            ],
            'title' => 'View Contact',
          ],
          [
            'type' => 'field',
            'key' => 'GROUP_CONCAT_Contact_Email_contact_id_01_email',
            'dataType' => 'String',
            'label' => '(List) Contact Emails: Email',
            'sortable' => TRUE,
            'alignment' => 'text-right',
            'cssRules' => [
              [
                'bg-danger',
                'Contact_Email_contact_id_01.on_hold:label',
                '=',
                'On Hold Bounce',
              ],
              [
                'bg-warning',
                'Contact_Email_contact_id_01.on_hold:label',
                '=',
                'On Hold Opt Out',
              ],
            ],
            'rewrite' => '',
            'title' => NULL,
          ],
        ],
        'cssRules' => [
          ['strikethrough', 'is_deceased', '=', TRUE],
        ],
      ],
    ];

    $result = SearchDisplay::Run(FALSE)
      ->setSavedSearch($search)
      ->setDisplay($display)
      ->setReturn('page:1')
      ->setSort([['id', 'ASC']])
      ->execute();

    // Non-conditional style rule
    $this->assertEquals('text-center', $result[0]['columns'][0]['cssClass']);
    // First contact is deceased, gets strikethrough class
    $this->assertEquals('strikethrough', $result[0]['cssClass']);
    $this->assertNotEquals('strikethrough', $result[1]['cssClass']);
    // Ensure the view contact link was formed
    $this->assertStringContainsString('cid=' . $contacts[0]['id'], $result[0]['columns'][1]['links'][0]['url']);
    $this->assertEquals('_blank', $result[0]['columns'][1]['links'][0]['target']);
    // 1st column gets static + conditional style
    $this->assertStringContainsString('text-right', $result[0]['columns'][2]['cssClass']);
    $this->assertStringContainsString('bg-danger', $result[0]['columns'][2]['cssClass']);
    // 2nd row gets static style but no conditional styles apply
    $this->assertEquals('text-right', $result[1]['columns'][2]['cssClass']);
    // 3rd column gets static + conditional style
    $this->assertStringContainsString('text-right', $result[2]['columns'][2]['cssClass']);
    $this->assertStringContainsString('bg-warning', $result[2]['columns'][2]['cssClass']);
  }

  /**
   * Test conditional and field-based icons
   */
  public function testIcons() {
    $subject = uniqid(__FUNCTION__);

    $source = Contact::create(FALSE)->execute()->first();

    $activities = [
      ['activity_type_id:name' => 'Meeting', 'subject' => $subject, 'status_id:name' => 'Scheduled'],
      ['activity_type_id:name' => 'Phone Call', 'subject' => $subject, 'status_id:name' => 'Completed'],
    ];
    Activity::save(FALSE)
      ->addDefault('source_contact_id', $source['id'])
      ->setRecords($activities)->execute();

    $search = [
      'api_entity' => 'Activity',
      'api_params' => [
        'version' => 4,
        'select' => [
          'id',
        ],
        'orderBy' => [],
        'where' => [],
        'groupBy' => [],
        'join' => [],
        'having' => [],
      ],
    ];

    $display = [
      'type' => 'table',
      'settings' => [
        'actions' => TRUE,
        'limit' => 50,
        'classes' => [
          'table',
          'table-striped',
        ],
        'pager' => [
          'show_count' => TRUE,
          'expose_limit' => TRUE,
        ],
        'sort' => [],
        'columns' => [
          [
            'type' => 'field',
            'key' => 'id',
            'dataType' => 'Integer',
            'label' => 'Activity ID',
            'sortable' => TRUE,
            'icons' => [
              [
                'field' => 'activity_type_id:icon',
                'side' => 'left',
              ],
              [
                'icon' => 'fa-star',
                'side' => 'right',
                'if' => [
                  'status_id:name',
                  '=',
                  'Completed',
                ],
              ],
            ],
          ],
        ],
      ],
      'acl_bypass' => FALSE,
    ];

    $result = SearchDisplay::Run(FALSE)
      ->setSavedSearch($search)
      ->setDisplay($display)
      ->setReturn('page:1')
      ->setSort([['id', 'ASC']])
      ->execute();

    // Icon based on activity type
    $this->assertEquals(['left' => ['fa-slideshare']], $result[0]['columns'][0]['icons']);
    // Activity type icon + conditional icon based on status
    $this->assertEquals(['right' => ['fa-star'], 'left' => ['fa-phone']], $result[1]['columns'][0]['icons']);
  }

  /**
   * Test value substitutions with empty fields & placeholders
   */
  public function testPlaceholderFields() {
    $lastName = uniqid(__FUNCTION__);
    $sampleContacts = [
      ['first_name' => 'Zero', 'last_name' => $lastName, 'nick_name' => 'Nick'],
      ['first_name' => 'First', 'last_name' => $lastName],
    ];
    Contact::save(FALSE)->setRecords($sampleContacts)->execute();

    $search = [
      'name' => 'Test',
      'label' => 'Test Me',
      'api_entity' => 'Contact',
      'api_params' => [
        'version' => 4,
        'select' => ['id', 'nick_name'],
        'where' => [['last_name', '=', $lastName]],
      ],
      'acl_bypass' => FALSE,
    ];

    $display = [
      'type' => 'table',
      'settings' => [
        'actions' => TRUE,
        'columns' => [
          [
            'type' => 'field',
            'key' => 'id',
            'dataType' => 'Integer',
            'label' => 'Contact ID',
            'sortable' => TRUE,
            'alignment' => 'text-center',
          ],
          [
            'type' => 'field',
            'key' => 'nick_name',
            'dataType' => 'String',
            'label' => 'Display Name',
            'sortable' => TRUE,
            'rewrite' => '[nick_name] [last_name]',
            'empty_value' => '[first_name] [last_name]',
            'link' => [
              'entity' => 'Contact',
              'action' => 'view',
              'target' => '_blank',
            ],
            'title' => '[display_name]',
          ],
        ],
      ],
    ];

    $result = SearchDisplay::Run(FALSE)
      ->setSavedSearch($search)
      ->setDisplay($display)
      ->setReturn('page:1')
      ->setSort([['id', 'ASC']])
      ->execute();

    // Has a nick name
    $this->assertEquals("Nick $lastName", $result[0]['columns'][1]['val']);
    $this->assertEquals("Nick $lastName", $result[0]['columns'][1]['links'][0]['text']);
    // Title is display name
    $this->assertEquals("Zero $lastName", $result[0]['columns'][1]['title']);
    // No nick name - using first name instead per empty_value setting
    $this->assertEquals("First $lastName", $result[1]['columns'][1]['val']);
    $this->assertEquals("First $lastName", $result[1]['columns'][1]['title']);
    $this->assertEquals("First $lastName", $result[1]['columns'][1]['links'][0]['text']);
    // Check links
    $this->assertNotEmpty($result[0]['columns'][1]['links'][0]['url']);
    $this->assertNotEmpty($result[1]['columns'][1]['links'][0]['url']);
  }

  /**
   * Ensure SearchKit can cope with a non-DAO-based entity
   */
  public function testRunWithNonDaoEntity() {
    $search = [
      'api_entity' => 'Entity',
      'api_params' => [
        'version' => 4,
        'select' => ['name'],
        'where' => [['name', '=', 'Contact']],
      ],
    ];

    $display = [
      'type' => 'table',
      'settings' => [
        'actions' => TRUE,
        'columns' => [
          [
            'type' => 'field',
            'key' => 'name',
            'label' => 'Name',
            'sortable' => TRUE,
          ],
        ],
      ],
    ];

    $result = SearchDisplay::Run(FALSE)
      ->setSavedSearch($search)
      ->setDisplay($display)
      ->setReturn('page:1')
      ->execute();

    $this->assertCount(1, $result);
    $this->assertEquals('Contact', $result[0]['columns'][0]['val']);
  }

  public function testGroupByContactType(): void {
    $source = uniqid(__FUNCTION__);
    $sampleData = [
      ['contact_type' => 'Individual'],
      ['contact_type' => 'Individual'],
      ['contact_type' => 'Individual'],
      ['contact_type' => 'Organization'],
      ['contact_type' => 'Organization'],
      ['contact_type' => 'Household'],
    ];
    Contact::save(FALSE)
      ->addDefault('source', $source)
      ->setRecords($sampleData)
      ->execute();

    $params = [
      'checkPermissions' => FALSE,
      'return' => 'page:1',
      'savedSearch' => [
        'api_entity' => 'Contact',
        'api_params' => [
          'version' => 4,
          'select' => ['contact_type:label', 'COUNT(id) AS COUNT_id'],
          'where' => [['source', '=', $source]],
          'groupBy' => ['contact_type'],
        ],
      ],
      'display' => NULL,
      'afform' => NULL,
    ];

    $result = civicrm_api4('SearchDisplay', 'run', $params);
    $this->assertCount(3, $result);
    $data = array_column(array_column((array) $result, 'data'), 'COUNT_id', 'contact_type:label');
    $this->assertEquals(3, $data['Individual']);
    $this->assertEquals(2, $data['Organization']);
    $this->assertEquals(1, $data['Household']);
  }

  public function testGroupByAddress(): void {
    $source = uniqid(__FUNCTION__);
    $sampleData = [
      ['contact_type' => 'Individual'],
      ['contact_type' => 'Individual'],
      ['contact_type' => 'Individual'],
      ['contact_type' => 'Organization'],
      ['contact_type' => 'Organization'],
      ['contact_type' => 'Household'],
    ];
    Contact::save(FALSE)
      ->addDefault('source', $source)
      ->setRecords($sampleData)
      ->addChain('address', Address::create()
        ->addValue('contact_id', '$id')
        ->addValue('street_address', '123')
      )
      ->execute();

    $params = [
      'checkPermissions' => FALSE,
      'return' => 'page:1',
      'savedSearch' => [
        'api_entity' => 'Contact',
        'api_params' => [
          'version' => 4,
          'select' => [
            'COUNT(id) AS COUNT_id',
            'Contact_Address_contact_id_01.street_address',
            'GROUP_CONCAT(DISTINCT sort_name) AS GROUP_CONCAT_sort_name',
            'GROUP_CONCAT(DISTINCT contact_type:label) AS GROUP_CONCAT_contact_type_label',
            'GROUP_CONCAT(DISTINCT contact_sub_type:label) AS GROUP_CONCAT_contact_sub_type_label',
          ],
          'where' => [
            ['source', '=', $source],
          ],
          'groupBy' => [
            'Contact_Address_contact_id_01.street_address',
          ],
          'join' => [
            [
              'Address AS Contact_Address_contact_id_01',
              'LEFT',
              ['id', '=', 'Contact_Address_contact_id_01.contact_id'],
              ['Contact_Address_contact_id_01.is_primary', '=', TRUE],
            ],
          ],
        ],
      ],
      'display' => NULL,
    ];

    $result = civicrm_api4('SearchDisplay', 'run', $params);
    $this->assertCount(1, $result);
    $this->assertEquals(6, $result[0]['data']['COUNT_id']);
    $this->assertEquals('123', $result[0]['data']['Contact_Address_contact_id_01.street_address']);
    sort($result[0]['data']['GROUP_CONCAT_contact_type_label']);
    $this->assertEquals(['Household', 'Individual', 'Organization'], $result[0]['data']['GROUP_CONCAT_contact_type_label']);
  }

  public function testGroupByFunction(): void {
    $source = uniqid(__FUNCTION__);
    $sampleData = [
      ['birth_date' => '2009-02-05'],
      ['birth_date' => '1999-02-22'],
      ['birth_date' => '2012-05-06'],
    ];
    Contact::save(FALSE)
      ->addDefault('source', $source)
      ->setRecords($sampleData)
      ->execute();

    $params = [
      'checkPermissions' => FALSE,
      'return' => 'page:1',
      'savedSearch' => [
        'api_entity' => 'Contact',
        'api_params' => [
          'version' => 4,
          'select' => ['COUNT(id) AS COUNT_id'],
          'where' => [['source', '=', $source]],
          'groupBy' => ['MONTH(birth_date)'],
        ],
      ],
      'display' => NULL,
      'afform' => NULL,
    ];

    $result = civicrm_api4('SearchDisplay', 'run', $params);
    $this->assertCount(2, $result);
    $data = array_column(array_column((array) $result, 'data'), 'COUNT_id');
    sort($data);
    $this->assertEquals([1, 2], $data);
  }

  public function testEditableContactFields() {
    $source = uniqid(__FUNCTION__);
    $sampleData = [
      ['contact_type' => 'Individual', 'first_name' => 'One'],
      ['contact_type' => 'Individual'],
      ['contact_type' => 'Organization'],
      ['contact_type' => 'Household'],
    ];
    $contact = Contact::save(FALSE)
      ->addDefault('source', $source)
      ->setRecords($sampleData)
      ->execute();

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
    // First Individual
    $expectedFirstNameEdit = [
      'entity' => 'Contact',
      'input_type' => 'Text',
      'data_type' => 'String',
      'options' => FALSE,
      'serialize' => FALSE,
      'nullable' => TRUE,
      'fk_entity' => NULL,
      'value_key' => 'first_name',
      'record' => ['id' => $contact[0]['id']],
      'action' => 'update',
      'value_path' => 'first_name',
    ];
    // Ensure first_name is editable but not organization_name or household_name
    $this->assertEquals($expectedFirstNameEdit, $result[0]['columns'][0]['edit']);
    $this->assertTrue(!isset($result[0]['columns'][1]['edit']));
    $this->assertTrue(!isset($result[0]['columns'][2]['edit']));

    // Second Individual
    $expectedFirstNameEdit['record']['id'] = $contact[1]['id'];
    $this->assertEquals($contact[1]['id'], $result[1]['key']);
    $this->assertEquals($expectedFirstNameEdit, $result[1]['columns'][0]['edit']);
    $this->assertTrue(!isset($result[1]['columns'][1]['edit']));
    $this->assertTrue(!isset($result[1]['columns'][2]['edit']));

    // Third contact: Organization
    $expectedFirstNameEdit['record']['id'] = $contact[2]['id'];
    $expectedFirstNameEdit['value_key'] = 'organization_name';
    $expectedFirstNameEdit['value_path'] = 'organization_name';
    $this->assertTrue(!isset($result[2]['columns'][0]['edit']));
    $this->assertEquals($expectedFirstNameEdit, $result[2]['columns'][1]['edit']);
    $this->assertTrue(!isset($result[2]['columns'][2]['edit']));

    // Third contact: Household
    $expectedFirstNameEdit['record']['id'] = $contact[3]['id'];
    $expectedFirstNameEdit['value_key'] = 'household_name';
    $expectedFirstNameEdit['value_path'] = 'household_name';
    $this->assertTrue(!isset($result[3]['columns'][0]['edit']));
    $this->assertTrue(!isset($result[3]['columns'][1]['edit']));
    $this->assertEquals($expectedFirstNameEdit, $result[3]['columns'][2]['edit']);
  }

  public function testContributionCurrency():void {
    $cid = $this->saveTestRecords('Contact', ['records' => 3])->column('id');
    $contributions = $this->saveTestRecords('Contribution', [
      'records' => [
        ['total_amount' => 100, 'currency' => 'GBP', 'contact_id' => $cid[0]],
        ['total_amount' => 200, 'currency' => 'USD', 'contact_id' => $cid[1]],
        ['total_amount' => 500, 'currency' => 'JPY', 'contact_id' => $cid[2]],
      ],
    ]);

    $params = [
      'checkPermissions' => FALSE,
      'return' => 'page:1',
      'savedSearch' => [
        'api_entity' => 'Contribution',
        'api_params' => [
          'version' => 4,
          // Include `id` column so the `sort` works
          'select' => ['total_amount', 'id'],
          'where' => [['id', 'IN', $contributions->column('id')]],
        ],
      ],
      'display' => NULL,
      'sort' => [['id', 'DESC']],
    ];

    $result = civicrm_api4('SearchDisplay', 'run', $params);
    $this->assertCount(3, $result);

    // Currency should have been fetched automatically and used to format the value
    $this->assertEquals('GBP', $result[2]['data']['currency']);
    $this->assertEquals('£100.00', $result[2]['columns'][0]['val']);

    $this->assertEquals('USD', $result[1]['data']['currency']);
    $this->assertEquals('$200.00', $result[1]['columns'][0]['val']);

    $this->assertEquals('JPY', $result[0]['data']['currency']);
    $this->assertEquals('¥500', $result[0]['columns'][0]['val']);

    // Now do a search for the contribution line-items
    $params['savedSearch'] = [
      'api_entity' => 'LineItem',
      'api_params' => [
        'version' => 4,
        'select' => ['line_total', 'id'],
        'where' => [['contribution_id', 'IN', $contributions->column('id')]],
      ],
    ];

    $result = civicrm_api4('SearchDisplay', 'run', $params);
    $this->assertCount(3, $result);

    // An automatic join should have been added to fetch the contribution currency
    $this->assertEquals('GBP', $result[2]['data']['contribution_id.currency']);
    $this->assertEquals('£100.00', $result[2]['columns'][0]['val']);

    $this->assertEquals('USD', $result[1]['data']['contribution_id.currency']);
    $this->assertEquals('$200.00', $result[1]['columns'][0]['val']);

    $this->assertEquals('JPY', $result[0]['data']['contribution_id.currency']);
    $this->assertEquals('¥500', $result[0]['columns'][0]['val']);

    // Now try it via joins
    $params['savedSearch'] = [
      'api_entity' => 'Contact',
      'api_params' => [
        'version' => 4,
        'select' => ['line_item.line_total', 'id'],
        'where' => [['contribution.id', 'IN', $contributions->column('id')]],
        'join' => [
          ['Contribution AS contribution', 'INNER', ['id', '=', 'contribution.contact_id']],
          ['LineItem AS line_item', 'INNER', ['contribution.id', '=', 'line_item.contribution_id']],
        ],
      ],
    ];
    $result = civicrm_api4('SearchDisplay', 'run', $params);
    $this->assertCount(3, $result);

    // The parent join should have been used rather than adding an unnecessary implicit join
    $this->assertEquals('GBP', $result[2]['data']['contribution.currency']);
    $this->assertEquals('£100.00', $result[2]['columns'][0]['val']);

    $this->assertEquals('USD', $result[1]['data']['contribution.currency']);
    $this->assertEquals('$200.00', $result[1]['columns'][0]['val']);

    $this->assertEquals('JPY', $result[0]['data']['contribution.currency']);
    $this->assertEquals('¥500', $result[0]['columns'][0]['val']);
  }

  public function testContributionAggregateCurrency():void {
    $contributions = $this->saveTestRecords('Contribution', [
      'records' => [
        ['total_amount' => 100, 'currency' => 'GBP'],
        ['total_amount' => 150, 'currency' => 'USD'],
        ['total_amount' => 500, 'currency' => 'JPY'],
        ['total_amount' => 250, 'currency' => 'USD'],
      ],
    ]);

    $params = [
      'checkPermissions' => FALSE,
      'return' => 'page:1',
      'savedSearch' => [
        'api_entity' => 'Contribution',
        'api_params' => [
          'version' => 4,
          'select' => ['SUM(total_amount) AS total', 'COUNT(id) AS count', 'currency'],
          'where' => [['id', 'IN', $contributions->column('id')]],
          'groupBy' => ['currency'],
        ],
      ],
      'display' => NULL,
      'sort' => [['currency', 'ASC']],
    ];

    $result = civicrm_api4('SearchDisplay', 'run', $params);
    $this->assertCount(3, $result);

    // Currency should have been used to format the aggregated values
    $this->assertEquals('GBP', $result[0]['data']['currency']);
    $this->assertEquals('£100.00', $result[0]['columns'][0]['val']);
    $this->assertEquals(1, $result[0]['columns'][1]['val']);

    $this->assertEquals('JPY', $result[1]['data']['currency']);
    $this->assertEquals('¥500', $result[1]['columns'][0]['val']);
    $this->assertEquals(1, $result[1]['columns'][1]['val']);

    $this->assertEquals('USD', $result[2]['data']['currency']);
    $this->assertEquals('$400.00', $result[2]['columns'][0]['val']);
    $this->assertEquals(2, $result[2]['columns'][1]['val']);

    $params = [
      'checkPermissions' => FALSE,
      'return' => 'page:1',
      'savedSearch' => [
        'api_entity' => 'Contribution',
        'api_params' => [
          'version' => 4,
          'select' => ['SUM(line_item.line_total) AS total', 'id'],
          'where' => [['id', 'IN', $contributions->column('id')]],
          'groupBy' => ['id'],
          'join' => [
            ['LineItem AS line_item', 'INNER', ['id', '=', 'line_item.contribution_id']],
          ],
        ],
      ],
      'display' => NULL,
      'sort' => [['id', 'ASC']],
    ];

    $result = civicrm_api4('SearchDisplay', 'run', $params);
    $this->assertCount(4, $result);

    // Currency should have been used to format the aggregated values
    $this->assertEquals('GBP', $result[0]['data']['currency']);
    $this->assertEquals('£100.00', $result[0]['columns'][0]['val']);

    $this->assertEquals('USD', $result[1]['data']['currency']);
    $this->assertEquals('$150.00', $result[1]['columns'][0]['val']);

    $this->assertEquals('JPY', $result[2]['data']['currency']);
    $this->assertEquals('¥500', $result[2]['columns'][0]['val']);

    $this->assertEquals('USD', $result[3]['data']['currency']);
    $this->assertEquals('$250.00', $result[3]['columns'][0]['val']);
  }

  public function testTally(): void {
    \Civi::settings()->set('dateformatshortdate', '%m/%d/%Y');
    $contacts = $this->saveTestRecords('Individual', [
      'records' => [
        ['first_name' => 'A', 'last_name' => 'A'],
        ['first_name' => 'B', 'last_name' => 'B'],
        ['first_name' => 'C', 'last_name' => 'C'],
      ],
    ]);

    $contributions = $this->saveTestRecords('Contribution', [
      'records' => [
        ['total_amount' => 100, 'contact_id' => $contacts[0]['id'], 'receive_date' => '2024-02-02', 'financial_type_id:name' => 'Donation'],
        ['total_amount' => 200, 'contact_id' => $contacts[0]['id'], 'receive_date' => '2021-02-02', 'financial_type_id:name' => 'Campaign Contribution'],
        ['total_amount' => 300, 'contact_id' => $contacts[1]['id'], 'receive_date' => '2022-02-02', 'financial_type_id:name' => 'Member Dues'],
        ['total_amount' => 400, 'contact_id' => $contacts[2]['id'], 'receive_date' => '2023-02-02', 'financial_type_id:name' => 'Donation'],
      ],
    ]);

    $this->createTestRecord('SavedSearch', [
      'name' => __FUNCTION__,
      'label' => __FUNCTION__,
      'api_entity' => 'Contribution',
      'api_params' => [
        'version' => 4,
        'select' => [
          'COUNT(id) AS COUNT_id',
          'GROUP_CONCAT(DISTINCT contact_id.sort_name) AS GROUP_CONCAT_contact_id_sort_name',
          'SUM(total_amount) AS SUM_total_amount',
          'GROUP_FIRST(receive_date ORDER BY receive_date ASC) AS GROUP_FIRST_receive_date',
          'GROUP_FIRST(financial_type_id:label ORDER BY receive_date ASC) AS GROUP_FIRST_financial_type_id_label',
        ],
        'orderBy' => [],
        'where' => [
          ['id', 'IN', $contributions->column('id')],
        ],
        'groupBy' => [
          'contact_id',
        ],
        'join' => [],
        'having' => [],
      ],
    ]);

    $this->createTestRecord('SearchDisplay', [
      'name' => __FUNCTION__,
      'label' => __FUNCTION__,
      'saved_search_id.name' => __FUNCTION__,
      'type' => 'table',
      'settings' => [
        'description' => NULL,
        'sort' => [],
        'limit' => 50,
        'pager' => [],
        'placeholder' => 5,
        'columns' => [
          [
            'type' => 'field',
            'key' => 'COUNT_id',
            'label' => '(Count) Contribution ID',
            'sortable' => TRUE,
            'tally' => [
              'fn' => 'SUM',
            ],
          ],
          [
            'type' => 'field',
            'key' => 'GROUP_CONCAT_contact_id_sort_name',
            'label' => '(List) Contact Sort Name',
            'sortable' => TRUE,
            'tally' => [
              'fn' => 'GROUP_CONCAT',
            ],
          ],
          [
            'type' => 'field',
            'key' => 'SUM_total_amount',
            'label' => '(Sum) Total Amount',
            'sortable' => TRUE,
            'tally' => [
              'fn' => 'SUM',
            ],
          ],
          [
            'type' => 'field',
            'key' => 'GROUP_FIRST_receive_date',
            'label' => 'First Contribution Date',
            'sortable' => TRUE,
            'tally' => [
              'fn' => 'GROUP_FIRST',
            ],
            'format' => 'dateformatshortdate',
          ],
          [
            'type' => 'field',
            'key' => 'GROUP_FIRST_financial_type_id_label',
            'label' => '(First) Financial Type',
            'sortable' => TRUE,
            'tally' => [
              'fn' => 'GROUP_FIRST',
            ],
          ],
        ],
        'actions' => TRUE,
        'classes' => [
          'table',
          'table-striped',
        ],
        'tally' => [
          'label' => 'Total',
        ],
      ],
    ]);

    $tally = SearchDisplay::run(FALSE)
      ->setReturn('tally')
      ->setDisplay(__FUNCTION__)
      ->setSavedSearch(__FUNCTION__)
      ->execute()->single();

    $this->assertSame('4', $tally['COUNT_id']);
    $this->assertEquals(['A, A', 'B, B', 'C, C'], $tally['GROUP_CONCAT_contact_id_sort_name']);
    $this->assertSame('$1,000.00', $tally['SUM_total_amount']);
    $this->assertSame('02/02/2021', $tally['GROUP_FIRST_receive_date']);
    $this->assertSame('Donation', $tally['GROUP_FIRST_financial_type_id_label']);
  }

  public function testContributionTotalCountWithTestAndTemplateContributions():void {
    // Add a source here for the where below, as if we use id, we get the test and template contributions
    $contributions = $this->saveTestRecords('Contribution', [
      'records' => [
        ['is_test' => TRUE, 'source' => 'TestTemplate'],
        ['is_template' => TRUE, 'source' => 'TestTemplate'],
        ['source' => 'TestTemplate'],
      ],
    ]);

    $params = [
      'checkPermissions' => FALSE,
      'return' => 'page:1',
      'savedSearch' => [
        'api_entity' => 'Contribution',
        'api_params' => [
          'version' => 4,
          'select' => ['id'],
          'where' => [['source', '=', 'TestTemplate']],
        ],
      ],
      'display' => [
        'settings' => [
          'columns' => [
            [
              'type' => 'field',
              'key' => 'id',
              'tally' => [
                'fn' => 'COUNT',
              ],
            ],
          ],
        ],
      ],
    ];

    $return = civicrm_api4('SearchDisplay', 'run', $params);
    $params['return'] = 'tally';
    $total = civicrm_api4('SearchDisplay', 'run', $params);
    $this->assertEquals($return->rowCount, $total[0]['id']);
  }

  public function testSelectEquations() {
    $activities = $this->saveTestRecords('Activity', [
      'records' => [
        ['duration' => 60],
        ['duration' => 120],
        ['duration' => 180],
      ],
    ]);
    $params = [
      'checkPermissions' => FALSE,
      'return' => 'page:1',
      'savedSearch' => [
        'api_entity' => 'Activity',
        'api_params' => [
          'version' => 4,
          'select' => ['id', '(duration / 60)'],
          'where' => [['id', 'IN', $activities->column('id')]],
        ],
      ],
      'display' => NULL,
      'sort' => [['id', 'ASC']],
    ];

    $result = civicrm_api4('SearchDisplay', 'run', $params);
    $this->assertCount(3, $result);

    $this->assertEquals(1, $result[0]['columns'][1]['val']);
    $this->assertEquals(2, $result[1]['columns'][1]['val']);
    $this->assertEquals(3, $result[2]['columns'][1]['val']);
  }

  public function testSelectPseudoFields() {
    $activities = $this->saveTestRecords('Activity', [
      'records' => 2,
    ]);
    $params = [
      'checkPermissions' => FALSE,
      'return' => 'page:1',
      'savedSearch' => [
        'api_entity' => 'Activity',
        'api_params' => [
          'version' => 4,
          'select' => ['id', 'NOW()', 'CURDATE()', 'result_row_num'],
          'where' => [['id', 'IN', $activities->column('id')]],
        ],
      ],
      'display' => [
        'type' => 'table',
        'label' => 'testDisplay',
        'settings' => [
          'actions' => TRUE,
          'pager' => [],
          'columns' => [
            [
              'type' => 'field',
              'key' => 'id',
              'label' => 'ID',
            ],
            [
              'type' => 'field',
              'key' => 'result_row_num',
              'label' => 'Row',
            ],
            [
              'type' => 'field',
              'key' => 'CURDATE()',
              'label' => 'Date',
            ],
            [
              'type' => 'field',
              'key' => 'NOW()',
              'label' => 'Date + Time',
            ],
          ],
        ],
      ],
    ];

    $result = civicrm_api4('SearchDisplay', 'run', $params);
    $this->assertCount(2, $result);

    $date = date('Y-m-d');

    // Check CURDATE()
    $this->assertEquals($date, $result[0]['data']['CURDATE:']);
    $this->assertEquals($date, $result[1]['data']['CURDATE:']);
    $this->assertEquals(\CRM_Utils_Date::customFormat($date), $result[0]['columns'][2]['val']);
    $this->assertEquals(\CRM_Utils_Date::customFormat($date), $result[1]['columns'][2]['val']);

    // Check NOW()
    $this->assertStringStartsWith("$date ", $result[0]['data']['NOW:']);
    $this->assertStringStartsWith("$date ", $result[1]['data']['NOW:']);
    $this->assertEquals(\CRM_Utils_Date::customFormat($result[0]['data']['NOW:']), $result[0]['columns'][3]['val']);
    $this->assertEquals(\CRM_Utils_Date::customFormat($result[1]['data']['NOW:']), $result[1]['columns'][3]['val']);

    // Check result_row_num
    $this->assertEquals(1, $result[0]['columns'][1]['val']);
    $this->assertEquals(2, $result[1]['columns'][1]['val']);
  }

  public function testLinkConditions() {
    $activities = $this->saveTestRecords('Activity', [
      'records' => [
        ['activity_date_time' => 'now - 1 day'],
        ['activity_date_time' => 'now + 1 day'],
      ],
    ]);
    $params = [
      'checkPermissions' => FALSE,
      'return' => 'page:1',
      'savedSearch' => [
        'api_entity' => 'Activity',
        'api_params' => [
          'version' => 4,
          'select' => ['id'],
          'where' => [['id', 'IN', $activities->column('id')]],
        ],
      ],
      'display' => [
        'type' => 'table',
        'label' => 'testDisplay',
        'settings' => [
          'actions' => TRUE,
          'pager' => [],
          'sort' => [['id', 'ASC']],
          'columns' => [
            [
              'type' => 'field',
              'key' => 'id',
              'label' => 'ID',
            ],
            [
              'type' => 'buttons',
              'links' => [
                [
                  'entity' => 'Activity',
                  'task' => 'update',
                  'icon' => 'fa-pencil',
                  'condition' => [
                    'activity_date_time',
                    '>',
                    'now',
                  ],
                ],
              ],
            ],
          ],
        ],
      ],
    ];

    $result = civicrm_api4('SearchDisplay', 'run', $params);
    $this->assertCount(2, $result);

    // Link should appear for 2nd activity but not the first
    $this->assertCount(0, $result[0]['columns'][1]['links']);
    $this->assertCount(1, $result[1]['columns'][1]['links']);
  }

  public function testContactTypeIcons(): void {
    $this->createTestRecord('ContactType', [
      'label' => 'Star',
      'name' => 'Star',
      'parent_id:name' => 'Individual',
      'icon' => 'fa-star',
    ]);
    $this->createTestRecord('ContactType', [
      'label' => 'None',
      'name' => 'None',
      'parent_id:name' => 'Individual',
      'icon' => NULL,
    ]);

    $lastName = uniqid(__FUNCTION__);
    $sampleData = [
      [
        'first_name' => 'Starry',
        'contact_sub_type' => ['Star'],
      ],
      [
        'first_name' => 'No icon',
        'contact_sub_type' => ['None'],
      ],
      [
        'first_name' => 'Both',
        'contact_sub_type' => ['None', 'Star'],
      ],
    ];
    $records = $this->saveTestRecords('Contact', [
      'records' => $sampleData,
      'defaults' => ['last_name' => $lastName],
    ]);

    $params = [
      'checkPermissions' => FALSE,
      'return' => 'page:1',
      'savedSearch' => [
        'api_entity' => 'Contact',
        'api_params' => [
          'version' => 4,
          'select' => ['first_name', 'last_name'],
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
              'icons' => [
                ['field' => 'contact_sub_type:icon'],
                ['field' => 'contact_type:icon'],
              ],
            ],
          ],
          'sort' => [
            ['sort_name', 'ASC'],
          ],
        ],
      ],
      'filters' => ['last_name' => $lastName],
    ];

    $result = civicrm_api4('SearchDisplay', 'run', $params);
    $this->assertCount(3, $result);

    // Contacts will be returned in order by sort_name
    $this->assertEquals('Both', $result[0]['columns'][0]['val']);
    $this->assertEquals('fa-star', $result[0]['columns'][0]['icons']['left'][0]);
    $this->assertEquals('No icon', $result[1]['columns'][0]['val']);
    $this->assertEquals('fa-user', $result[1]['columns'][0]['icons']['left'][0]);
    $this->assertEquals('Starry', $result[2]['columns'][0]['val']);
    $this->assertEquals('fa-star', $result[2]['columns'][0]['icons']['left'][0]);
  }

  public function testKeyIsReturned(): void {
    $id = $this->createTestRecord('Email')['id'];
    $params = [
      'checkPermissions' => FALSE,
      'return' => 'page:1',
      'savedSearch' => [
        'api_entity' => 'Email',
        'api_params' => [
          'version' => 4,
          'select' => ['email'],
          'where' => [
            ['id', 'IN', [$id]],
          ],
        ],
      ],
      'display' => [
        'type' => 'table',
        'label' => 'tesdDisplay',
        'settings' => [
          'limit' => 20,
          'pager' => TRUE,
          'actions' => TRUE,
          'columns' => [
            [
              'key' => 'email',
              'label' => 'Email',
              'dataType' => 'String',
              'type' => 'field',
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
    $this->assertCount(1, $result);
    $this->assertEquals($id, $result[0]['key']);
  }

  public function testRunWithToolbar(): void {
    $params = [
      'return' => 'page:1',
      'savedSearch' => [
        'api_entity' => 'Contact',
        'api_params' => [
          'version' => 4,
          'select' => ['first_name', 'contact_type'],
        ],
      ],
      'display' => [
        'type' => 'table',
        'label' => 'tesdDisplay',
        'settings' => [
          'actions' => TRUE,
          'pager' => [],
          'toolbar' => [
            [
              'entity' => 'Contact',
              'action' => 'add',
              'text' => 'Add Contact',
              'target' => 'crm-popup',
              'icon' => 'fa-plus',
              'style' => 'primary',
            ],
          ],
          'columns' => [
            [
              'key' => 'first_name',
              'label' => 'First',
              'dataType' => 'String',
              'type' => 'field',
            ],
          ],
          'sort' => [],
        ],
      ],
      'filters' => ['contact_type' => 'Individual'],
    ];
    // No 'add contacts' permission == no "Add contacts" button
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = [
      'access CiviCRM',
      'administer search_kit',
    ];
    $result = civicrm_api4('SearchDisplay', 'run', $params);
    $this->assertCount(0, $result->toolbar);
    // With 'add contacts' permission the button will be shown
    \CRM_Core_Config::singleton()->userPermissionClass->permissions[] = 'add contacts';
    // Clear getLinks cache after changing permissions
    \Civi::$statics[GetLinks::class] = [];

    $result = civicrm_api4('SearchDisplay', 'run', $params);
    $this->assertCount(1, $result->toolbar);
    $menu = $result->toolbar[0];
    $this->assertEquals('Add Contact', $menu['text']);
    $this->assertEquals('fa-plus', $menu['icon']);
    $button = $menu['children'][0];
    $this->assertEquals('fa-user', $button['icon']);
    $this->assertEquals('Add Individual', $button['text']);
    $this->assertStringContainsString('=Individual', $button['url']);

    // Try with pseudoconstant (for proper test the label needs to be different from the name)
    ContactType::update(FALSE)
      ->addValue('label', 'Disorganization')
      ->addWhere('name', '=', 'Organization')
      ->execute();
    $params['filters'] = ['contact_type:label' => 'Disorganization'];
    // Use default label this time
    unset($params['display']['settings']['toolbar'][0]['text']);
    $result = civicrm_api4('SearchDisplay', 'run', $params);
    $menu = $result->toolbar[0];
    $this->assertEquals('Add Disorganization', $menu['text']);
    $button = $menu['children'][0];
    $this->assertStringContainsString('=Organization', $button['url']);
    $this->assertEquals('Add Disorganization', $button['text']);

    // Test legacy 'addButton' setting
    $params['display']['settings']['toolbar'] = NULL;
    $params['display']['settings']['addButton'] = [
      'path' => 'civicrm/test/url?test=[contact_type]',
      'text' => 'Test',
      'icon' => 'fa-old',
      'autoOpen' => TRUE,
    ];
    $result = civicrm_api4('SearchDisplay', 'run', $params);
    $this->assertCount(1, $result->toolbar);
    $button = $result->toolbar[0];
    $this->assertStringContainsString('test=Organization', $button['url']);
    $this->assertTrue($button['autoOpen']);
  }

  public static function toolbarLinkPermissions(): array {
    $sets = [];
    $sets[] = [
      'CONTAINS',
      ['access CiviCRM', 'administer CiviCRM'],
      ['access CiviCRM'],
      TRUE,
    ];
    $sets[] = [
      '=',
      ['access CiviCRM', 'administer CiviCRM'],
      ['access CiviCRM'],
      FALSE,
    ];
    $sets[] = [
      '!=',
      ['access CiviCRM', 'administer CiviCRM'],
      ['access CiviCRM'],
      TRUE,
    ];
    $sets[] = [
      'CONTAINS',
      ['access CiviCRM', 'administer CiviCRM'],
      [],
      FALSE,
    ];
    $sets[] = [
      '=',
      [],
      [],
      TRUE,
    ];
    return $sets;
  }

  /**
   * @dataProvider toolbarLinkPermissions
   */
  public function testToolbarLinksPermissionOperators($linkOperator, $linkPerms, $userPerms, $shouldBeVisible): void {
    $params = [
      'return' => 'page:1',
      'savedSearch' => [
        'api_entity' => 'Contact',
        'api_params' => [
          'version' => 4,
          'select' => ['first_name', 'contact_type'],
        ],
      ],
      'display' => [
        'type' => 'table',
        'label' => 'tesdDisplay',
        'settings' => [
          'actions' => TRUE,
          'pager' => [],
          'toolbar' => [
            [
              'path' => 'civicrm/test',
              'text' => 'Test',
              'condition' => [
                'check user permission',
                $linkOperator,
                $linkPerms,
              ],
            ],
          ],
          'columns' => [],
        ],
      ],
    ];
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = array_merge(['administer search_kit'], $userPerms);
    $result = civicrm_api4('SearchDisplay', 'run', $params);
    $this->assertCount((int) $shouldBeVisible, $result->toolbar);
  }

  public function testRunWithEntityFile(): void {
    $cid = $this->createTestRecord('Contact')['id'];
    $notes = $this->saveTestRecords('Note', [
      'records' => 2,
      'defaults' => ['entity_table' => 'civicrm_contact', 'entity_id' => $cid],
    ])->column('id');

    foreach (['text/plain' => 'txt', 'image/png' => 'png', 'image/foo' => 'foo'] as $mimeType => $ext) {
      // FIXME: Use api4 when available
      civicrm_api3('Attachment', 'create', [
        'entity_table' => 'civicrm_note',
        'entity_id' => $notes[0],
        'name' => 'test_file.' . $ext,
        'mime_type' => $mimeType,
        'content' => 'hello',
      ])['id'];
    }

    $params = [
      'checkPermissions' => FALSE,
      'debug' => TRUE,
      'return' => 'page:1',
      'savedSearch' => [
        'api_entity' => 'Note',
        'api_params' => [
          'version' => 4,
          'select' => [
            'id',
            'subject',
            'note',
            'note_date',
            'modified_date',
            'contact_id.sort_name',
            'GROUP_CONCAT(UNIQUE Note_EntityFile_File_01.file_name) AS GROUP_CONCAT_Note_EntityFile_File_01_file_name',
            'GROUP_CONCAT(UNIQUE Note_EntityFile_File_01.url) AS GROUP_CONCAT_Note_EntityFile_File_01_url',
            'GROUP_CONCAT(UNIQUE Note_EntityFile_File_01.icon) AS GROUP_CONCAT_Note_EntityFile_File_01_icon',
          ],
          'where' => [
            ['entity_id', 'IN', [$cid]],
            ['entity_table:name', '=', 'Contact'],
          ],
          'groupBy' => [
            'id',
          ],
          'join' => [
            [
              'File AS Note_EntityFile_File_01',
              'LEFT',
              'EntityFile',
              [
                'id',
                '=',
                'Note_EntityFile_File_01.entity_id',
              ],
              [
                'Note_EntityFile_File_01.entity_table',
                '=',
                "'civicrm_note'",
              ],
            ],
          ],
          'having' => [],
        ],
      ],
      'display' => [
        'type' => 'table',
        'label' => 'tesdDisplay',
        'settings' => [
          'limit' => 20,
          'pager' => TRUE,
          'actions' => TRUE,
          'columns' => [
            [
              'type' => 'field',
              'key' => 'id',
              'dataType' => 'Integer',
              'label' => 'ID',
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'GROUP_CONCAT_Note_EntityFile_File_01_file_name',
              'dataType' => 'String',
              'label' => ts('Attachments'),
              'sortable' => TRUE,
              'link' => [
                'path' => '[GROUP_CONCAT_Note_EntityFile_File_01_url]',
                'entity' => '',
                'action' => '',
                'join' => '',
                'target' => '',
              ],
              'icons' => [
                [
                  'field' => 'Note_EntityFile_File_01.icon',
                  'side' => 'left',
                ],
                [
                  'icon' => 'fa-search',
                  'side' => 'right',
                  'if' => ['Note_EntityFile_File_01.is_image'],
                ],
              ],
              'cssRules' => [
                ['crm-image-popup', 'Note_EntityFile_File_01.is_image', '=', TRUE],
              ],
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
    $this->assertCount(2, $result);
    $this->assertEquals(['fa-file-text-o', 'fa-file-image-o', 'fa-file-image-o'], $result[0]['columns'][1]['icons']['left']);
    $this->assertEquals([NULL, 'fa-search', NULL], $result[0]['columns'][1]['icons']['right']);
    $this->assertEquals(['', 'crm-image-popup', ''], array_column($result[0]['columns'][1]['links'], 'style'));
    $this->assertEquals(['test_file.txt', 'test_file.png', 'test_file_foo.unknown'], array_column($result[0]['columns'][1]['links'], 'text'));
  }

  public function testRunWithAddressProximity(): void {
    require_once __DIR__ . '/../../../../../../../tests/phpunit/CRM/Utils/Geocode/TestProvider.php';
    $sampleData = [
      ['geo_code_1' => \CRM_Utils_Geocode_TestProvider::GEO_CODE_1, 'geo_code_2' => \CRM_Utils_Geocode_TestProvider::GEO_CODE_2],
      ['geo_code_1' => \CRM_Utils_Geocode_TestProvider::GEO_CODE_1 - .05, 'geo_code_2' => \CRM_Utils_Geocode_TestProvider::GEO_CODE_2 + .05],
      ['geo_code_1' => '0', 'geo_code_2' => '0'],
    ];
    $addresses = $this->saveTestRecords('Address', ['records' => $sampleData])
      ->column('id');

    \Civi\Api4\Setting::set(FALSE)
      ->addValue('geoProvider', 'TestProvider')
      ->execute();

    $params = [
      'checkPermissions' => FALSE,
      'return' => 'page:1',
      'savedSearch' => [
        'api_entity' => 'Address',
        'api_params' => [
          'version' => 4,
          // Hack proximity into select clause to allow filter
          'select' => ['id', 'proximity'],
          'where' => [
            ['id', 'IN', $addresses],
          ],
        ],
      ],
      'display' => NULL,
      'filters' => ['proximity' => ['distance' => 1000, 'address' => \CRM_Utils_Geocode_TestProvider::ADDRESS]],
      'afform' => NULL,
      'debug' => TRUE,
    ];

    $result = civicrm_api4('SearchDisplay', 'run', $params);
    $this->assertCount(2, $result);
  }

  /**
   * Returns all contacts in VIEW mode but only specified contact for EDIT.
   *
   * @implements CRM_Utils_Hook::aclWhereClause
   *
   * @param int $type
   * @param array $tables
   * @param array $whereTables
   * @param int $contactID
   * @param string|null $where
   */
  public function aclViewAllEditOne(int $type, array &$tables, array &$whereTables, int &$contactID, ?string &$where): void {
    if ($type === \CRM_Core_Permission::VIEW) {
      $where = ' (1) ';
    }
    elseif ($type === \CRM_Core_Permission::EDIT) {
      $where = ' contact_a.id = ' . $this->allowedContactId;
    }
  }

}
