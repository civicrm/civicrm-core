<?php

namespace E2E\AfformMock;

use Civi;

/**
 * Perform some tests against `mockNumberRangeSearch.aff.html`.
 *
 * @group e2e
 */
class MockNumberRangeSearchBrowserTest extends Civi\Test\MinkBase {

  use Civi\Test\Api4TestTrait;

  public static function setUpBeforeClass(): void {
    \Civi\Test::e2e()
      ->install(['org.civicrm.afform', 'org.civicrm.search_kit', 'org.civicrm.afform-mock'])
      ->apply();
  }

  public static function getRanges(): array {
    return [
      'both bounds' => ['5-10', '5', '10'],
      'negative low bound' => ['-5-10', '-5', '10'],
      'negative bounds' => ['-10--5', '-10', '-5'],
      'decimals' => ['2.5-7.5', '2.5', '7.5'],
      'low bound only' => ['5-', '5', ''],
      'single value' => ['5', '5', ''],
      'invalid high bound' => ['5-x', '5', ''],
    ];
  }

  /**
   * A number search range takes its bounds from the url.
   *
   * @group ornery
   * @dataProvider getRanges
   */
  public function testRangeFromUrl(string $range, string $low, string $high): void {
    $this->createSearch();
    $session = $this->mink->getSession();
    $this->login($GLOBALS['_CV']['ADMIN_USER']);
    $this->visit(Civi::url('backend://civicrm/mock-number-range-search')->addFragmentQuery(['id' => $range]));
    $this->assertTrue($session->wait(5000, 'document.querySelector("af-field[name=id] input")?.value === ' . json_encode($low)), "Low bound $low");
    $this->assertSession()->elementsCount('css', 'af-field[name=id] input', 2);
    $inputs = $session->getPage()->findAll('css', 'af-field[name=id] input');
    $this->assertSame([$low, $high], [$inputs[0]->getValue(), $inputs[1]->getValue()]);
  }

  /**
   * Number and slider inputs keep a bound of 0.
   *
   * @group ornery
   */
  public function testZeroBoundIsKept(): void {
    $this->createSearch();
    $session = $this->mink->getSession();
    $this->login($GLOBALS['_CV']['ADMIN_USER']);
    $this->visit(Civi::url('backend://civicrm/mock-number-range-search'));
    $this->assertTrue($session->wait(5000, 'document.querySelector("af-field[name=legal_identifier] input") !== null'));
    $bounds = $session->evaluateScript('return [...document.querySelectorAll("af-field:not([name=id]) input")].map((input) => [input.type, input.min, input.max]);');
    $this->assertSame([
      ['number', '-10', '0'],
      ['number', '-10', '0'],
      ['number', '0', '10'],
      ['range', '-10', '0'],
    ], $bounds);
  }

  private function createSearch(): void {
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
  }

  public function tearDown(): void {
    $this->deleteTestRecords();
    parent::tearDown();
  }

}
