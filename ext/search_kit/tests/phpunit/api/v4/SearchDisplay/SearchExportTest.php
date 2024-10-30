<?php
namespace api\v4\SearchDisplay;

use Civi\Api4\SavedSearch;
use Civi\Api4\SearchDisplay;
use Civi\Test\HeadlessInterface;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class SearchExportTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface, TransactionalInterface {

  public function setUpHeadless() {
    // Civi\Test has many helpers, like install(), uninstall(), sql(), and sqlFile().
    // See: https://docs.civicrm.org/dev/en/latest/testing/phpunit/#civitest
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  /**
   * Test using the Export action on a SavedSearch.
   */
  public function testExportSearch() {
    $search = SavedSearch::create(FALSE)
      ->setValues([
        'name' => 'TestSearchToExport',
        'label' => 'TestSearchToExport',
        'api_entity' => 'Contact',
        'api_params' => [
          'version' => 4,
          'select' => ['id'],
        ],
      ])
      ->execute()->first();

    SearchDisplay::create(FALSE)
      ->setValues([
        'name' => 'TestDisplayToExport',
        'label' => 'TestDisplayToExport',
        'saved_search_id.name' => 'TestSearchToExport',
        'type' => 'table',
        'settings' => [
          'columns' => [
            [
              'key' => 'id',
              'label' => 'Contact ID',
              'dataType' => 'Integer',
              'type' => 'field',
            ],
          ],
        ],
        'acl_bypass' => FALSE,
      ])
      ->execute();

    $export = SavedSearch::export(FALSE)
      ->setId($search['id'])
      ->execute()
      ->indexBy('name');

    $this->assertCount(2, $export);
    // Default update policy should be 'unmodified'
    $this->assertEquals('unmodified', $export->first()['update']);
    $this->assertEquals('unmodified', $export->itemAt(1)['update']);
    // Default cleanup policy should be 'unused'
    $this->assertEquals('unused', $export->first()['cleanup']);
    $this->assertEquals('unused', $export->itemAt(1)['cleanup']);
    // The savedSearch should be first before its reference entities
    $this->assertEquals('SavedSearch', $export->first()['entity']);
    // Ensure api version is set to 4
    $this->assertEquals(4, $export['SavedSearch_TestSearchToExport']['params']['version']);
    $this->assertEquals('Contact', $export['SavedSearch_TestSearchToExport']['params']['values']['api_entity']);
    // Ensure FK is set correctly
    $this->assertArrayNotHasKey('saved_search_id', $export['SavedSearch_TestSearchToExport_SearchDisplay_TestDisplayToExport']['params']['values']);
    $this->assertEquals('TestSearchToExport', $export['SavedSearch_TestSearchToExport_SearchDisplay_TestDisplayToExport']['params']['values']['saved_search_id.name']);
    // Ensure value is used instead of pseudoconstant
    $this->assertEquals('table', $export['SavedSearch_TestSearchToExport_SearchDisplay_TestDisplayToExport']['params']['values']['type']);
    $this->assertArrayNotHasKey('type:name', $export['SavedSearch_TestSearchToExport_SearchDisplay_TestDisplayToExport']['params']['values']);
    // Readonly fields should not be included
    $this->assertArrayNotHasKey('created_date', $export['SavedSearch_TestSearchToExport_SearchDisplay_TestDisplayToExport']['params']['values']);
    $this->assertArrayNotHasKey('modified_date', $export['SavedSearch_TestSearchToExport_SearchDisplay_TestDisplayToExport']['params']['values']);
    // Match criteria
    $this->assertEquals(['name'], $export['SavedSearch_TestSearchToExport']['params']['match']);
    sort($export['SavedSearch_TestSearchToExport_SearchDisplay_TestDisplayToExport']['params']['match']);
    $this->assertEquals(['name', 'saved_search_id'], $export['SavedSearch_TestSearchToExport_SearchDisplay_TestDisplayToExport']['params']['match']);

    // Add a second display
    SearchDisplay::create(FALSE)
      ->setValues([
        'name' => 'SecondDisplayToExport',
        'label' => 'TestDisplayToExport',
        'saved_search_id.name' => 'TestSearchToExport',
        'type' => 'table',
        'settings' => [
          'columns' => [
            [
              'key' => 'id',
              'label' => 'Contact ID',
              'dataType' => 'Integer',
              'type' => 'field',
            ],
          ],
        ],
        'acl_bypass' => FALSE,
      ])
      ->execute();

    $export = SavedSearch::export(FALSE)
      ->setId($search['id'])
      ->setCleanup('always')
      ->setUpdate('never')
      ->execute()
      ->indexBy('name');

    $this->assertCount(3, $export);
    $this->assertEquals('always', $export->first()['cleanup']);
    $this->assertEquals('never', $export->first()['update']);
    $this->assertEquals('always', $export->last()['cleanup']);
    $this->assertEquals('never', $export->last()['update']);
    $this->assertEquals('TestSearchToExport', $export['SavedSearch_TestSearchToExport_SearchDisplay_SecondDisplayToExport']['params']['values']['saved_search_id.name']);
  }

}
