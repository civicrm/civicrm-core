<?php

use Civi\Api4\EntityTag;
use Civi\Api4\Tag;

class CRM_Event_Form_ManageEvent_EventInfoTest extends CiviUnitTestCase {

  /**
   * Set up a correct array of form values.
   *
   * @return array
   */
  private function getCorrectFormFields(): array {
    return [
      'title' => 'A test event',
      'event_type_id' => 1,
      'default_role_id' => 1,
      'start_date' => date('Y-m-d'),
      'end_date' => date('Y-m-d', time() + 86400),
      'is_template' => '0',
    ];
  }

  /**
   * Test correct form submission.
   * @dataProvider isTemplateProvider
   * @param string $is_template
   */
  public function testValidFormSubmission(string $is_template): void {
    $values = array_merge($this->getCorrectFormFields(), ['is_template' => $is_template]);
    $validationResult = \CRM_Event_Form_ManageEvent_EventInfo::formRule($values);
    $this->assertEmpty($validationResult);
  }

  /**
   * Test end date not allowed with only 'time' part.
   */
  public function testEndDateWithoutDateNotAllowed(): void {
    $values = $this->getCorrectFormFields();
    $values['end_date'] = '00:01';
    $validationResult = \CRM_Event_Form_ManageEvent_EventInfo::formRule($values);
    $this->assertArrayHasKey('end_date', $validationResult);
  }

  /**
   * Test end date must be after start date.
   */
  public function testEndDateBeforeStartDateNotAllowed(): void {
    $values = $this->getCorrectFormFields();
    $values['end_date'] = '1900-01-01 00:00';
    $validationResult = \CRM_Event_Form_ManageEvent_EventInfo::formRule($values);
    $this->assertArrayHasKey('end_date', $validationResult);
  }

  /**
   * Submitted tags replace the event's existing tags.
   */
  public function testTagsSavedOnEdit(): void {
    $tagA = $this->createEventTag('A');
    $tagB = $this->createEventTag('B');
    $eventID = $this->eventCreateUnpaid()['id'];

    $this->addEventTag($eventID, $tagA);

    $this->submitEventInfo(
    ['tag' => (string) $tagB],
    ['id' => $eventID, 'action' => 'update']
    );
    $this->assertSame([$tagB], $this->getEventTagIDs($eventID));

    $this->submitEventInfo(
    ['tag' => ''],
    ['id' => $eventID, 'action' => 'update']
    );
    $this->assertSame([], $this->getEventTagIDs($eventID));
  }

  /**
   * Event templates can have tags.
   */
  public function testTagsSavedOnEventTemplate(): void {
    $tag = $this->createEventTag('A');
    $templateID = $this->createTestEntity('Event', [
      'is_template' => TRUE,
      'template_title' => 'A template',
      'event_type_id' => 1,
    ], 'template')['id'];

    $this->submitEventInfo([
      'is_template' => '1',
      'template_title' => 'A template',
      'tag' => (string) $tag,
    ], ['id' => $templateID, 'action' => 'update']);

    $this->assertSame([$tag], $this->getEventTagIDs($templateID));
  }

  /**
   * Submitted tags replace the tags inherited from a template.
   */
  public function testTagsInheritedFromTemplateAreReplaced(): void {
    $tagA = $this->createEventTag('A');
    $tagB = $this->createEventTag('B');
    $tagSet = $this->createEventTag('Set', ['is_tagset' => 1]);
    $child1 = $this->createEventTag('Child 1', ['parent_id' => $tagSet]);
    $child2 = $this->createEventTag('Child 2', ['parent_id' => $tagSet]);

    $templateID = $this->createTestEntity('Event', [
      'is_template' => TRUE,
      'template_title' => 'A template',
      'event_type_id' => 1,
    ], 'template')['id'];

    $this->addEventTag($templateID, $tagA);
    $this->addEventTag($templateID, $child1);

    $this->submitEventInfo([
      'template_id' => $templateID,
      'tag' => (string) $tagB,
      'event_taglist' => [$tagSet => "$child1,$child2"],
    ], ['action' => 'add', 'template_id' => $templateID]);

    $eventID = (int) CRM_Core_DAO::singleValueQuery(
    'SELECT MAX(id) FROM civicrm_event'
    );

    $expectedTags = [$tagB, $child1, $child2];
    sort($expectedTags);

    $this->assertSame($expectedTags, $this->getEventTagIDs($eventID));

    // The template's tags should not change.
    $templateTags = [$tagA, $child1];
    sort($templateTags);

    $this->assertSame($templateTags, $this->getEventTagIDs($templateID));
  }

  /**
   * An empty tagset clears inherited tags.
   */
  public function testEmptyTagsetClearsInheritedTags(): void {
    $tagSet = $this->createEventTag('Set', ['is_tagset' => 1]);
    $child = $this->createEventTag('Child', ['parent_id' => $tagSet]);

    $templateID = $this->createTestEntity('Event', [
      'is_template' => TRUE,
      'template_title' => 'A template',
      'event_type_id' => 1,
    ], 'template')['id'];

    $this->addEventTag($templateID, $child);

    $this->submitEventInfo([
      'template_id' => $templateID,
      'event_taglist' => [$tagSet => ''],
    ], ['action' => 'add', 'template_id' => $templateID]);

    $eventID = (int) CRM_Core_DAO::singleValueQuery(
    'SELECT MAX(id) FROM civicrm_event'
    );

    $this->assertSame([], $this->getEventTagIDs($eventID));
  }

  /**
   * The tag element is available to Smarty even when no event tags exist.
   */
  public function testTagElementDeclaredWhenNoEventTagsExist(): void {
    // Delete child tags before their tagset parents.
    Tag::delete(FALSE)
      ->addWhere('used_for', 'CONTAINS', 'civicrm_event')
      ->addWhere('parent_id', 'IS NOT NULL')
      ->execute();

    Tag::delete(FALSE)
      ->addWhere('used_for', 'CONTAINS', 'civicrm_event')
      ->execute();

    $eventID = $this->eventCreateUnpaid()['id'];
    $form = $this->getFormObject(
    'CRM_Event_Form_ManageEvent_EventInfo',
    [],
    ['id' => $eventID, 'action' => 'update']
    );

    $form->preProcess();
    $form->buildForm();

    $this->assertFalse($form->elementExists('tag'));
    $this->assertArrayHasKey('tag', $form->toSmarty());
  }

  /**
   * Submit the event info form.
   */
  private function submitEventInfo(array $values, array $urlParameters): void {
    $this->getTestForm(
    'CRM_Event_Form_ManageEvent_EventInfo',
    array_merge($this->getCorrectFormFields(), ['end_date' => ''], $values),
    $urlParameters
    )->processForm();
  }

  /**
   * Create a tag for an event.
   */
  private function createEventTag(string $label, array $values = []): int {
    return (int) $this->createTestEntity('Tag', $values + [
      'name' => uniqid('event_tag_'),
      'label' => $label,
      'used_for' => ['civicrm_event'],
    ], $label)['id'];
  }

  /**
   * Add a tag to an event.
   */
  private function addEventTag(int $eventID, int $tagID): void {
    EntityTag::create(FALSE)->setValues([
      'entity_table' => 'civicrm_event',
      'entity_id' => $eventID,
      'tag_id' => $tagID,
    ])->execute();
  }

  /**
   * Return the tag IDs for an event.
   */
  private function getEventTagIDs(int $eventID): array {
    $ids = (array) EntityTag::get(FALSE)
      ->addWhere('entity_table', '=', 'civicrm_event')
      ->addWhere('entity_id', '=', $eventID)
      ->execute()
      ->column('tag_id');

    sort($ids);
    return $ids;
  }

  /**
   * Data Provider to test both regular events and event templates.
   *
   * @return array
   */
  public static function isTemplateProvider(): array {
    return [['0'], ['1']];
  }

}
