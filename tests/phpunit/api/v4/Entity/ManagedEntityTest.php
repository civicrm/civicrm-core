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

use api\v4\UnitTestCase;
use Civi\Api4\Domain;
use Civi\Api4\Group;
use Civi\Api4\Navigation;
use Civi\Api4\OptionGroup;
use Civi\Api4\OptionValue;
use Civi\Api4\SavedSearch;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class ManagedEntityTest extends UnitTestCase implements TransactionalInterface, HookInterface {
  /**
   * @var array[]
   */
  private $_managedEntities = [];

  public function setUp(): void {
    $this->_managedEntities = [];
    parent::setUp();
  }

  public function hook_civicrm_managed(array &$entities): void {
    $entities = array_merge($entities, $this->_managedEntities);
  }

  public function testGetFields() {
    $fields = SavedSearch::getFields(FALSE)
      ->addWhere('type', '=', 'Extra')
      ->setLoadOptions(TRUE)
      ->execute()->indexBy('name');

    $this->assertEquals('Boolean', $fields['has_base']['data_type']);
    // If this core extension ever goes away or gets renamed, just pick a different one here
    $this->assertArrayHasKey('org.civicrm.flexmailer', $fields['base_module']['options']);
  }

  public function testRevertSavedSearch() {
    $this->_managedEntities[] = [
      // Setting module to 'civicrm' works for the test but not sure we should actually support that
      // as it's probably better to package stuff in a core extension instead of core itself.
      'module' => 'civicrm',
      'name' => 'testSavedSearch',
      'entity' => 'SavedSearch',
      'cleanup' => 'never',
      'update' => 'never',
      'params' => [
        'version' => 4,
        'values' => [
          'name' => 'TestManagedSavedSearch',
          'label' => 'Test Saved Search',
          'description' => 'Original state',
          'api_entity' => 'Contact',
          'api_params' => [
            'version' => 4,
            'select' => ['id'],
            'orderBy' => ['id', 'ASC'],
          ],
        ],
      ],
    ];

    \CRM_Core_ManagedEntities::singleton(TRUE)->reconcile();

    $search = SavedSearch::get(FALSE)
      ->addWhere('name', '=', 'TestManagedSavedSearch')
      ->addSelect('description', 'local_modified_date')
      ->execute()->single();
    $this->assertEquals('Original state', $search['description']);
    $this->assertNull($search['local_modified_date']);

    SavedSearch::update(FALSE)
      ->addValue('id', $search['id'])
      ->addValue('description', 'Altered state')
      ->execute();

    $time = $this->getCurrentTimestamp();
    $search = SavedSearch::get(FALSE)
      ->addWhere('name', '=', 'TestManagedSavedSearch')
      ->addSelect('description', 'has_base', 'base_module', 'local_modified_date')
      ->execute()->single();
    $this->assertEquals('Altered state', $search['description']);
    // Check calculated fields
    $this->assertTrue($search['has_base']);
    $this->assertEquals('civicrm', $search['base_module']);
    // local_modified_date should reflect the update just made
    $this->assertGreaterThanOrEqual($time, $search['local_modified_date']);
    $this->assertLessThanOrEqual($this->getCurrentTimestamp(), $search['local_modified_date']);

    SavedSearch::revert(FALSE)
      ->addWhere('name', '=', 'TestManagedSavedSearch')
      ->execute();

    // Entity should be revered to original state
    $result = SavedSearch::get(FALSE)
      ->addWhere('name', '=', 'TestManagedSavedSearch')
      ->addSelect('description', 'has_base', 'base_module', 'local_modified_date')
      ->execute();
    $search = $result->single();
    $this->assertEquals('Original state', $search['description']);
    // Check calculated fields
    $this->assertTrue($search['has_base']);
    $this->assertEquals('civicrm', $search['base_module']);
    // local_modified_date should be reset by the revert action
    $this->assertNull($search['local_modified_date']);

    // Check calculated fields for a non-managed entity - they should be empty
    $newName = uniqid(__FUNCTION__);
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

  public function testAutoUpdateSearch() {
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
    \CRM_Core_ManagedEntities::singleton(TRUE)->reconcile();

    $search = SavedSearch::get(FALSE)
      ->addWhere('name', '=', 'TestAutoUpdateSavedSearch')
      ->addSelect('description', 'local_modified_date')
      ->execute()->single();
    $this->assertEquals('Original state', $search['description']);
    $this->assertNull($search['local_modified_date']);

    // Remove managed search
    $this->_managedEntities = [];
    \CRM_Core_ManagedEntities::singleton(TRUE)->reconcile();

    // Because the search has no displays, it will be deleted (cleanup = unused)
    $search = SavedSearch::get(FALSE)
      ->addWhere('name', '=', 'TestAutoUpdateSavedSearch')
      ->execute();
    $this->assertCount(0, $search);

    // Restore managed entity
    $this->_managedEntities = [];
    $this->_managedEntities[] = $autoUpdateSearch;
    \CRM_Core_ManagedEntities::singleton(TRUE)->reconcile();

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
    $this->_managedEntities = [];
    $this->_managedEntities[] = $autoUpdateSearch;
    \CRM_Core_ManagedEntities::singleton(TRUE)->reconcile();

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
    $this->_managedEntities = [];
    $this->_managedEntities[] = $autoUpdateSearch;
    \CRM_Core_ManagedEntities::singleton(TRUE)->reconcile();

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
  }

  public function testOptionGroupAndValues() {
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
    \CRM_Core_ManagedEntities::singleton(TRUE)->reconcile();

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
    $result = OptionValue::update(FALSE)
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

    \CRM_Core_ManagedEntities::singleton(TRUE)->reconcile();

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
    \CRM_Core_ManagedEntities::singleton(TRUE)->reconcile();

    $this->assertCount(0, OptionValue::get(FALSE)->addWhere('id', 'IN', $values->column('id'))->execute());
    $this->assertCount(0, OptionGroup::get(FALSE)->addWhere('name', '=', 'testManagedOptionGroup')->execute());
  }

  public function testExportOptionGroupWithDomain() {
    $result = OptionGroup::get(FALSE)
      ->addWhere('name', '=', 'from_email_address')
      ->addChain('export', OptionGroup::export()->setId('$id'))
      ->execute()->first();
    $this->assertEquals('from_email_address', $result['export'][1]['params']['values']['option_group_id.name']);
    $this->assertNull($result['export'][1]['params']['values']['visibility_id']);
    $this->assertStringStartsWith('OptionGroup_from_email_address_OptionValue_', $result['export'][1]['name']);
    // All references should be from the current domain
    foreach (array_slice($result['export'], 1) as $reference) {
      $this->assertEquals('current_domain', $reference['params']['values']['domain_id']);
    }
  }

  public function testManagedNavigationWeights() {
    $this->_managedEntities = [
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

    // Throw a monkey wrench by placing duplicates in another domain
    $d2 = Domain::create(FALSE)
      ->addValue('name', 'Decoy domain')
      ->addValue('version', \CRM_Utils_System::version())
      ->execute()->single();
    foreach ($this->_managedEntities as $item) {
      $decoys[] = civicrm_api4('Navigation', 'create', [
        'checkPermissions' => FALSE,
        'values' => ['domain_id' => $d2['id']] + $item['params']['values'],
      ])->first();
    }

    // Refresh managed entities with module active
    $allModules = [
      new \CRM_Core_Module('unit.test.fake.ext', TRUE),
    ];
    (new \CRM_Core_ManagedEntities($allModules))->reconcile();

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
    $this->assertEquals('Decoy domain', $decoyExport[0]['params']['values']['domain_id.name']);
    $this->assertEquals('Decoy domain', $decoyExport[1]['params']['values']['domain_id.name']);
    $this->assertArrayNotHasKey('weight', $decoyExport[1]['params']['values']);

    // Refresh managed entities with module disabled
    $allModules = [
      new \CRM_Core_Module('unit.test.fake.ext', FALSE),
    ];
    (new \CRM_Core_ManagedEntities($allModules))->reconcile();

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
      new \CRM_Core_Module('unit.test.fake.ext', TRUE),
    ];
    (new \CRM_Core_ManagedEntities($allModules))->reconcile();

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

  public function testExportAndCreateGroup() {
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
    \CRM_Core_ManagedEntities::singleton(TRUE)->reconcile();

    $created = Group::get(FALSE)
      ->addWhere('name', '=', $original['name'])
      ->execute()->single();

    $this->assertEquals('My Managed Group', $created['title']);
    $this->assertEquals($original['name'], $created['name']);
    $this->assertGreaterThan($original['id'], $created['id']);
  }

  /**
   * Tests a scenario where a record may already exist and we want to make it a managed entity
   */
  public function testMatchExisting() {
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
          'title' => "Cool new title",
          'description' => 'Cool new description',
        ],
      ],
    ];
    $this->_managedEntities = [$managed];

    // Without "match" in the params, it will try and fail to add a duplicate managed record
    try {
      \CRM_Core_ManagedEntities::singleton(TRUE)->reconcile();
    }
    catch (\Exception $e) {
    }
    $this->assertStringContainsString('already exists', $e->getMessage());

    // Now reconcile using a match param
    $managed['params']['match'] = ['name'];
    $this->_managedEntities = [$managed];
    \CRM_Core_ManagedEntities::singleton(TRUE)->reconcile();

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
   * @dataProvider sampleEntityTypes
   * @param string $entityName
   * @param bool $expected
   */
  public function testIsApi4ManagedType($entityName, $expected) {
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

  private function getCurrentTimestamp() {
    return \CRM_Core_DAO::singleValueQuery('SELECT CURRENT_TIMESTAMP');
  }

}
