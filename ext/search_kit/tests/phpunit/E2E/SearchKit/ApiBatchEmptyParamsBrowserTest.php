<?php

namespace E2E\SearchKit;

use Civi;
use Civi\Api4\Activity;

/**
 * An apiBatch task declared with empty params, run from a search display.
 *
 * @group e2e
 * @group ornery
 */
class ApiBatchEmptyParamsBrowserTest extends Civi\Test\MinkBase {

  use Civi\Test\Api4TestTrait;

  public static function setUpBeforeClass(): void {
    \Civi\Test::e2e()
      ->install(['org.civicrm.search_kit', 'searchkit-apibatch-test'])
      ->apply();
  }

  public function tearDown(): void {
    $this->deleteTestRecords();
    parent::tearDown();
  }

  public function testTaskWithoutFieldsRunsOnTheSelectedRow(): void {
    [$selectedId, $otherId] = $this->createActivities();
    $this->runTask('Empty Params Delete', FALSE, 'document.querySelectorAll("tbody tr").length === 1');

    $remaining = Activity::get(FALSE)->addWhere('id', 'IN', [$selectedId, $otherId])->execute()->column('id');
    $this->assertEquals([$otherId], $remaining);
  }

  public function testTaskWithFieldsRunsOnTheSelectedRow(): void {
    [$selectedId, $otherId] = $this->createActivities();
    $this->runTask('Empty Params Update', TRUE, 'document.querySelector("tbody").textContent.includes("Updated in batch")');

    $subjects = Activity::get(FALSE)->addWhere('id', 'IN', [$selectedId, $otherId])->execute()->column('subject', 'id');
    $this->assertEquals([$selectedId => 'Updated in batch', $otherId => 'Untouched'], $subjects);
  }

  /**
   * @return int[]
   *   The activity the test selects, then one it leaves alone.
   */
  private function createActivities(): array {
    $sourceContactId = $this->createTestRecord('Individual')['id'];
    $ids = [];
    foreach (['Selected', 'Untouched'] as $subject) {
      $ids[] = $this->createTestRecord('Activity', [
        'activity_type_id:name' => 'Meeting',
        'source_contact_id' => $sourceContactId,
        'subject' => $subject,
      ])['id'];
    }
    $savedSearch = $this->createTestRecord('SavedSearch', [
      'name' => 'ApiBatchEmptyParams',
      'label' => 'ApiBatch Empty Params',
      'api_entity' => 'Activity',
      'api_params' => [
        'version' => 4,
        'select' => ['id', 'subject'],
        'where' => [['id', 'IN', $ids]],
        'orderBy' => ['id' => 'ASC'],
      ],
    ]);
    $this->createTestRecord('SearchDisplay', [
      'name' => 'ApiBatchEmptyParamsTable',
      'label' => 'ApiBatch Empty Params Table',
      'saved_search_id' => $savedSearch['id'],
      'type' => 'table',
      'settings' => [
        'actions' => TRUE,
        'columns' => [
          ['type' => 'field', 'key' => 'id'],
          ['type' => 'field', 'key' => 'subject'],
        ],
      ],
    ]);
    return $ids;
  }

  /**
   * Selects the first row of the display, runs the task on it and waits for the refreshed display.
   */
  private function runTask(string $title, bool $hasFields, string $refreshedDisplay): void {
    $session = $this->mink->getSession();
    $page = $session->getPage();
    $this->login($GLOBALS['_CV']['ADMIN_USER']);
    $this->visit(Civi::url('backend://civicrm/search#/display/ApiBatchEmptyParams/ApiBatchEmptyParamsTable'));
    $this->assertTrue($session->wait(10000, '!!document.querySelector("tbody tr input[type=checkbox]") && !document.querySelector("button.crm-search-task-menu").disabled'), 'Display ready');

    $page->find('css', 'tbody tr input[type=checkbox]')->check();
    $page->find('css', 'button.crm-search-task-menu')->click();
    $taskLink = '//ul[contains(@class, "dropdown-menu")]//a[normalize-space(.)=' . json_encode($title) . ']';
    $this->assertTrue($session->wait(10000, 'document.evaluate(' . json_encode($taskLink) . ', document, null, XPathResult.FIRST_ORDERED_NODE_TYPE, null).singleNodeValue !== null'), "$title in the task menu");
    $page->find('xpath', $taskLink)->click();
    if ($hasFields) {
      $button = '//div[contains(@class, "ui-dialog-buttonpane")]//button[normalize-space(.)=' . json_encode($title) . ']';
      $this->assertTrue($session->wait(10000, 'document.evaluate(' . json_encode($button) . ', document, null, XPathResult.FIRST_ORDERED_NODE_TYPE, null).singleNodeValue !== null'), "$title dialog");
      $page->find('xpath', $button)->click();
    }
    $this->assertTrue($session->wait(15000, $refreshedDisplay), "$title applied to the display");
  }

}
