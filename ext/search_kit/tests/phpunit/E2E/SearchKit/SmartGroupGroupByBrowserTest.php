<?php

namespace E2E\SearchKit;

use Civi;
use Civi\Api4\SavedSearch;

/**
 * Grouping a search that has a smart group, in the SearchKit editor.
 *
 * @group e2e
 * @group ornery
 */
class SmartGroupGroupByBrowserTest extends Civi\Test\MinkBase {

  use Civi\Test\Api4TestTrait;

  public static function setUpBeforeClass(): void {
    \Civi\Test::e2e()
      ->install(['org.civicrm.search_kit'])
      ->apply();
  }

  /**
   * Grouping by Contact Type turns the contact column into a count, which must not be saved.
   */
  public function testGroupingRefusesToSaveACountAsTheContactColumn(): void {
    $savedSearchId = $this->createSmartGroupSearch();
    $this->groupByInEditor($savedSearchId, 'Contact Type');

    $this->assertTrue($this->mink->getSession()->wait(5000, '!!document.querySelector(".ui-notify-message") || [...document.querySelectorAll("button")].some(b => b.textContent.trim() === "Saved")'), 'Save finished or refused');
    $stored = SavedSearch::get(FALSE)->addWhere('id', '=', $savedSearchId)->execute()->single();
    $this->assertEquals('id', $stored['api_params']['select'][0]);
    $this->assertSession()->elementTextContains('css', '.ui-notify-message', 'must hold contact IDs');
  }

  public function testGroupingByTheContactIdSaves(): void {
    $savedSearchId = $this->createSmartGroupSearch();
    $this->groupByInEditor($savedSearchId, 'Contact ID');

    $this->assertTrue($this->mink->getSession()->wait(5000, '[...document.querySelectorAll("button")].some(b => b.textContent.trim() === "Saved")'), 'Saved');
    $stored = SavedSearch::get(FALSE)->addWhere('id', '=', $savedSearchId)->execute()->single();
    $this->assertEquals(['id'], $stored['api_params']['groupBy']);
    $this->assertEquals('id', $stored['api_params']['select'][0]);
  }

  private function createSmartGroupSearch(): int {
    $savedSearch = $this->createTestRecord('SavedSearch', [
      'label' => 'Smart Group Grouped',
      'api_entity' => 'Contact',
      'api_params' => [
        'version' => 4,
        'select' => ['id', 'contact_type'],
        'where' => [],
      ],
    ]);
    $this->createTestRecord('Group', [
      'title' => 'Smart Group Grouped',
      'saved_search_id' => $savedSearch['id'],
    ]);
    return $savedSearch['id'];
  }

  /**
   * Adds a Group By field in the SearchKit editor and clicks Save.
   */
  private function groupByInEditor(int $savedSearchId, string $fieldLabel): void {
    $session = $this->mink->getSession();
    $page = $session->getPage();
    $this->login($GLOBALS['_CV']['ADMIN_USER']);
    $this->visit(Civi::url('backend://civicrm/admin/search#/edit/' . $savedSearchId));
    $this->assertTrue($session->wait(10000, '!!document.querySelector("#s2id_crm-search-add-groupBy")'), 'Group By control');

    $page->find('css', '#s2id_crm-search-add-groupBy a')->click();
    $this->assertTrue($session->wait(5000, '!!document.querySelector(".select2-drop-active .select2-result-selectable")'), 'Group By options');
    $page->find('css', '.select2-drop-active input.select2-input')->setValue($fieldLabel);
    $option = '[...document.querySelectorAll(".select2-drop-active .select2-result-label")].find(el => el.firstChild.textContent.trim() === ' . json_encode($fieldLabel) . ')';
    $this->assertTrue($session->wait(5000, "!!$option"), "$fieldLabel option");
    $session->executeScript("$option.dispatchEvent(new MouseEvent('mouseup', {bubbles: true}))");
    $this->assertTrue($session->wait(5000, '!!document.querySelector(".crm-search-groupbys")?.textContent.includes(' . json_encode($fieldLabel) . ')'), "Grouped by $fieldLabel");

    $page->find('xpath', '//button[normalize-space(.)="Save"]')->click();
  }

  public function tearDown(): void {
    $this->deleteTestRecords();
    parent::tearDown();
  }

}
