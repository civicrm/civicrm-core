<?php
namespace api\v4\SearchDisplay;

use Civi\API\Exception\UnauthorizedException;
use Civi\Api4\Contact;
use Civi\Api4\ContactType;
use Civi\Api4\Email;
use Civi\Api4\SavedSearch;
use Civi\Api4\SearchDisplay;
use Civi\Api4\UFMatch;
use Civi\Test\HeadlessInterface;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class SearchRunTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface, TransactionalInterface {
  use \Civi\Test\ACLPermissionTrait;

  public function setUpHeadless() {
    // Civi\Test has many helpers, like install(), uninstall(), sql(), and sqlFile().
    // See: https://docs.civicrm.org/dev/en/latest/testing/phpunit/#civitest
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply();
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
          'where' => [],
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
        'label' => '',
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
    $this->assertArrayNotHasKey('contact_type', $result->first());
    $this->assertArrayNotHasKey('source', $result->first());
    $this->assertArrayNotHasKey('last_name', $result->first());

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
      ->indexBy('first_name')->column('id');

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
          'label' => '',
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

    $config->userPermissionClass->permissions = ['access CiviCRM', 'administer CiviCRM data'];

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
        'label' => '',
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
    catch (\API_Exception $e) {
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
                'Contact_Email_contact_id_01.on_hold:name',
                '=',
                'On Hold Bounce',
              ],
              [
                'bg-warning',
                'Contact_Email_contact_id_01.on_hold:name',
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
   * Test conditional styles
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

}
