<?php

namespace E2E\CCase;

use Civi;
use Civi\Api4\Activity;

/**
 * Inline edits of case activities in the activity lists.
 *
 * @group e2e
 * @group ornery
 */
class ActivityInlineEditBrowserTest extends Civi\Test\MinkBase {

  use Civi\Test\Api4TestTrait;

  public static function setUpBeforeClass(): void {
    parent::setUpBeforeClass();
    Civi\Test::e2e()
      ->install(['civi_case'])
      ->apply();
  }

  public function tearDown(): void {
    $this->deleteTestRecords();
    Civi::settings()->revert('civicaseShowCaseActivities');
    parent::tearDown();
  }

  /**
   * A timeline activity edited inline is no longer untouched, so regenerating the timeline must keep it.
   */
  public function testInlineEditsMarkTimelineActivityAsModified(): void {
    [$caseId, $clientId] = $this->createCaseWithTimeline();
    $timeline = Activity::get(FALSE)
      ->addSelect('id', 'activity_type_id:name')
      ->addWhere('case_id', '=', $caseId)
      ->addWhere('is_auto', '=', TRUE)
      ->execute()->column('id', 'activity_type_id:name');
    $this->assertEquals(['Meeting', 'Phone Call', 'Follow up'], array_keys($timeline));

    $this->login($GLOBALS['_CV']['ADMIN_USER']);
    $this->visit(Civi::url("backend://civicrm/contact/view/case?reset=1&action=view&id=$caseId&cid=$clientId"));
    $this->editCell("#case_id_$caseId", $timeline['Meeting'], 'status_id', 'Completed');
    $this->editCell("#case_id_$caseId", $timeline['Phone Call'], 'subject', 'Called back');

    $after = Activity::get(FALSE)
      ->addSelect('is_auto', 'subject', 'status_id:name', 'activity_type_id:name')
      ->addWhere('id', 'IN', $timeline)
      ->execute()->indexBy('activity_type_id:name');
    $this->assertEquals('Completed', $after['Meeting']['status_id:name']);
    $this->assertFalse($after['Meeting']['is_auto']);
    $this->assertEquals('Called back', $after['Phone Call']['subject']);
    $this->assertFalse($after['Phone Call']['is_auto']);
    $this->assertTrue($after['Follow up']['is_auto']);
  }

  /**
   * With case activities shown in core views, the client's Activities tab uses the same inline editor.
   */
  public function testInlineEditOnContactActivitiesTabMarksTimelineActivityAsModified(): void {
    Civi::settings()->set('civicaseShowCaseActivities', TRUE);
    [$caseId, $clientId] = $this->createCaseWithTimeline();
    $meetingId = Activity::get(FALSE)
      ->addWhere('case_id', '=', $caseId)
      ->addWhere('activity_type_id:name', '=', 'Meeting')
      ->execute()->single()['id'];

    $this->login($GLOBALS['_CV']['ADMIN_USER']);
    $this->visit(Civi::url("backend://civicrm/contact/view?reset=1&cid=$clientId&selectedChild=activity"));
    $this->editCell('table.contact-activity-selector-activity', $meetingId, 'status_id', 'Completed');

    $meeting = Activity::get(FALSE)
      ->addSelect('is_auto', 'status_id:name')
      ->addWhere('id', '=', $meetingId)
      ->execute()->single();
    $this->assertEquals('Completed', $meeting['status_id:name']);
    $this->assertFalse($meeting['is_auto']);
  }

  private function createCaseWithTimeline(): array {
    $caseType = $this->createTestRecord('CaseType', [
      'title' => 'Inline Edit Timeline',
      'name' => 'inline_edit_timeline',
      'definition' => [
        'activityTypes' => [
          ['name' => 'Open Case', 'max_instances' => 1],
          ['name' => 'Meeting'],
          ['name' => 'Phone Call'],
          ['name' => 'Follow up'],
        ],
        'activitySets' => [
          [
            'name' => 'standard_timeline',
            'label' => 'Standard Timeline',
            'timeline' => 1,
            'activityTypes' => [
              ['name' => 'Open Case', 'status' => 'Completed'],
              ['name' => 'Meeting', 'reference_activity' => 'Open Case', 'reference_offset' => 1, 'reference_select' => 'newest'],
              ['name' => 'Phone Call', 'reference_activity' => 'Open Case', 'reference_offset' => 2, 'reference_select' => 'newest'],
              ['name' => 'Follow up', 'reference_activity' => 'Open Case', 'reference_offset' => 3, 'reference_select' => 'newest'],
            ],
          ],
        ],
        'caseRoles' => [
          ['name' => 'Case Coordinator', 'creator' => 1, 'manager' => 1],
        ],
      ],
    ]);
    $clientId = $this->createTestRecord('Individual')['id'];
    $case = $this->createTestRecord('Case', [
      'case_type_id' => $caseType['id'],
      'contact_id' => [$clientId],
      'creator_id' => $this->createTestRecord('Individual')['id'],
      'status_id:name' => 'Open',
      'subject' => 'Inline edit',
    ]);
    return [$case['id'], $clientId];
  }

  private function editCell(string $tableSelector, int $activityId, string $field, string $value): void {
    $session = $this->mink->getSession();
    $cellSelector = "$tableSelector tr[id=\"$activityId\"] td.crmf-$field .crm-editable-enabled";
    // jeditable loads asynchronously and sets the tooltip once it handles clicks.
    $this->assertTrue($session->wait(10000, '!!document.querySelector(' . json_encode("{$cellSelector}[title]") . ')'), "Editable $field cell");
    $cell = $session->getPage()->find('css', $cellSelector);
    $cell->click();
    $this->assertTrue($session->wait(5000, '!!document.querySelector(' . json_encode("$cellSelector form") . ')'), "$field editor");
    if ($field === 'status_id') {
      $cell->find('css', 'select')->selectOption($value);
    }
    else {
      $cell->find('css', 'input')->setValue($value);
    }
    $cell->find('css', 'button[type=submit]')->click();
    $this->assertTrue($session->wait(10000, '!document.querySelector(".crm-editable-saving, .crm-editable-form")'), "$field saved");
  }

}
