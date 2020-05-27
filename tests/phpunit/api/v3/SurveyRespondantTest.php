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

/**
 * Class api_v3_SurveyRespondantTest
 * @group headless
 */
class api_v3_SurveyRespondantTest extends CiviUnitTestCase {
  protected $_apiversion = 3;
  protected $params;

  public function setUp() {
    parent::setUp();
    $this->useTransaction(TRUE);
    $phoneBankActivity = $this->callAPISuccess('Option_value', 'Get', ['label' => 'PhoneBank', 'sequential' => 1]);
    $phoneBankActivityTypeID = $phoneBankActivity['values'][0]['value'];
    $surveyParams = [
      'title' => "survey respondent",
      'activity_type_id' => $phoneBankActivityTypeID,
      'instructions' => "Call people, ask for money",
    ];
    $survey = $this->callAPISuccess('survey', 'create', $surveyParams);
    $surveyID = $survey['id'];
    $this->params = [
      'sequential' => '1',
      'survey_id' => $surveyID,
    ];
  }

  /**
   * Test survey respondent get.
   */
  public function testGetSurveyRespondants() {
    $result = $this->callAPIAndDocument("SurveyRespondant", "get", $this->params, __FUNCTION__, __FILE__);
  }

}
