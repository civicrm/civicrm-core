<?php

namespace E2E\AfformMock;

use Civi;

/**
 * Perform some tests against `mockNumberBounds.aff.html`.
 *
 * @group e2e
 */
class MockNumberBoundsBrowserTest extends Civi\Test\MinkBase {

  use Civi\Test\Api4TestTrait;

  public static function setUpBeforeClass(): void {
    \Civi\Test::e2e()
      ->install(['org.civicrm.afform', 'org.civicrm.search_kit', 'org.civicrm.afform-mock'])
      ->apply();
  }

  /**
   * A number input keeps a bound of 0.
   */
  public function testZeroBoundIsKept(): void {
    $this->createTestRecord('SavedSearch', [
      'name' => 'MockNumberRangeSearch',
      'label' => 'MockNumberRangeSearch',
      'api_entity' => 'Contact',
      'api_params' => ['version' => 4, 'select' => ['id', 'display_name']],
    ]);
    $this->createTestRecord('SearchDisplay', [
      'name' => 'MockNumberRangeDisplay',
      'label' => 'MockNumberRangeDisplay',
      'saved_search_id.name' => 'MockNumberRangeSearch',
      'type' => 'table',
      'settings' => ['columns' => [['type' => 'field', 'key' => 'display_name']]],
    ]);

    $session = $this->mink->getSession();
    $this->login($GLOBALS['_CV']['ADMIN_USER']);
    $this->visit(Civi::url('backend://civicrm/mock-number-bounds'));
    $this->assertTrue($session->wait(5000, 'document.querySelectorAll("af-field[name=id] input").length === 2'));
    $bounds = $session->evaluateScript('return [...document.querySelectorAll("af-field[name=id] input")].map((input) => [input.min, input.max]);');
    $this->assertSame([['-10', '0'], ['-10', '0']], $bounds);
  }

  public function tearDown(): void {
    $this->deleteTestRecords();
    parent::tearDown();
  }

}
