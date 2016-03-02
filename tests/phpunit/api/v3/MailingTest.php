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
  protected $_params = array();
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
    CRM_Mailing_BAO_MailingJob::$mailsProcessed = 0; // DGW
    $this->_contactID = $this->individualCreate();
    $this->_groupID = $this->groupCreate();
    $this->_email = 'test@test.test';
    $this->_params = array(
      'subject' => 'Hello {contact.display_name}',
      'body_text' => "This is {contact.display_name}.\n{domain.address}{action.optOutUrl}",
      'body_html' => "<p>This is {contact.display_name}.</p><p>{domain.address}{action.optOutUrl}</p>",
      'name' => 'mailing name',
      'created_id' => $this->_contactID,
      'header_id' => '',
      'footer_id' => '',
    );

    $this->footer = civicrm_api3('MailingComponent', 'create', array(
      'body_html' => '<p>From {domain.address}. To opt out, go to {action.optOutUrl}.</p>',
      'body_text' => 'From {domain.address}. To opt out, go to {action.optOutUrl}.',
    ));
  }

  public function tearDown() {
    CRM_Mailing_BAO_MailingJob::$mailsProcessed = 0; // DGW
    parent::tearDown();
  }

  /**
   * Test civicrm_mailing_create.
   */
  public function testMailerCreateSuccess() {
    $result = $this->callAPIAndDocument('mailing', 'create', $this->_params + array('scheduled_date' => 'now'), __FUNCTION__, __FILE__);
    $jobs = $this->callAPISuccess('mailing_job', 'get', array('mailing_id' => $result['id']));
    $this->assertEquals(1, $jobs['count']);
    unset($this->_params['created_id']); // return isn't working on this in getAndCheck so lets not check it for now
    $this->getAndCheck($this->_params, $result['id'], 'mailing');
  }

  /**
   * The Mailing.create API supports magic properties "groups[include,enclude]" and "mailings[include,exclude]".
   * Make sure these work
   */
  public function testMagicGroups_create_update() {
    // BEGIN SAMPLE DATA
    $groupIDs['a'] = $this->groupCreate(array('name' => 'Example include group', 'title' => 'Example include group'));
    $groupIDs['b'] = $this->groupCreate(array('name' => 'Example exclude group', 'title' => 'Example exclude group'));
    $contactIDs['a'] = $this->individualCreate(array(
        'email' => 'include.me@example.org',
        'first_name' => 'Includer',
        'last_name' => 'Person',
      ));
    $contactIDs['b'] = $this->individualCreate(array(
        'email' => 'exclude.me@example.org',
        'last_name' => 'Excluder',
      ));
    $this->callAPISuccess('GroupContact', 'create', array(
        'group_id' => $groupIDs['a'],
        'contact_id' => $contactIDs['a'],
      ));
    $this->callAPISuccess('GroupContact', 'create', array(
        'group_id' => $groupIDs['b'],
        'contact_id' => $contactIDs['b'],
      ));
    // END SAMPLE DATA

    // ** Pass 1: Create
    $createParams = $this->_params;
    $createParams['groups']['include'] = array($groupIDs['a']);
    $createParams['groups']['exclude'] = array();
    $createParams['mailings']['include'] = array();
    $createParams['mailings']['exclude'] = array();
    $createParams['api.mailing_job.create'] = 1;
    $createResult = $this->callAPISuccess('Mailing', 'create', $createParams);
    $getGroup1 = $this->callAPISuccess('MailingGroup', 'get', array('mailing_id' => $createResult['id']));
    $getGroup1_ids = array_values(CRM_Utils_Array::collect('entity_id', $getGroup1['values']));
    $this->assertEquals(array($groupIDs['a']), $getGroup1_ids);
    $getRecipient1 = $this->callAPISuccess('MailingRecipients', 'get', array('mailing_id' => $createResult['id']));
    $getRecipient1_ids = array_values(CRM_Utils_Array::collect('contact_id', $getRecipient1['values']));
    $this->assertEquals(array($contactIDs['a']), $getRecipient1_ids);

    // ** Pass 2: Update without any changes to groups[include]
    $nullOpParams = $createParams;
    $nullOpParams['id'] = $createResult['id'];
    $updateParams['api.mailing_job.create'] = 1;
    unset($nullOpParams['groups']['include']);
    $this->callAPISuccess('Mailing', 'create', $nullOpParams);
    $getGroup2 = $this->callAPISuccess('MailingGroup', 'get', array('mailing_id' => $createResult['id']));
    $getGroup2_ids = array_values(CRM_Utils_Array::collect('entity_id', $getGroup2['values']));
    $this->assertEquals(array($groupIDs['a']), $getGroup2_ids);
    $getRecipient2 = $this->callAPISuccess('MailingRecipients', 'get', array('mailing_id' => $createResult['id']));
    $getRecip2_ids = array_values(CRM_Utils_Array::collect('contact_id', $getRecipient2['values']));
    $this->assertEquals(array($contactIDs['a']), $getRecip2_ids);

    // ** Pass 3: Update with different groups[include]
    $updateParams = $createParams;
    $updateParams['id'] = $createResult['id'];
    $updateParams['groups']['include'] = array($groupIDs['b']);
    $updateParams['api.mailing_job.create'] = 1;
    $this->callAPISuccess('Mailing', 'create', $updateParams);
    $getGroup3 = $this->callAPISuccess('MailingGroup', 'get', array('mailing_id' => $createResult['id']));
    $getGroup3_ids = array_values(CRM_Utils_Array::collect('entity_id', $getGroup3['values']));
    $this->assertEquals(array($groupIDs['b']), $getGroup3_ids);
    $getRecipient3 = $this->callAPISuccess('MailingRecipients', 'get', array('mailing_id' => $createResult['id']));
    $getRecipient3_ids = array_values(CRM_Utils_Array::collect('contact_id', $getRecipient3['values']));
    $this->assertEquals(array($contactIDs['b']), $getRecipient3_ids);
  }

  public function testMailerPreview() {
    // BEGIN SAMPLE DATA
    $contactID = $this->individualCreate();
    $displayName = $this->callAPISuccess('contact', 'get', array('id' => $contactID));
    $displayName = $displayName['values'][$contactID]['display_name'];
    $this->assertTrue(!empty($displayName));

    $params = $this->_params;
    $params['api.Mailing.preview'] = array(
      'id' => '$value.id',
      'contact_id' => $contactID,
    );
    $params['options']['force_rollback'] = 1;
    // END SAMPLE DATA

    $maxIDs = array(
      'mailing' => CRM_Core_DAO::singleValueQuery('SELECT MAX(id) FROM civicrm_mailing'),
      'job' => CRM_Core_DAO::singleValueQuery('SELECT MAX(id) FROM civicrm_mailing_job'),
      'group' => CRM_Core_DAO::singleValueQuery('SELECT MAX(id) FROM civicrm_mailing_group'),
      'recipient' => CRM_Core_DAO::singleValueQuery('SELECT MAX(id) FROM civicrm_mailing_recipients'),
    );
    $result = $this->callAPISuccess('mailing', 'create', $params);
    $this->assertDBQuery($maxIDs['mailing'], 'SELECT MAX(id) FROM civicrm_mailing'); // 'Preview should not create any mailing records'
    $this->assertDBQuery($maxIDs['job'], 'SELECT MAX(id) FROM civicrm_mailing_job'); // 'Preview should not create any mailing_job record'
    $this->assertDBQuery($maxIDs['group'], 'SELECT MAX(id) FROM civicrm_mailing_group'); // 'Preview should not create any mailing_group records'
    $this->assertDBQuery($maxIDs['recipient'], 'SELECT MAX(id) FROM civicrm_mailing_recipients'); // 'Preview should not create any mailing_recipient records'

    $previewResult = $result['values'][$result['id']]['api.Mailing.preview'];
    $this->assertEquals("Hello $displayName", $previewResult['values']['subject']);
    $this->assertContains("This is $displayName", $previewResult['values']['body_text']);
    $this->assertContains("<p>This is $displayName.</p>", $previewResult['values']['body_html']);
  }

  public function testMailerPreviewRecipients() {
    // BEGIN SAMPLE DATA
    $groupIDs['inc'] = $this->groupCreate(array('name' => 'Example include group', 'title' => 'Example include group'));
    $groupIDs['exc'] = $this->groupCreate(array('name' => 'Example exclude group', 'title' => 'Example exclude group'));
    $contactIDs['include_me'] = $this->individualCreate(array(
        'email' => 'include.me@example.org',
        'first_name' => 'Includer',
        'last_name' => 'Person',
      ));
    $contactIDs['exclude_me'] = $this->individualCreate(array(
        'email' => 'exclude.me@example.org',
        'last_name' => 'Excluder',
      ));
    $this->callAPISuccess('GroupContact', 'create', array(
        'group_id' => $groupIDs['inc'],
        'contact_id' => $contactIDs['include_me'],
      ));
    $this->callAPISuccess('GroupContact', 'create', array(
        'group_id' => $groupIDs['inc'],
        'contact_id' => $contactIDs['exclude_me'],
      ));
    $this->callAPISuccess('GroupContact', 'create', array(
        'group_id' => $groupIDs['exc'],
        'contact_id' => $contactIDs['exclude_me'],
      ));

    $params = $this->_params;
    $params['groups']['include'] = array($groupIDs['inc']);
    $params['groups']['exclude'] = array($groupIDs['exc']);
    $params['mailings']['include'] = array();
    $params['mailings']['exclude'] = array();
    $params['options']['force_rollback'] = 1;
    $params['api.mailing_job.create'] = 1;
    $params['api.MailingRecipients.get'] = array(
      'mailing_id' => '$value.id',
      'api.contact.getvalue' => array(
        'return' => 'display_name',
      ),
      'api.email.getvalue' => array(
        'return' => 'email',
      ),
    );
    // END SAMPLE DATA

    $maxIDs = array(
      'mailing' => CRM_Core_DAO::singleValueQuery('SELECT MAX(id) FROM civicrm_mailing'),
      'job' => CRM_Core_DAO::singleValueQuery('SELECT MAX(id) FROM civicrm_mailing_job'),
      'group' => CRM_Core_DAO::singleValueQuery('SELECT MAX(id) FROM civicrm_mailing_group'),
    );
    $create = $this->callAPIAndDocument('Mailing', 'create', $params, __FUNCTION__, __FILE__);
    $this->assertDBQuery($maxIDs['mailing'], 'SELECT MAX(id) FROM civicrm_mailing'); // 'Preview should not create any mailing records'
    $this->assertDBQuery($maxIDs['job'], 'SELECT MAX(id) FROM civicrm_mailing_job'); // 'Preview should not create any mailing_job record'
    $this->assertDBQuery($maxIDs['group'], 'SELECT MAX(id) FROM civicrm_mailing_group'); // 'Preview should not create any mailing_group records'

    $preview = $create['values'][$create['id']]['api.MailingRecipients.get'];
    $previewIds = array_values(CRM_Utils_Array::collect('contact_id', $preview['values']));
    $this->assertEquals(array((string) $contactIDs['include_me']), $previewIds);
    $previewEmails = array_values(CRM_Utils_Array::collect('api.email.getvalue', $preview['values']));
    $this->assertEquals(array('include.me@example.org'), $previewEmails);
    $previewNames = array_values(CRM_Utils_Array::collect('api.contact.getvalue', $preview['values']));
    $this->assertTrue((bool) preg_match('/Includer Person/', $previewNames[0]), "Name 'Includer Person' should appear in '" . $previewNames[0] . '"');
  }

  /**
   *
   */
  public function testMailerSendTest_email() {
    $contactIDs['alice'] = $this->individualCreate(array(
        'email' => 'alice@example.org',
        'first_name' => 'Alice',
        'last_name' => 'Person',
      ));

    $mail = $this->callAPISuccess('mailing', 'create', $this->_params);

    $params = array('mailing_id' => $mail['id'], 'test_email' => 'alice@example.org', 'test_group' => NULL);
    $deliveredInfo = $this->callAPISuccess($this->_entity, 'send_test', $params);
    $this->assertEquals(1, $deliveredInfo['count'], "in line " . __LINE__); // verify mail has been sent to user by count

    $deliveredContacts = array_values(CRM_Utils_Array::collect('contact_id', $deliveredInfo['values']));
    $this->assertEquals(array($contactIDs['alice']), $deliveredContacts);

    $deliveredEmails = array_values(CRM_Utils_Array::collect('email', $deliveredInfo['values']));
    $this->assertEquals(array('alice@example.org'), $deliveredEmails);
  }

  /**
   *
   */
  public function testMailerSendTest_group() {
    // BEGIN SAMPLE DATA
    $groupIDs['inc'] = $this->groupCreate(array('name' => 'Example include group', 'title' => 'Example include group'));
    $contactIDs['alice'] = $this->individualCreate(array(
        'email' => 'alice@example.org',
        'first_name' => 'Alice',
        'last_name' => 'Person',
      ));
    $contactIDs['bob'] = $this->individualCreate(array(
        'email' => 'bob@example.org',
        'first_name' => 'Bob',
        'last_name' => 'Person',
      ));
    $contactIDs['carol'] = $this->individualCreate(array(
        'email' => 'carol@example.org',
        'first_name' => 'Carol',
        'last_name' => 'Person',
      ));
    $this->callAPISuccess('GroupContact', 'create', array(
        'group_id' => $groupIDs['inc'],
        'contact_id' => $contactIDs['alice'],
      ));
    $this->callAPISuccess('GroupContact', 'create', array(
        'group_id' => $groupIDs['inc'],
        'contact_id' => $contactIDs['bob'],
      ));
    $this->callAPISuccess('GroupContact', 'create', array(
        'group_id' => $groupIDs['inc'],
        'contact_id' => $contactIDs['carol'],
      ));
    // END SAMPLE DATA

    $mail = $this->callAPISuccess('mailing', 'create', $this->_params);
    $deliveredInfo = $this->callAPISuccess($this->_entity, 'send_test', array(
      'mailing_id' => $mail['id'],
      'test_email' => NULL,
      'test_group' => $groupIDs['inc'],
    ));
    $this->assertEquals(3, $deliveredInfo['count'], "in line " . __LINE__); // verify mail has been sent to user by count

    $deliveredContacts = array_values(CRM_Utils_Array::collect('contact_id', $deliveredInfo['values']));
    $this->assertEquals(array($contactIDs['alice'], $contactIDs['bob'], $contactIDs['carol']), $deliveredContacts);

    $deliveredEmails = array_values(CRM_Utils_Array::collect('email', $deliveredInfo['values']));
    $this->assertEquals(array('alice@example.org', 'bob@example.org', 'carol@example.org'), $deliveredEmails);
  }

  /**
   * @return array
   */
  public function submitProvider() {
    $cases = array(); // $useLogin, $params, $expectedFailure, $expectedJobCount
    $cases[] = array(
      TRUE, //useLogin
      array(), // createParams
      array('scheduled_date' => '2014-12-13 10:00:00', 'approval_date' => '2014-12-13 00:00:00'),
      FALSE, // expectedFailure
      1, // expectedJobCount
    );
    $cases[] = array(
      FALSE, //useLogin
      array(), // createParams
      array('scheduled_date' => '2014-12-13 10:00:00', 'approval_date' => '2014-12-13 00:00:00'),
      "/Failed to determine current user/", // expectedFailure
      0, // expectedJobCount
    );
    $cases[] = array(
      TRUE, //useLogin
      array(), // createParams
      array('scheduled_date' => '2014-12-13 10:00:00'),
      FALSE, // expectedFailure
      1, // expectedJobCount
    );
    $cases[] = array(
      TRUE, //useLogin
      array(), // createParams
      array(),
      "/Missing parameter scheduled_date and.or approval_date/", // expectedFailure
      0, // expectedJobCount
    );
    $cases[] = array(
      TRUE, //useLogin
      array('name' => ''), // createParams
      array('scheduled_date' => '2014-12-13 10:00:00', 'approval_date' => '2014-12-13 00:00:00'),
      "/Mailing cannot be sent. There are missing or invalid fields \\(name\\)./", // expectedFailure
      0, // expectedJobCount
    );
    $cases[] = array(
      TRUE, //useLogin
      array('body_html' => '', 'body_text' => ''), // createParams
      array('scheduled_date' => '2014-12-13 10:00:00', 'approval_date' => '2014-12-13 00:00:00'),
      "/Mailing cannot be sent. There are missing or invalid fields \\(body\\)./", // expectedFailure
      0, // expectedJobCount
    );
    $cases[] = array(
      TRUE, //useLogin
      array('body_html' => 'Oops, did I leave my tokens at home?'), // createParams
      array('scheduled_date' => '2014-12-13 10:00:00', 'approval_date' => '2014-12-13 00:00:00'),
      "/Mailing cannot be sent. There are missing or invalid fields \\(.*body_html.*optOut.*\\)./", // expectedFailure
      0, // expectedJobCount
    );
    $cases[] = array(
      TRUE, //useLogin
      array('body_text' => 'Oops, did I leave my tokens at home?'), // createParams
      array('scheduled_date' => '2014-12-13 10:00:00', 'approval_date' => '2014-12-13 00:00:00'),
      "/Mailing cannot be sent. There are missing or invalid fields \\(.*body_text.*optOut.*\\)./", // expectedFailure
      0, // expectedJobCount
    );
    $cases[] = array(
      TRUE, //useLogin
      array('body_text' => 'Look ma, magic tokens in the text!', 'footer_id' => '%FOOTER%'), // createParams
      array('scheduled_date' => '2014-12-13 10:00:00', 'approval_date' => '2014-12-13 00:00:00'),
      FALSE, // expectedFailure
      1, // expectedJobCount
    );
    $cases[] = array(
      TRUE, //useLogin
      array('body_html' => '<p>Look ma, magic tokens in the markup!</p>', 'footer_id' => '%FOOTER%'), // createParams
      array('scheduled_date' => '2014-12-13 10:00:00', 'approval_date' => '2014-12-13 00:00:00'),
      FALSE, // expectedFailure
      1, // expectedJobCount
    );
    return $cases;
  }

  /**
   * @param bool $useLogin
   * @param array $createParams
   * @param array $submitParams
   * @param null|string $expectedFailure
   * @param int $expectedJobCount
   * @dataProvider submitProvider
   */
  public function testMailerSubmit($useLogin, $createParams, $submitParams, $expectedFailure, $expectedJobCount) {
    if ($useLogin) {
      $this->createLoggedInUser();
    }

    if (isset($createParams['footer_id']) && $createParams['footer_id'] == '%FOOTER%') {
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
    $this->assertDBQuery($expectedJobCount, 'SELECT count(*) FROM civicrm_mailing_job WHERE mailing_id = %1', array(
      1 => array($id, 'Integer'),
    ));
  }

  /**
   *
   */
  public function testMailerStats() {
    $result = $this->groupContactCreate($this->_groupID, 100);
    $this->assertEquals(100, $result['added']); //verify if 100 contacts are added for group

    //Create and send test mail first and change the mail job to live,
    //because stats api only works on live mail
    $mail = $this->callAPISuccess('mailing', 'create', $this->_params);
    $params = array('mailing_id' => $mail['id'], 'test_email' => NULL, 'test_group' => $this->_groupID);
    $deliveredInfo = $this->callAPISuccess($this->_entity, 'send_test', $params);
    $deliveredIds = implode(',', array_keys($deliveredInfo['values']));

    //Change the test mail into live
    $sql = "UPDATE civicrm_mailing_job SET is_test = 0 WHERE mailing_id = {$mail['id']}";
    CRM_Core_DAO::executeQuery($sql);

    foreach (array('bounce', 'unsubscribe', 'opened') as $type) {
      $sql = "CREATE TEMPORARY TABLE mail_{$type}_temp
(event_queue_id int, time_stamp datetime, delivered_id int)
SELECT event_queue_id, time_stamp, id
 FROM civicrm_mailing_event_delivered
 WHERE id IN ($deliveredIds)
 ORDER BY RAND() LIMIT 0,20;";
      CRM_Core_DAO::executeQuery($sql);

      $sql = "DELETE FROM civicrm_mailing_event_delivered WHERE id IN (SELECT delivered_id FROM mail_{$type}_temp);";
      CRM_Core_DAO::executeQuery($sql);

      if ($type == 'unsubscribe') {
        $sql = "INSERT INTO civicrm_mailing_event_{$type} (event_queue_id, time_stamp, org_unsubscribe)
SELECT event_queue_id, time_stamp, 1 FROM mail_{$type}_temp";
      }
      else {
        $sql = "INSERT INTO civicrm_mailing_event_{$type} (event_queue_id, time_stamp)
SELECT event_queue_id, time_stamp FROM mail_{$type}_temp";
      }
      CRM_Core_DAO::executeQuery($sql);
    }

    $result = $this->callAPISuccess('mailing', 'stats', array('mailing_id' => $mail['id']));
    $expectedResult = array(
      'Delivered' => 80, //since among 100 mails 20 has been bounced
      'Bounces' => 20,
      'Opened' => 20,
      'Unique Clicks' => 0,
      'Unsubscribers' => 20,
    );
    $this->checkArrayEquals($expectedResult, $result['values'][$mail['id']]);
  }

  /**
   * Test civicrm_mailing_delete.
   */
  public function testMailerDeleteSuccess() {
    $result = $this->callAPISuccess($this->_entity, 'create', $this->_params);
    $this->callAPIAndDocument($this->_entity, 'delete', array('id' => $result['id']), __FUNCTION__, __FILE__);
    $this->assertAPIDeleted($this->_entity, $result['id']);
  }

  /**
   * Test Mailing.gettokens.
   */
  public function testMailGetTokens() {
    $description = "Demonstrates fetching tokens for one or more entities (in this case \"Contact\" and \"Mailing\").
      Optionally pass sequential=1 to have output ready-formatted for the select2 widget.";
    $result = $this->callAPIAndDocument($this->_entity, 'gettokens', array('entity' => array('Contact', 'Mailing')), __FUNCTION__, __FILE__, $description);
    $this->assertContains('Contact Type', $result['values']);

    // Check that passing "sequential" correctly outputs a hierarchical array
    $result = $this->callAPISuccess($this->_entity, 'gettokens', array('entity' => 'contact', 'sequential' => 1));
    $this->assertArrayHasKey('text', $result['values'][0]);
    $this->assertArrayHasKey('id', $result['values'][0]['children'][0]);
  }

  public function testClone() {
    // BEGIN SAMPLE DATA
    $groupIDs['inc'] = $this->groupCreate(array('name' => 'Example include group', 'title' => 'Example include group'));
    $contactIDs['include_me'] = $this->individualCreate(array(
      'email' => 'include.me@example.org',
      'first_name' => 'Includer',
      'last_name' => 'Person',
    ));
    $this->callAPISuccess('GroupContact', 'create', array(
      'group_id' => $groupIDs['inc'],
      'contact_id' => $contactIDs['include_me'],
    ));

    $params = $this->_params;
    $params['groups']['include'] = array($groupIDs['inc']);
    $params['groups']['exclude'] = array();
    $params['mailings']['include'] = array();
    $params['mailings']['exclude'] = array();
    // END SAMPLE DATA

    $create = $this->callAPISuccess('Mailing', 'create', $params);
    $createId = $create['id'];
    $this->createLoggedInUser();
    $clone = $this->callAPIAndDocument('Mailing', 'clone', array('id' => $create['id']), __FUNCTION__, __FILE__);
    $cloneId = $clone['id'];

    $this->assertNotEquals($createId, $cloneId, 'Create and clone should return different records');
    $this->assertTrue(is_numeric($cloneId));

    $this->assertNotEmpty($clone['values'][$cloneId]['subject']);
    $this->assertEquals($params['subject'], $clone['values'][$cloneId]['subject'], "Cloned subject should match");

    // created_id is special - populated based on current user (ie the cloner).
    $this->assertNotEmpty($clone['values'][$cloneId]['created_id']);
    $this->assertNotEquals($create['values'][$createId]['created_id'], $clone['values'][$cloneId]['created_id'], 'Clone should be created by a different person');

    // Target groups+mailings are special.
    $cloneGroups = $this->callAPISuccess('MailingGroup', 'get', array('mailing_id' => $cloneId, 'sequential' => 1));
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
    $params = array(
      'job_id' => 'Wrong ID',
      'event_queue_id' => 'Wrong ID',
      'hash' => 'Wrong Hash',
      'body' => 'Body...',
      'time_stamp' => '20111109212100',
    );
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
    $params = array(
      'contact_id' => 'Wrong ID',
      'subscribe_id' => 'Wrong ID',
      'hash' => 'Wrong Hash',
      'event_subscribe_id' => '123',
      'time_stamp' => '20111111010101',
    );
    $this->callAPIFailure('mailing_event', 'confirm', $params,
      'Confirmation failed'
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
    $params = array(
      'job_id' => 'Wrong ID',
      'event_queue_id' => 'Wrong ID',
      'hash' => 'Wrong Hash',
      'bodyTxt' => 'Body...',
      'replyTo' => $this->_email,
      'time_stamp' => '20111111010101',
    );
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
    $params = array(
      'job_id' => 'Wrong ID',
      'event_queue_id' => 'Wrong ID',
      'hash' => 'Wrong Hash',
      'email' => $this->_email,
      'time_stamp' => '20111111010101',
    );
    $this->callAPIFailure('mailing_event', 'forward', $params,
      'Queue event could not be found'
    );
  }

  /**
   * @param array $params
   *   Extra parameters for the draft mailing.
   * @return array|int
   */
  public function createDraftMailing($params = array()) {
    $createParams = array_merge($this->_params, $params);
    $createResult = $this->callAPISuccess('mailing', 'create', $createParams, __FUNCTION__, __FILE__);
    $this->assertTrue(is_numeric($createResult['id']));
    $this->assertDBQuery(0, 'SELECT count(*) FROM civicrm_mailing_job WHERE mailing_id = %1', array(
      1 => array($createResult['id'], 'Integer'),
    ));
    return $createResult['id'];
  }

  //----------- civicrm_mailing_create ----------

}
