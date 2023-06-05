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

  public function setUp(): void {
    parent::setUp();
    $phoneBankActivityTypeID = $this->callAPISuccessGetValue('Option_value', [
      'label' => 'PhoneBank',
      'return' => 'value',
    ], 'integer');
    $this->useTransaction();
    $this->enableCiviCampaign();
    $this->params = [
      'title' => 'survey title',
      'activity_type_id' => $phoneBankActivityTypeID,
      'max_number_of_contacts' => 12,
      'instructions' => 'Call people, ask for money',
    ];
  }

  /**
   * Test create function succeeds.
   */
  public function testCreateSurvey(): void {
    $result = $this->callAPIAndDocument('Survey', 'create', $this->params, __FUNCTION__, __FILE__);
    $this->getAndCheck($this->params, $result['id'], $this->entity);
  }

  /**
   * Test get function succeeds.
   *
   * This is actually largely tested in the get
   * action on create. Add extra checks for any 'special' return values or
   * behaviours
   */
  public function testGetSurvey(): void {
    $this->createTestEntity('Survey', $this->params);
    $result = $this->callAPIAndDocument('Survey', 'get', $this->params, __FUNCTION__, __FILE__);
    $this->assertEquals(1, $result['count']);
    $this->assertNotNull($result['values'][$result['id']]['id']);
  }

  /**
   * Check the delete function succeeds.
   */
  public function testDeleteSurvey(): void {
    $entity = $this->createTestEntity('Survey', $this->params);
    $this->callAPIAndDocument('survey', 'delete', ['id' => $entity['id']], __FUNCTION__, __FILE__);
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
  public function testGetSurveyChainDelete(): void {
    $description = 'Demonstrates get + delete in the same call.';
    $params = [
      'title' => 'survey title',
      'api.survey.delete' => 1,
    ];
    $this->callAPISuccess('Survey', 'create', $this->params);
    $this->callAPIAndDocument('Survey', 'get', $params, __FUNCTION__, __FILE__, $description, 'ChainedGetDelete');
    $this->assertEquals(0, $this->callAPISuccess('survey', 'getcount', []));
  }

}
