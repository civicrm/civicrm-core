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
    $phoneBankActivityTypeID = $this->callAPISuccessGetValue('Option_value', [
      'label' => 'PhoneBank',
      'return' => 'value',
    ], 'integer');
    $this->useTransaction();
    $this->enableCiviCampaign();
    $this->params = [
      'title' => "survey title",
      'activity_type_id' => $phoneBankActivityTypeID,
      'max_number_of_contacts' => 12,
      'instructions' => "Call people, ask for money",
    ];
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
    $result = $this->callAPIAndDocument('survey', 'delete', ['id' => $entity['id']], __FUNCTION__, __FILE__);
    $checkDeleted = $this->callAPISuccess($this->entity, 'get', []);
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
    $params = [
      'title' => "survey title",
      'api.survey.delete' => 1,
    ];
    $result = $this->callAPISuccess('survey', 'create', $this->params);
    $result = $this->callAPIAndDocument('survey', 'get', $params, __FUNCTION__, __FILE__, $description, $subfile);
    $this->assertEquals(0, $this->callAPISuccess('survey', 'getcount', []));
  }

}
