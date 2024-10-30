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
    $result = $this->callAPISuccess('Survey', 'create', $this->params);
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
    $result = $this->callAPISuccess('Survey', 'get', $this->params);
    $this->assertEquals(1, $result['count']);
    $this->assertNotNull($result['values'][$result['id']]['id']);
  }

  /**
   * Check the delete function succeeds.
   */
  public function testDeleteSurvey(): void {
    $entity = $this->createTestEntity('Survey', $this->params);
    $this->callAPISuccess('survey', 'delete', ['id' => $entity['id']]);
    $checkDeleted = $this->callAPISuccess($this->entity, 'get', []);
    $this->assertEquals(0, $checkDeleted['count']);
  }

  /**
   * Test chained delete pattern.
   */
  public function testGetSurveyChainDelete(): void {
    $params = [
      'title' => 'survey title',
      'api.survey.delete' => 1,
    ];
    $this->callAPISuccess('Survey', 'create', $this->params);
    $this->callAPISuccess('Survey', 'get', $params);
    $this->assertEquals(0, $this->callAPISuccess('survey', 'getcount', []));
  }

}
