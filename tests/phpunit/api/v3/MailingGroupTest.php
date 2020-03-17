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
 *  Test APIv3 civicrm_mailing_group_* functions
 *
 * @package   CiviCRM
 * @group headless
 */
class api_v3_MailingGroupTest extends CiviUnitTestCase {
  protected $_groupID;
  protected $_email;
  protected $_apiversion;

  public function setUp() {
    parent::setUp();
    $this->useTransaction(TRUE);
    $this->_apiversion = 3;
    $this->_groupID = $this->groupCreate();
    $this->_email = 'test@test.test';
  }

  /**
   * Test civicrm_mailing_group_event_subscribe with wrong params.
   */
  public function testMailerGroupSubscribeWrongParams() {
    $params = [
      'email' => $this->_email,
      'group_id' => 'Wrong Group ID',
      'contact_id' => '2121',
      'time_stamp' => '20111111010101',
      'hash' => 'sasa',
    ];
    $this->callAPIFailure('mailing_event_subscribe', 'create', $params);
  }

  /**
   * Test civicrm_mailing_group_event_subscribe with given contact ID.
   */
  public function testMailerGroupSubscribeGivenContactId() {
    $params = [
      'first_name' => 'Test',
      'last_name' => 'Test',
      'email' => $this->_email,
      'contact_type' => 'Individual',
    ];
    $contactID = $this->individualCreate($params);

    $params = [
      'email' => $this->_email,
      'group_id' => $this->_groupID,
      'contact_id' => $contactID,
      'hash' => 'b15de8b64e2cec34',
      'time_stamp' => '20101212121212',
    ];
    $result = $this->callAPIAndDocument('mailing_event_subscribe', 'create', $params, __FUNCTION__, __FILE__);
    $this->assertEquals($result['values'][$result['id']]['contact_id'], $contactID);

    $this->contactDelete($contactID);
  }

  /**
   * Test civicrm_mailing_group_event_unsubscribe with wrong params.
   */
  public function testMailerGroupUnsubscribeWrongParams() {
    $params = [
      'job_id' => 'Wrong ID',
      'event_queue_id' => 'Wrong ID',
      'hash' => 'Wrong Hash',
      'time_stamp' => '20101212121212',
    ];

    $this->callAPIFailure('mailing_event_unsubscribe', 'create', $params);
  }

  /**
   * Test civicrm_mailing_group_event_domain_unsubscribe with wrong params.
   */
  public function testMailerGroupDomainUnsubscribeWrongParams() {
    $params = [
      'job_id' => 'Wrong ID',
      'event_queue_id' => 'Wrong ID',
      'hash' => 'Wrong Hash',
      'org_unsubscribe' => 1,
      'time_stamp' => '20101212121212',
    ];

    $this->callAPIFailure('mailing_event_unsubscribe', 'create', $params);
  }

  /**
   * Test civicrm_mailing_group_event_resubscribe with wrong params type.
   */

  /**
   * Test civicrm_mailing_group_event_resubscribe with wrong params.
   */
  public function testMailerGroupResubscribeWrongParams() {
    $params = [
      'job_id' => 'Wrong ID',
      'event_queue_id' => 'Wrong ID',
      'hash' => 'Wrong Hash',
      'org_unsubscribe' => 'test',
      'time_stamp' => '20101212121212',
    ];
    $this->callAPIFailure('mailing_event_resubscribe', 'create', $params);
  }

  /**
   * Test civicrm_mailing_group_event_subscribe and civicrm_mailing_event_confirm functions - success expected.
   */
  public function testMailerProcess() {
    $params = [
      'first_name' => 'Test',
      'last_name' => 'Test',
      'email' => $this->_email,
      'contact_type' => 'Individual',
    ];
    $contactID = $this->individualCreate($params);

    $params = [
      'email' => $this->_email,
      'group_id' => $this->_groupID,
      'contact_id' => $contactID,
      'hash' => 'b15de8b64e2cec34',
      'time_stamp' => '20101212121212',
    ];
    $result = $this->callAPISuccess('mailing_event_subscribe', 'create', $params);

    $this->assertEquals($result['values'][$result['id']]['contact_id'], $contactID);

    $params = [
      'contact_id' => $result['values'][$result['id']]['contact_id'],
      'subscribe_id' => $result['values'][$result['id']]['subscribe_id'],
      'hash' => $result['values'][$result['id']]['hash'],
      'time_stamp' => '20101212121212',
      'event_subscribe_id' => $result['values'][$result['id']]['subscribe_id'],
    ];

    $this->callAPISuccess('mailing_event_confirm', 'create', $params);
    $this->contactDelete($contactID);
  }

}
