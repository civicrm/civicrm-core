<?php

class CRM_Event_Form_ManageEvent_EventInfoTest extends CiviUnitTestCase {

  /**
   * Set up a correct array of form values.
   *
   * @return array
   */
  private function getCorrectFormFields(): array {
    return [
      'title' => 'A test event',
      'event_type_id' => 1,
      'default_role_id' => 1,
      'start_date' => date('Y-m-d'),
      'end_date' => date('Y-m-d', time() + 86400),
      'is_template' => '0',
    ];
  }

  /**
   * Test correct form submission.
   * @dataProvider isTemplateProvider
   * @param string $is_template
   */
  public function testValidFormSubmission(string $is_template): void {
    $values = array_merge($this->getCorrectFormFields(), ['is_template' => $is_template]);
    $validationResult = \CRM_Event_Form_ManageEvent_EventInfo::formRule($values);
    $this->assertEmpty($validationResult);
  }

  /**
   * Test end date not allowed with only 'time' part.
   */
  public function testEndDateWithoutDateNotAllowed(): void {
    $values = $this->getCorrectFormFields();
    $values['end_date'] = '00:01';
    $validationResult = \CRM_Event_Form_ManageEvent_EventInfo::formRule($values);
    $this->assertArrayHasKey('end_date', $validationResult);
  }

  /**
   * Test end date must be after start date.
   */
  public function testEndDateBeforeStartDateNotAllowed(): void {
    $values = $this->getCorrectFormFields();
    $values['end_date'] = '1900-01-01 00:00';
    $validationResult = \CRM_Event_Form_ManageEvent_EventInfo::formRule($values);
    $this->assertArrayHasKey('end_date', $validationResult);
  }

  /**
   * Data Provider to test both regular events and event templates.
   *
   * @return array
   */
  public function isTemplateProvider(): array {
    return [['0'], ['1']];
  }

}
