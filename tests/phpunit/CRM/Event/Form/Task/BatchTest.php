<?php

/**
 *  Test CRM_Event_Form_Registration functions.
 *
 * @package   CiviCRM
 * @group headless
 */
class CRM_Event_Form_Task_BatchTest extends CiviUnitTestCase {

  /**
   * Test the the submit function on the event participant submit function.
   *
   * @todo extract submit functions on other Batch update classes, use dataprovider to test on all.
   */
  public function testSubmit() {
    $group = $this->CustomGroupCreate(['extends' => 'Participant', 'title' => 'Participant']);
    $field = $this->customFieldCreate(['custom_group_id' => $group['id'], 'html_type' => 'CheckBox', 'option_values' => ['two' => 'A couple', 'three' => 'A few', 'four' => 'Too Many']]);
    $participantID = $this->participantCreate();
    $participant = $this->callAPISuccessGetSingle('Participant', ['id' => $participantID]);
    $this->assertEquals(2, $participant['participant_status_id']);

    $form = $this->getFormObject('CRM_Event_Form_Task_Batch');
    $form->submit(['field' => [$participantID => ['participant_status_id' => 1, 'custom_' . $field['id'] => ['two' => 1, 'four' => 1]]]]);

    $participant = $this->callAPISuccessGetSingle('Participant', ['id' => $participantID]);
    $this->assertEquals(1, $participant['participant_status_id']);
  }

}
