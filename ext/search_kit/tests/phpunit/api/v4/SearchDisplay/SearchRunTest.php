<?php
namespace api\v4\SearchDisplay;

// Not sure why this is needed but without it Jenkins crashed
require_once __DIR__ . '/../../../../../../../tests/phpunit/api/v4/Api4TestBase.php';

use api\v4\Api4TestBase;
use Civi\API\Exception\UnauthorizedException;
use Civi\Api4\Activity;
use Civi\Api4\Contact;
use Civi\Api4\ContactType;
use Civi\Api4\Email;
use Civi\Api4\Phone;
use Civi\Api4\SavedSearch;
use Civi\Api4\SearchDisplay;
use Civi\Api4\UFMatch;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class SearchRunTest extends Api4TestBase implements TransactionalInterface {
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
              'key' => 'first_name',
              'label' => 'Name',
              'type' => 'field',
              'rewrite' => '{if "[nick_name]"}[nick_name]{else}[first_name]{/if} [last_name]',
            ],
            [
              'key' => 'Contact_Email_contact_id_01.email',
              'label' => 'Email',
              'type' => 'field',
              'rewrite' => '{if "[Contact_Email_contact_id_01.email]"}[Contact_Email_contact_id_01.email] ([Contact_Email_contact_id_01.location_type_id:label]){/if}',
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

    // Contact 1 first name can be updated
    $this->assertEquals('One', $result[0]['columns'][0]['val']);
    $this->assertEquals($contacts[0], $result[0]['columns'][0]['edit']['record']['id']);
    $this->assertEquals('Contact', $result[0]['columns'][0]['edit']['entity']);
    $this->assertEquals('Text', $result[0]['columns'][0]['edit']['input_type']);
    $this->assertEquals('String', $result[0]['columns'][0]['edit']['data_type']);
    $this->assertEquals('first_name', $result[0]['columns'][0]['edit']['value_key']);
    $this->assertEquals('update', $result[0]['columns'][0]['edit']['action']);
    $this->assertEquals('One', $result[0]['columns'][0]['edit']['value']);

    // Contact 1 email can be updated
    $this->assertEquals('testmail@unit.test', $result[0]['columns'][1]['val']);
    $this->assertEquals($email, $result[0]['columns'][1]['edit']['record']['id']);
    $this->assertEquals('Email', $result[0]['columns'][1]['edit']['entity']);
    $this->assertEquals('Text', $result[0]['columns'][1]['edit']['input_type']);
    $this->assertEquals('String', $result[0]['columns'][1]['edit']['data_type']);
    $this->assertEquals('email', $result[0]['columns'][1]['edit']['value_key']);
    $this->assertEquals('update', $result[0]['columns'][1]['edit']['action']);
    $this->assertEquals('testmail@unit.test', $result[0]['columns'][1]['edit']['value']);

    // Contact 1 - new phone can be created
    $this->assertNull($result[0]['columns'][2]['val']);
    $this->assertEquals(['contact_id' => $contacts[0]], $result[0]['columns'][2]['edit']['record']);
    $this->assertEquals('Phone', $result[0]['columns'][2]['edit']['entity']);
    $this->assertEquals('Text', $result[0]['columns'][2]['edit']['input_type']);
    $this->assertEquals('String', $result[0]['columns'][2]['edit']['data_type']);
    $this->assertEquals('phone', $result[0]['columns'][2]['edit']['value_key']);
    $this->assertEquals('create', $result[0]['columns'][2]['edit']['action']);
    $this->assertNull($result[0]['columns'][2]['edit']['value']);

    // Contact 2 first name can be added
    $this->assertNull($result[1]['columns'][0]['val']);
    $this->assertEquals($contacts[1], $result[1]['columns'][0]['edit']['record']['id']);
    $this->assertEquals('Contact', $result[1]['columns'][0]['edit']['entity']);
    $this->assertEquals('Text', $result[1]['columns'][0]['edit']['input_type']);
    $this->assertEquals('String', $result[1]['columns'][0]['edit']['data_type']);
    $this->assertEquals('first_name', $result[1]['columns'][0]['edit']['value_key']);
    $this->assertEquals('update', $result[1]['columns'][0]['edit']['action']);
    $this->assertNull($result[1]['columns'][0]['edit']['value']);

    // Contact 2 - new email can be created
    $this->assertNull($result[1]['columns'][1]['val']);
    $this->assertEquals(['contact_id' => $contacts[1], 'is_primary' => TRUE], $result[1]['columns'][1]['edit']['record']);
    $this->assertEquals('Email', $result[1]['columns'][1]['edit']['entity']);
    $this->assertEquals('Text', $result[1]['columns'][1]['edit']['input_type']);
    $this->assertEquals('String', $result[1]['columns'][1]['edit']['data_type']);
    $this->assertEquals('email', $result[1]['columns'][1]['edit']['value_key']);
    $this->assertEquals('create', $result[1]['columns'][1]['edit']['action']);
    $this->assertNull($result[1]['columns'][1]['edit']['value']);

    // Contact 2 phone can be updated
    $this->assertEquals('123456', $result[1]['columns'][2]['val']);
    $this->assertEquals($phone, $result[1]['columns'][2]['edit']['record']['id']);
    $this->assertEquals('Phone', $result[1]['columns'][2]['edit']['entity']);
    $this->assertEquals('Text', $result[1]['columns'][2]['edit']['input_type']);
    $this->assertEquals('String', $result[1]['columns'][2]['edit']['data_type']);
    $this->assertEquals('phone', $result[1]['columns'][2]['edit']['value_key']);
    $this->assertEquals('update', $result[1]['columns'][2]['edit']['action']);
    $this->assertEquals('123456', $result[1]['columns'][2]['edit']['value']);
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

    $config->userPermissionClass->permissions = ['access CiviCRM', 'administer search_kit'];

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
          'orderBy' => ['activity_type_id:label'],
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
    $this->assertEquals([['class' => 'fa-slideshare', 'side' => 'left']], $result[0]['columns'][0]['icons']);
    // Activity type icon + conditional icon based on status
    $this->assertEquals([['class' => 'fa-star', 'side' => 'right'], ['class' => 'fa-phone', 'side' => 'left']], $result[1]['columns'][0]['icons']);
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
        'label' => '',
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
      'value' => 'One',
    ];
    // Ensure first_name is editable but not organization_name or household_name
    $this->assertEquals($expectedFirstNameEdit, $result[0]['columns'][0]['edit']);
    $this->assertTrue(!isset($result[0]['columns'][1]['edit']));
    $this->assertTrue(!isset($result[0]['columns'][2]['edit']));

    // Second Individual
    $expectedFirstNameEdit['record']['id'] = $contact[1]['id'];
    $expectedFirstNameEdit['value'] = NULL;
    $this->assertEquals($expectedFirstNameEdit, $result[1]['columns'][0]['edit']);
    $this->assertTrue(!isset($result[1]['columns'][1]['edit']));
    $this->assertTrue(!isset($result[1]['columns'][2]['edit']));

    // Third contact: Organization
    $expectedFirstNameEdit['record']['id'] = $contact[2]['id'];
    $expectedFirstNameEdit['value_key'] = 'organization_name';
    $this->assertTrue(!isset($result[2]['columns'][0]['edit']));
    $this->assertEquals($expectedFirstNameEdit, $result[2]['columns'][1]['edit']);
    $this->assertTrue(!isset($result[2]['columns'][2]['edit']));

    // Third contact: Household
    $expectedFirstNameEdit['record']['id'] = $contact[3]['id'];
    $expectedFirstNameEdit['value_key'] = 'household_name';
    $this->assertTrue(!isset($result[3]['columns'][0]['edit']));
    $this->assertTrue(!isset($result[3]['columns'][1]['edit']));
    $this->assertEquals($expectedFirstNameEdit, $result[3]['columns'][2]['edit']);
  }

  public function testContributionCurrency():void {
    $contributions = $this->saveTestRecords('Contribution', [
      'records' => [
        ['total_amount' => 100, 'currency' => 'GBP'],
        ['total_amount' => 200, 'currency' => 'USD'],
        ['total_amount' => 500, 'currency' => 'JPY'],
      ],
    ]);

    $params = [
      'checkPermissions' => FALSE,
      'return' => 'page:1',
      'savedSearch' => [
        'api_entity' => 'Contribution',
        'api_params' => [
          'version' => 4,
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
        'label' => '',
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
    $this->assertEquals('fa-star', $result[0]['columns'][0]['icons'][0]['class']);
    $this->assertEquals('No icon', $result[1]['columns'][0]['val']);
    $this->assertEquals('fa-user', $result[1]['columns'][0]['icons'][0]['class']);
    $this->assertEquals('Starry', $result[2]['columns'][0]['val']);
    $this->assertEquals('fa-star', $result[2]['columns'][0]['icons'][0]['class']);
  }

}
