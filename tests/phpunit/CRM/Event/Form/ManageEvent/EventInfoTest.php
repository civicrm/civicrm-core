<?php

use Civi\Api4\Event;

class CRM_Event_Form_ManageEvent_EventInfoTest extends CiviUnitTestCase {

  public function preventCiviExit($event) {
    if (is_a($event->form, 'CRM_Event_Form_ManageEvent_EventInfo')) {
      // EventInfo form redirects to the location form if the action is ADD
      $event->form->setAction(CRM_Core_Action::NONE);
    }
  }

  public function testTimeZone() {
    $formValues = [
      'start_date' => '2022-06-22 12:00:00',
      'end_date' => '2022-06-22 20:00:00',
      'event_tz' => 'Australia/Sydney',
    ];

    Civi::dispatcher()->addListener('hook_civicrm_postProcess', [$this, 'preventCiviExit']);

    $event_id = $this->submitForm($formValues);

    $this->assertIsInt($event_id, 'Event creation success');

    Civi::dispatcher()->removeListener('hook_civicrm_postProcess', [$this, 'preventCiviExit']);

    $event = Event::get(FALSE)
      ->addWhere('id', '=', $event_id)
      ->addSelect('id', 'start_date', 'end_date', 'event_tz')
      ->execute()[0];

    $this->assertEquals('2022-06-22 02:00:00', $event['start_date'], 'Event start date resolved by timezone.');
    $this->assertEquals('2022-06-22 10:00:00', $event['end_date'], 'Event end date resolved by timezone.');
  }

  public function getFormValues() {
    if (empty(Civi::$statics[__CLASS__])) {
      Civi::$statics[__CLASS__] = $this->eventCreate();
      Civi::$statics[__CLASS__]['id'] = NULL;
    }

    return Civi::$statics[__CLASS__];
  }

  public function submitForm(array $formValues, ?int $eventID = NULL): int {
    $form = $this->getFormObject(CRM_Event_Form_ManageEvent_EventInfo::class, array_merge($this->getFormValues(), $formValues));
    if ($eventID) {
      $form->set('id', $eventID);
    }

    $form->preProcess();
    $form->buildQuickForm();
    $form->postProcess();
    return $form->get('id');
  }

}
