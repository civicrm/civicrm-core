<?php

use Civi\Api4\WordReplacement;
use Civi\Test\FormTrait;

/**
 * @group headless
 */
class CRM_Case_Form_ActivityViewTest extends CiviCaseTestCase {
  use FormTrait;

  public function tearDown(): void {
    WordReplacement::delete(FALSE)->addWhere('id', '>', 0)->execute();
    CRM_Core_BAO_WordReplacement::rebuild();
    parent::tearDown();
  }

  /**
   * Line breaks in the details are kept, whatever the details row is labelled.
   *
   * @throws \CRM_Core_Exception
   */
  public function testDetailsLineBreaksWithTranslatedLabel(): void {
    WordReplacement::save(FALSE)
      ->setRecords([['find_word' => 'Details', 'replace_word' => 'Einzelheiten', 'match_type' => 'exactMatch']])
      ->execute();
    CRM_Core_BAO_WordReplacement::rebuild();
    $clientID = $this->individualCreate();
    $caseID = $this->createCase($clientID, $this->getLoggedInUser())->id;
    $activityID = $this->callAPISuccess('Activity', 'create', [
      'case_id' => $caseID,
      'activity_type_id' => 'Follow up',
      'source_contact_id' => $this->getLoggedInUser(),
      'target_contact_id' => $clientID,
      'subject' => 'Call',
      'details' => "First line\nSecond line",
    ])['id'];

    $this->getTestForm('CRM_Case_Form_ActivityView', [], ['aid' => $activityID, 'cid' => $clientID])
      ->processForm(\Civi\Test\FormWrapper::BUILT);
    $html = CRM_Core_Smarty::singleton()->fetch('CRM/Case/Form/ActivityView.tpl');

    $this->assertStringContainsString('Einzelheiten', $html);
    $this->assertMatchesRegularExpression('/First line<br\s*\/?>\s*Second line/', $html);
  }

}
