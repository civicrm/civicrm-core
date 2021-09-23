<?php
namespace api\v4\SearchDisplay;

use Civi\API\Exception\UnauthorizedException;
use Civi\Api4\Contact;
use Civi\Api4\ContactType;
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
    $this->assertEquals('One', $result[0]['first_name']['raw']);
    $this->assertEquals('Two', $result[1]['first_name']['raw']);

    // Raw value should be boolean, view value should be string
    $this->assertEquals(FALSE, $result[0]['is_deceased']['raw']);
    $this->assertEquals(ts('No'), $result[0]['is_deceased']['view']);

    $params['filters'] = ['last_name' => $lastName, 'id' => ['>' => $result[0]['id']['raw'], '<=' => $result[1]['id']['raw'] + 1]];
    $params['sort'] = [['first_name', 'ASC']];
    $result = civicrm_api4('SearchDisplay', 'run', $params);
    $this->assertCount(2, $result);
    $this->assertEquals('Three', $result[0]['first_name']['raw']);
    $this->assertEquals('Two', $result[1]['first_name']['raw']);

    $params['filters'] = ['last_name' => $lastName, 'contact_sub_type:label' => ['Tester', 'Bot']];
    $result = civicrm_api4('SearchDisplay', 'run', $params);
    $this->assertCount(3, $result);

    // Comma indicates first_name OR last_name
    $params['filters'] = ['first_name,last_name' => $lastName, 'contact_sub_type' => ['Tester']];
    $result = civicrm_api4('SearchDisplay', 'run', $params);
    $this->assertCount(2, $result);

    // Comma indicates first_name OR middle_name, matches "One" or "None"
    $params['filters'] = ['first_name,middle_name' => 'one', 'last_name' => $lastName];
    $result = civicrm_api4('SearchDisplay', 'run', $params);
    $this->assertCount(2, $result);
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
    $this->assertNotEmpty($result->first()['display_name']['raw']);
    // Assert that display name was added to the search due to the link token
    $this->assertNotEmpty($result->first()['sort_name']['raw']);

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
    $this->assertEquals('Individual', $result->first()['contact_type']['raw']);
    $this->assertEquals('Unit test', $result->first()['source']['raw']);
    $this->assertEquals($lastName, $result->first()['last_name']['raw']);
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
    $this->assertEquals($sampleData['Two'], $result[0]['id']['raw']);

    $hooks->setHook('civicrm_aclWhereClause', [$this, 'aclWhereGreaterThan']);
    $this->cleanupCachedPermissions();
    $result = civicrm_api4('SearchDisplay', 'run', $params);
    $this->assertCount(2, $result);
    $this->assertEquals($sampleData['Three'], $result[0]['id']['raw']);
    $this->assertEquals($sampleData['Four'], $result[1]['id']['raw']);
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
      $this->assertEquals($seeded->column('id'), $repeat->column('id'));
    }
  }

}
