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


/**
 *  Test APIv3 civicrm_survey_* functions
 *
 *  @package CiviCRM_APIv3
 *  @subpackage API_Campaign
 */

require_once 'CiviTest/CiviUnitTestCase.php';
class api_v3_SurveyTest extends CiviUnitTestCase {
  protected $_apiversion;
  protected $params;
  protected $id;
  public $DBResetRequired = FALSE; function setUp() {
    $this->_apiversion = 3;
    $phoneBankActivity = civicrm_api('Option_value', 'Get', array('label' => 'PhoneBank', 'version' => $this->_apiversion, 'sequential' => 1));
    $phoneBankActivityTypeID = $phoneBankActivity['values'][0]['value'];
    $this->params = array(
      'version' => 3,
      'title' => "survey title",
      'activity_type_id' => $phoneBankActivityTypeID,
      'max_number_of_contacts' => 12,
      'instructions' => "Call people, ask for money",
    );
    parent::setUp();
  }

  function tearDown() {}

  public function testCreateSurvey() {
    $result = civicrm_api('survey', 'create', $this->params);
    $this->documentMe($this->params, $result, __FUNCTION__, __FILE__);
    $this->assertAPISuccess($result, 'In line ' . __LINE__);
    $this->assertEquals(1, $result['count'], 'In line ' . __LINE__);
    $this->assertNotNull($result['values'][$result['id']]['id'], 'In line ' . __LINE__);
  }

  public function testGetSurvey() {

    $result = civicrm_api('survey', 'get', $this->params);
    $this->documentMe($this->params, $result, __FUNCTION__, __FILE__);
    $this->assertAPISuccess($result, 'In line ' . __LINE__);
    $this->assertEquals(1, $result['count'], 'In line ' . __LINE__);
    $this->assertNotNull($result['values'][$result['id']]['id'], 'In line ' . __LINE__);
    $this->id = $result['id'];
  }

  public function testDeleteSurvey() {
    $entity = civicrm_api('survey', 'get', $this->params);
    $result = civicrm_api('survey', 'delete', array('version' => 3, 'id' => $entity['id']));
    $this->documentMe($this->params, $result, __FUNCTION__, __FILE__);
    $this->assertAPISuccess($result, 'In line ' . __LINE__);
    $checkDeleted = civicrm_api('survey', 'get', array(
      'version' => 3,
      ));
    $this->assertEquals(0, $checkDeleted['count'], 'In line ' . __LINE__);
  }

  public function testGetSurveyChainDelete() {
    $description = "demonstrates get + delete in the same call";
    $subfile     = 'ChainedGetDelete';
    $params      = array(
      'version' => 3,
      'title' => "survey title",
      'api.survey.delete' => 1,
    );
    $result = civicrm_api('survey', 'create', $this->params);
    $result = civicrm_api('survey', 'get', $params);
    $this->documentMe($params, $result, __FUNCTION__, __FILE__, $description, $subfile);
    $this->assertAPISuccess($result, 'In line ' . __LINE__);
    $this->assertEquals(0, civicrm_api('survey', 'getcount', array('version' => 3)), 'In line ' . __LINE__);
  }
}

