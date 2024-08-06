<?php

use Civi\Api4\ActivityContact;

/**
 * Class CRM_Utils_Mail_EmailProcessorInboundTest
 * @group headless
 */
class CRM_Utils_Mail_EmailProcessorInboundTest extends CiviUnitTestCase {

  /**
   * MailSettings record id.
   *
   * @var int
   */
  protected $mailSettingsId;

  public function setUp(): void {
    parent::setUp();
    CRM_Utils_File::cleanDir(__DIR__ . '/data/mail');
    mkdir(__DIR__ . '/data/mail');
    // Note this is configured for Inbound Email Processing (not bounces)
    // but otherwise is the same as bounces.
    $this->mailSettingsId = $this->callAPISuccess('MailSettings', 'create', [
      'name' => 'local',
      'protocol' => 'Localdir',
      'source' => __DIR__ . '/data/mail',
      'domain' => 'example.com',
      // a little weird - is_default=0 means for inbound email processing
      'is_default' => '0',
      'domain_id' => 1,
      'is_active' => 1,
      'activity_type_id' => 'Inbound Email',
      'activity_source' => 'from',
      'activity_targets' => 'to,cc,bcc',
      'activity_assignees' => 'from',
    ])['id'];
    $this->callAPISuccess('Tag', 'create', ['name' => 'FileToOrgAlways']);
    $this->callAPISuccess('Tag', 'create', ['name' => 'FileToOrgCatchallForDomain']);
  }

  public function tearDown(): void {
    CRM_Utils_File::cleanDir(__DIR__ . '/data/mail');
    $this->callAPISuccess('MailSettings', 'delete', [
      'id' => $this->mailSettingsId,
    ]);
    $this->quickCleanup([
      'civicrm_file',
      'civicrm_entity_file',
      'civicrm_activity',
      'civicrm_activity_contact',
      'civicrm_entity_tag',
      'civicrm_tag',
      'civicrm_email',
      'civicrm_contact',
    ]);
    parent::tearDown();
  }

  /**
   * Fetch activities with many attachments
   *
   * In particular the default limit for the UI is 3, which is how this came up
   * because it was also being used as a limit for backend processes. So we
   * test 4, which is bigger than 3 (unless running on a 2-bit CPU).
   */
  public function testFetchActivitiesWithManyAttachments(): void {
    $mail = 'test_message_many_attachments.eml';

    // paranoid check that settings are the standard defaults
    $currentUIMax = Civi::settings()->get('max_attachments');
    $currentBackendMax = Civi::settings()->get('max_attachments_backend');
    if ($currentUIMax > 3) {
      Civi::settings()->set('max_attachments', 3);
    }
    if ($currentBackendMax < CRM_Core_BAO_File::DEFAULT_MAX_ATTACHMENTS_BACKEND) {
      Civi::settings()->set('max_attachments_backend', CRM_Core_BAO_File::DEFAULT_MAX_ATTACHMENTS_BACKEND);
    }

    // create some contacts
    $senderContactId = $this->individualCreate([], 1);
    $senderContact = $this->callAPISuccess('Contact', 'getsingle', [
      'id' => $senderContactId,
    ]);
    $recipientContactId = $this->individualCreate([], 2);
    $recipientContact = $this->callAPISuccess('Contact', 'getsingle', [
      'id' => $recipientContactId,
    ]);

    $templateFillData = [
      'the_date' => date('r'),
      'from_name' => $senderContact['display_name'],
      'from_email' => $senderContact['email'],
      'to_email' => $recipientContact['email'],
    ];

    // Retrieve the template and insert our data like current dates
    $file_contents = file_get_contents(__DIR__ . '/data/inbound/' . $mail);
    foreach ($templateFillData as $field => $value) {
      $file_contents = str_replace("%%{$field}%%", $value, $file_contents);
    }
    // put it in the mail dir
    file_put_contents(__DIR__ . '/data/mail/' . $mail, $file_contents);

    // run the job
    $this->callAPISuccess('job', 'fetch_activities', []);

    // check that file was removed from mail dir
    $this->assertFalse(file_exists(__DIR__ . '/data/mail/' . $mail));

    // get the filed activity, by sender contact id
    $activities = $this->callAPISuccess('Activity', 'get', [
      'source_contact_id' => $senderContact['id'],
    ]);
    $this->assertEquals(1, $activities['count']);

    // check subject
    $activity = $activities['values'][$activities['id']];
    $this->assertEquals('Testing 4 attachments', $activity['subject']);

    // Check target is our recipient
    $targets = $this->callAPISuccess('ActivityContact', 'get', [
      'activity_id' => $activity['id'],
      'record_type_id' => 'Activity Targets',
    ]);
    $this->assertEquals($recipientContact['id'], $targets['values'][$targets['id']]['contact_id']);

    // Check we have 4 attachments
    $attachments = $this->callAPISuccess('Attachment', 'get', [
      'entity_id' => $activity['id'],
      'entity_table' => 'civicrm_activity',
    ]);
    $this->assertEquals(4, $attachments['count']);

    // reset in case it was different from defaults
    Civi::settings()->set('max_attachments', $currentUIMax);
    Civi::settings()->set('max_attachments_backend', $currentBackendMax);
  }

  /**
   * hook implementation for testHookEmailProcessor
   */
  public function hookImplForEmailProcessor($type, &$params, $mail, &$result, $action = NULL, ?int $mailSettingId = NULL) {
    $this->assertEquals($this->mailSettingsId, $mailSettingId);
    if ($type !== 'activity') {
      return;
    }
    // change the activity type depending on subject
    if (strpos($mail->subject, 'for hooks') !== FALSE) {
      $this->callAPISuccess('Activity', 'create', ['id' => $result['id'], 'activity_type_id' => 'Phone Call']);
    }
  }

  /**
   * Test messed up from.
   *
   * This ensures fix for https://issues.civicrm.org/jira/browse/CRM-19215.
   *
   * @throws \CRM_Core_Exception
   */
  public function testBadFrom() :void {
    $email = file_get_contents(__DIR__ . '/data/inbound/test_broken_from.eml');
    $badEmails = [
      'foo@example.com' => 'foo@example.com (foo)',
      "KO'Bananas@benders.com" => "KO'Bananas@benders.com",
    ];
    foreach ($badEmails as $index => $badEmail) {
      $file = fopen(__DIR__ . '/data/mail/test_broken_from.eml' . $index, 'wb');
      fwrite($file, str_replace('bad-email-placeholder', $badEmail, $email));
      fclose($file);
    }

    $this->callAPISuccess('Job', 'fetch_activities', []);
    $activities = ActivityContact::get()
      ->addSelect('contact_id.email_primary.email', 'activity_id.activity_type_id:name', 'activity_id.subject')
      ->addWhere('contact_id.email_primary.email', 'IN', array_keys($badEmails))
      ->addWhere('record_type_id:name', '=', 'Activity Source')
      ->execute();
    $this->assertCount(2, $activities);
  }

  /**
   * test hook_civicrm_emailProcessor
   */
  public function testHookEmailProcessor(): void {
    $this->hookClass->setHook('civicrm_emailProcessor', [$this, 'hookImplForEmailProcessor']);

    copy(__DIR__ . '/data/inbound/test_hook.eml', __DIR__ . '/data/mail/test_hook.eml');
    $this->callAPISuccess('Job', 'fetch_activities', []);

    // The activity type should be changed.
    $activity = $this->callAPISuccess('Activity', 'getsingle', ['subject' => 'This is for hooks']);
    $this->assertEquals(
      CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'Phone Call'),
      $activity['activity_type_id']
    );

    // Now repeat with a different subject, and the type should be the default.
    copy(__DIR__ . '/data/inbound/test_non_cases_email.eml', __DIR__ . '/data/mail/test_non_cases_email.eml');
    $this->callAPISuccess('Job', 'fetch_activities', []);
    $activity = $this->callAPISuccess('Activity', 'getsingle', ['subject' => 'Love letter']);
    $this->assertEquals(
      CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'Inbound Email'),
      $activity['activity_type_id']
    );
  }

  /**
   * This hook is the example from the docs, which at one time was
   * being used in production.
   * If an org is specially tagged and has the same domain, then file
   * on that org, otherwise if an individual had matched already, then use
   * that, otherwise if there is an org specially tagged
   * as a "catchall" for the domain, then use that.
   * The use case is where you get emails from billing@acme.com, sales@acme.com,
   * info@acme.com, do-not-reply@acme.com, the
   * account-rep-of-the-week@acme.com, etc and these are really all the same
   * org contact as far as you are concerned. However for some orgs you want it
   * all filed on one contact no matter what, and for others you only want that
   * as a fallback if it doesn't match an individual first.
   */
  public function hookImplForEmailProcessorContact($email, $contactID, &$result) {
    [$mailName, $mailDomain] = CRM_Utils_System::explode('@', $email, 2);
    if (empty($mailDomain)) {
      return;
    }
    $org = \Civi\Api4\Contact::get(FALSE)
      ->addWhere('email_primary.email', 'LIKE', '%@' . $mailDomain)
      ->addWhere('tags:name', 'IN', ['FileToOrgAlways'])
      ->addWhere('contact_type:name', '=', 'Organization')
      ->execute()->first();
    if ($org['id'] ?? NULL) {
      $result = ['contactID' => $org['id'], 'action' => CRM_Utils_Mail_Incoming::EMAILPROCESSOR_OVERRIDE];
      return;
    }
    if ($contactID) {
      return;
    }
    $org = \Civi\Api4\Contact::get(FALSE)
      ->addWhere('email_primary.email', 'LIKE', '%@' . $mailDomain)
      ->addWhere('tags:name', 'IN', ['FileToOrgCatchallForDomain'])
      ->addWhere('contact_type:name', '=', 'Organization')
      ->execute()->first();
    if ($org['id'] ?? NULL) {
      $result = ['contactID' => $org['id'], 'action' => CRM_Utils_Mail_Incoming::EMAILPROCESSOR_OVERRIDE];
      return;
    }
    $result = ['action' => CRM_Utils_Mail_Incoming::EMAILPROCESSOR_CREATE_INDIVIDUAL];
  }

  /**
   * test hook_civicrm_emailProcessorContact with catchall
   */
  public function testHookEmailProcessorContactCatchall(): void {
    $this->hookClass->setHook('civicrm_emailProcessorContact', [$this, 'hookImplForEmailProcessorContact']);

    // org with same domain as data fixture's email
    $catchall_id = $this->organizationCreate(['organization_name' => 'Paradox Unlimited Ltd.', 'email' => 'info@paradox.biz']);
    $tag = $this->callAPISuccess('Tag', 'getsingle', ['name' => 'FileToOrgCatchallForDomain', 'return' => ['id']]);
    $this->callAPISuccess('EntityTag', 'create', ['entity_table' => 'civicrm_contact', 'entity_id' => $catchall_id, 'tag_id' => $tag['id']]);

    copy(__DIR__ . '/data/inbound/test_hook.eml', __DIR__ . '/data/mail/test_hook.eml');
    $this->callAPISuccess('Job', 'fetch_activities', []);

    // The source contact should be our catchall not the original From contact.
    $activity = $this->callAPISuccess('Activity', 'getsingle', ['source_contact_id' => $catchall_id]);
    $this->assertEquals('This is for hooks', $activity['subject']);
  }

  /**
   * test hook_civicrm_emailProcessorContact with catchall but matching individual
   */
  public function testHookEmailProcessorContactCatchallWithMatch(): void {
    $this->hookClass->setHook('civicrm_emailProcessorContact', [$this, 'hookImplForEmailProcessorContact']);

    // org with same domain as data fixture's email
    $catchall_id = $this->organizationCreate(['organization_name' => 'Paradox Unlimited Ltd.', 'email' => 'info@paradox.biz']);
    $tag = $this->callAPISuccess('Tag', 'getsingle', ['name' => 'FileToOrgCatchallForDomain', 'return' => ['id']]);
    $this->callAPISuccess('EntityTag', 'create', ['entity_table' => 'civicrm_contact', 'entity_id' => $catchall_id, 'tag_id' => $tag['id']]);

    // individual with same email as the data fixture's email.
    $contact_id = $this->individualCreate(['email' => 'billing@paradox.biz']);

    copy(__DIR__ . '/data/inbound/test_hook.eml', __DIR__ . '/data/mail/test_hook.eml');
    $this->callAPISuccess('Job', 'fetch_activities', []);

    // The source contact should be our individual not the catchall
    $activity = $this->callAPISuccess('Activity', 'getsingle', ['source_contact_id' => $contact_id]);
    $this->assertEquals('This is for hooks', $activity['subject']);
  }

  /**
   * test hook_civicrm_emailProcessorContact with Always and matching individual
   *
   * The difference from testHookEmailProcessorContactCatchallWithMatch is
   * here the presence of the matching individual is irrelevant - it will always
   * file on the org.
   */
  public function testHookEmailProcessorContactAlwaysWithMatch(): void {
    $this->hookClass->setHook('civicrm_emailProcessorContact', [$this, 'hookImplForEmailProcessorContact']);

    // org with same domain as data fixture's email
    $catchall_id = $this->organizationCreate(['organization_name' => 'Paradox Unlimited Ltd.', 'email' => 'info@paradox.biz']);
    $tag = $this->callAPISuccess('Tag', 'getsingle', ['name' => 'FileToOrgAlways', 'return' => ['id']]);
    $this->callAPISuccess('EntityTag', 'create', ['entity_table' => 'civicrm_contact', 'entity_id' => $catchall_id, 'tag_id' => $tag['id']]);

    // individual with same email as the data fixture's email.
    $contact_id = $this->individualCreate(['email' => 'billing@paradox.biz']);

    copy(__DIR__ . '/data/inbound/test_hook.eml', __DIR__ . '/data/mail/test_hook.eml');
    $this->callAPISuccess('Job', 'fetch_activities', []);

    // The source contact should be the org not the individual
    $activity = $this->callAPISuccess('Activity', 'getsingle', ['source_contact_id' => $catchall_id]);
    $this->assertEquals('This is for hooks', $activity['subject']);
  }

}
