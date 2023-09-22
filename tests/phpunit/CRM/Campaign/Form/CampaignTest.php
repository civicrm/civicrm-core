<?php

class CRM_Campaign_Form_CampaignTest extends CiviUnitTestCase {

  /**
   * Set up a correct array of form values.
   *
   * @return array
   */
  private function getCorrectFormFields($thousandSeparator) {
    return [
      'goal_revenue' => '$10' . $thousandSeparator . '000',
      'is_active' => 1,
      'title' => 'Test Campaign',
      'start_date' => date('Y-m-d'),
      'includeGroups' => [],
      'custom' => [],
      'campaign_type_id' => 1,
    ];
  }

  /**
   * Test the submit function on the campaign page.
   *
   * @param string $thousandSeparator
   *
   * @dataProvider getThousandSeparators
   */
  public function testSubmit($thousandSeparator) {
    $this->setCurrencySeparators($thousandSeparator);
    $this->createLoggedInUser();
    $form = new CRM_Campaign_Form_Campaign();
    $form->_action = CRM_Core_Action::ADD;
    $values = $this->getCorrectFormFields($thousandSeparator);
    $result = CRM_Campaign_Form_Campaign::Submit($values, $form);
    $campaign = $this->callAPISuccess('campaign', 'get', ['id' => $result['id']]);
    $this->assertEquals('10000.00', $campaign['values'][$campaign['id']]['goal_revenue']);
  }

  /**
   * Test end date not allowed with only 'time' part.
   *
   * @param string $thousandSeparator
   *
   * @dataProvider getThousandSeparators
   */
  public function testEndDateWithoutDateNotAllowed($thousandSeparator) {
    $this->setCurrencySeparators($thousandSeparator);
    $values = $this->getCorrectFormFields($thousandSeparator);
    $values['end_date'] = '00:01';
    $validationResult = \CRM_Campaign_Form_Campaign::formRule($values);
    $this->assertArrayHasKey('end_date', $validationResult);
  }

  /**
   * Test end date must be after start date.
   *
   * @param string $thousandSeparator
   *
   * @dataProvider getThousandSeparators
   */
  public function testEndDateBeforeStartDateNotAllowed($thousandSeparator) {
    $this->setCurrencySeparators($thousandSeparator);
    $values = $this->getCorrectFormFields($thousandSeparator);
    $values['end_date'] = '1900-01-01 00:00';
    $validationResult = \CRM_Campaign_Form_Campaign::formRule($values);
    $this->assertArrayHasKey('end_date', $validationResult);
  }

}
