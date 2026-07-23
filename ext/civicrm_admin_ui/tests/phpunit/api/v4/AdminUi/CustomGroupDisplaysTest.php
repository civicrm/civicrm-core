<?php
namespace api\v4\AdminUi;

use Civi\Test\HeadlessInterface;

/**
 * @group headless
 */
class CustomGroupDisplaysTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface {
  use \Civi\Test\Api4TestTrait;

  public function setUpHeadless() {
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  public function tearDown(): void {
    $this->conditionallyDeleteTestRecords();
    parent::tearDown();
  }

  public function testGetSearchKit() {
    $this->createTestRecord('CustomGroup', [
      'title' => __FUNCTION__,
      'extends' => 'Contact',
      'style' => 'Tab with table',
      'is_multiple' => TRUE,
    ]);

    $this->saveTestRecords('CustomField', [
      'records' => [
        ['label' => 'column1'],
        ['label' => 'column2', 'is_active' => FALSE],
        ['label' => 'column3', 'in_selector' => FALSE],
        ['label' => 'column4'],
      ],
      'defaults' => [
        'custom_group_id.name' => __FUNCTION__,
        'is_active' => TRUE,
        'in_selector' => TRUE,
      ],
    ]);

    // check the managed record rendering
    $mgd = \Civi\Api4\CustomGroup::getSearchKit(FALSE)
      ->addWhere('name', '=', __FUNCTION__)
      ->execute()->single()['managed'];

    $this->assertCount(2, $mgd);
    // Match params should be set to prevent error on existing records
    $this->assertSame(['name'], $mgd[0]['params']['match']);
    $this->assertEquals(['saved_search_id', 'name'], $mgd[1]['params']['match']);
    // Inactive and not-in-selector columns should be excluded
    $this->assertEquals([
      'column1',
      'column4',
      'id',
      'entity_id',
    ], $mgd[0]['params']['values']['api_params']['select']);
    $this->assertEquals([
      'column1',
      'column4',
    ], array_column($mgd[1]['params']['values']['settings']['columns'], 'key'));
    $this->assertEquals('Custom_testGetSearchKit', $mgd[1]['params']['values']['settings']['toolbar'][0]['entity']);
    $this->assertEquals('add', $mgd[1]['params']['values']['settings']['toolbar'][0]['action']);

    // check the search kit exists
    $search = \Civi\Api4\SavedSearch::get(FALSE)
      ->addWhere('name', '=', 'Custom_testGetSearchKit_Search')
      ->execute()
      ->single();

    // check the search columns
    $this->assertEquals(['column1', 'column4', 'id', 'entity_id'], $search['api_params']['select']);

    // add a new field
    $this->saveTestRecords('CustomField', [
      'records' => [
        ['label' => 'column5'],
      ],
      'defaults' => [
        'custom_group_id.name' => __FUNCTION__,
        'is_active' => TRUE,
        'in_selector' => TRUE,
      ],
    ]);

    // check the new field column has been added
    $search = \Civi\Api4\SavedSearch::get(FALSE)
      ->addWhere('name', '=', 'Custom_testGetSearchKit_Search')
      ->execute()
      ->single();

    // check the search columns
    $this->assertTrue(in_array('column5', $search['api_params']['select']));
  }

}
