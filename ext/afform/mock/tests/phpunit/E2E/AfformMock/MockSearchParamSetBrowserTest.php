<?php

namespace E2E\AfformMock;

use Civi;

/**
 * Perform some tests against `mockSearchParamSet.aff.html`.
 *
 * @group e2e
 */
class MockSearchParamSetBrowserTest extends Civi\Test\MinkBase {

  use Civi\Test\Api4TestTrait;

  public static function setUpBeforeClass(): void {
    \Civi\Test::e2e()
      ->install(['org.civicrm.afform', 'org.civicrm.search_kit', 'org.civicrm.afform-mock'])
      ->apply();
  }

  public static function getUrls(): array {
    return [
      'saved set' => [[], 'Ada', ''],
      'url value before saved set' => [['first_name' => 'Grace'], 'Grace', ''],
    ];
  }

  /**
   * The fields take the values of the SearchParamSet in the url.
   *
   * @dataProvider getUrls
   */
  public function testValuesFromSavedSet(array $urlValues, string $firstName, string $lastName): void {
    $this->createTestRecord('SavedSearch', [
      'name' => 'MockSearchParamSetSearch',
      'label' => 'MockSearchParamSetSearch',
      'api_entity' => 'Contact',
      'api_params' => ['version' => 4, 'select' => ['id', 'display_name']],
    ]);
    $this->createTestRecord('SearchDisplay', [
      'name' => 'MockSearchParamSetDisplay',
      'label' => 'MockSearchParamSetDisplay',
      'saved_search_id.name' => 'MockSearchParamSetSearch',
      'type' => 'table',
      'settings' => ['columns' => [['type' => 'field', 'key' => 'display_name']]],
    ]);
    $set = $this->createTestRecord('SearchParamSet', [
      'afform_name' => 'mockSearchParamSet',
      'label' => 'MockSearchParamSet',
      'filters' => ['first_name' => 'Ada'],
    ]);

    $session = $this->mink->getSession();
    $this->login($GLOBALS['_CV']['ADMIN_USER']);
    $this->visit(Civi::url('backend://civicrm/mock-search-param-set')->addFragmentQuery(['_s' => $set['id']] + $urlValues));
    $this->assertTrue($session->wait(5000, 'document.querySelector("af-field[name=first_name] input")?.value === ' . json_encode($firstName)), "First name $firstName");
    $this->assertSame($lastName, $this->assertSession()->elementExists('css', 'af-field[name=last_name] input')->getValue());
  }

  public function tearDown(): void {
    $this->deleteTestRecords();
    parent::tearDown();
  }

}
