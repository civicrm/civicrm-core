<?php

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

  public function setUp() {
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
    ])['id'];
  }

  public function tearDown() {
    CRM_Utils_File::cleanDir(__DIR__ . '/data/mail');
    $this->callAPISuccess('MailSettings', 'delete', [
      'id' => $this->mailSettingsId,
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
  public function testFetchActivitiesWithManyAttachments() {
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

}
