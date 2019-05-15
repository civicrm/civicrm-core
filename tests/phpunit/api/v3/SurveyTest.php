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
 *  Test APIv3 civicrm_survey_* functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Campaign
 */

/**
 * All API should contain at minimum a success test for each
 * function - in this case - create, get & delete
 * In addition any extra functionality should be tested & documented
 *
 * Failure tests should be added for specific api behaviours but note that
 * many generic patterns are tested in the syntax conformance test
 *
 * @author eileen
 *
 * @group headless
 */
class api_v3_SurveyTest extends CiviUnitTestCase {
  protected $params;
  protected $entity = 'survey';
  public $DBResetRequired = FALSE;

  public function setUp() {
    $phoneBankActivityTypeID = $this->callAPISuccessGetValue('Option_value', array(
      'label' => 'PhoneBank',
      'return' => 'value',
    ), 'integer');
    $this->useTransaction();
    $this->enableCiviCampaign();
    $this->params = array(
      'title' => "survey title",
      'activity_type_id' => $phoneBankActivityTypeID,
      'max_number_of_contacts' => 12,
      'instructions' => "Call people, ask for money",
    );
    parent::setUp();
  }

  /**
   * Test create function succeeds.
   */
  public function testCreateSurvey() {
    $result = $this->callAPIAndDocument('survey', 'create', $this->params, __FUNCTION__, __FILE__);
    $this->getAndCheck($this->params, $result['id'], $this->entity);
  }

  /**
   * Test get function succeeds.
   *
   * This is actually largely tested in the get
   * action on create. Add extra checks for any 'special' return values or
   * behaviours
   */
  public function testGetSurvey() {
    $this->createTestEntity();
    $result = $this->callAPIAndDocument('survey', 'get', $this->params, __FUNCTION__, __FILE__);
    $this->assertEquals(1, $result['count']);
    $this->assertNotNull($result['values'][$result['id']]['id']);
  }

  /**
   * Check the delete function succeeds.
   */
  public function testDeleteSurvey() {
    $entity = $this->createTestEntity();
    $result = $this->callAPIAndDocument('survey', 'delete', array('id' => $entity['id']), __FUNCTION__, __FILE__);
    $checkDeleted = $this->callAPISuccess($this->entity, 'get', array());
    $this->assertEquals(0, $checkDeleted['count']);
  }

  /**
   * Test & document chained delete pattern.
   *
   * Note that explanation of the pattern
   * is best put in the $description variable as it will then be displayed in the
   * test generated examples. (these are to be found in the api/examples folder).
   */
  public function testGetSurveyChainDelete() {
    $description = "Demonstrates get + delete in the same call.";
    $subfile = 'ChainedGetDelete';
    $params = array(
      'title' => "survey title",
      'api.survey.delete' => 1,
    );
    $result = $this->callAPISuccess('survey', 'create', $this->params);
    $result = $this->callAPIAndDocument('survey', 'get', $params, __FUNCTION__, __FILE__, $description, $subfile);
    $this->assertEquals(0, $this->callAPISuccess('survey', 'getcount', array()));
  }

}
