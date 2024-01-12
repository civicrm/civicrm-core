<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

use Civi\Api4\Participant;
use Civi\Test\EventTestTrait;

/**
 * Trait to do set up for event scenarios.
 *
 * This allows multiple test classes to act on various scenarios. e.g a
 * user might complete a pending registration using the back office form, an api
 * call or the horrible front end form overload method.
 *
 * It might make sense to move this to Civi\Test for extensions to use
 * but it might need to 'settle' a bit first as there is a balance to be found
 * between accepting parameters & winding up with crazy complex functions.
 */
trait CRMTraits_Event_ScenarioTrait {

  use EventTestTrait;

  /**
   * Mail sent during form submission.
   *
   * @var array
   */
  protected $sentMail = [];

  /**
   * Create a participant registration with 2 registered_by participants.
   *
   * This follows the front end form multiple participant flow with tax enabled.
   *
   * @throws \Civi\API\Exception\UnauthorizedException
   * @throws \CRM_Core_Exception
   */
  protected function createScenarioMultipleParticipantPendingWithTax(): void {
    $this->eventCreatePaid();
    $this->addTaxAccountToFinancialType(CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'financial_type_id', 'Event Fee'));
    $form = $this->getTestForm('CRM_Event_Form_Registration_Register', [
      'first_name' => 'Participant1',
      'last_name' => 'LastName',
      'job_title' => 'oracle',
      'email-Primary' => 'participant1@example.com',
      'additional_participants' => 2,
      'payment_processor_id' => 0,
      'priceSetId' => $this->getPriceSetID('PaidEvent'),
      'price_' . $this->ids['PriceField']['PaidEvent'] => $this->ids['PriceFieldValue']['PaidEvent_standard'],
      'defaultRole' => 1,
      'participant_role_id' => '1',
      'button' => '_qf_Register_upload',
    ], ['id' => $this->getEventID()])
      ->addSubsequentForm('CRM_Event_Form_Registration_AdditionalParticipant', [
        'first_name' => 'Participant2',
        'last_name' => 'LastName',
        'job_title' => 'wizard',
        'email-Primary' => 'participant2@example.com',
        'priceSetId' => $this->getPriceSetID('PaidEvent'),
        'price_' . $this->ids['PriceField']['PaidEvent'] => $this->ids['PriceFieldValue']['PaidEvent_student'],
      ])
      ->addSubsequentForm('CRM_Event_Form_Registration_AdditionalParticipant', [
        'first_name' => 'Participant3',
        'last_name' => 'LastName',
        'job_title' => 'seer',
        'email-Primary' => 'participant3@example.com',
        'priceSetId' => $this->getPriceSetID('PaidEvent'),
        'price_' . $this->ids['PriceField']['PaidEvent'] => $this->ids['PriceFieldValue']['PaidEvent_student_plus'],
      ])
      ->addSubsequentForm('CRM_Event_Form_Registration_Confirm')
      ->processForm();
    $this->sentMail = $form->getMail();
    $participants = Participant::get(FALSE)
      ->addWhere('event_id', '=', $this->getEventID('PaidEvent'))
      ->addOrderBy('registered_by_id')
      ->execute();
    foreach ($participants as $index => $participant) {
      $identifier = $participant['registered_by_id'] ? 'participant_' . $index : 'primary';
      $this->setTestEntity('Participant', $participant, $identifier);
      $this->setTestEntityID('Contact', $participant['contact_id'], $identifier);
    }

  }

}
