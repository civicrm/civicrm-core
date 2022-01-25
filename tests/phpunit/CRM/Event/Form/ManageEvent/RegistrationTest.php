<?php

use Civi\Api4\Event;

class CRM_Event_Form_ManageEvent_RegistrationTest extends CiviUnitTestCase {

  public function preventCiviExit($event) {
    if (is_a($event->form, 'CRM_Event_Form_ManageEvent_Registration')) {
      // EventInfo form redirects to the location form if the action is ADD
      $event->form->setAction(CRM_Core_Action::NONE);
    }
  }

  public function testTimeZone() {
    $event_id = $this->eventCreate([
      'event_tz' => 'Australia/Sydney',
      'start_date' => '2022-06-22 12:00:00',
      'end_date' => '2022-06-22 20:00:00',
    ])['id'];

    $formValues = [
      'registration_start_date' => '2022-05-23 09:00:00',
      'registration_end_date' => '2022-06-20 17:00:00',
    ];

    Civi::dispatcher()->addListener('hook_civicrm_postProcess', [$this, 'preventCiviExit']);

    $this->submitForm($formValues, $event_id);

    $this->assertIsInt($event_id, 'Event creation success');

    Civi::dispatcher()->removeListener('hook_civicrm_postProcess', [$this, 'preventCiviExit']);

    $event = Event::get(FALSE)
      ->addWhere('id', '=', $event_id)
      ->addSelect('id', 'registration_start_date', 'registration_end_date', 'event_tz')
      ->execute()[0];

    $this->assertEquals('2022-05-22 23:00:00', $event['registration_start_date'], 'Registration start date resolved by timezone.');
    $this->assertEquals('2022-06-20 07:00:00', $event['registration_end_date'], 'Registration end date resolved by timezone.');

  }

  public function submitForm(array $formValues, int $eventID): int {
    $form = $this->getFormObject(CRM_Event_Form_ManageEvent_Registration::class, $formValues);
    if ($eventID) {
      $form->set('id', $eventID);
    }

    $form->preProcess();
    $form->buildQuickForm();
    $form->postProcess();
    return $form->get('id');
  }

}
