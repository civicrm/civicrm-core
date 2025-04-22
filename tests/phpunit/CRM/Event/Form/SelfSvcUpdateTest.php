<?php

/**
 *  Test CRM_Event_Form_Registration functions.
 *
 * @package   CiviCRM
 * @group headless
 */
class CRM_Event_Form_SelfSvcUpdateTest extends CiviUnitTestCase {

  /**
   * Test cancellation.
   */
  public function testForm(): void {
    $this->participantCreate(['status_id.name' => 'Registered']);
    $this->addLocationBlockToDomain();
    $this->individualCreate(['email' => 'new@example.org']);

    $this->getTestForm('CRM_Event_Form_SelfSvcUpdate', [
      'email' => 'new@example.org',
      'action' => 2,
      'is_confirmation_email' => 1,
    ], [
      'pid' => $this->ids['Participant']['default'],
      'is_backoffice' => 1,
    ])->processForm();

    $this->assertMailSentContainingStrings([
      'Your Event Registration has been cancelled',
      'Annual CiviCRM meet',
      'Registration Date',
      'February 19th, 2007',
      'Please contact us at 123 or send email to fixme.domainemail@example.org',
      'Tuesday October 21st, 2008 12:00 AM-Thursday October 23rd, 2008 12:00 AM',
    ]);
  }

}
