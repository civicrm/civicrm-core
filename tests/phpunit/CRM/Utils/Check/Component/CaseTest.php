<?php

/**
 * @package CiviCRM
 * @group headless
 */
class CRM_Utils_Check_Component_CaseTest extends CiviUnitTestCase {

  public function setUp(): void {
    parent::setUp();
    $this->useTransaction();
  }

  /**
   * The cross-duplication table shows both sides of the other type.
   */
  public function testCrossDuplicationShowsOtherType(): void {
    $first = $this->callAPISuccess('RelationshipType', 'create', [
      'name_a_b' => 'Mentor of',
      'name_b_a' => 'Mentee of',
      'label_a_b' => 'Mentor of label',
      'label_b_a' => 'Mentee of label',
    ]);
    $second = $this->callAPISuccess('RelationshipType', 'create', [
      'name_a_b' => 'coach_of',
      'name_b_a' => 'coached_by',
      'label_a_b' => 'Mentor of',
      'label_b_a' => 'Coached by',
    ]);

    $check = new CRM_Utils_Check_Component_Case();
    $messages = $check->checkRelationshipTypeProblems();
    $dupes = array_values(array_filter($messages, fn($message) => $message->getName() === 'checkRelationshipTypeProblems' . $first['id'] . 'dupe3'));

    $this->assertCount(1, $dupes);
    $this->assertStringContainsString('<tr><td>' . $second['id'] . '</td><td>coach_of</td><td>coached_by</td><td>Mentor of</td><td>Coached by</td></tr>', $dupes[0]->getMessage());
  }

}
