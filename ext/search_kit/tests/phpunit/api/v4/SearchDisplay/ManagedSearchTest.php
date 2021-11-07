<?php
namespace api\v4\SearchDisplay;

use Civi\Api4\SavedSearch;
use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class ManagedSearchTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface, TransactionalInterface, HookInterface {

  /**
   * @var array[]
   */
  private $_managedEntities = [];

  public function setUp(): void {
    $this->_managedEntities = [];
    parent::setUp();
  }

  public function setUpHeadless() {
    // Civi\Test has many helpers, like install(), uninstall(), sql(), and sqlFile().
    // See: https://docs.civicrm.org/dev/en/latest/testing/phpunit/#civitest
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  public function hook_civicrm_managed(array &$entities): void {
    $entities = array_merge($entities, $this->_managedEntities);
  }

  public function testDeleteUnusedSearch() {
    $savedSearch = [
      'module' => 'civicrm',
      'name' => 'testDeleteUnusedSearch',
      'entity' => 'SavedSearch',
      'cleanup' => 'unused',
      'update' => 'unmodified',
      'params' => [
        'version' => 4,
        'values' => [
          'name' => 'testDeleteUnusedSearch',
          'label' => 'Test Search',
          'description' => 'Original state',
          'api_entity' => 'Contact',
          'api_params' => [
            'version' => 4,
            'select' => ['id'],
          ],
        ],
      ],
    ];
    $searchDisplay = [
      'module' => 'civicrm',
      'name' => 'testDeleteUnusedDisplay',
      'entity' => 'SearchDisplay',
      'cleanup' => 'unused',
      'update' => 'unmodified',
      'params' => [
        'version' => 4,
        'values' => [
          'type' => 'table',
          'name' => 'testDeleteUnusedDisplay',
          'label' => 'testDeleteUnusedDisplay',
          'saved_search_id.name' => 'testDeleteUnusedSearch',
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
            ],
          ],
        ],
      ],
    ];
    // Add managed search + display
    $this->_managedEntities[] = $savedSearch;
    $this->_managedEntities[] = $searchDisplay;
    \CRM_Core_ManagedEntities::singleton(TRUE)->reconcile();

    $search = SavedSearch::get(FALSE)
      ->selectRowCount()
      ->addWhere('name', '=', 'testDeleteUnusedSearch')
      ->execute();
    $this->assertCount(1, $search);

    $this->_managedEntities = [];
    \CRM_Core_ManagedEntities::singleton(TRUE)->reconcile();

    $search = SavedSearch::get(FALSE)
      ->selectRowCount()
      ->addWhere('name', '=', 'testDeleteUnusedSearch')
      ->execute();
    $this->assertCount(0, $search);
  }

}
