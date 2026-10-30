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

    // add date field
    $this->saveTestRecords('CustomField', [
      'records' => [
        ['label' => 'date_col', 'data_type' => 'Date', 'date_format' => 'yy'],
      ],
      'defaults' => [
        'custom_group_id.name' => __FUNCTION__,
        'is_active' => TRUE,
        'in_selector' => TRUE,
      ],
    ]);

    $mgd = \Civi\Api4\CustomGroup::getSearchKit(FALSE)
      ->addWhere('name', '=', __FUNCTION__)
      ->execute()->single()['managed'];

    $dateCol = array_values(array_filter($mgd[1]['params']['values']['settings']['columns'], fn($col) => ($col['key'] ?? NULL) === 'date_col'))[0] ?? NULL;
    $this->assertNotNull($dateCol);
    $this->assertEquals('%Y', $dateCol['format']);
  }

  public function testContactReferenceField(): void {
    $this->createTestRecord('CustomGroup', [
      'title' => __FUNCTION__,
      'extends' => 'Contact',
      'style' => 'Tab with table',
      'is_multiple' => TRUE,
    ]);

    $this->createTestRecord('CustomField', [
      'label' => 'my_contact_ref',
      'custom_group_id.name' => __FUNCTION__,
      'data_type' => 'ContactReference',
      'html_type' => 'Autocomplete-Select',
      'is_active' => TRUE,
      'in_selector' => TRUE,
    ]);

    $mgd = \Civi\Api4\CustomGroup::getSearchKit(FALSE)
      ->addWhere('name', '=', __FUNCTION__)
      ->execute()->single()['managed'];

    $savedSearchSelect = $mgd[0]['params']['values']['api_params']['select'];
    $this->assertContains('my_contact_ref', $savedSearchSelect);

    $columns = $mgd[1]['params']['values']['settings']['columns'];
    $col = array_values(array_filter($columns, fn($c) => ($c['key'] ?? NULL) === 'my_contact_ref.display_name'))[0] ?? NULL;
    $this->assertNotNull($col);
    $this->assertEquals([
      'entity' => 'Contact',
      'action' => 'view',
      'join' => 'my_contact_ref',
    ], $col['link']);
  }

  public function testEntityReferenceField(): void {
    $this->createTestRecord('CustomGroup', [
      'title' => __FUNCTION__,
      'extends' => 'Contact',
      'style' => 'Tab with table',
      'is_multiple' => TRUE,
    ]);

    $this->createTestRecord('CustomField', [
      'label' => 'my_activity_ref',
      'custom_group_id.name' => __FUNCTION__,
      'data_type' => 'EntityReference',
      'fk_entity' => 'Activity',
      'html_type' => 'Autocomplete-Select',
      'is_active' => TRUE,
      'in_selector' => TRUE,
    ]);

    $this->createTestRecord('CustomField', [
      'label' => 'my_tag_ref',
      'custom_group_id.name' => __FUNCTION__,
      'data_type' => 'EntityReference',
      'fk_entity' => 'Tag',
      'html_type' => 'Autocomplete-Select',
      'is_active' => TRUE,
      'in_selector' => TRUE,
    ]);

    $mgd = \Civi\Api4\CustomGroup::getSearchKit(FALSE)
      ->addWhere('name', '=', __FUNCTION__)
      ->execute()->single()['managed'];

    $savedSearchSelect = $mgd[0]['params']['values']['api_params']['select'];
    $this->assertContains('my_activity_ref', $savedSearchSelect);
    $this->assertContains('my_tag_ref', $savedSearchSelect);

    $columns = $mgd[1]['params']['values']['settings']['columns'];

    // Activity reference has a view path, so it should have a link, icons, empty_value
    $actCol = array_values(array_filter($columns, fn($c) => ($c['key'] ?? NULL) === 'my_activity_ref.subject'))[0] ?? NULL;
    $this->assertNotNull($actCol);
    $this->assertEquals('(no subject)', $actCol['empty_value'] ?? NULL);
    $this->assertEquals('my_activity_ref.activity_type_id:icon', $actCol['icons'][0]['field'] ?? NULL);
    $this->assertEquals([
      'entity' => 'Activity',
      'action' => 'view',
      'join' => 'my_activity_ref',
    ], $actCol['link']);

    // Tag has no view path, so it should not have a link
    $tagCol = array_values(array_filter($columns, fn($c) => ($c['key'] ?? NULL) === 'my_tag_ref.label'))[0] ?? NULL;
    $this->assertNotNull($tagCol);
    $this->assertArrayNotHasKey('link', $tagCol);
  }

  public function testEntityReferenceFieldWithRewrite(): void {
    $this->createTestRecord('CustomGroup', [
      'title' => __FUNCTION__,
      'extends' => 'Contact',
      'style' => 'Tab with table',
      'is_multiple' => TRUE,
    ]);

    $this->createTestRecord('CustomField', [
      'label' => 'my_contrib_ref',
      'custom_group_id.name' => __FUNCTION__,
      'data_type' => 'EntityReference',
      'fk_entity' => 'Contribution',
      'html_type' => 'Autocomplete-Select',
      'is_active' => TRUE,
      'in_selector' => TRUE,
    ]);

    $mgd = \Civi\Api4\CustomGroup::getSearchKit(FALSE)
      ->addWhere('name', '=', __FUNCTION__)
      ->execute()->single()['managed'];

    $savedSearchSelect = $mgd[0]['params']['values']['api_params']['select'];
    $this->assertContains('my_contrib_ref', $savedSearchSelect);

    $columns = $mgd[1]['params']['values']['settings']['columns'];
    $contribCol = array_values(array_filter($columns, fn($c) => ($c['key'] ?? NULL) === 'my_contrib_ref.contact_id.sort_name'))[0] ?? NULL;
    $this->assertNotNull($contribCol);
    $this->assertEquals('[my_contrib_ref.contact_id.sort_name] - [my_contrib_ref.total_amount]', $contribCol['rewrite']);
    $this->assertEquals([
      'entity' => 'Contribution',
      'action' => 'view',
      'join' => 'my_contrib_ref',
    ], $contribCol['link']);
  }

}
