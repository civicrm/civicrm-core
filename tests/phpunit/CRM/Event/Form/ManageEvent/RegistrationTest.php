<?php

use Civi\Api4\Event;
use Civi\Api4\UFGroup;
use Civi\Api4\UFJoin;
use Civi\Test\FormTrait;

class CRM_Event_Form_ManageEvent_RegistrationTest extends CiviUnitTestCase {

  use FormTrait;

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
      'is_template' => '0',
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

  /**
   * Test for https://lab.civicrm.org/dev/core/-/work_items/6705
   *
   * Leaving "Bottom Profile Fields for Additional Participants" unselected
   * must not silently inherit the primary participant's own profile - that
   * now requires explicitly choosing 'inherit'. Leaving it blank (or
   * choosing the explicit '- none -' option) must result in no additional
   * profile at all - including on a second save where the field is still
   * left blank, to guard against blank being reinterpreted as 'inherit'.
   */
  public function testAdditionalParticipantProfileNotInheritedWhenBlank(): void {
    $eventID = $this->createTestEvent();
    $primaryPostProfileID = $this->createTestProfile();
    $additionalPreProfileID = $this->createTestProfile();

    $formValues = [
      'is_online_registration' => 1,
      'is_multiple_registrations' => 1,
      'max_additional_participants' => 9,
      'custom_post_id' => $primaryPostProfileID,
      'additional_custom_pre_id' => $additionalPreProfileID,
      'additional_custom_post_id' => '',
      'is_email_confirm' => 0,
      'is_template' => 0,
      'registration_start_date' => '2029-09-08',
      'registration_end_date' => '2029-09-20',
    ];
    $this->getTestForm('CRM_Event_Form_ManageEvent_Registration', $formValues, ['id' => $eventID])->processForm();

    $profileIDs = UFJoin::get(FALSE)
      ->addWhere('entity_table', '=', 'civicrm_event')
      ->addWhere('entity_id', '=', $eventID)
      ->addWhere('module', '=', 'CiviEvent_Additional')
      ->execute()->indexBy('uf_group_id');
    // Only the pre is found - the post profile is 'null' - ie do not inherit the primary's post profile
    $this->assertEquals($additionalPreProfileID, $profileIDs->single()['uf_group_id']);

    // Save again with the field still blank - it must stay unset, not drift
    // towards inheriting the primary's profile on a subsequent save.
    $this->getTestForm('CRM_Event_Form_ManageEvent_Registration', $formValues, ['id' => $eventID])->processForm();
    $profileIDs = UFJoin::get(FALSE)
      ->addWhere('entity_table', '=', 'civicrm_event')
      ->addWhere('entity_id', '=', $eventID)
      ->addWhere('module', '=', 'CiviEvent_Additional')
      ->execute()->indexBy('uf_group_id');
    // A second save with the field still blank must not inherit the primary\'s post profile either.
    $this->assertEquals($additionalPreProfileID, $profileIDs->single()['uf_group_id']);
  }

  /**
   * Explicitly choosing 'inherit' should copy the primary's profile.
   */
  public function testAdditionalParticipantProfileInheritsWhenExplicit(): void {
    $eventID = $this->createTestEvent();
    $primaryPostProfileID = $this->createTestProfile();

    $this->getTestForm('CRM_Event_Form_ManageEvent_Registration', [
      'is_online_registration' => 1,
      'is_multiple_registrations' => 1,
      'max_additional_participants' => 9,
      'custom_post_id' => $primaryPostProfileID,
      'additional_custom_post_id' => 'inherit',
      'registration_start_date' => '2028-09-01',
      'registration_end_date' => '2028-09-06',
      'is_template' => 0,
      'is_email_confirm' => 0,
    ], ['id' => $eventID])->processForm();

    $profileIDs = UFJoin::get(FALSE)
      ->addWhere('entity_table', '=', 'civicrm_event')
      ->addWhere('entity_id', '=', $eventID)
      ->addWhere('module', '=', 'CiviEvent_Additional')
      ->execute()->column('uf_group_id');
    $this->assertEquals([$primaryPostProfileID], $profileIDs);
  }

  private function createTestEvent(): int {
    return (int) Event::create(FALSE)->setValues([
      'title' => 'Test event',
      'event_type_id' => 1,
      'start_date' => date('Y-m-d'),
      'is_active' => TRUE,
    ])->execute()->first()['id'];
  }

  private function createTestProfile(): int {
    return (int) UFGroup::create(FALSE)->setValues([
      'title' => 'Test profile ' . uniqid(),
      'group_type' => 'Individual,Contact',
    ])->execute()->first()['id'];
  }

}
