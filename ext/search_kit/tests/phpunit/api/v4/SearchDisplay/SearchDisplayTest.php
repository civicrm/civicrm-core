<?php
namespace api\v4\SearchDisplay;

use Civi\Api4\SavedSearch;
use Civi\Api4\SearchDisplay;
use Civi\Test\HeadlessInterface;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class SearchDisplayTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface, TransactionalInterface {

  public function setUpHeadless() {
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  public function testGetDefault() {
    $params = [
      'api_entity' => 'Contact',
      'api_params' => [
        'version' => 4,
        'select' => ['first_name', 'display_name', 'contact_sub_type:label', 'gender_id'],
        'where' => [],
      ],
    ];
    $display = SearchDisplay::getDefault(FALSE)
      ->setSavedSearch($params)
      ->addSelect('*', 'saved_search_id.api_entity', 'saved_search_id.api_entity:label', 'type:name', 'type:icon')
      ->execute()->single();

    $this->assertCount(5, $display['settings']['columns']);
    $this->assertEquals('Contacts', $display['label']);
    $this->assertEquals('crm-search-display-table', $display['type:name']);
    $this->assertEquals('fa-table', $display['type:icon']);
    $this->assertEquals('Contact', $display['saved_search_id.api_entity']);
    $this->assertEquals('Contacts', $display['saved_search_id.api_entity:label']);

    // Default sort order should have been added
    $this->assertEquals([['sort_name', 'ASC']], $display['settings']['sort']);

    // `display_name` column should have a link
    $this->assertEquals('Contact', $display['settings']['columns'][1]['link']['entity']);
    $this->assertEquals('view', $display['settings']['columns'][1]['link']['action']);
    $this->assertEmpty($display['settings']['columns'][1]['link']['join']);

    // `first_name` column should not have a link
    $this->assertArrayNotHasKey('link', $display['settings']['columns'][0]);
  }

  public function testGetDefaultNoEntity() {
    $display = SearchDisplay::getDefault(FALSE)
      ->addSelect('*', 'saved_search_id', 'type:name', 'type:icon')
      ->execute()->single();

    $this->assertCount(1, $display['settings']['columns']);
    $this->assertEquals('', $display['label']);
    $this->assertEquals('crm-search-display-table', $display['type:name']);
    $this->assertEquals('fa-table', $display['type:icon']);
    $this->assertNull($display['saved_search_id']);
  }

  public function testAutoFormatName() {
    // Create 3 saved searches; they should all get unique names
    $savedSearch0 = SavedSearch::create(FALSE)
      ->addValue('label', 'My test search')
      ->execute()->first();
    $savedSearch1 = SavedSearch::create(FALSE)
      ->addValue('label', 'My test search')
      ->execute()->first();
    $savedSearch2 = SavedSearch::create(FALSE)
      ->addValue('label', 'My test search')
      ->execute()->first();
    // Name will be created from munged label
    $this->assertEquals('My_test_search', $savedSearch0['name'], "SavedSearch 0");
    // Name will have _r appended to ensure it's unique, where r is a string of
    // random chars.
    $this->assertEquals('My_test_search_', substr($savedSearch1['name'], 0, 15), "SavedSearch 1");
    $this->assertEquals('My_test_search_', substr($savedSearch2['name'], 0, 15), "SavedSearch 2");
    $this->assertNotSame($savedSearch1['name'], $savedSearch2['name'], "SavedSearch 1,2");

    $display0 = SearchDisplay::create()
      ->addValue('saved_search_id', $savedSearch0['id'])
      ->addValue('label', 'My test display')
      ->addValue('type', 'table')
      ->execute()->first();
    $display1 = SearchDisplay::create()
      ->addValue('saved_search_id', $savedSearch0['id'])
      ->addValue('label', 'My test display')
      ->addValue('type', 'table')
      ->execute()->first();
    $display2 = SearchDisplay::create()
      ->addValue('saved_search_id', $savedSearch1['id'])
      ->addValue('label', 'My test display')
      ->addValue('type', 'table')
      ->execute()->first();
    // Name will be created from munged label
    $this->assertEquals('My_test_display', $display0['name'], "SearchDisplay 0");
    // Name will have _r appended (r is random string) to ensure it's unique to
    // savedSearch0.
    $this->assertEquals('My_test_display_', substr($display1['name'], 0, 16), "SearchDisplay 1");
    // This is for a different saved search so doesn't need a suffix appended
    $this->assertEquals('My_test_display', $display2['name'], "SearchDisplay 2");
  }

}
