<?php

class CRM_Event_Form_ManageEvent_RegistrationTest extends CiviUnitTestCase {

  /**
   * Set up a correct array of form values.
   * @todo More fields are required for formRule to return no errors
   *
   * @return array
   */
  private function getCorrectFormFields() {
    return [
      'is_online_registration' => 1,
      'registration_start_date' => date('Y-m-d'),
      'registration_end_date' => date('Y-m-d', time() + 86400),
      'is_email_confirm' => 0,
      'confirm_title' => 'Confirm your registration',
      'thankyou_title' => 'Thank you for your registration',
      'registration_link_text' => 'Register Now',
    ];
  }

  /**
   * Test end date not allowed with only 'time' part.
   */
  public function testEndDateWithoutDateNotAllowed(): void {
    $values = $this->getCorrectFormFields();
    $values['registration_end_date'] = '00:01';
    $form = new CRM_Event_Form_ManageEvent_Registration();
    $validationResult = \CRM_Event_Form_ManageEvent_Registration::formRule($values, [], $form);
    $this->assertArrayHasKey('registration_end_date', $validationResult);
  }

  /**
   * Test end date must be after start date.
   */
  public function testEndDateBeforeStartDateNotAllowed(): void {
    $values = $this->getCorrectFormFields();
    $values['registration_end_date'] = '1900-01-01 00:00';
    $form = new CRM_Event_Form_ManageEvent_Registration();
    $validationResult = \CRM_Event_Form_ManageEvent_Registration::formRule($values, [], $form);
    $this->assertArrayHasKey('registration_end_date', $validationResult);
  }

}
