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
use Civi\Api4\SavedSearch;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class ManagedEntityTest extends UnitTestCase implements TransactionalInterface, HookInterface {

  public function hook_civicrm_managed(array &$entities): void {
    $entities[] = [
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
    \CRM_Core_ManagedEntities::singleton(TRUE)->reconcile();

    $search = SavedSearch::get(FALSE)
      ->addWhere('name', '=', 'TestManagedSavedSearch')
      ->execute()->single();
    $this->assertEquals('Original state', $search['description']);

    SavedSearch::update(FALSE)
      ->addValue('id', $search['id'])
      ->addValue('description', 'Altered state')
      ->execute();

    $search = SavedSearch::get(FALSE)
      ->addWhere('name', '=', 'TestManagedSavedSearch')
      ->addSelect('description', 'has_base', 'base_module')
      ->execute()->single();
    $this->assertEquals('Altered state', $search['description']);
    // Check calculated fields
    $this->assertTrue($search['has_base']);
    $this->assertEquals('civicrm', $search['base_module']);

    SavedSearch::revert(FALSE)
      ->addWhere('name', '=', 'TestManagedSavedSearch')
      ->execute();

    // Entity should be revered to original state
    $result = SavedSearch::get(FALSE)
      ->addWhere('name', '=', 'TestManagedSavedSearch')
      ->addSelect('description', 'has_base', 'base_module')
      ->setDebug(TRUE)
      ->execute();
    $search = $result->single();
    $this->assertEquals('Original state', $search['description']);
    // Check calculated fields
    $this->assertTrue($search['has_base']);
    $this->assertEquals('civicrm', $search['base_module']);

    // Check calculated fields for a non-managed entity - they should be empty
    $newName = uniqid(__FUNCTION__);
    SavedSearch::create(FALSE)
      ->addValue('name', $newName)
      ->addValue('label', 'Whatever')
      ->execute();
    $search = SavedSearch::get(FALSE)
      ->addWhere('name', '=', $newName)
      ->addSelect('label', 'has_base', 'base_module')
      ->execute()->single();
    $this->assertEquals('Whatever', $search['label']);
    // Check calculated fields
    $this->assertEquals(NULL, $search['base_module']);
    $this->assertFalse($search['has_base']);
  }

}
