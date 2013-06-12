<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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

require_once 'CiviTest/CiviUnitTestCase.php';

class api_v3_SurveyRespondantTest extends CiviUnitTestCase {
  protected $_apiversion;
  protected $params;
  function setUp() {
    $this->_apiversion = 3;
    $phoneBankActivity = civicrm_api('Option_value', 'Get', array('label' => 'PhoneBank', 'version' => $this->_apiversion, 'sequential' => 1));
    $phoneBankActivityTypeID = $phoneBankActivity['values'][0]['value'];
    $surveyParams = array(
      'version' => $this->_apiversion,
      'title' => "survey respondent",
      'activity_type_id' => $phoneBankActivityTypeID,
      'instructions' => "Call people, ask for money",
    );
    $survey = civicrm_api('survey', 'create', $surveyParams);
    $surveyID = $survey['id'];
    $this->params = array (
                           'version' => $this->_apiversion,
                           'sequential' =>'1',
                           'survey_id' => $surveyID
                           );
    parent::setUp();
  }

  function tearDown() {
    $this->quickCleanup(array('civicrm_survey'));
  }

  /**
   * Test surveyRespondent get with wrong params type.
   */
  public function testGetWrongParamsType() {
    $params = 'abc';
    $GetWrongParamsType = civicrm_api("SurveyRespondant","get", $params );
    $this->assertEquals($GetWrongParamsType['error_message'], 'Input variable `params` is not an array');
  }

  /**
   * Test surveyRespondent get with empty params.
   */
  public function testGetEmptyParams() {
    $params = array();
    $GetEmptyParams = civicrm_api("SurveyRespondant","get", $params );
    $this->assertEquals($GetEmptyParams['error_message'], 'Mandatory key(s) missing from params array: version');
  }
  /**
   * Test survey respondent get.
   */
  public function testGetSurveyRespondants() {
    $result = civicrm_api("SurveyRespondant","get", $this->params );
    $this->assertAPISuccess($result);
    $this->documentMe($this->params, $result, __FUNCTION__, __FILE__);
    $this->assertAPISuccess($result, 'In line ' . __LINE__);
  }

}

