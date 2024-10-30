<?php

/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

namespace api\v4\Entity;

use Civi\Api4\Domain;
use Civi\Api4\Group;
use Civi\Api4\Managed;
use Civi\Api4\Navigation;
use Civi\Api4\OptionGroup;
use Civi\Api4\OptionValue;
use Civi\Api4\SavedSearch;
use Civi\Test;
use Civi\Test\CiviEnvBuilder;
use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;
use CRM_Core_ManagedEntities;
use CRM_Core_Module;
use CRM_Utils_System;
use PHPUnit\Framework\TestCase;

/**
 * @group headless
 */
class ManagedEntityTest extends TestCase implements HeadlessInterface, TransactionalInterface, HookInterface {

  use Test\Api4TestTrait;

  /**
   * @var array[]
   */
  private $_managedEntities = [];

  public function setUp(): void {
    $this->_managedEntities = [];
    // Ensure exceptions get thrown
    \Civi::settings()->set('debug_enabled', TRUE);
    parent::setUp();
  }

  public function tearDown(): void {
    \Civi::settings()->revert('debug_enabled');
    // Disable multisite
    \Civi::settings()->revert('is_enabled');
    parent::tearDown();
  }

  public function setUpHeadless(): CiviEnvBuilder {
    return Test::headless()->apply();
  }

  public function hook_civicrm_managed(array &$entities): void {
    $entities = array_merge($entities, $this->_managedEntities);
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public function testGetFields(): void {
    $fields = SavedSearch::getFields(FALSE)
      ->addWhere('type', '=', 'Extra')
      ->setLoadOptions(TRUE)
      ->execute()->indexBy('name');

    $this->assertEquals('Boolean', $fields['has_base']['data_type']);
    // If this core extension ever goes away or gets renamed, just pick a different one here
    $this->assertArrayHasKey('org.civicrm.flexmailer', $fields['base_module']['options']);
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public function testRevertSavedSearch(): void {
    $originalState = [
      'name' => 'TestManagedSavedSearch',
      'label' => 'Test Saved Search',
      'description' => 'Original state',
      'api_entity' => 'Contact',
      'api_params' => [
        'version' => 4,
        'select' => ['id'],
        'orderBy' => ['id', 'ASC'],
      ],
    ];
    $this->_managedEntities[] = [
      'module' => 'civicrm',
      'name' => 'testSavedSearch',
      'entity' => 'SavedSearch',
      'cleanup' => 'never',
      'update' => 'never',
      'params' => [
        'version' => 4,
        'values' => $originalState,
      ],
    ];

    Managed::reconcile(FALSE)->addModule('civicrm')->execute();

    $search = SavedSearch::get(FALSE)
      ->addWhere('name', '=', 'TestManagedSavedSearch')
      ->addSelect('*', 'local_modified_date')
      ->execute()->single();
    foreach ($originalState as $fieldName => $originalValue) {
      $this->assertEquals($originalValue, $search[$fieldName]);
    }
    $this->assertNull($search['expires_date']);
    $this->assertNull($search['local_modified_date']);

    SavedSearch::update(FALSE)
      ->addValue('id', $search['id'])
      ->addValue('description', 'Altered state')
      ->addValue('expires_date', 'now + 1 year')
      ->execute();

    $time = $this->getCurrentTimestamp();
    $search = SavedSearch::get(FALSE)
      ->addWhere('name', '=', 'TestManagedSavedSearch')
      ->addSelect('*', 'has_base', 'base_module', 'local_modified_date')
      ->execute()->single();
    $this->assertEquals('Altered state', $search['description']);
    // Check calculated fields
    $this->assertTrue($search['has_base']);
    $this->assertEquals('civicrm', $search['base_module']);
    // local_modified_date should reflect the update just made
    $this->assertGreaterThanOrEqual($time, $search['local_modified_date']);
    $this->assertLessThanOrEqual($this->getCurrentTimestamp(), $search['local_modified_date']);
    $this->assertGreaterThan($time, $search['expires_date']);

    SavedSearch::revert(FALSE)
      ->addWhere('name', '=', 'TestManagedSavedSearch')
      ->execute();

    // Entity should be revered to original state
    $result = SavedSearch::get(FALSE)
      ->addWhere('name', '=', 'TestManagedSavedSearch')
      ->addSelect('*', 'has_base', 'base_module', 'local_modified_date')
      ->execute();
    $search = $result->single();
    foreach ($originalState as $fieldName => $originalValue) {
      $this->assertEquals($originalValue, $search[$fieldName]);
    }
    $this->assertNull($search['expires_date']);
    // Check calculated fields
    $this->assertTrue($search['has_base']);
    $this->assertEquals('civicrm', $search['base_module']);
    // local_modified_date should be reset by the revert action
    $this->assertNull($search['local_modified_date']);

    // Check calculated fields for a non-managed entity - they should be empty
    $newName = 'search name';
    SavedSearch::create(FALSE)
      ->addValue('name', $newName)
      ->addValue('label', 'Whatever')
      ->execute();
    $search = SavedSearch::get(FALSE)
      ->addWhere('name', '=', $newName)
      ->addSelect('label', 'has_base', 'base_module', 'local_modified_date')
      ->execute()->single();
    $this->assertEquals('Whatever', $search['label']);
    // Check calculated fields
    $this->assertEquals(NULL, $search['base_module']);
    $this->assertFalse($search['has_base']);
    $this->assertNull($search['local_modified_date']);
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public function testAutoUpdateSearch(): void {
    $autoUpdateSearch = [
      'module' => 'civicrm',
      'name' => 'testAutoUpdate',
      'entity' => 'SavedSearch',
      'cleanup' => 'unused',
      'update' => 'unmodified',
      'params' => [
        'version' => 4,
        'values' => [
          'name' => 'TestAutoUpdateSavedSearch',
          'label' => 'Test AutoUpdate Search',
          'description' => 'Original state',
          'api_entity' => 'Email',
          'api_params' => [
            'version' => 4,
            'select' => ['id'],
            'orderBy' => ['id', 'ASC'],
          ],
        ],
      ],
    ];
    // Add managed search
    $this->_managedEntities[] = $autoUpdateSearch;
    CRM_Core_ManagedEntities::singleton(TRUE)->reconcile();

    $search = SavedSearch::get(FALSE)
      ->addWhere('name', '=', 'TestAutoUpdateSavedSearch')
      ->addSelect('description', 'local_modified_date')
      ->execute()->single();
    $this->assertEquals('Original state', $search['description']);
    $this->assertNull($search['local_modified_date']);

    // Remove managed search
    $this->_managedEntities = [];
    CRM_Core_ManagedEntities::singleton(TRUE)->reconcile();

    // Because the search has no displays, it will be deleted (cleanup = unused)
    $search = SavedSearch::get(FALSE)
      ->addWhere('name', '=', 'TestAutoUpdateSavedSearch')
      ->execute();
    $this->assertCount(0, $search);

    // Restore managed entity
    $this->_managedEntities = [$autoUpdateSearch];
    CRM_Core_ManagedEntities::singleton(TRUE)->reconcile();

    // Entity should be restored
    $result = SavedSearch::get(FALSE)
      ->addWhere('name', '=', 'TestAutoUpdateSavedSearch')
      ->addSelect('description', 'has_base', 'base_module', 'local_modified_date')
      ->execute();
    $search = $result->single();
    $this->assertEquals('Original state', $search['description']);
    // Check calculated fields
    $this->assertTrue($search['has_base']);
    $this->assertEquals('civicrm', $search['base_module']);
    $this->assertNull($search['local_modified_date']);

    $search = SavedSearch::get(FALSE)
      ->addWhere('name', '=', 'TestAutoUpdateSavedSearch')
      ->addSelect('description', 'local_modified_date')
      ->execute()->single();
    $this->assertEquals('Original state', $search['description']);
    $this->assertNull($search['local_modified_date']);

    // Update packaged version
    $autoUpdateSearch['params']['values']['description'] = 'New packaged state';
    $this->_managedEntities = [$autoUpdateSearch];
    CRM_Core_ManagedEntities::singleton(TRUE)->reconcile();

    // Because the entity was not modified, it will be updated to match the new packaged version
    $search = SavedSearch::get(FALSE)
      ->addWhere('name', '=', 'TestAutoUpdateSavedSearch')
      ->addSelect('description', 'local_modified_date')
      ->execute()->single();
    $this->assertEquals('New packaged state', $search['description']);
    $this->assertNull($search['local_modified_date']);

    // Update local
    SavedSearch::update(FALSE)
      ->addValue('id', $search['id'])
      ->addValue('description', 'Altered state')
      ->execute();

    // Update packaged version
    $autoUpdateSearch['params']['values']['description'] = 'Newer packaged state';
    $this->_managedEntities = [$autoUpdateSearch];
    CRM_Core_ManagedEntities::singleton(TRUE)->reconcile();

    // Because the entity was  modified, it will not be updated
    $search = SavedSearch::get(FALSE)
      ->addWhere('name', '=', 'TestAutoUpdateSavedSearch')
      ->addSelect('description', 'local_modified_date')
      ->execute()->single();
    $this->assertEquals('Altered state', $search['description']);
    $this->assertNotNull($search['local_modified_date']);

    SavedSearch::revert(FALSE)
      ->addWhere('name', '=', 'TestAutoUpdateSavedSearch')
      ->execute();

    // Entity should be revered to newer packaged state
    $result = SavedSearch::get(FALSE)
      ->addWhere('name', '=', 'TestAutoUpdateSavedSearch')
      ->addSelect('description', 'has_base', 'base_module', 'local_modified_date')
      ->execute();
    $search = $result->single();
    $this->assertEquals('Newer packaged state', $search['description']);
    // Check calculated fields
    $this->assertTrue($search['has_base']);
    $this->assertEquals('civicrm', $search['base_module']);
    // local_modified_date should be reset by the revert action
    $this->assertNull($search['local_modified_date']);

    // Delete by user
    SavedSearch::delete(FALSE)
      ->addWhere('name', '=', 'TestAutoUpdateSavedSearch')
      ->execute();
    CRM_Core_ManagedEntities::singleton(TRUE)->reconcile();

    // Because update policy is 'unmodified' the search won't be re-created
    $search = SavedSearch::get(FALSE)
      ->addWhere('name', '=', 'TestAutoUpdateSavedSearch')
      ->execute();
    $this->assertCount(0, $search);

    // But the managed record is still hanging around with a null entity_id
    $managed = Managed::get(FALSE)
      ->addWhere('name', '=', 'testAutoUpdate')
      ->addWhere('module', '=', 'civicrm')
      ->execute();
    $this->assertCount(1, $managed);
    $this->assertNull($managed[0]['entity_id']);
    $managedId = $managed[0]['id'];

    // Change update policy to 'always'
    $autoUpdateSearch['update'] = 'always';
    $this->_managedEntities = [$autoUpdateSearch];
    CRM_Core_ManagedEntities::singleton(TRUE)->reconcile();

    // Because update policy is 'always' the search will be re-created
    $search = SavedSearch::get(FALSE)
      ->addWhere('name', '=', 'TestAutoUpdateSavedSearch')
      ->execute();
    $this->assertCount(1, $search);

    // But the managed record is still hanging around with a null id
    $managed = Managed::get(FALSE)
      ->addWhere('name', '=', 'testAutoUpdate')
      ->addWhere('module', '=', 'civicrm')
      ->execute();
    $this->assertCount(1, $managed);
    $this->assertEquals($search[0]['id'], $managed[0]['entity_id']);
    $this->assertEquals($managedId, $managed[0]['id']);
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public function testOptionGroupAndValues(): void {
    $optionGroup = [
      'module' => 'civicrm',
      'name' => 'testManagedOptionGroup',
      'entity' => 'OptionGroup',
      'cleanup' => 'unused',
      'update' => 'unmodified',
      'params' => [
        'version' => 4,
        'values' => [
          'name' => 'testManagedOptionGroup',
          'title' => 'Test Managed Option Group',
          'description' => 'Original state',
          'is_active' => TRUE,
          'is_locked' => FALSE,
        ],
      ],
    ];
    $optionValue1 = [
      'module' => 'civicrm',
      'name' => 'testManagedOptionValue1',
      'entity' => 'OptionValue',
      'cleanup' => 'unused',
      'update' => 'unmodified',
      'params' => [
        'version' => 4,
        'values' => [
          'option_group_id.name' => 'testManagedOptionGroup',
          'value' => 1,
          'label' => 'Option Value 1',
          'description' => 'Original state',
          'is_active' => TRUE,
          'is_reserved' => FALSE,
          'weight' => 1,
          'is_default' => 1,
          'domain_id' => NULL,
          'icon' => 'fa-test',
        ],
      ],
    ];
    $this->_managedEntities[] = $optionGroup;
    $this->_managedEntities[] = $optionValue1;
    CRM_Core_ManagedEntities::singleton(TRUE)->reconcile();

    $values = OptionValue::get(FALSE)
      ->addSelect('*', 'local_modified_date', 'has_base')
      ->addWhere('option_group_id.name', '=', 'testManagedOptionGroup')
      ->execute();

    $this->assertCount(1, $values);
    $this->assertEquals('Option Value 1', $values[0]['label']);
    $this->assertNull($values[0]['local_modified_date']);
    $this->assertTrue($values[0]['has_base']);

    // Update option 1, now it should have a local_modified_date
    // And the new label should persist after a reconcile
    OptionValue::update(FALSE)
      ->addWhere('id', '=', $values[0]['id'])
      ->addValue('label', '1 New Label')
      ->execute();

    $optionValue2 = [
      'module' => 'civicrm',
      'name' => 'testManagedOptionValue2',
      'entity' => 'OptionValue',
      'cleanup' => 'unused',
      'update' => 'unmodified',
      'params' => [
        'version' => 4,
        'values' => [
          'option_group_id.name' => 'testManagedOptionGroup',
          'value' => 2,
          'label' => 'Option Value 2',
          'description' => 'Original state',
          'is_active' => TRUE,
          'is_reserved' => FALSE,
          'weight' => 2,
          'icon' => 'fa-test',
        ],
      ],
    ];
    $this->_managedEntities[] = $optionValue2;

    CRM_Core_ManagedEntities::singleton(TRUE)->reconcile();

    $values = OptionValue::get(FALSE)
      ->addWhere('option_group_id.name', '=', 'testManagedOptionGroup')
      ->addSelect('*', 'local_modified_date', 'has_base')
      ->addOrderBy('weight')
      ->execute();

    $this->assertCount(2, $values);
    $this->assertEquals('1 New Label', $values[0]['label']);
    $this->assertNotNull($values[0]['local_modified_date']);
    $this->assertTrue($values[0]['has_base']);
    $this->assertEquals('Option Value 2', $values[1]['label']);
    $this->assertNull($values[1]['local_modified_date']);
    $this->assertTrue($values[1]['has_base']);

    $this->_managedEntities = [];
    CRM_Core_ManagedEntities::singleton(TRUE)->reconcile();

    $this->assertCount(0, OptionValue::get(FALSE)->addWhere('id', 'IN', $values->column('id'))->execute());
    $this->assertCount(0, OptionGroup::get(FALSE)->addWhere('name', '=', 'testManagedOptionGroup')->execute());
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public function testManagedNavigationWeights(): void {
    $managedEntities = [
      [
        'module' => 'unit.test.fake.ext',
        'name' => 'Navigation_Test_Parent',
        'entity' => 'Navigation',
        'cleanup' => 'unused',
        'update' => 'unmodified',
        'params' => [
          'version' => 4,
          'values' => [
            'label' => 'Test Parent',
            'name' => 'Test_Parent',
            'url' => NULL,
            'icon' => 'crm-i test',
            'permission' => 'access CiviCRM',
            'permission_operator' => '',
            'is_active' => TRUE,
            'weight' => 50,
            'parent_id' => NULL,
            'has_separator' => NULL,
            'domain_id' => 'current_domain',
          ],
        ],
      ],
      [
        'module' => 'unit.test.fake.ext',
        'name' => 'Navigation_Test_Child_1',
        'entity' => 'Navigation',
        'cleanup' => 'unused',
        'update' => 'unmodified',
        'params' => [
          'version' => 4,
          'values' => [
            'label' => 'Test Child 1',
            'name' => 'Test_Child_1',
            'url' => 'civicrm/test1?reset=1',
            'icon' => NULL,
            'permission' => 'access CiviCRM',
            'permission_operator' => '',
            'parent_id.name' => 'Test_Parent',
            'is_active' => TRUE,
            'has_separator' => 1,
            'domain_id' => 'current_domain',
          ],
        ],
      ],
      [
        'module' => 'unit.test.fake.ext',
        'name' => 'Navigation_Test_Child_2',
        'entity' => 'Navigation',
        'cleanup' => 'unused',
        'update' => 'unmodified',
        'params' => [
          'version' => 4,
          'values' => [
            'label' => 'Test Child 2',
            'name' => 'Test_Child_2',
            'url' => 'civicrm/test2?reset=1',
            'icon' => NULL,
            'permission' => 'access CiviCRM',
            'permission_operator' => '',
            'parent_id.name' => 'Test_Parent',
            'is_active' => TRUE,
            'has_separator' => 2,
            'domain_id' => 'current_domain',
          ],
        ],
      ],
      [
        'module' => 'unit.test.fake.ext',
        'name' => 'Navigation_Test_Child_3',
        'entity' => 'Navigation',
        'cleanup' => 'unused',
        'update' => 'unmodified',
        'params' => [
          'version' => 4,
          'values' => [
            'label' => 'Test Child 3',
            'name' => 'Test_Child_3',
            'url' => 'civicrm/test3?reset=1',
            'icon' => NULL,
            'permission' => 'access CiviCRM',
            'permission_operator' => '',
            'parent_id.name' => 'Test_Parent',
            'is_active' => TRUE,
            'has_separator' => NULL,
            'domain_id' => 'current_domain',
          ],
        ],
      ],
    ];
    $this->_managedEntities = $managedEntities;

    // Throw a monkey wrench by placing duplicates in another domain
    $d2 = Domain::create(FALSE)
      ->addValue('name', 'Decoy domain')
      ->addValue('version', CRM_Utils_System::version())
      ->execute()->single();
    foreach ($managedEntities as $item) {
      $decoys[] = civicrm_api4('Navigation', 'create', [
        'checkPermissions' => FALSE,
        'values' => ['domain_id' => $d2['id']] + $item['params']['values'],
      ])->first();
    }

    // Refresh managed entities with module active
    $allModules = [
      new CRM_Core_Module('unit.test.fake.ext', TRUE),
    ];
    $modulesToReconcile = ['unit.test.fake.ext'];
    (new CRM_Core_ManagedEntities($allModules))->reconcile($modulesToReconcile);

    $nav = Navigation::get(FALSE)
      ->addWhere('name', '=', 'Test_Parent')
      ->addChain('export', Navigation::export()->setId('$id'))
      ->execute()->first();

    $this->assertCount(4, $nav['export']);
    $this->assertEquals(TRUE, $nav['is_active']);

    $this->assertEquals(50, $nav['export'][0]['params']['values']['weight']);
    $this->assertEquals('Navigation_Test_Parent_Navigation_Test_Child_1', $nav['export'][1]['name']);
    $this->assertEquals('Navigation_Test_Parent_Navigation_Test_Child_2', $nav['export'][2]['name']);
    $this->assertEquals('Navigation_Test_Parent_Navigation_Test_Child_3', $nav['export'][3]['name']);
    // The has_separator should be using numeric key not pseudoconstant
    $this->assertNull($nav['export'][0]['params']['values']['has_separator']);
    $this->assertEquals(1, $nav['export'][1]['params']['values']['has_separator']);
    $this->assertEquals(2, $nav['export'][2]['params']['values']['has_separator']);
    // Weight should not be included in export of children, leaving it to be auto-managed
    $this->assertArrayNotHasKey('weight', $nav['export'][1]['params']['values']);
    // Domain is auto-managed & should not be included in export
    $this->assertArrayNotHasKey('domain_id', $nav['export'][1]['params']['values']);
    $this->assertArrayNotHasKey('domain_id.name', $nav['export'][1]['params']['values']);
    $this->assertArrayNotHasKey('domain_id:name', $nav['export'][1]['params']['values']);

    // Children should have been assigned correct auto-weights
    $children = Navigation::get(FALSE)
      ->addWhere('parent_id.name', '=', 'Test_Parent')
      ->addOrderBy('weight')
      ->execute();
    foreach ([1, 2, 3] as $index => $weight) {
      $this->assertEquals($weight, $children[$index]['weight']);
      $this->assertEquals(TRUE, $children[$index]['is_active']);
    }

    // Try exporting the decoy records
    $decoyExport = Navigation::export(FALSE)
      ->setId($decoys[0]['id'])
      ->execute();
    $this->assertCount(4, $decoyExport);
    $this->assertArrayNotHasKey('weight', $decoyExport[1]['params']['values']);
    $this->assertArrayNotHasKey('domain_id', $decoyExport[1]['params']['values']);
    $this->assertArrayNotHasKey('domain_id.name', $decoyExport[1]['params']['values']);
    $this->assertArrayNotHasKey('domain_id:name', $decoyExport[1]['params']['values']);

    // Refresh managed entities with module disabled
    $allModules = [
      new CRM_Core_Module('unit.test.fake.ext', FALSE),
    ];
    // If module is disabled it will not run hook_civicrm_managed.
    $this->_managedEntities = [];
    (new CRM_Core_ManagedEntities($allModules))->reconcile($modulesToReconcile);

    // Children's weight should have been unaffected, but they should be disabled
    $children = Navigation::get(FALSE)
      ->addWhere('parent_id.name', '=', 'Test_Parent')
      ->addOrderBy('weight')
      ->execute();
    foreach ([1, 2, 3] as $index => $weight) {
      $this->assertEquals($weight, $children[$index]['weight']);
      $this->assertEquals(FALSE, $children[$index]['is_active']);
    }

    $nav = Navigation::get(FALSE)
      ->addWhere('name', '=', 'Test_Parent')
      ->execute()->first();
    $this->assertEquals(FALSE, $nav['is_active']);

    // Refresh managed entities with module active
    $allModules = [
      new CRM_Core_Module('unit.test.fake.ext', TRUE),
    ];
    $this->_managedEntities = $managedEntities;
    (new CRM_Core_ManagedEntities($allModules))->reconcile($modulesToReconcile);

    // Children's weight should have been unaffected, but they should be enabled
    $children = Navigation::get(FALSE)
      ->addWhere('parent_id.name', '=', 'Test_Parent')
      ->addOrderBy('weight')
      ->execute();
    foreach ([1, 2, 3] as $index => $weight) {
      $this->assertEquals($weight, $children[$index]['weight']);
      $this->assertEquals(TRUE, $children[$index]['is_active']);
    }
    // Parent should also be re-enabled
    $nav = Navigation::get(FALSE)
      ->addWhere('name', '=', 'Test_Parent')
      ->execute()->first();
    $this->assertEquals(TRUE, $nav['is_active']);
  }

  /**
   * Test multisite managed entities
   * @see \Civi\Managed\MultisiteManaged
   */
  public function testMultiDomainNavigation(): void {
    $this->_managedEntities[] = [
      'module' => 'unit.test.fake.ext',
      'name' => 'Navigation_Test_Domains',
      'entity' => 'Navigation',
      'cleanup' => 'unused',
      'update' => 'unmodified',
      'params' => [
        'version' => 4,
        'values' => [
          'label' => 'Test Domains',
          'name' => 'Test_Domains',
          'url' => 'civicrm/foo/bar',
          'icon' => 'crm-i test',
          'permission' => ['access CiviCRM'],
          'weight' => 50,
          'domain_id' => 'current_domain',
        ],
      ],
    ];
    $managedRecords = [];
    \CRM_Utils_Hook::managed($managedRecords, ['unit.test.fake.ext']);
    $result = \CRM_Utils_Array::findAll($managedRecords, ['module' => 'unit.test.fake.ext', 'name' => 'Navigation_Test_Domains']);
    $this->assertCount(1, $result);

    // Enable multisite with multiple domains
    \Civi::settings()->set('is_enabled', TRUE);
    Domain::create(FALSE)
      ->addValue('name', 'Another domain')
      ->addValue('version', CRM_Utils_System::version())
      ->execute()->single();
    $allDomains = Domain::get(FALSE)->addSelect('id')->addOrderBy('id')->execute();
    $this->assertGreaterThan(1, $allDomains->count());

    $managedRecords = [];
    \CRM_Utils_Hook::managed($managedRecords, ['unit.test.fake.ext']);

    // Base entity should not have been renamed
    $result = \CRM_Utils_Array::findAll($managedRecords, ['module' => 'unit.test.fake.ext', 'name' => 'Navigation_Test_Domains']);
    $this->assertCount(1, $result);

    // New item should have been inserted for extra domains
    foreach (array_slice($allDomains->column('id'), 1) as $domain) {
      $result = \CRM_Utils_Array::findAll($managedRecords, ['module' => 'unit.test.fake.ext', 'name' => 'Navigation_Test_Domains_' . $domain]);
      $this->assertCount(1, $result);
    }
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public function testExportAndCreateGroup(): void {
    $original = Group::create(FALSE)
      ->addValue('title', 'My Managed Group')
      ->execute()->single();

    $export = Group::export(FALSE)
      ->setId($original['id'])
      ->execute()->single();

    Group::delete(FALSE)->addWhere('id', '=', $original['id'])->execute();

    $this->_managedEntities = [
      ['module' => 'civicrm'] + $export,
    ];
    CRM_Core_ManagedEntities::singleton(TRUE)->reconcile();

    $created = Group::get(FALSE)
      ->addWhere('name', '=', $original['name'])
      ->execute()->single();

    $this->assertEquals('My Managed Group', $created['title']);
    $this->assertEquals($original['name'], $created['name']);
    $this->assertGreaterThan($original['id'], $created['id']);
  }

  /**
   * Tests a scenario where a record may already exist and we want to make it a managed entity.
   *
   * @throws \CRM_Core_Exception
   */
  public function testMatchExisting(): void {
    $optionGroup = OptionGroup::create(FALSE)
      ->addValue('title', 'My pre-existing group')
      ->addValue('name', 'My_pre_existing_group')
      ->execute()->first();

    $managed = [
      'module' => 'civicrm',
      'name' => 'preExistingGroup',
      'entity' => 'OptionGroup',
      'cleanup' => 'always',
      'update' => 'always',
      'params' => [
        'version' => 4,
        'values' => [
          'name' => $optionGroup['name'],
          'title' => 'Cool new title',
          'description' => 'Cool new description',
        ],
      ],
    ];
    $this->_managedEntities = [$managed];

    \CRM_Core_Session::singleton()->getStatus(TRUE);
    $this->assertEquals([], \CRM_Core_Session::singleton()->getStatus());

    // Without "match" in the params, it will try and fail to add a duplicate managed record
    CRM_Core_ManagedEntities::singleton(TRUE)->reconcile();

    $status = \CRM_Core_Session::singleton()->getStatus(TRUE);
    $this->assertStringContainsString('already exists', $status[0]['text']);
    $this->assertEquals('error', $status[0]['type']);

    // Now reconcile using a match param
    $managed['params']['match'] = ['name'];
    $this->_managedEntities = [$managed];
    CRM_Core_ManagedEntities::singleton(TRUE)->reconcile();

    $managedGroup = OptionGroup::get(FALSE)
      ->addWhere('name', '=', $optionGroup['name'])
      ->addSelect('id', 'title', 'description', 'base_module')
      ->execute()->single();

    $this->assertEquals($optionGroup['id'], $managedGroup['id']);
    $this->assertEquals('Cool new title', $managedGroup['title']);
    $this->assertEquals('Cool new description', $managedGroup['description']);
    // The existing record has been converted to a managed entity!
    $this->assertEquals('civicrm', $managedGroup['base_module']);
  }

  /**
   * Tests removing a managed record when the underlying entity that has been deleted
   *
   * @throws \CRM_Core_Exception
   */
  public function testRemoveDeleted(): void {

    // introduce a managed record
    $managed = [
      'module' => 'civicrm',
      'name' => 'record_on_its_way_out',
      'entity' => 'Group',
      'cleanup' => 'unused',
      'update' => 'always',
      'params' => [
        'version' => 4,
        'values' => [
          'name' => 'group_on_its_way_out',
          'title' => 'Not long for this world',
        ],
      ],
    ];
    $this->_managedEntities = [$managed];

    // first reconcile will create the new Group
    CRM_Core_ManagedEntities::singleton(TRUE)->reconcile();

    // managed record should be created
    $managedRecords = Managed::get(FALSE)
      ->addWhere('name', '=', 'record_on_its_way_out')
      ->execute();

    $this->assertEquals(1, count($managedRecords));

    // delete the group
    Group::delete(FALSE)
      ->addWhere('name', '=', 'group_on_its_way_out')
      ->execute();

    // stop managing it
    $this->_managedEntities = [];
    CRM_Core_ManagedEntities::singleton(TRUE)->reconcile();

    // the stale managed record should be cleaned up
    $managedRecords = Managed::get(FALSE)
      ->addWhere('name', '=', 'record_on_its_way_out')
      ->execute();

    $this->assertEquals(0, count($managedRecords));
  }

  /**
   * @dataProvider sampleEntityTypes
   *
   * @param string $entityName
   * @param bool $expected
   */
  public function testIsApi4ManagedType(string $entityName, bool $expected): void {
    $this->assertEquals($expected, \CRM_Core_BAO_Managed::isAPi4ManagedType($entityName));
  }

  public function sampleEntityTypes() {
    $entityTypes = [
      // v3 pseudo-entity
      'ActivityType' => FALSE,
      // v3 pseudo-entity
      'CustomSearch' => FALSE,
      // Non-dao entities can't be managed
      'Entity' => FALSE,
      'Afform' => FALSE,
      'Settings' => FALSE,
      // v4 entity not using ManagedEntity trait
      'UFJoin' => FALSE,
      // v4 entities using ManagedEntity trait
      'ContactType' => TRUE,
      'CustomField' => TRUE,
      'CustomGroup' => TRUE,
      'Group' => TRUE,
      'MembershipType' => TRUE,
      'Navigation' => TRUE,
      'OptionGroup' => TRUE,
      'OptionValue' => TRUE,
      'SavedSearch' => TRUE,
    ];
    return array_combine(array_keys($entityTypes), \CRM_Utils_Array::makeNonAssociative($entityTypes, 0, 1));
  }

  private function getCurrentTimestamp(): string {
    return \CRM_Core_DAO::singleValueQuery('SELECT CURRENT_TIMESTAMP');
  }

}
