<?php
namespace api\v4\SearchDisplay;

use Civi\Api4\Contact;
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
  public function testRunDisplay() {
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
    $this->assertEquals('One', $result[0]['first_name']);
    $this->assertEquals('Two', $result[1]['first_name']);

    $params['filters'] = ['id' => ['>' => $result[0]['id'], '<=' => $result[1]['id'] + 1]];
    $params['sort'] = [['first_name', 'ASC']];
    $result = civicrm_api4('SearchDisplay', 'run', $params);
    $this->assertCount(2, $result);
    $this->assertEquals('Three', $result[0]['first_name']);
    $this->assertEquals('Two', $result[1]['first_name']);
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
    $this->assertEquals($sampleData['Two'], $result[0]['id']);

    $hooks->setHook('civicrm_aclWhereClause', [$this, 'aclWhereGreaterThan']);
    $this->cleanupCachedPermissions();
    $result = civicrm_api4('SearchDisplay', 'run', $params);
    $this->assertCount(2, $result);
    $this->assertEquals($sampleData['Three'], $result[0]['id']);
    $this->assertEquals($sampleData['Four'], $result[1]['id']);

  }

}
