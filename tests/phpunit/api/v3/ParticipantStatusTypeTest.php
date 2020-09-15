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
 * Class api_v3_ParticipantStatusTypeTest
 * @group headless
 */
class api_v3_ParticipantStatusTypeTest extends CiviUnitTestCase {
  protected $_apiversion;
  protected $params;
  protected $id;

  public $DBResetRequired = FALSE;

  public function setUp() {
    $this->_apiversion = 3;
    $this->params = [
      'name' => 'test status',
      'label' => 'I am a test',
      'class' => 'Positive',
      'is_reserved' => 0,
      'is_active' => 1,
      'is_counted' => 1,
      'visibility_id' => 1,
      'weight' => 10,
    ];
    parent::setUp();
    $this->useTransaction(TRUE);
  }

  public function testCreateParticipantStatusType() {
    $result = $this->callAPIAndDocument('participant_status_type', 'create', $this->params, __FUNCTION__, __FILE__);
    $this->assertEquals(1, $result['count']);
    $this->assertNotNull($result['values'][$result['id']]['id']);
  }

  public function testGetParticipantStatusType() {
    $result = $this->callAPIAndDocument('participant_status_type', 'create', $this->params, __FUNCTION__, __FILE__);
    $this->assertEquals(1, $result['count']);

    $result = $this->callAPIAndDocument('participant_status_type', 'get', $this->params, __FUNCTION__, __FILE__);
    $this->assertEquals(1, $result['count']);
    $this->assertNotNull($result['values'][$result['id']]['id']);
    $this->id = $result['id'];
  }

  public function testDeleteParticipantStatusType() {

    $ParticipantStatusType = $this->callAPISuccess('ParticipantStatusType', 'Create', $this->params);
    $entity = $this->callAPISuccess('participant_status_type', 'get', []);
    $result = $this->callAPIAndDocument('participant_status_type', 'delete', ['id' => $ParticipantStatusType['id']], __FUNCTION__, __FILE__);
    $getCheck = $this->callAPISuccess('ParticipantStatusType', 'GET', ['id' => $ParticipantStatusType['id']]);
    $checkDeleted = $this->callAPISuccess('ParticipantStatusType', 'Get', []);
    $this->assertEquals($entity['count'] - 1, $checkDeleted['count']);
  }

}
