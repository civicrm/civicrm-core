<?php

/**
 *  Test CRM_Event_Form_Registration functions.
 *
 * @package   CiviCRM
 * @group headless
 */
class CRM_Event_Form_Task_RegisterTest extends CiviUnitTestCase {

  /**
   * Initial test of form class.
   *
   * @throws \CRM_Core_Exception
   */
  public function testGet(): void {
    /** @var CRM_Event_Form_Task_Register $form */
    $form = $this->getFormObject('CRM_Event_Form_Task_Register');
    $this->assertEquals(FALSE, $form->_single);
  }

  /**
   * Test that duplicate participants are not registered.
   *
   * @dataProvider participantDataProvider
   *
   * @return void
   */
  public function testRegisterDuplicateParticipant(array $participantValues, bool $isDuplicate): void {
    $event = $this->eventCreateUnpaid();

    $this->participantCreate($participantValues + ['event_id' => $event['id'], 'contact_id' => $this->individualCreate([])]);

    $this->getTestForm('CRM_Contact_Form_Search_Basic', [
      'radio_ts' => 'ts_all',
      ['contact_id', 'IN', $this->ids['Contact']],
      'task' => CRM_Core_Task::BATCH_UPDATE,
    ], ['action' => 1])
      ->addSubsequentForm('CRM_Event_Form_Task_Register', ['event_id' => $this->getEventID()])
      ->processForm();
    $message = CRM_Core_Session::singleton()->getStatus();
    if ($isDuplicate) {
      $this->assertEquals('1 contacts have already been assigned to this event. They were not added a second time.', $message[0]['text']);
      $this->assertEquals('No participants were added.', $message[1]['text']);
    }
    else {
      $this->assertEquals('Total Participant(s) added to event: 1.', $message[0]['text']);
    }
  }

  public function participantDataProvider(): array {
    return [
      'basic' => [
        'participant_values' => [],
        'duplicate' => TRUE,
      ],
      'different_event' => [
        'participant_values' => ['is_test' => TRUE],
        'duplicate' => FALSE,
      ],
      'cancelled' => [
        'participant_values' => ['participant_status_id.name' => 'Cancelled'],
        // @todo - TRUE is existing behaviour but it might be better to change the behaviour.
        'duplicate' => TRUE,
      ],
    ];
  }

}
