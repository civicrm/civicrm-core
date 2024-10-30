<?php

class CRM_Contribute_Form_ContributionPage_SettingsTest extends CiviUnitTestCase {

  /**
   * Set up a correct array of form values.
   *
   * @return array
   */
  private function getCorrectFormFields() {
    return [
      'title' => 'Test contribution page',
      'financial_type_id' => 1,
      'start_date' => date('Y-m-d'),
      'end_date' => date('Y-m-d', time() + 86400),
    ];
  }

  /**
   * Test correct form submission.
   */
  public function testValidFormSubmission(): void {
    $values = $this->getCorrectFormFields();
    $form = new CRM_Contribute_Form_ContributionPage_Settings();
    $validationResult = \CRM_Contribute_Form_ContributionPage_Settings::formRule($values, [], $form);
    $this->assertEmpty($validationResult);
  }

  /**
   * Test end date not allowed with only 'time' part.
   */
  public function testEndDateWithoutDateNotAllowed(): void {
    $values = $this->getCorrectFormFields();
    $values['end_date'] = '00:01';
    $form = new CRM_Contribute_Form_ContributionPage_Settings();
    $validationResult = \CRM_Contribute_Form_ContributionPage_Settings::formRule($values, [], $form);
    $this->assertArrayHasKey('end_date', $validationResult);
  }

  /**
   * Test end date must be after start date.
   */
  public function testEndDateBeforeStartDateNotAllowed(): void {
    $values = $this->getCorrectFormFields();
    $values['end_date'] = '1900-01-01 00:00';
    $form = new CRM_Contribute_Form_ContributionPage_Settings();
    $validationResult = \CRM_Contribute_Form_ContributionPage_Settings::formRule($values, [], $form);
    $this->assertArrayHasKey('end_date', $validationResult);
  }

}
