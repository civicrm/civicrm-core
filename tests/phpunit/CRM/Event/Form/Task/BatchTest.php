<?php

use Civi\Api4\Contribution;
use Civi\Api4\Participant;

/**
 *  Test CRM_Event_Form_Registration functions.
 *
 * @package   CiviCRM
 * @group headless
 */
class CRM_Event_Form_Task_BatchTest extends CiviUnitTestCase {
  use CRMTraits_Financial_OrderTrait;

  protected function tearDown(): void {
    $this->quickCleanUpFinancialEntities();
    parent::tearDown();
  }

  /**
   * Test the submit function on the event participant submit function.
   */
  public function testSubmit(): void {
    $group = $this->customGroupCreate(['extends' => 'Participant', 'title' => 'Participant']);
    $field = $this->customFieldCreate(['custom_group_id' => $group['id'], 'html_type' => 'CheckBox', 'option_values' => ['two' => 'A couple', 'three' => 'A few', 'four' => 'Too Many']]);
    $participantID = $this->participantCreate();
    $participant = $this->callAPISuccessGetSingle('Participant', ['id' => $participantID]);
    $this->assertEquals($this->getParticipantStatusID('Attended'), $participant['participant_status_id']);

    $this->getTestForm('CRM_Event_Form_Task_Batch', [
      'field' => [$participantID => ['participant_status_id' => $this->getParticipantStatusID('Registered'), 'custom_' . $field['id'] => ['two' => 1, 'four' => 1]]],
    ])->postProcess();

    $participant = $this->callAPISuccessGetSingle('Participant', ['id' => $participantID]);
    $this->assertEquals($this->getParticipantStatusID('Registered'), $participant['participant_status_id']);
  }

  /**
   * Cancelling a pay-later online registration cancels its pending contribution.
   *
   * @throws \CRM_Core_Exception
   */
  public function testSubmitCancel(): void {
    $this->createPayLaterRegistration('Online Event Registration: Annual CiviCRM meet', 'Pending from pay later');
    $this->submitParticipantStatus('Cancelled');
    $this->assertContributionStatus('Cancelled');
  }

  /**
   * The registration is recognised by its status, not by the wording of its source.
   *
   * The source may have been stored in another language or typed in by the
   * registrant through a participant_source profile field.
   *
   * @throws \CRM_Core_Exception
   */
  public function testSubmitCancelWithOtherSource(): void {
    $this->createPayLaterRegistration('Anmeldung zu Annual CiviCRM meet (online)', 'Pending from pay later');
    $this->submitParticipantStatus('Cancelled');
    $this->assertContributionStatus('Cancelled');
  }

  /**
   * A pending contribution of a participant who is not waiting for a pay-later payment is left alone.
   *
   * @throws \CRM_Core_Exception
   */
  public function testSubmitCancelKeepsContributionOfRegisteredParticipant(): void {
    $this->createPayLaterRegistration('Online Event Registration: Annual CiviCRM meet', 'Registered');
    $this->submitParticipantStatus('Cancelled');
    $this->assertContributionStatus('Pending');
  }

  /**
   * Registering the participant does not mark the pay-later contribution as paid.
   *
   * @throws \CRM_Core_Exception
   */
  public function testSubmitRegisteredKeepsContributionPending(): void {
    $this->createPayLaterRegistration('Online Event Registration: Annual CiviCRM meet', 'Pending from pay later');
    $this->submitParticipantStatus('Registered');
    $this->assertContributionStatus('Pending');
  }

  /**
   * Create a pending pay-later event order with the given source and participant status.
   *
   * @param string $source
   * @param string $participantStatus
   *
   * @throws \CRM_Core_Exception
   */
  private function createPayLaterRegistration(string $source, string $participantStatus): void {
    $this->createEventOrder(['source' => $source, 'is_pay_later' => 1]);
    Participant::update(FALSE)
      ->addWhere('id', '=', $this->ids['Participant'][0])
      ->addValue('status_id:name', $participantStatus)
      ->execute();
  }

  /**
   * Submit the batch update form with a new status for the participant.
   *
   * @param string $status
   */
  private function submitParticipantStatus(string $status): void {
    $statusID = $this->getParticipantStatusID($status);
    $this->getTestForm('CRM_Event_Form_Task_Batch', [
      'field' => [$this->ids['Participant'][0] => ['participant_status' => $statusID]],
    ])->postProcess();
    $this->assertEquals($status, Participant::get(FALSE)
      ->addWhere('id', '=', $this->ids['Participant'][0])
      ->addSelect('status_id:name')
      ->execute()->single()['status_id:name']);
  }

  /**
   * @param string $status
   *
   * @throws \CRM_Core_Exception
   */
  private function assertContributionStatus(string $status): void {
    $this->assertEquals($status, Contribution::get(FALSE)
      ->addWhere('id', '=', $this->ids['Contribution'][0])
      ->addSelect('contribution_status_id:name')
      ->execute()->single()['contribution_status_id:name']);
  }

}
