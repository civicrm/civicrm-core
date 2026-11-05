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
 * @group headless
 */
class CRM_Mailing_BAO_SMSJobTest extends CiviUnitTestCase {

  public function setUp(): void {
    parent::setUp();
    $this->setupForSmsTests();
  }

  public function tearDown(): void {
    $this->setupForSmsTests(TRUE);
    $this->quickCleanup([
      'civicrm_mailing',
      'civicrm_mailing_job',
      'civicrm_mailing_group',
      'civicrm_mailing_recipients',
      'civicrm_mailing_event_queue',
      'civicrm_mailing_event_delivered',
      'civicrm_sms_provider',
      'civicrm_group',
      'civicrm_group_contact',
      'civicrm_phone',
      'civicrm_activity',
      'civicrm_activity_contact',
      'civicrm_contact',
    ]);
    parent::tearDown();
  }

  /**
   * A provider error for one recipient does not stop delivery to the others.
   */
  public function testProviderErrorDoesNotAbortDelivery(): void {
    $providerID = $this->callAPISuccess('SmsProvider', 'create', [
      'name' => 'CiviTestSMSProvider',
      'api_type' => 1,
      'username' => 1,
      'password' => 1,
      'api_url' => 1,
      'api_params' => 'a=1',
      'is_default' => 1,
      'is_active' => 1,
      'domain_id' => 1,
    ])['id'];
    $groupID = $this->groupCreate();
    $contactIDs = [];
    foreach (['5550001', '5550002'] as $phone) {
      $contactID = $this->individualCreate([], count($contactIDs));
      $this->callAPISuccess('Phone', 'create', [
        'contact_id' => $contactID,
        'phone' => $phone,
        'phone_type_id' => 'Mobile',
        'is_primary' => 1,
      ]);
      $this->callAPISuccess('GroupContact', 'create', ['group_id' => $groupID, 'contact_id' => $contactID]);
      $contactIDs[] = $contactID;
    }
    $mailingID = $this->callAPISuccess('Mailing', 'create', [
      'name' => 'SMS mailing',
      'subject' => 'SMS mailing',
      'body_text' => 'Hello world',
      'sms_provider_id' => $providerID,
      'groups' => ['include' => [$groupID]],
      'scheduled_id' => $this->individualCreate([], 2),
      'scheduled_date' => 'now',
    ])['id'];
    // The test provider records each message as an activity, so this stands in for a failing provider.
    $failingContactID = $contactIDs[0];
    $this->hookClass->setHook('civicrm_pre', function ($op, $objectName, $id, $params) use ($failingContactID) {
      if ($op === 'create' && $objectName === 'Activity' && ($params['target_contact_id'] ?? NULL) == $failingContactID) {
        throw new RuntimeException('Provider unreachable');
      }
    });
    $this->callAPISuccess('Job', 'process_sms', []);
    $this->assertDBQuery(1, 'SELECT COUNT(*) FROM civicrm_mailing_event_delivered d
      INNER JOIN civicrm_mailing_event_queue q ON q.id = d.event_queue_id
      INNER JOIN civicrm_mailing_job j ON j.id = q.job_id
      WHERE j.mailing_id = %1', [1 => [$mailingID, 'Integer']]);
  }

}
