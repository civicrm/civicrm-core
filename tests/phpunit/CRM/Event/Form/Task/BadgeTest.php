<?php

/**
 *  Test CRM_Event_Form_Registration functions.
 *
 * @package   CiviCRM
 * @group headless
 */
class CRM_Event_Form_Task_BadgeTest extends CiviUnitTestCase {

  use CRMTraits_Custom_CustomDataTrait;

  /**
   * Test the the submit function on the event participant submit function.
   */
  public function testSubmit(): void {
    $this->createCustomGroupWithFieldOfType(['extends' => 'Participant']);
    $contactID = $this->individualCreate();
    $participantID = $this->participantCreate(['contact_id' => $contactID]);

    $_REQUEST['context'] = 'view';
    $_REQUEST['id'] = $participantID;
    $_REQUEST['cid'] = $contactID;
    /* @var CRM_Event_Form_Task_Badge $form */
    $form = $this->getFormObject(
      'CRM_Event_Form_Task_Badge',
      ['badge_id' => 1],
      NULL,
      [
        'task' => CRM_Core_Task::BATCH_UPDATE,
        'radio_ts' => 'ts_sel',
        'mark_x_' . $participantID => 1,
      ]
    );
    $form->buildForm();
    try {
      $form->postProcess();
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      $tokens = $e->errorData['formattedRow']['token'];
      $this->assertEquals([
        1 => [
          'value' => 'Annual CiviCRM meet',
          'font_name' => 'dejavusans',
          'font_size' => '9',
          'font_style' => '',
          'text_alignment' => 'L',
          'token' => '{event.title}',
        ],
        2 =>
          [
            'value' => 'Mr. Anthony Anderson II',
            'font_name' => 'dejavusans',
            'font_size' => '20',
            'font_style' => '',
            'text_alignment' => 'C',
            'token' => '{contact.display_name}',
          ],
        3 =>
          [
            'value' => NULL,
            'font_name' => 'dejavusans',
            'font_size' => '15',
            'font_style' => '',
            'text_alignment' => 'C',
            'token' => '{contact.current_employer}',
          ],
        4 =>
          [
            'value' => 'October 21st',
            'font_name' => 'dejavusans',
            'font_size' => '9',
            'font_style' => '',
            'text_alignment' => 'R',
            'token' => '{event.start_date}',
          ],
      ], $tokens);
      return;
    }
    $this->fail('Should not be reached');
  }

}
