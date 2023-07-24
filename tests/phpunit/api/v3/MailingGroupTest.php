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

  public function setUp(): void {
    parent::setUp();
    $this->useTransaction();
  }

  /**
   * Test civicrm_mailing_group_event_subscribe with given contact ID.
   */
  public function testMailerGroupSubscribeGivenContactID(): void {
    $email = 'test@example.org';
    $params = [
      'first_name' => 'Test',
      'last_name' => 'Test',
      'email' => $email,
      'contact_type' => 'Individual',
    ];
    $contactID = $this->individualCreate($params);

    $params = [
      'email' => $email,
      'group_id' => $this->groupCreate(),
      'contact_id' => $contactID,
      'hash' => 'b15de8b64e2cec34',
      'time_stamp' => '20101212121212',
    ];
    $result = $this->callAPISuccess('MailingEventSubscribe', 'create', $params);
    $this->assertEquals($result['values'][$result['id']]['contact_id'], $contactID);
  }

  /**
   * Test civicrm_mailing_group_event_unsubscribe with wrong params.
   */
  public function testMailerGroupUnsubscribeWrongParams(): void {
    $this->callAPIFailure('MailingEventUnsubscribe', 'create', [
      'job_id' => 'Wrong ID',
      'event_queue_id' => 'Wrong ID',
      'hash' => 'Wrong Hash',
      'time_stamp' => '20101212121212',
    ]);
  }

  /**
   * Test civicrm_mailing_group_event_domain_unsubscribe with wrong params.
   */
  public function testMailerGroupDomainUnsubscribeWrongParams(): void {
    $this->callAPIFailure('MailingEventUnsubscribe', 'create', [
      'job_id' => 'Wrong ID',
      'event_queue_id' => 'Wrong ID',
      'hash' => 'Wrong Hash',
      'org_unsubscribe' => 1,
      'time_stamp' => '20101212121212',
    ]);
  }

  /**
   * Test civicrm_mailing_group_event_resubscribe with wrong params.
   */
  public function testMailerGroupResubscribeWrongParams(): void {
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
   * Test civicrm_mailing_group_event_subscribe and
   * civicrm_mailing_event_confirm functions - success expected.
   *
   * @throws \CRM_Core_Exception
   */
  public function testMailerProcess(): void {
    $email = 'test@example.org';
    $this->callAPISuccess('MailSettings', 'create', [
      'domain_id' => 1,
      'name' => 'my mail setting',
      'domain' => 'setting.com',
      'localpart' => 'civicrm+',
      'server' => 'localhost',
      'username' => 'sue',
      'password' => 'pass',
      'is_default' => 1,
    ]);
    $mut = new CiviMailUtils($this, TRUE);
    Civi::settings()->set('include_message_id', 1);
    $params = [
      'first_name' => 'Test',
      'last_name' => 'Test',
      'email' => $email,
      'contact_type' => 'Individual',
    ];
    $contactID = $this->individualCreate($params);

    $params = [
      'email' => $email,
      'group_id' => $this->groupCreate(),
      'contact_id' => $contactID,
      'hash' => 'b15de8b64e2cec34',
      'time_stamp' => '20101212121212',
    ];
    $result = $this->callAPISuccess('mailing_event_subscribe', 'create', $params);
    // Check that subscription email has been sent.
    $msgs = $mut->getAllMessages();
    $this->assertCount(1, $msgs, 'Subscription email failed to send');
    $mut->checkMailLog([
      'Message-ID: <civicrm+s',
      'To confirm this subscription, reply to this email or click',
    ]);

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
    Civi::settings()->set('include_message_id', 0);
  }

}
