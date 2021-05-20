<?php

/**
 *  Test CRM_Event_Form_Registration functions.
 *
 * @package   CiviCRM
 * @group headless
 */
class CRM_Event_Form_Task_BatchTest extends CiviUnitTestCase {
  use CRMTraits_Financial_OrderTrait;

  /**
   * Test the the submit function on the event participant submit function.
   */
  public function testSubmit() {
    $group = $this->customGroupCreate(['extends' => 'Participant', 'title' => 'Participant']);
    $field = $this->customFieldCreate(['custom_group_id' => $group['id'], 'html_type' => 'CheckBox', 'option_values' => ['two' => 'A couple', 'three' => 'A few', 'four' => 'Too Many']]);
    $participantID = $this->participantCreate();
    $participant = $this->callAPISuccessGetSingle('Participant', ['id' => $participantID]);
    $this->assertEquals(2, $participant['participant_status_id']);

    /* @var CRM_Event_Form_Task_Batch $form */
    $form = $this->getFormObject('CRM_Event_Form_Task_Batch');
    $form->submit(['field' => [$participantID => ['participant_status_id' => 1, 'custom_' . $field['id'] => ['two' => 1, 'four' => 1]]]]);

    $participant = $this->callAPISuccessGetSingle('Participant', ['id' => $participantID]);
    $this->assertEquals(1, $participant['participant_status_id']);
  }

  /**
   * Test the the submit function on the event participant submit function.
   *
   * Test is to establish existing behaviour prior to code cleanup. It turns
   * out the existing code ONLY cancels the contribution as well as the
   * participant record if is_pay_later is true AND the source is 'Online Event
   * Registration'.
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testSubmitCancel(): void {
    $this->createEventOrder(['source' => 'Online Event Registration', 'is_pay_later' => 1]);
    $participantCancelledStatusID = CRM_Core_PseudoConstant::getKey('CRM_Event_BAO_Participant', 'status_id', 'Cancelled');

    /* @var CRM_Event_Form_Task_Batch $form */
    $form = $this->getFormObject('CRM_Event_Form_Task_Batch');
    $form->submit(['field' => [$this->ids['Participant'][0] => ['participant_status' => $participantCancelledStatusID]]]);

    $participant = $this->callAPISuccessGetSingle('Participant', ['id' => $this->ids['Participant'][0]]);
    $this->assertEquals($participantCancelledStatusID, $participant['participant_status_id']);
    $this->callAPISuccessGetSingle('Contribution', ['id' => $this->ids['Contribution'][0], 'contribution_status_id' => 'Cancelled']);
  }

}
