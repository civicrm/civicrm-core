<?php

/**
 *  Test CRM_Event_Form_Registration functions.
 *
 * @package   CiviCRM
 * @group headless
 */
class CRM_Event_Form_Task_BatchTest extends CiviUnitTestCase {

  public function testSubmit() {
    $participantID = $this->participantCreate();
    $participant = $this->callAPISuccessGetSingle('Participant', ['id' => $participantID]);
    $this->assertEquals(2, $participant['participant_status_id']);

    $form = $this->getFormObject('CRM_Event_Form_Task_Batch');
    $form->submit(['field' => [$participantID => ['participant_status_id' => 1]]]);

    $participant = $this->callAPISuccessGetSingle('Participant', ['id' => $participantID]);
    $this->assertEquals(1, $participant['participant_status_id']);
  }
}
