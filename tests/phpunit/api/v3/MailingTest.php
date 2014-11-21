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

require_once 'CiviTest/CiviUnitTestCase.php';


/**
 *  Test APIv3 civicrm_mailing_* functions
 *
 *  @package   CiviCRM
 */
class api_v3_MailingTest extends CiviUnitTestCase {
  protected $_groupID;
  protected $_email;
  protected $_apiversion = 3;
  protected $_params = array();
  protected $_entity = 'Mailing';
  protected $_groupIDs; // array(string $pseudonym => int $id)
  protected $_contactIDs; // array(string $pseudonym => int $id)

  /**
   * @return array
   */
  function get_info() {
    return array(
      'name' => 'Mailer',
      'description' => 'Test all Mailer methods.',
      'group' => 'CiviCRM API Tests',
    );
  }

  function setUp() {
    parent::setUp();
    $this->useTransaction();
    CRM_Mailing_BAO_MailingJob::$mailsProcessed = 0; // DGW
    $this->_contactIDs = array();
    $this->_contactIDs[] = $this->individualCreate();
    $this->_groupID = $this->groupCreate();
    $this->_groupIDs = array();
    $this->_email = 'test@test.test';
    $this->_params = array(
      'subject' => 'Hello {contact.display_name}',
      'body_text' => "This is {contact.display_name}",
      'body_html' => "<p>This is {contact.display_name}</p>",
      'name' => 'mailing name',
      'created_id' => $this->_contactIDs[0],
    );
  }

  function tearDown() {
    CRM_Mailing_BAO_MailingJob::$mailsProcessed = 0; // DGW
    parent::tearDown();
  }

  /**
   * Test civicrm_mailing_create
   */
  public function testMailerCreateSuccess() {
    $result = $this->callAPIAndDocument('mailing', 'create', $this->_params, __FUNCTION__, __FILE__);
    $jobs = $this->callAPISuccess('mailing_job', 'get', array('mailing_id' => $result['id']));
    $this->assertEquals(1, $jobs['count']);
    unset($this->_params['created_id']); // return isn't working on this in getAndCheck so lets not check it for now
    $this->getAndCheck($this->_params, $result['id'], 'mailing');
  }

  public function testMailerPreview() {
    // BEGIN SAMPLE DATA
    $contactID =  $this->individualCreate();
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

    $maxIDs =  array(
      'mailing' => CRM_Core_DAO::singleValueQuery('SELECT MAX(id) FROM civicrm_mailing'),
      'job' => CRM_Core_DAO::singleValueQuery('SELECT MAX(id) FROM civicrm_mailing_job'),
      'group' => CRM_Core_DAO::singleValueQuery('SELECT MAX(id) FROM civicrm_mailing_group'),
      'recip' => CRM_Core_DAO::singleValueQuery('SELECT MAX(id) FROM civicrm_mailing_recipients'),
    );
    $result = $this->callAPISuccess('mailing', 'create', $params);
    $this->assertDBQuery($maxIDs['mailing'], 'SELECT MAX(id) FROM civicrm_mailing'); // 'Preview should not create any mailing records'
    $this->assertDBQuery($maxIDs['job'], 'SELECT MAX(id) FROM civicrm_mailing_job'); // 'Preview should not create any mailing_job record'
    $this->assertDBQuery($maxIDs['group'], 'SELECT MAX(id) FROM civicrm_mailing_group'); // 'Preview should not create any mailing_group records'
    $this->assertDBQuery($maxIDs['recip'], 'SELECT MAX(id) FROM civicrm_mailing_recipients'); // 'Preview should not create any mailing_recipient records'

    $previewResult = $result['values'][$result['id']]['api.Mailing.preview'];
    $this->assertEquals("Hello $displayName", $previewResult['values']['subject']);
    $this->assertEquals("This is $displayName", $previewResult['values']['body_text']);
    $this->assertContains("<p>This is $displayName</p>", $previewResult['values']['body_html']);
  }

  public function testMailerPreviewRecipients() {
    // BEGIN SAMPLE DATA
    $this->groupIDs['inc'] = $this->groupCreate(array('name' => 'Example include group', 'title' => 'Example include group'));
    $this->groupIDs['exc'] = $this->groupCreate(array('name' => 'Example exclude group', 'title' => 'Example exclude group'));
    $this->contactIDs['includeme'] = $this->individualCreate(array('email' => 'include.me@example.org', 'first_name' => 'Includer', 'last_name' => 'Person'));
    $this->contactIDs['excludeme'] = $this->individualCreate(array('email' => 'exclude.me@example.org', 'last_name' => 'Excluder', 'last_name' => 'Excluder'));
    $this->callAPISuccess('GroupContact', 'create', array('group_id' => $this->groupIDs['inc'], 'contact_id' => $this->contactIDs['includeme']));
    $this->callAPISuccess('GroupContact', 'create', array('group_id' => $this->groupIDs['inc'], 'contact_id' => $this->contactIDs['excludeme']));
    $this->callAPISuccess('GroupContact', 'create', array('group_id' => $this->groupIDs['exc'], 'contact_id' => $this->contactIDs['excludeme']));

    $params = $this->_params;
    $params['groups']['include'] = array($this->groupIDs['inc']);
    $params['groups']['exclude'] = array($this->groupIDs['exc']);
    $params['mailings']['include'] = array();
    $params['mailings']['exclude'] = array();
    $params['options']['force_rollback'] = 1;
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

    $maxIDs =  array(
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
    $this->assertEquals(array((string)$this->contactIDs['includeme']), $previewIds);
    $previewEmails = array_values(CRM_Utils_Array::collect('api.email.getvalue', $preview['values']));
    $this->assertEquals(array('include.me@example.org'), $previewEmails);
    $previewNames = array_values(CRM_Utils_Array::collect('api.contact.getvalue', $preview['values']));
    $this->assertTrue((bool)preg_match('/Includer Person/', $previewNames[0]), "Name 'Includer Person' should appear in '" . $previewNames[0] . '"');
  }

  public function testMailerSendTest_email() {
    $contactID =  $this->individualCreate();
    $result = $this->callAPISuccess('contact', 'get', array('id' => $contactID));
    $email = $result['values'][$contactID]['email'];

    $mail = $this->callAPISuccess('mailing', 'create', $this->_params);

    $params = array('mailing_id' => $mail['id'], 'test_email' => $email, 'test_group' => NULL);
    $deliveredInfo = $this->callAPISuccess($this->_entity, 'send_test', $params);
    $this->assertEquals(1, $deliveredInfo['count'], "in line " . __LINE__); // verify mail has been sent to user by count
    $this->assertEquals($contactID, $deliveredInfo['values'][$deliveredInfo['id']]['contact_id'], "in line " . __LINE__); //verify the contact_id of the recipient
    $this->deleteMailing($mail['id']);
  }

  public function testMailerSendTest_group() {
    // BEGIN SAMPLE DATA
    $this->groupIDs['inc'] = $this->groupCreate(array('name' => 'Example include group', 'title' => 'Example include group'));
    $this->contactIDs['alice'] = $this->individualCreate(array('email' => 'alice@example.org', 'first_name' => 'Alice', 'last_name' => 'Person'));
    $this->contactIDs['bob'] = $this->individualCreate(array('email' => 'bob@example.org', 'first_name' => 'Bob', 'last_name' => 'Person'));
    $this->contactIDs['carol'] = $this->individualCreate(array('email' => 'carol@example.org', 'first_name' => 'Carol', 'last_name' => 'Person'));
    $this->callAPISuccess('GroupContact', 'create', array('group_id' => $this->groupIDs['inc'], 'contact_id' => $this->contactIDs['alice']));
    $this->callAPISuccess('GroupContact', 'create', array('group_id' => $this->groupIDs['inc'], 'contact_id' => $this->contactIDs['bob']));
    $this->callAPISuccess('GroupContact', 'create', array('group_id' => $this->groupIDs['inc'], 'contact_id' => $this->contactIDs['carol']));
    // END SAMPLE DATA

    $mail = $this->callAPISuccess('mailing', 'create', $this->_params);
    $deliveredInfo = $this->callAPISuccess($this->_entity, 'send_test', array(
      'mailing_id' => $mail['id'],
      'test_email' => NULL,
      'test_group' => $this->groupIDs['inc'],
    ));
    $this->assertEquals(3, $deliveredInfo['count'], "in line " . __LINE__); // verify mail has been sent to user by count
    $deliveredContacts = array_values(CRM_Utils_Array::collect('contact_id', $deliveredInfo['values']));
    $this->assertEquals(array($this->contactIDs['alice'], $this->contactIDs['bob'], $this->contactIDs['carol']), $deliveredContacts);
    $deliveredEmails = array_values(CRM_Utils_Array::collect('email', $deliveredInfo['values']));
    $this->assertEquals(array('alice@example.org', 'bob@example.org', 'carol@example.org'), $deliveredEmails);
  }

  public function testMailerStats() {
    $result = $this->groupContactCreate($this->_groupID, 100);
    $this->assertEquals(100, $result['added']); //verify if 100 contacts are added for group

    //Create and send test mail first and change the mail job to live,
    //because stats api only works on live mail
    $mail = $this->callAPISuccess('mailing', 'create', $this->_params);
    $params = array('mailing_id' => $mail['id'], 'test_email' => NULL, 'test_group' => $this->_groupID);
    $deliveredInfo = $this->callAPISuccess($this->_entity, 'send_test', $params);
    $deliveredIds  = implode(',', array_keys($deliveredInfo['values']));

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
      'Unsubscribers' => 20
    );
    $this->checkArrayEquals($expectedResult, $result['values'][$mail['id']]);
    $this->deleteMailing($mail['id']);
  }
  /**
   * Test civicrm_mailing_delete
   */
  public function testMailerDeleteSuccess() {
    $result = $this->callAPISuccess($this->_entity, 'create', $this->_params);
    $jobs = $this->callAPIAndDocument($this->_entity, 'delete', array('id' => $result['id']), __FUNCTION__, __FILE__);
    $this->assertAPIDeleted($this->_entity, $result['id']);
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
    $result = $this->callAPIFailure('mailing_event', 'bounce', $params,
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
    $result = $this->callAPIFailure('mailing_event', 'confirm', $params,
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
    $result = $this->callAPIFailure('mailing_event', 'reply', $params,
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
    $result = $this->callAPIFailure('mailing_event', 'forward', $params,
      'Queue event could not be found'
    );
  }

//----------- civicrm_mailing_create ----------

}
