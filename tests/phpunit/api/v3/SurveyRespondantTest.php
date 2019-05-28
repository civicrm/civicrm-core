<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
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
    $phoneBankActivity = $this->callAPISuccess('Option_value', 'Get', array('label' => 'PhoneBank', 'sequential' => 1));
    $phoneBankActivityTypeID = $phoneBankActivity['values'][0]['value'];
    $surveyParams = array(
      'title' => "survey respondent",
      'activity_type_id' => $phoneBankActivityTypeID,
      'instructions' => "Call people, ask for money",
    );
    $survey = $this->callAPISuccess('survey', 'create', $surveyParams);
    $surveyID = $survey['id'];
    $this->params = array(
      'sequential' => '1',
      'survey_id' => $surveyID,
    );
  }

  /**
   * Test survey respondent get.
   */
  public function testGetSurveyRespondants() {
    $result = $this->callAPIAndDocument("SurveyRespondant", "get", $this->params, __FUNCTION__, __FILE__);
  }

}
