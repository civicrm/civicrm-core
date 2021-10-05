<?php

use Civi\Api4\PrintLabel;

/**
 *  Test CRM_Event_Form_Registration functions.
 *
 * @package   CiviCRM
 * @group headless
 */
class CRM_Event_Form_Task_BadgeTest extends CiviUnitTestCase {

  use CRMTraits_Custom_CustomDataTrait;

  public function tearDown(): void {
    $this->quickCleanup(['civicrm_participant', 'civicrm_print_label'], TRUE);
    parent::tearDown();
  }

  /**
   * Test the the submit function on the event participant submit function.
   */
  public function testSubmit(): void {
    $this->createCustomGroupWithFieldOfType(['extends' => 'Participant']);
    $contactID = $this->individualCreate(['employer_id' => 1]);
    $participantID = $this->participantCreate([
      'contact_id' => $contactID,
      'fee_level' => 'low',
    ]);

    $badgeLayout = PrintLabel::get()->addSelect('data')->execute()->first();
    $values = [
      'data' => array_merge((array) $badgeLayout['data'], ['token' => [], 'font_name' => [''], 'font_size' => [], 'text_alignment' => []]),
    ];
    foreach (array_keys($this->getAvailableTokens()) as $id => $token) {
      $index = $id + 1;
      $values['data']['token'][$index] = $token;
      $values['data']['font_name'][$index] = 'dejavusans';
      $values['data']['font_size'][$index] = '20';
      $values['data']['font_style'][$index] = '';
      $values['data']['text_alignment'][$index] = 'C';
    }
    PrintLabel::update()->addWhere('id', '=', 1)->setValues($values)->execute();

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
        'value' => 'Annual CiviCRM meet',
        'font_name' => 'dejavusans',
        'font_size' => '20',
        'font_style' => '',
        'text_alignment' => 'C',
        'token' => '{event.title}',
      ], $tokens[1]);
      $index = 1;
      foreach ($this->getAvailableTokens() as $token => $expected) {
        $this->assertEquals($expected, $tokens[$index]['value'], 'failure in token ' . $token);
        $index++;
      }
      return;
    }
    $this->fail('Should not be reached');
  }

  /**
   * @return string[]
   */
  protected function getAvailableTokens(): array {
    return [
      '{event.title}' => 'Annual CiviCRM meet',
      '{contact.display_name}' => 'Mr. Anthony Anderson II',
      '{contact.current_employer}' => 'Default Organization',
      '{event.start_date|crmDate:"%B %E%f"}' => 'October 21st',
      '{participant.status_id}' => 2,
      '{participant.role_id}' => 1,
      '{participant.register_date}' => 'February 19th, 2007',
      '{participant.source}' => 'Wimbeldon',
      '{participant.fee_level}' => 'low',
      '{participant.fee_amount}' => NULL,
      '{participant.registered_by_id}' => NULL,
      '{participant.transferred_to_contact_id}' => NULL,
      '{participant.role_id:label}' => 'Attendee',
      '{participant.fee_label}' => NULL,
      '{event.end_date|crmDate:"%B %E%f"}' => 'October 23rd',
      '{participant.event_id}' => 1,
    ];
  }

}
