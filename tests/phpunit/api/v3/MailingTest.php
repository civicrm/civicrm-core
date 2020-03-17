<?php
/*
 *  File for the TestMailing class
 *
 *  (PHP 5)
 *
 *   @package   CiviCRM
 *
 *   This file is part of CiviCRM
 *
 *   CiviCRM is free software; you can redistribute it and/or
 *   modify it under the terms of the GNU Affero General Public License
 *   as published by the Free Software Foundation; either version 3 of
 *   the License, or (at your option) any later version.
 *
 *   CiviCRM is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU Affero General Public License for more details.
 *
 *   You should have received a copy of the GNU Affero General Public
 *   License along with this program.  If not, see
 *   <http://www.gnu.org/licenses/>.
 */


/**
 *  Test APIv3 civicrm_mailing_* functions
 *
 * @package   CiviCRM
 * @group headless
 */
class api_v3_MailingTest extends CiviUnitTestCase {
  protected $_groupID;
  protected $_email;
  protected $_apiversion = 3;
  protected $_params = [];
  protected $_entity = 'Mailing';
  protected $_contactID;

  /**
   * APIv3 result from creating an example footer
   * @var array
   */
  protected $footer;

  public function setUp() {
    parent::setUp();
    $this->useTransaction();
    // DGW
    CRM_Mailing_BAO_MailingJob::$mailsProcessed = 0;
    $this->_contactID = $this->individualCreate();
    $this->_groupID = $this->groupCreate();
    $this->_email = 'test@test.test';
    $this->_params = [
      'subject' => 'Hello {contact.display_name}',
      'body_text' => "This is {contact.display_name}.\nhttps://civicrm.org\n{domain.address}{action.optOutUrl}",
      'body_html' => "<link href='https://fonts.googleapis.com/css?family=Roboto+Condensed:400,700|Zilla+Slab:500,700' rel='stylesheet' type='text/css'><p><a href=\"http://{action.forward}\">Forward this email</a><a href=\"{action.forward}\">Forward this email with no protocol</a></p<p>This is {contact.display_name}.</p><p><a href='https://civicrm.org/'>CiviCRM.org</a></p><p>{domain.address}{action.optOutUrl}</p>",
      'name' => 'mailing name',
      'created_id' => $this->_contactID,
      'header_id' => '',
      'footer_id' => '',
    ];

    $this->footer = civicrm_api3('MailingComponent', 'create', [
      'name' => 'test domain footer',
      'component_type' => 'footer',
      'body_html' => '<p>From {domain.address}. To opt out, go to {action.optOutUrl}.</p>',
      'body_text' => 'From {domain.address}. To opt out, go to {action.optOutUrl}.',
    ]);
  }

  public function tearDown() {
    // DGW
    CRM_Mailing_BAO_MailingJob::$mailsProcessed = 0;
    parent::tearDown();
  }

  /**
   * Test civicrm_mailing_create.
   */
  public function testMailerCreateSuccess() {
    $result = $this->callAPIAndDocument('mailing', 'create', $this->_params + ['scheduled_date' => 'now'], __FUNCTION__, __FILE__);
    $jobs = $this->callAPISuccess('mailing_job', 'get', ['mailing_id' => $result['id']]);
    $this->assertEquals(1, $jobs['count']);
    // return isn't working on this in getAndCheck so lets not check it for now
    unset($this->_params['created_id']);
    $this->getAndCheck($this->_params, $result['id'], 'mailing');
  }

  /**
   * Tes that the parameter _skip_evil_bao_auto_schedule_ is respected & prevents jobs being created.
   */
  public function testSkipAutoSchedule() {
    $this->callAPISuccess('Mailing', 'create', array_merge($this->_params, [
      '_skip_evil_bao_auto_schedule_' => TRUE,
      'scheduled_date' => 'now',
    ]));
    $this->callAPISuccessGetCount('Mailing', [], 1);
    $this->callAPISuccessGetCount('MailingJob', [], 0);
  }

  /**
   * Create a completed mailing (e.g when importing from a provider).
   *
   * @throws \CRM_Core_Exception
   */
  public function testMailerCreateCompleted() {
    $this->_params['body_html'] = 'I am completed so it does not matter if there is an opt out link since I have already been sent by another system';
    $this->_params['is_completed'] = 1;
    $result = $this->callAPIAndDocument('mailing', 'create', $this->_params + ['scheduled_date' => 'now'], __FUNCTION__, __FILE__);
    $jobs = $this->callAPISuccess('mailing_job', 'get', ['mailing_id' => $result['id']]);
    $this->assertEquals(1, $jobs['count']);
    $this->assertEquals('Complete', $jobs['values'][$jobs['id']]['status']);
    // return isn't working on this in getAndCheck so lets not check it for now
    unset($this->_params['created_id']);
    $this->getAndCheck($this->_params, $result['id'], 'mailing');
  }

  /**
   * Per CRM-20316 the mailing should still create without created_id (not mandatory).
   */
  public function testMailerCreateSuccessNoCreatedID() {
    unset($this->_params['created_id']);
    $result = $this->callAPIAndDocument('mailing', 'create', $this->_params + ['scheduled_date' => 'now'], __FUNCTION__, __FILE__);
    $this->getAndCheck($this->_params, $result['id'], 'mailing');
  }

  /**
   *
   */
  public function testTemplateTypeOptions() {
    $types = $this->callAPISuccess('Mailing', 'getoptions', ['field' => 'template_type']);
    $this->assertTrue(isset($types['values']['traditional']));
  }

  /**
   * Check that default header+footer are available.
   */
  public function testHeaderFooterOptions() {
    $headers = $this->callAPISuccess('Mailing', 'getoptions', ['field' => 'header']);
    $this->assertTrue(in_array('Mailing Header', $headers['values']));
    $footers = $this->callAPISuccess('Mailing', 'getoptions', ['field' => 'footer']);
    $this->assertTrue(in_array('Mailing Footer', $footers['values']));
  }

  /**
   * The `template_options` field should be treated a JSON object.
   *
   * This test will create, read, and update the field.
   *
   * @throws \CRM_Core_Exception
   */
  public function testMailerCreateTemplateOptions() {
    // 1. Create mailing with template_options.
    $params = $this->_params;
    $params['template_options'] = json_encode(['foo' => 'bar_1']);
    $createResult = $this->callAPISuccess('mailing', 'create', $params);
    $id = $createResult['id'];
    $this->assertDBQuery('{"foo":"bar_1"}', 'SELECT template_options FROM civicrm_mailing WHERE id = %1', [
      1 => [$id, 'Int'],
    ]);
    $this->assertEquals('bar_1', $createResult['values'][$id]['template_options']['foo']);

    // 2. Get mailing with template_options.
    $getResult = $this->callAPISuccess('mailing', 'get', [
      'id' => $id,
    ]);
    $this->assertEquals('bar_1', $getResult['values'][$id]['template_options']['foo']);
    $getValueResult = $this->callAPISuccess('mailing', 'getvalue', [
      'id' => $id,
      'return' => 'template_options',
    ]);
    $this->assertEquals('bar_1', $getValueResult['foo']);

    // 3. Update mailing with template_options.
    $updateResult = $this->callAPISuccess('mailing', 'create', [
      'id' => $id,
      'template_options' => ['foo' => 'bar_2'],
    ]);
    $this->assertDBQuery('{"foo":"bar_2"}', 'SELECT template_options FROM civicrm_mailing WHERE id = %1', [
      1 => [$id, 'Int'],
    ]);
    $this->assertEquals('bar_2', $updateResult['values'][$id]['template_options']['foo']);
  }

  /**
   * The Mailing.create API supports magic properties "groups[include,enclude]" and "mailings[include,exclude]".
   * Make sure these work
   *
   * @throws \CRM_Core_Exception
   */
  public function testMagicGroups_create_update() {
    // BEGIN SAMPLE DATA
    $groupIDs['a'] = $this->groupCreate(['name' => 'Example include group', 'title' => 'Example include group']);
    $groupIDs['b'] = $this->groupCreate(['name' => 'Example exclude group', 'title' => 'Example exclude group']);
    $contactIDs['a'] = $this->individualCreate([
      'email' => 'include.me@example.org',
      'first_name' => 'Includer',
      'last_name' => 'Person',
    ]);
    $contactIDs['b'] = $this->individualCreate([
      'email' => 'exclude.me@example.org',
      'last_name' => 'Excluder',
    ]);
    $this->callAPISuccess('GroupContact', 'create', [
      'group_id' => $groupIDs['a'],
      'contact_id' => $contactIDs['a'],
    ]);
    $this->callAPISuccess('GroupContact', 'create', [
      'group_id' => $groupIDs['b'],
      'contact_id' => $contactIDs['b'],
    ]);
    // END SAMPLE DATA

    // ** Pass 1: Create
    $createParams = $this->_params;
    $createParams['groups']['include'] = [$groupIDs['a']];
    $createParams['groups']['exclude'] = [];
    $createParams['mailings']['include'] = [];
    $createParams['mailings']['exclude'] = [];
    $createParams['scheduled_date'] = 'now';
    $createResult = $this->callAPISuccess('Mailing', 'create', $createParams);
    $getGroup1 = $this->callAPISuccess('MailingGroup', 'get', ['mailing_id' => $createResult['id']]);
    $getGroup1_ids = array_values(CRM_Utils_Array::collect('entity_id', $getGroup1['values']));
    $this->assertEquals([$groupIDs['a']], $getGroup1_ids);
    $getRecipient1 = $this->callAPISuccess('MailingRecipients', 'get', ['mailing_id' => $createResult['id']]);
    $getRecipient1_ids = array_values(CRM_Utils_Array::collect('contact_id', $getRecipient1['values']));
    $this->assertEquals([$contactIDs['a']], $getRecipient1_ids);

    // ** Pass 2: Update without any changes to groups[include]
    $nullOpParams = $createParams;
    $nullOpParams['id'] = $createResult['id'];
    $updateParams['api.mailing_job.create'] = 1;
    unset($nullOpParams['groups']['include']);
    $this->callAPISuccess('Mailing', 'create', $nullOpParams);
    $getGroup2 = $this->callAPISuccess('MailingGroup', 'get', ['mailing_id' => $createResult['id']]);
    $getGroup2_ids = array_values(CRM_Utils_Array::collect('entity_id', $getGroup2['values']));
    $this->assertEquals([$groupIDs['a']], $getGroup2_ids);
    $getRecipient2 = $this->callAPISuccess('MailingRecipients', 'get', ['mailing_id' => $createResult['id']]);
    $getRecip2_ids = array_values(CRM_Utils_Array::collect('contact_id', $getRecipient2['values']));
    $this->assertEquals([$contactIDs['a']], $getRecip2_ids);

    // ** Pass 3: Update with different groups[include]
    $updateParams = $createParams;
    $updateParams['id'] = $createResult['id'];
    $updateParams['groups']['include'] = [$groupIDs['b']];
    $updateParams['scheduled_date'] = 'now';
    $this->callAPISuccess('Mailing', 'create', $updateParams);
    $getGroup3 = $this->callAPISuccess('MailingGroup', 'get', ['mailing_id' => $createResult['id']]);
    $getGroup3_ids = array_values(CRM_Utils_Array::collect('entity_id', $getGroup3['values']));
    $this->assertEquals([$groupIDs['b']], $getGroup3_ids);
    $getRecipient3 = $this->callAPISuccess('MailingRecipients', 'get', ['mailing_id' => $createResult['id']]);
    $getRecipient3_ids = array_values(CRM_Utils_Array::collect('contact_id', $getRecipient3['values']));
    $this->assertEquals([$contactIDs['b']], $getRecipient3_ids);
  }

  public function testMailerPreview() {
    // BEGIN SAMPLE DATA
    $contactID = $this->individualCreate();
    $displayName = $this->callAPISuccess('contact', 'get', ['id' => $contactID]);
    $displayName = $displayName['values'][$contactID]['display_name'];
    $this->assertTrue(!empty($displayName));

    $params = $this->_params;
    $params['api.Mailing.preview'] = [
      'id' => '$value.id',
      'contact_id' => $contactID,
    ];
    $params['options']['force_rollback'] = 1;
    // END SAMPLE DATA

    $maxIDs = [
      'mailing' => CRM_Core_DAO::singleValueQuery('SELECT MAX(id) FROM civicrm_mailing'),
      'job' => CRM_Core_DAO::singleValueQuery('SELECT MAX(id) FROM civicrm_mailing_job'),
      'group' => CRM_Core_DAO::singleValueQuery('SELECT MAX(id) FROM civicrm_mailing_group'),
      'recipient' => CRM_Core_DAO::singleValueQuery('SELECT MAX(id) FROM civicrm_mailing_recipients'),
    ];
    $result = $this->callAPISuccess('mailing', 'create', $params);
    // 'Preview should not create any mailing records'
    $this->assertDBQuery($maxIDs['mailing'], 'SELECT MAX(id) FROM civicrm_mailing');
    // 'Preview should not create any mailing_job record'
    $this->assertDBQuery($maxIDs['job'], 'SELECT MAX(id) FROM civicrm_mailing_job');
    // 'Preview should not create any mailing_group records'
    $this->assertDBQuery($maxIDs['group'], 'SELECT MAX(id) FROM civicrm_mailing_group');
    // 'Preview should not create any mailing_recipient records'
    $this->assertDBQuery($maxIDs['recipient'], 'SELECT MAX(id) FROM civicrm_mailing_recipients');
    $baseurl = CRM_Utils_System::baseCMSURL();
    $previewResult = $result['values'][$result['id']]['api.Mailing.preview'];
    $this->assertEquals("Hello $displayName", $previewResult['values']['subject']);
    $this->assertContains("This is $displayName", $previewResult['values']['body_text']);
    $this->assertContains("<p>This is $displayName.</p>", $previewResult['values']['body_html']);
    $this->assertContains('<a href="' . $baseurl . 'index.php?q=civicrm/mailing/forward&amp;amp;reset=1&amp;jid=&amp;qid=&amp;h=">Forward this email with no protocol</a>', $previewResult['values']['body_html']);
    $this->assertNotContains("http://http://", $previewResult['values']['body_html']);
  }

  public function testMailerPreviewUnknownContact() {
    $params = $this->_params;
    $params['api.Mailing.preview'] = [
      'id' => '$value.id',
    ];

    $result = $this->callAPISuccess('mailing', 'create', $params);

    // NOTE: It's highly debatable what's best to do with contact-tokens for an
    // unknown-contact. However, changes should be purposeful, so we'll test
    // for the current behavior (i.e. returning blanks).
    $previewResult = $result['values'][$result['id']]['api.Mailing.preview'];
    $this->assertEquals("Hello ", $previewResult['values']['subject']);
    $this->assertContains("This is .", $previewResult['values']['body_text']);
    $this->assertContains("<p>This is .</p>", $previewResult['values']['body_html']);
  }

  public function testMailerPreviewRecipients() {
    // BEGIN SAMPLE DATA
    $groupIDs['inc'] = $this->groupCreate(['name' => 'Example include group', 'title' => 'Example include group']);
    $groupIDs['exc'] = $this->groupCreate(['name' => 'Example exclude group', 'title' => 'Example exclude group']);
    $contactIDs['include_me'] = $this->individualCreate([
      'email' => 'include.me@example.org',
      'first_name' => 'Includer',
      'last_name' => 'Person',
    ]);
    $contactIDs['exclude_me'] = $this->individualCreate([
      'email' => 'exclude.me@example.org',
      'last_name' => 'Excluder',
    ]);
    $this->callAPISuccess('GroupContact', 'create', [
      'group_id' => $groupIDs['inc'],
      'contact_id' => $contactIDs['include_me'],
    ]);
    $this->callAPISuccess('GroupContact', 'create', [
      'group_id' => $groupIDs['inc'],
      'contact_id' => $contactIDs['exclude_me'],
    ]);
    $this->callAPISuccess('GroupContact', 'create', [
      'group_id' => $groupIDs['exc'],
      'contact_id' => $contactIDs['exclude_me'],
    ]);

    $params = $this->_params;
    $params['groups']['include'] = [$groupIDs['inc']];
    $params['groups']['exclude'] = [$groupIDs['exc']];
    $params['mailings']['include'] = [];
    $params['mailings']['exclude'] = [];
    $params['options']['force_rollback'] = 1;
    $params['api.MailingRecipients.get'] = [
      'mailing_id' => '$value.id',
      'api.contact.getvalue' => [
        'return' => 'display_name',
      ],
      'api.email.getvalue' => [
        'return' => 'email',
      ],
    ];
    // END SAMPLE DATA

    $maxIDs = [
      'mailing' => CRM_Core_DAO::singleValueQuery('SELECT MAX(id) FROM civicrm_mailing'),
      'group' => CRM_Core_DAO::singleValueQuery('SELECT MAX(id) FROM civicrm_mailing_group'),
    ];
    $create = $this->callAPIAndDocument('Mailing', 'create', $params, __FUNCTION__, __FILE__);
    // 'Preview should not create any mailing records'
    $this->assertDBQuery($maxIDs['mailing'], 'SELECT MAX(id) FROM civicrm_mailing');
    // 'Preview should not create any mailing_group records'
    $this->assertDBQuery($maxIDs['group'], 'SELECT MAX(id) FROM civicrm_mailing_group');

    $preview = $create['values'][$create['id']]['api.MailingRecipients.get'];
    $previewIds = array_values(CRM_Utils_Array::collect('contact_id', $preview['values']));
    $this->assertEquals([(string) $contactIDs['include_me']], $previewIds);
    $previewEmails = array_values(CRM_Utils_Array::collect('api.email.getvalue', $preview['values']));
    $this->assertEquals(['include.me@example.org'], $previewEmails);
    $previewNames = array_values(CRM_Utils_Array::collect('api.contact.getvalue', $preview['values']));
    $this->assertTrue((bool) preg_match('/Includer Person/', $previewNames[0]), "Name 'Includer Person' should appear in '" . $previewNames[0] . '"');
  }

  /**
   * Test if Mailing recipients include duplicate OR on_hold emails
   *
   * @throws \CRM_Core_Exception
   */
  public function testMailerPreviewRecipientsDeduplicateAndOnholdEmails() {
    // BEGIN SAMPLE DATA
    $groupIDs['grp'] = $this->groupCreate(['name' => 'Example group', 'title' => 'Example group']);
    $contactIDs['include_me'] = $this->individualCreate([
      'email' => 'include.me@example.org',
      'first_name' => 'Includer',
      'last_name' => 'Person',
    ]);
    $contactIDs['include_me_duplicate'] = $this->individualCreate([
      'email' => 'include.me@example.org',
      'first_name' => 'IncluderDuplicate',
      'last_name' => 'Person',
    ]);

    $contactIDs['include_me_onhold'] = $this->individualCreate([
      'email' => 'onholdinclude.me@example.org',
      'first_name' => 'Onhold',
      'last_name' => 'Person',
    ]);
    $emailId = $this->callAPISuccessGetValue('Email', [
      'return' => 'id',
      'contact_id' => $contactIDs['include_me_onhold'],
    ]);
    $this->callAPISuccess('Email', 'create', [
      'id' => $emailId,
      'on_hold' => 1,
    ]);

    $this->callAPISuccess('GroupContact', 'create', [
      'group_id' => $groupIDs['grp'],
      'contact_id' => $contactIDs['include_me'],
    ]);
    $this->callAPISuccess('GroupContact', 'create', [
      'group_id' => $groupIDs['grp'],
      'contact_id' => $contactIDs['include_me_duplicate'],
    ]);
    $this->callAPISuccess('GroupContact', 'create', [
      'group_id' => $groupIDs['grp'],
      'contact_id' => $contactIDs['include_me_onhold'],
    ]);

    $params = $this->_params;
    $params['groups']['include'] = [$groupIDs['grp']];
    $params['mailings']['include'] = [];
    $params['options']['force_rollback'] = 1;
    $params['dedupe_email'] = 1;
    $params['api.MailingRecipients.get'] = [
      'mailing_id' => '$value.id',
      'api.contact.getvalue' => [
        'return' => 'display_name',
      ],
      'api.email.getvalue' => [
        'return' => 'email',
      ],
    ];
    // END SAMPLE DATA

    $create = $this->callAPISuccess('Mailing', 'create', $params);

    //Recipient should not contain duplicate or on_hold emails.
    $preview = $create['values'][$create['id']]['api.MailingRecipients.get'];
    $this->assertEquals(1, $preview['count']);
    $previewEmails = array_values(CRM_Utils_Array::collect('api.email.getvalue', $preview['values']));
    $this->assertEquals(['include.me@example.org'], $previewEmails);
  }

  /**
   * Test sending a test mailing.
   */
  public function testMailerSendTest_email() {
    $contactIDs['alice'] = $this->individualCreate([
      'email' => 'alice@example.org',
      'first_name' => 'Alice',
      'last_name' => 'Person',
    ]);

    $mail = $this->callAPISuccess('mailing', 'create', $this->_params);

    $params = ['mailing_id' => $mail['id'], 'test_email' => 'ALicE@example.org', 'test_group' => NULL];
    // Per https://lab.civicrm.org/dev/core/issues/229 ensure this is not passed through!
    // Per https://lab.civicrm.org/dev/mail/issues/32 test non-lowercase email
    $params['id'] = $mail['id'];
    $deliveredInfo = $this->callAPISuccess($this->_entity, 'send_test', $params);
    // verify mail has been sent to user by count
    $this->assertEquals(1, $deliveredInfo['count']);

    $deliveredContacts = array_values(CRM_Utils_Array::collect('contact_id', $deliveredInfo['values']));
    $this->assertEquals([$contactIDs['alice']], $deliveredContacts);

    $deliveredEmails = array_values(CRM_Utils_Array::collect('email', $deliveredInfo['values']));
    $this->assertEquals(['alice@example.org'], $deliveredEmails);
  }

  /**
   *
   */
  public function testMailerSendTest_group() {
    // BEGIN SAMPLE DATA
    $groupIDs['inc'] = $this->groupCreate(['name' => 'Example include group', 'title' => 'Example include group']);
    $contactIDs['alice'] = $this->individualCreate([
      'email' => 'alice@example.org',
      'first_name' => 'Alice',
      'last_name' => 'Person',
    ]);
    $contactIDs['bob'] = $this->individualCreate([
      'email' => 'bob@example.org',
      'first_name' => 'Bob',
      'last_name' => 'Person',
    ]);
    $contactIDs['carol'] = $this->individualCreate([
      'email' => 'carol@example.org',
      'first_name' => 'Carol',
      'last_name' => 'Person',
    ]);
    $this->callAPISuccess('GroupContact', 'create', [
      'group_id' => $groupIDs['inc'],
      'contact_id' => $contactIDs['alice'],
    ]);
    $this->callAPISuccess('GroupContact', 'create', [
      'group_id' => $groupIDs['inc'],
      'contact_id' => $contactIDs['bob'],
    ]);
    $this->callAPISuccess('GroupContact', 'create', [
      'group_id' => $groupIDs['inc'],
      'contact_id' => $contactIDs['carol'],
    ]);
    // END SAMPLE DATA

    $mail = $this->callAPISuccess('mailing', 'create', $this->_params);
    $deliveredInfo = $this->callAPISuccess($this->_entity, 'send_test', [
      'mailing_id' => $mail['id'],
      'test_email' => NULL,
      'test_group' => $groupIDs['inc'],
    ]);
    // verify mail has been sent to user by count
    $this->assertEquals(3, $deliveredInfo['count'], "in line " . __LINE__);

    $deliveredContacts = array_values(CRM_Utils_Array::collect('contact_id', $deliveredInfo['values']));
    $this->assertEquals([$contactIDs['alice'], $contactIDs['bob'], $contactIDs['carol']], $deliveredContacts);

    $deliveredEmails = array_values(CRM_Utils_Array::collect('email', $deliveredInfo['values']));
    $this->assertEquals(['alice@example.org', 'bob@example.org', 'carol@example.org'], $deliveredEmails);
  }

  /**
   * @return array
   */
  public function submitProvider() {
    // $useLogin, $params, $expectedFailure, $expectedJobCount
    $cases = [];
    $cases[] = [
      //useLogin
      TRUE,
      // createParams
      [],
      ['scheduled_date' => '2014-12-13 10:00:00', 'approval_date' => '2014-12-13 00:00:00'],
      // expectedFailure
      FALSE,
      // expectedJobCount
      1,
    ];
    $cases[] = [
      //useLogin
      FALSE,
      // createParams
      [],
      ['scheduled_date' => '2014-12-13 10:00:00', 'approval_date' => '2014-12-13 00:00:00'],
      // expectedFailure
      "/Failed to determine current user/",
      // expectedJobCount
      0,
    ];
    $cases[] = [
      //useLogin
      TRUE,
      // createParams
      [],
      ['scheduled_date' => '2014-12-13 10:00:00'],
      // expectedFailure
      FALSE,
      // expectedJobCount
      1,
    ];
    $cases[] = [
      //useLogin
      TRUE,
      // createParams
      [],
      [],
      // expectedFailure
      "/Missing parameter scheduled_date and.or approval_date/",
      // expectedJobCount
      0,
    ];
    $cases[] = [
      //useLogin
      TRUE,
      // createParams
      ['name' => ''],
      ['scheduled_date' => '2014-12-13 10:00:00', 'approval_date' => '2014-12-13 00:00:00'],
      // expectedFailure
      "/Mailing cannot be sent. There are missing or invalid fields \\(name\\)./",
      // expectedJobCount
      0,
    ];
    $cases[] = [
      //useLogin
      TRUE,
      // createParams
      ['body_html' => '', 'body_text' => ''],
      ['scheduled_date' => '2014-12-13 10:00:00', 'approval_date' => '2014-12-13 00:00:00'],
      // expectedFailure
      "/Mailing cannot be sent. There are missing or invalid fields \\(body\\)./",
      // expectedJobCount
      0,
    ];
    $cases[] = [
      //useLogin
      TRUE,
      // createParams
      ['body_html' => 'Oops, did I leave my tokens at home?'],
      ['scheduled_date' => '2014-12-13 10:00:00', 'approval_date' => '2014-12-13 00:00:00'],
      // expectedFailure
      "/Mailing cannot be sent. There are missing or invalid fields \\(.*body_html.*optOut.*\\)./",
      // expectedJobCount
      0,
    ];
    $cases[] = [
      //useLogin
      TRUE,
      // createParams
      ['body_text' => 'Oops, did I leave my tokens at home?'],
      ['scheduled_date' => '2014-12-13 10:00:00', 'approval_date' => '2014-12-13 00:00:00'],
      // expectedFailure
      "/Mailing cannot be sent. There are missing or invalid fields \\(.*body_text.*optOut.*\\)./",
      // expectedJobCount
      0,
    ];
    $cases[] = [
      //useLogin
      TRUE,
      // createParams
      ['body_text' => 'Look ma, magic tokens in the text!', 'footer_id' => '%FOOTER%'],
      ['scheduled_date' => '2014-12-13 10:00:00', 'approval_date' => '2014-12-13 00:00:00'],
      // expectedFailure
      FALSE,
      // expectedJobCount
      1,
    ];
    $cases[] = [
      //useLogin
      TRUE,
      // createParams
      ['body_html' => '<p>Look ma, magic tokens in the markup!</p>', 'footer_id' => '%FOOTER%'],
      ['scheduled_date' => '2014-12-13 10:00:00', 'approval_date' => '2014-12-13 00:00:00'],
      // expectedFailure
      FALSE,
      // expectedJobCount
      1,
    ];
    return $cases;
  }

  /**
   * @param bool $useLogin
   * @param array $createParams
   * @param array $submitParams
   * @param null|string $expectedFailure
   * @param int $expectedJobCount
   *
   * @dataProvider submitProvider
   * @throws \CRM_Core_Exception
   */
  public function testMailerSubmit($useLogin, $createParams, $submitParams, $expectedFailure, $expectedJobCount) {
    if ($useLogin) {
      $this->createLoggedInUser();
    }

    if (isset($createParams['footer_id']) && $createParams['footer_id'] === '%FOOTER%') {
      $createParams['footer_id'] = $this->footer['id'];
    }

    $id = $this->createDraftMailing($createParams);

    $submitParams['id'] = $id;
    if ($expectedFailure) {
      $submitResult = $this->callAPIFailure('mailing', 'submit', $submitParams);
      $this->assertRegExp($expectedFailure, $submitResult['error_message']);
    }
    else {
      $submitResult = $this->callAPIAndDocument('mailing', 'submit', $submitParams, __FUNCTION__, __FILE__);
      $this->assertTrue(is_numeric($submitResult['id']));
      $this->assertTrue(is_numeric($submitResult['values'][$id]['scheduled_id']));
      $this->assertEquals($submitParams['scheduled_date'], $submitResult['values'][$id]['scheduled_date']);
    }
    $this->assertDBQuery($expectedJobCount, 'SELECT count(*) FROM civicrm_mailing_job WHERE mailing_id = %1', [
      1 => [$id, 'Integer'],
    ]);
  }

  /**
   * Test unsubscribe list contains correct groups
   * when include = 'previous mailing'
   *
   * @throws \CiviCRM_API3_Exception
   * @throws \CRM_Core_Exception
   */
  public function testUnsubscribeGroupList() {
    // Create set of groups and add a contact to both of them.
    $groupID2 = $this->groupCreate(['name' => 'Test group 2', 'title' => 'group title 2']);
    $groupID3 = $this->groupCreate(['name' => 'Test group 3', 'title' => 'group title 3']);
    $contactId = $this->individualCreate();
    foreach ([$groupID2, $groupID3] as $grp) {
      $params = [
        'contact_id' => $contactId,
        'group_id' => $grp,
      ];
      $this->callAPISuccess('GroupContact', 'create', $params);
    }

    //Send mail to groupID3
    $mail = $this->callAPISuccess('mailing', 'create', $this->_params);
    $params = ['mailing_id' => $mail['id'], 'test_email' => NULL, 'test_group' => $groupID3];
    $this->callAPISuccess($this->_entity, 'send_test', $params);

    $mgParams = [
      'mailing_id' => $mail['id'],
      'entity_table' => 'civicrm_group',
      'entity_id' => $groupID3,
      'group_type' => 'Include',
    ];
    $mailingGroup = $this->callAPISuccess('MailingGroup', 'create', $mgParams);

    //Include previous mail in the mailing group.
    $mail2 = $this->callAPISuccess('mailing', 'create', $this->_params);
    $params = ['mailing_id' => $mail2['id'], 'test_email' => NULL, 'test_group' => $groupID3];
    $this->callAPISuccess($this->_entity, 'send_test', $params);

    $mgParams = [
      'mailing_id' => $mail2['id'],
      'entity_table' => 'civicrm_mailing',
      'entity_id' => $mail['id'],
      'group_type' => 'Include',
    ];
    $this->callAPISuccess('MailingGroup', 'create', $mgParams);
    //CRM-20431 - Delete group id that matches first mailing id.
    $this->callAPISuccess('Group', 'delete', ['id' => $this->_groupID]);
    $jobId = CRM_Core_DAO::getFieldValue('CRM_Mailing_DAO_MailingJob', $mail2['id'], 'id', 'mailing_id');
    $hash = CRM_Core_DAO::getFieldValue('CRM_Mailing_Event_DAO_Queue', $jobId, 'hash', 'job_id');
    $queueId = CRM_Core_DAO::getFieldValue('CRM_Mailing_Event_DAO_Queue', $jobId, 'id', 'job_id');

    $group = CRM_Mailing_Event_BAO_Unsubscribe::unsub_from_mailing($jobId, $queueId, $hash, TRUE);
    //Assert only one group returns in the unsubscribe list.
    $this->assertCount(1, $group);
    $this->assertEquals($groupID3, key($group));
  }

  /**
   *
   */
  public function testMailerStats() {
    $result = $this->groupContactCreate($this->_groupID, 100);
    //verify if 100 contacts are added for group
    $this->assertEquals(100, $result['added']);

    //Create and send test mail first and change the mail job to live,
    //because stats api only works on live mail
    $mail = $this->callAPISuccess('mailing', 'create', $this->_params);
    $params = ['mailing_id' => $mail['id'], 'test_email' => NULL, 'test_group' => $this->_groupID];
    $deliveredInfo = $this->callAPISuccess($this->_entity, 'send_test', $params);
    $deliveredIds = implode(',', array_keys($deliveredInfo['values']));

    //Change the test mail into live
    $sql = "UPDATE civicrm_mailing_job SET is_test = 0 WHERE mailing_id = {$mail['id']}";
    CRM_Core_DAO::executeQuery($sql);

    foreach (['bounce', 'unsubscribe', 'opened'] as $type) {
      $temporaryTable = CRM_Utils_SQL_TempTable::build()->setCategory($type)->createWithQuery("SELECT event_queue_id, time_stamp, id as delivered_id
        FROM civicrm_mailing_event_delivered
        WHERE id IN ($deliveredIds)
         ORDER BY RAND() LIMIT 0,20");
      $temporaryTableName = $temporaryTable->getName();

      if ($type == 'unsubscribe') {
        $sql = "INSERT INTO civicrm_mailing_event_{$type} (event_queue_id, time_stamp, org_unsubscribe)
SELECT event_queue_id, time_stamp, 1 FROM {$temporaryTableName}";
      }
      else {
        $sql = "INSERT INTO civicrm_mailing_event_{$type} (event_queue_id, time_stamp)
SELECT event_queue_id, time_stamp FROM {$temporaryTableName}";
      }
      CRM_Core_DAO::executeQuery($sql);
    }

    $result = $this->callAPISuccess('mailing', 'stats', ['mailing_id' => $mail['id']]);
    $expectedResult = [
      //since among 100 mails 20 has been bounced
      'Delivered' => 80,
      'Bounces' => 20,
      'Opened' => 20,
      'Unique Clicks' => 0,
      'Unsubscribers' => 20,
      'delivered_rate' => '80%',
      'opened_rate' => '25%',
      'clickthrough_rate' => '0%',
    ];
    $this->checkArrayEquals($expectedResult, $result['values'][$mail['id']]);
    $temporaryTable->drop();
  }

  /**
   * Test civicrm_mailing_delete.
   */
  public function testMailerDeleteSuccess() {
    $result = $this->callAPISuccess($this->_entity, 'create', $this->_params);
    $this->callAPIAndDocument($this->_entity, 'delete', ['id' => $result['id']], __FUNCTION__, __FILE__);
    $this->assertAPIDeleted($this->_entity, $result['id']);
  }

  /**
   * Test Mailing.gettokens.
   */
  public function testMailGetTokens() {
    $description = "Demonstrates fetching tokens for one or more entities (in this case \"Contact\" and \"Mailing\").
      Optionally pass sequential=1 to have output ready-formatted for the select2 widget.";
    $result = $this->callAPIAndDocument($this->_entity, 'gettokens', ['entity' => ['Contact', 'Mailing']], __FUNCTION__, __FILE__, $description);
    $this->assertContains('Contact Type', $result['values']);

    // Check that passing "sequential" correctly outputs a hierarchical array
    $result = $this->callAPISuccess($this->_entity, 'gettokens', ['entity' => 'contact', 'sequential' => 1]);
    $this->assertArrayHasKey('text', $result['values'][0]);
    $this->assertArrayHasKey('id', $result['values'][0]['children'][0]);
  }

  /**
   * Test clone function.
   *
   * @throws \CRM_Core_Exception
   */
  public function testClone() {
    // BEGIN SAMPLE DATA
    $groupIDs['inc'] = $this->groupCreate(['name' => 'Example include group', 'title' => 'Example include group']);
    $contactIDs['include_me'] = $this->individualCreate([
      'email' => 'include.me@example.org',
      'first_name' => 'Includer',
      'last_name' => 'Person',
    ]);
    $this->callAPISuccess('GroupContact', 'create', [
      'group_id' => $groupIDs['inc'],
      'contact_id' => $contactIDs['include_me'],
    ]);

    $params = $this->_params;
    $params['groups']['include'] = [$groupIDs['inc']];
    $params['groups']['exclude'] = [];
    $params['mailings']['include'] = [];
    $params['mailings']['exclude'] = [];
    // END SAMPLE DATA

    $create = $this->callAPISuccess('Mailing', 'create', $params);
    $created = $this->callAPISuccess('Mailing', 'get', []);
    $createId = $create['id'];
    $this->createLoggedInUser();
    $clone = $this->callAPIAndDocument('Mailing', 'clone', ['id' => $create['id']], __FUNCTION__, __FILE__);
    $cloneId = $clone['id'];

    $this->assertNotEquals($createId, $cloneId, 'Create and clone should return different records');
    $this->assertTrue(is_numeric($cloneId));

    $this->assertNotEmpty($clone['values'][$cloneId]['subject']);
    $this->assertEquals($params['subject'], $clone['values'][$cloneId]['subject'], "Cloned subject should match");

    // created_id is special - populated based on current user (ie the cloner).
    $this->assertNotEmpty($clone['values'][$cloneId]['created_id']);
    $this->assertNotEquals($create['values'][$createId]['created_id'], $clone['values'][$cloneId]['created_id'], 'Clone should be created by a different person');

    // Target groups+mailings are special.
    $cloneGroups = $this->callAPISuccess('MailingGroup', 'get', ['mailing_id' => $cloneId, 'sequential' => 1]);
    $this->assertEquals(1, $cloneGroups['count']);
    $this->assertEquals($cloneGroups['values'][0]['group_type'], 'Include');
    $this->assertEquals($cloneGroups['values'][0]['entity_table'], 'civicrm_group');
    $this->assertEquals($cloneGroups['values'][0]['entity_id'], $groupIDs['inc']);
  }

  //@ todo tests below here are all failure tests which are not hugely useful - need success tests

  //------------ civicrm_mailing_event_bounce methods------------

  /**
   * Test civicrm_mailing_event_bounce with wrong params.
   * Note that tests like this are slightly better than no test but an
   * api function cannot be considered supported  / 'part of the api' without a
   * success test
   */
  public function testMailerBounceWrongParams() {
    $params = [
      'job_id' => 'Wrong ID',
      'event_queue_id' => 'Wrong ID',
      'hash' => 'Wrong Hash',
      'body' => 'Body...',
      'time_stamp' => '20111109212100',
    ];
    $this->callAPIFailure('mailing_event', 'bounce', $params,
      'Queue event could not be found'
    );
  }

  //----------- civicrm_mailing_event_confirm methods -----------

  /**
   * Test civicrm_mailing_event_confirm with wrong params.
   * Note that tests like this are slightly better than no test but an
   * api function cannot be considered supported  / 'part of the api' without a
   * success test
   */
  public function testMailerConfirmWrongParams() {
    $params = [
      'contact_id' => 'Wrong ID',
      'subscribe_id' => 'Wrong ID',
      'hash' => 'Wrong Hash',
      'event_subscribe_id' => '123',
      'time_stamp' => '20111111010101',
    ];
    $this->callAPIFailure('mailing_event', 'confirm', $params,
      'contact_id is not a valid integer'
    );
  }

  //---------- civicrm_mailing_event_reply methods -----------

  /**
   * Test civicrm_mailing_event_reply with wrong params.
   *
   * Note that tests like this are slightly better than no test but an
   * api function cannot be considered supported  / 'part of the api' without a
   * success test
   */
  public function testMailerReplyWrongParams() {
    $params = [
      'job_id' => 'Wrong ID',
      'event_queue_id' => 'Wrong ID',
      'hash' => 'Wrong Hash',
      'bodyTxt' => 'Body...',
      'replyTo' => $this->_email,
      'time_stamp' => '20111111010101',
    ];
    $this->callAPIFailure('mailing_event', 'reply', $params,
      'Queue event could not be found'
    );
  }

  //----------- civicrm_mailing_event_forward methods ----------

  /**
   * Test civicrm_mailing_event_forward with wrong params.
   * Note that tests like this are slightly better than no test but an
   * api function cannot be considered supported  / 'part of the api' without a
   * success test
   */
  public function testMailerForwardWrongParams() {
    $params = [
      'job_id' => 'Wrong ID',
      'event_queue_id' => 'Wrong ID',
      'hash' => 'Wrong Hash',
      'email' => $this->_email,
      'time_stamp' => '20111111010101',
    ];
    $this->callAPIFailure('mailing_event', 'forward', $params,
      'Queue event could not be found'
    );
  }

  /**
   * @param array $params
   *   Extra parameters for the draft mailing.
   * @return array|int
   */
  public function createDraftMailing($params = []) {
    $createParams = array_merge($this->_params, $params);
    $createResult = $this->callAPISuccess('mailing', 'create', $createParams, __FUNCTION__, __FILE__);
    $this->assertTrue(is_numeric($createResult['id']));
    $this->assertDBQuery(0, 'SELECT count(*) FROM civicrm_mailing_job WHERE mailing_id = %1', [
      1 => [$createResult['id'], 'Integer'],
    ]);
    return $createResult['id'];
  }

  /**
   * Test to make sure that if the event queue hashes have been archived,
   * we can still have working click-trough URLs working (CRM-17959).
   *
   * @throws \CRM_Core_Exception
   */
  public function testUrlWithMissingTrackingHash() {
    $mail = $this->callAPISuccess('mailing', 'create', $this->_params + ['scheduled_date' => 'now'], __FUNCTION__, __FILE__);
    $jobs = $this->callAPISuccess('mailing_job', 'get', ['mailing_id' => $mail['id']]);
    $this->assertEquals(1, $jobs['count']);

    $params = ['mailing_id' => $mail['id'], 'test_email' => 'alice@example.org', 'test_group' => NULL];
    $deliveredInfo = $this->callAPISuccess($this->_entity, 'send_test', $params);

    $sql = "SELECT turl.id as url_id, turl.url, q.id as queue_id
      FROM civicrm_mailing_trackable_url as turl
      INNER JOIN civicrm_mailing_job as j ON turl.mailing_id = j.mailing_id
      INNER JOIN civicrm_mailing_event_queue q ON j.id = q.job_id
      ORDER BY turl.id DESC LIMIT 1";

    $dao = CRM_Core_DAO::executeQuery($sql);
    $this->assertTrue($dao->fetch());

    $url = CRM_Mailing_Event_BAO_TrackableURLOpen::track($dao->queue_id, $dao->url_id);
    $this->assertContains('https://civicrm.org', $url);

    // Now delete the event queue hashes and see if the tracking still works.
    CRM_Core_DAO::executeQuery('DELETE FROM civicrm_mailing_event_queue');

    $url = CRM_Mailing_Event_BAO_TrackableURLOpen::track($dao->queue_id, $dao->url_id);
    $this->assertContains('https://civicrm.org', $url);

    // Ensure that Google CSS link is not tracked.
    $sql = "SELECT id FROM civicrm_mailing_trackable_url where url = 'https://fonts.googleapis.com/css?family=Roboto+Condensed:400,700|Zilla+Slab:500,700'";
    $this->assertEquals([], CRM_Core_DAO::executeQuery($sql)->fetchAll());
  }

  /**
   * Test Trackable URL with unicode character
   */
  public function testTrackableURLWithUnicodeSign() {
    $unicodeURL = "https://civińcrm.org";
    $this->_params['body_text'] = str_replace("https://civicrm.org", $unicodeURL, $this->_params['body_text']);
    $this->_params['body_html'] = str_replace("https://civicrm.org", $unicodeURL, $this->_params['body_html']);

    $mail = $this->callAPISuccess('mailing', 'create', $this->_params + ['scheduled_date' => 'now']);

    $params = ['mailing_id' => $mail['id'], 'test_email' => 'alice@example.org', 'test_group' => NULL];
    $this->callAPISuccess($this->_entity, 'send_test', $params);

    $sql = "SELECT turl.id as url_id, turl.url, q.id as queue_id
      FROM civicrm_mailing_trackable_url as turl
      INNER JOIN civicrm_mailing_job as j ON turl.mailing_id = j.mailing_id
      INNER JOIN civicrm_mailing_event_queue q ON j.id = q.job_id
      ORDER BY turl.id DESC LIMIT 1";

    $dao = CRM_Core_DAO::executeQuery($sql);
    $this->assertTrue($dao->fetch());

    $url = CRM_Mailing_Event_BAO_TrackableURLOpen::track($dao->queue_id, $dao->url_id);
    $this->assertContains($unicodeURL, $url);

    // Now delete the event queue hashes and see if the tracking still works.
    CRM_Core_DAO::executeQuery('DELETE FROM civicrm_mailing_event_queue');

    $url = CRM_Mailing_Event_BAO_TrackableURLOpen::track($dao->queue_id, $dao->url_id);
    $this->assertContains($unicodeURL, $url);
  }

  /**
   * CRM-20892 : Test if Mail.create API throws error on update,
   *  if modified_date less then the date when the mail was last updated/created
   */
  public function testModifiedDateMismatchOnMailingUpdate() {
    $mail = $this->callAPISuccess('mailing', 'create', $this->_params + ['modified_date' => 'now']);
    try {
      $this->callAPISuccess('mailing', 'create', $this->_params + ['id' => $mail['id'], 'modified_date' => '2 seconds ago']);
    }
    catch (Exception $e) {
      $this->assertRegExp("/Failure in api call for mailing create:  Mailing has not been saved, Content maybe out of date, please refresh the page and try again/", $e->getMessage());
    }
  }

  /**
   * Test that api Mailing.update_email_resetdate does not throw a core error.
   *
   * @throws \CRM_Core_Exception
   */
  public function testUpdateEmailResetdate() {
    $this->callAPISuccess('Mailing', 'update_email_resetdate', []);
  }

}
