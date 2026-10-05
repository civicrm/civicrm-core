<?php

namespace E2E\AfformMock;

use Civi;

/**
 * Perform some tests against `mockDateRangeSearch.aff.html`.
 *
 * @group e2e
 */
class MockDateRangeSearchBrowserTest extends Civi\Test\MinkBase {

  use Civi\Test\Api4TestTrait;

  public static function setUpBeforeClass(): void {
    \Civi\Test::e2e()
      ->install(['org.civicrm.afform', 'org.civicrm.search_kit', 'org.civicrm.afform-mock'])
      ->apply();
  }

  /**
   * A date search range passes its input_attrs to both datepickers, also after a reset.
   */
  public function testInputAttrsOnDateRange(): void {
    $this->createTestRecord('SavedSearch', [
      'name' => 'MockDateRangeSearch',
      'label' => 'MockDateRangeSearch',
      'api_entity' => 'Contact',
      'api_params' => ['version' => 4, 'select' => ['id', 'display_name']],
    ]);
    $this->createTestRecord('SearchDisplay', [
      'name' => 'MockDateRangeDisplay',
      'label' => 'MockDateRangeDisplay',
      'saved_search_id.name' => 'MockDateRangeSearch',
      'type' => 'table',
      'settings' => ['columns' => [['type' => 'field', 'key' => 'display_name']]],
    ]);

    $session = $this->mink->getSession();
    $this->login($GLOBALS['_CV']['ADMIN_USER']);
    $this->visit(Civi::url('backend://civicrm/mock-date-range-search'));
    $session->wait(5000, 'document.querySelectorAll("af-field[name=modified_date] input.crm-form-time").length > 0');
    $this->assertTimeInputs(0, 2);

    $lowDate = $this->assertSession()->elementExists('css', 'af-field[name=created_date] input.crm-form-date');
    $lowDate->setValue('01/01/2020');
    $this->assertEquals('01/01/2020', $lowDate->getValue());
    $this->assertSession()->elementExists('css', 'button.af-button[type=reset]')->click();
    $this->assertTrue($session->wait(5000, 'document.querySelector("af-field[name=created_date] input.crm-form-date").value === ""'), 'Reset clears the date');
    $this->assertTimeInputs(0, 2);
  }

  private function assertTimeInputs(int $created, int $modified): void {
    $this->assertSession()->elementsCount('css', 'af-field[name=created_date] input.crm-form-date', 2);
    $this->assertSession()->elementsCount('css', 'af-field[name=created_date] input.crm-form-time', $created);
    $this->assertSession()->elementsCount('css', 'af-field[name=modified_date] input.crm-form-time', $modified);
  }

  public function tearDown(): void {
    $this->deleteTestRecords();
    parent::tearDown();
  }

}
